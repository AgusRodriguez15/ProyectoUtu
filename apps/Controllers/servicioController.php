<?php
// Desactivar display de errores para que no interfiera con JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Models/servicio.php';
require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/habilidad.php';
require_once __DIR__ . '/../Models/proveedor.php';
require_once __DIR__ . '/../Models/palabraClave.php';
require_once __DIR__ . '/../Models/foto.php';

session_start();

try {
    // Actualizar un servicio existente
    if (isset($_POST['actualizar']) && isset($_POST['id'])) {
        if (!isset($_SESSION['IdUsuario'])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        $idServicio = intval($_POST['id']);
        $servicio = Servicio::obtenerPorId($idServicio);
        if (!$servicio) {
            echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
            exit;
        }

        // Validar propietario
        $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
        if (!$proveedor || $servicio->IdProveedor !== $proveedor->getIdUsuario()) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este servicio']);
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'DISPONIBLE';
        $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0.0;
        $divisa = $_POST['divisa'] ?? 'UYU';

        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
            exit;
        }

        if (!isset($_POST['precio'])) {
            echo json_encode(['success' => false, 'message' => 'El precio es obligatorio']);
            exit;
        }

        // Actualizar datos básicos
        $ok = Servicio::actualizarBasico($idServicio, $proveedor->getIdUsuario(), $nombre, $descripcion, $precio, $divisa, $estado);
        
        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el servicio']);
            exit;
        }

        // Manejar eliminación de fotos
        if (isset($_POST['fotosAEliminar'])) {
            $fotosAEliminar = json_decode($_POST['fotosAEliminar'], true);
            if (is_array($fotosAEliminar)) {
                foreach ($fotosAEliminar as $fotoUrl) {
                    // Extraer nombre del archivo de la URL
                    $nombreArchivo = basename($fotoUrl);
                    Foto::eliminarPorNombre($idServicio, $nombreArchivo);
                }
            }
        }

        // Manejar nuevas fotos
        if (isset($_FILES['nuevasFotos']) && !empty($_FILES['nuevasFotos']['name'][0])) {
            $uploadDir = '../../public/recursos/imagenes/servicios/';
            
            // Crear directorio si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['nuevasFotos']['name'] as $key => $nombreOriginal) {
                if ($_FILES['nuevasFotos']['error'][$key] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($extension, $extensionesPermitidas)) {
                        // Generar nombre único
                        $nombreArchivo = 'servicio_' . $idServicio . '_' . time() . '_' . $key . '.' . $extension;
                        $rutaCompleta = $uploadDir . $nombreArchivo;
                        
                        if (move_uploaded_file($_FILES['nuevasFotos']['tmp_name'][$key], $rutaCompleta)) {
                            // Guardar en base de datos
                            Foto::crear($idServicio, $nombreArchivo);
                        }
                    }
                }
            }
        }

        // Manejar palabras clave
        if (isset($_POST['palabrasClave'])) {
            $palabrasClave = json_decode($_POST['palabrasClave'], true);
            if (is_array($palabrasClave)) {
                try {
                    PalabraClave::actualizarPorServicio($idServicio, $palabrasClave);
                } catch (Exception $e) {
                    error_log("Error al actualizar palabras clave: " . $e->getMessage());
                }
            }
        } else {
            // Si no se enviaron palabras clave, eliminar todas las existentes
            try {
                PalabraClave::eliminarPorServicio($idServicio);
            } catch (Exception $e) {
                error_log("Error al eliminar palabras clave: " . $e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'message' => 'Servicio actualizado correctamente']);
        exit;
    }
    // Si se solicita eliminar un servicio
    if (isset($_POST['eliminar']) && isset($_POST['idServicio'])) {
        if (!isset($_SESSION['IdUsuario'])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
        
        $idServicio = intval($_POST['idServicio']);
        $servicio = Servicio::obtenerPorId($idServicio);
        
        if (!$servicio) {
            echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
            exit;
        }
        
        // Verificar que el servicio pertenece al usuario actual
        $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
        
        if (!$proveedor || $servicio->IdProveedor !== $proveedor->getIdUsuario()) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este servicio']);
            exit;
        }
        
        // Eliminar el servicio y todas sus dependencias
        $eliminado = Servicio::eliminar($idServicio, $proveedor->getIdUsuario());
        
        if (!$eliminado) {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el servicio']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
        exit;
    }
    
    // Si se solicitan los servicios del proveedor actual
    if (isset($_POST['misServicios'])) {
        if (!isset($_SESSION['IdUsuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        // Obtener el IdProveedor del usuario actual
        $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
        
        if (!$proveedor) {
            echo json_encode([]);
            exit;
        }
        
        $servicios = Servicio::obtenerPorProveedor($proveedor->getIdUsuario());
        
        // Armar respuesta
        $respuesta = array_map(function ($s) {
            $fotos = [];
            if (!empty($s->Fotos) && is_array($s->Fotos)) {
                foreach ($s->Fotos as $foto) {
                    // Las fotos ahora vienen como strings con rutas completas
                    $fotos[] = [
                        'Url' => $foto,
                        'Descripcion' => ''
                    ];
                }
            }
            
            return [
                'IdServicio' => $s->IdServicio,
                'Nombre' => $s->Nombre,
                'Descripcion' => $s->Descripcion,
                'FechaPublicacion' => $s->FechaPublicacion,
                'Estado' => $s->Estado,
                'Fotos' => $fotos
            ];
        }, $servicios);
        
        echo json_encode($respuesta);
        exit;
    }
    
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
        
        // Obtener fotos del servicio (ahora son rutas completas como strings)
        $fotos = [];
        if (is_array($servicio->Fotos) && count($servicio->Fotos) > 0) {
            foreach ($servicio->Fotos as $foto) {
                // Si viene como objeto y tiene getRutaFoto
                if (is_object($foto) && method_exists($foto, 'getRutaFoto')) {
                    $fotos[] = $foto->getRutaFoto();
                } elseif (is_string($foto)) {
                    // Si ya es una ruta string completa
                    $fotos[] = $foto;
                }
            }
        }
        
        // Obtener palabras clave del servicio
        $palabrasClave = [];
        try {
            $palabrasClave = PalabraClave::obtenerPorServicio($servicio->IdServicio);
        } catch (Exception $e) {
            error_log("Error al obtener palabras clave: " . $e->getMessage());
        }

        $respuesta = [
            'id' => $servicio->IdServicio,
            'nombre' => $servicio->Nombre,
            'descripcion' => $servicio->Descripcion,
            'precio' => property_exists($servicio, 'Precio') ? $servicio->Precio : null,
            'divisa' => property_exists($servicio, 'Divisa') ? $servicio->Divisa : 'UYU',
            'foto' => $servicio->getFotoServicio(),
            'fotos' => $fotos,
            'palabrasClave' => $palabrasClave,
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
    
    // Capturar término de búsqueda (búsqueda por defecto si no hay otro parámetro)
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
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
