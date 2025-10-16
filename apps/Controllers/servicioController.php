<?php
// Activar errores temporalmente para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Models/Servicio.php';
require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/habilidad.php';

try {
    // Si se solicita un servicio por ID
    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        error_log("Buscando servicio con ID: " . $id);
        
        $servicio = Servicio::obtenerPorId($id);
        
        if (!$servicio) {
            echo json_encode(['error' => 'Servicio no encontrado']);
            exit;
        }
        
        error_log("Servicio encontrado: " . $servicio->Nombre);
        
        // Obtener información del proveedor
        $nombreProveedor = 'Información no disponible';
        $descripcionProveedor = '';
        $fotoProveedor = '';
        $contactos = [];
        $habilidades = [];
        
        if ($servicio->IdProveedor) {
            try {
                $proveedor = usuario::obtenerPor('IdUsuario', $servicio->IdProveedor);
                
                if ($proveedor) {
                    $nombreProveedor = $proveedor->getNombre() . ' ' . $proveedor->getApellido();
                    $descripcionProveedor = $proveedor->getDescripcion() ?: '';
                    $fotoProveedor = $proveedor->getFotoPerfil() ?: '';
                    
                    // Obtener datos de contacto del proveedor
                    try {
                        $datosContacto = dato::obtenerPorUsuario($servicio->IdProveedor);
                        error_log("Contactos obtenidos: " . count($datosContacto));
                        foreach ($datosContacto as $dato) {
                            error_log("Tipo: " . $dato->Tipo . ", Contacto: " . $dato->Contacto);
                            $contactos[] = [
                                'tipo' => $dato->Tipo,
                                'contacto' => $dato->Contacto
                            ];
                        }
                        error_log("Total contactos agregados: " . count($contactos));
                    } catch (Exception $e) {
                        error_log("Error al obtener contactos: " . $e->getMessage());
                    }
                    
                    // Obtener habilidades del proveedor
                    try {
                        $habilidadesData = habilidad::obtenerPorUsuario($servicio->IdProveedor);
                        error_log("Habilidades obtenidas: " . count($habilidadesData));
                        foreach ($habilidadesData as $hab) {
                            $habilidades[] = [
                                'habilidad' => is_array($hab) ? $hab['Habilidad'] : $hab->Habilidad,
                                'experiencia' => is_array($hab) ? $hab['AniosExperiencia'] : $hab->AniosExperiencia
                            ];
                        }
                        error_log("Total habilidades agregadas: " . count($habilidades));
                    } catch (Exception $e) {
                        error_log("Error al obtener habilidades: " . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log("Error al obtener proveedor: " . $e->getMessage());
            }
        }
        
        // Obtener fotos del servicio
        $fotos = [];
        if (is_array($servicio->Fotos) && count($servicio->Fotos) > 0) {
            foreach ($servicio->Fotos as $foto) {
                if (method_exists($foto, 'getRutaFoto')) {
                    $fotos[] = $foto->getRutaFoto();
                }
            }
        }
        
        $respuesta = [
            'id' => $servicio->IdServicio,
            'nombre' => $servicio->Nombre,
            'descripcion' => $servicio->Descripcion,
            'foto' => $servicio->getFotoServicio(),
            'fotos' => $fotos,
            'fechaPublicacion' => $servicio->FechaPublicacion,
            'estado' => $servicio->Estado,
            'proveedor' => [
                'nombre' => $nombreProveedor,
                'descripcion' => $descripcionProveedor,
                'foto' => $fotoProveedor,
                'contactos' => $contactos,
                'habilidades' => $habilidades,
                'idUsuario' => $servicio->IdProveedor
            ]
        ];
        
        echo json_encode($respuesta);
        exit;
    }
    
    // Capturar término de búsqueda
    $termino = isset($_POST['q']) ? trim($_POST['q']) : '';

    // Buscar servicios
    $servicios = Servicio::buscarPorCategoriaYTitulo($termino);

    // Armar respuesta para el front (solo datos necesarios)
    $respuesta = array_map(function ($s) {
        return [
            'id' => $s->IdServicio,
            'nombre' => $s->Nombre,
            'descripcion' => $s->Descripcion,
            'foto' => $s->getFotoServicio()
        ];
    }, $servicios);

    echo json_encode($respuesta);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
