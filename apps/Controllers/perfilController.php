<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/habilidad.php';
require_once __DIR__ . '/../Models/ubicacion.php';

session_start();

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: ../../public/login.html");
    exit;
}

header('Content-Type: application/json');

try {
    error_log("ID de usuario en sesión: " . $_SESSION['IdUsuario']);
    $usuario = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    error_log("Usuario obtenido: " . $usuario->getNombre());

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Solo lectura de datos cuando se carga la página
        $datos = dato::obtenerPorUsuario($usuario->getIdUsuario());
        $habilidades = habilidad::obtenerPorUsuario($usuario->getIdUsuario());

        // Obtener ubicación si existe
        $ubicacionData = null;
        $idUbicacion = $usuario->getIdUbicacion();
        error_log("IdUbicacion del usuario: " . ($idUbicacion ?? 'NULL'));
        
        if ($idUbicacion) {
            $ubicacion = ubicacion::obtenerPorId($idUbicacion);
            if ($ubicacion) {
                $ubicacionData = [
                    'pais' => $ubicacion->getPais(),
                    'ciudad' => $ubicacion->getCiudad(),
                    'calle' => $ubicacion->getCalle(),
                    'numero' => $ubicacion->getNumero()
                ];
            }
        }

        // Preparar la respuesta
        $rutaFoto = $usuario->getFotoPerfil();
        error_log("Ruta de foto que se enviará: " . ($rutaFoto ?? 'NULL'));
        
        echo json_encode([
            'success' => true,
            'usuario' => [
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'email' => $usuario->getEmail(),
                'descripcion' => $usuario->getDescripcion(),
                'rutaFoto' => $rutaFoto
            ],
            'ubicacion' => $ubicacionData,
            'contactos' => $datos,
            'habilidades' => $habilidades
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Actualizar campos de usuario
        $usuario->setNombre($_POST['Nombre'] ?? $usuario->getNombre());
        $usuario->setApellido($_POST['Apellido'] ?? $usuario->getApellido());
        $usuario->setEmail($_POST['Email'] ?? $usuario->getEmail());
        $usuario->setDescripcion($_POST['Descripcion'] ?? $usuario->getDescripcion());

        // Manejar ubicación
        if (!empty($_POST['Pais']) || !empty($_POST['Ciudad']) || !empty($_POST['Calle']) || !empty($_POST['Numero'])) {
            $pais = $_POST['Pais'] ?? '';
            $ciudad = $_POST['Ciudad'] ?? '';
            $calle = $_POST['Calle'] ?? '';
            $numero = !empty($_POST['Numero']) ? (int)$_POST['Numero'] : null;
            
            // Si el usuario ya tiene una ubicación, actualizarla
            if ($usuario->getIdUbicacion()) {
                $ubicacion = ubicacion::obtenerPorId($usuario->getIdUbicacion());
                if ($ubicacion) {
                    $ubicacion->setPais($pais);
                    $ubicacion->setCiudad($ciudad);
                    $ubicacion->setCalle($calle);
                    $ubicacion->setNumero($numero);
                    $ubicacion->guardar();
                }
            } else {
                // Crear nueva ubicación
                $nuevaUbicacion = new ubicacion(null, $pais, $ciudad, $calle, $numero);
                if ($nuevaUbicacion->guardar()) {
                    $usuario->setIdUbicacion($nuevaUbicacion->getIdUbicacion());
                }
            }
        }

        // Subir foto si hay
        if (!empty($_FILES['FotoPerfil']['tmp_name'])) {
            $usuario->subirFotoPerfil($_FILES['FotoPerfil']);
        }

        // Guardar usuario principal
        $usuarioGuardado = $usuario->guardar();

        // Antes de insertar los nuevos contactos y habilidades, eliminar los antiguos
        // para evitar duplicados si el usuario guarda varias veces.
        dato::eliminarPorUsuario($usuario->getIdUsuario());
        habilidad::eliminarPorUsuario($usuario->getIdUsuario());

        // Guardar contactos
        if (isset($_POST['Tipos']) && isset($_POST['Contactos'])) {
            foreach ($_POST['Tipos'] as $i => $tipo) {
                // Ignorar campos vacíos
                $contactoValor = $_POST['Contactos'][$i] ?? '';
                if (trim($tipo) === '' && trim($contactoValor) === '') continue;

                $dato = new dato($usuario->getIdUsuario(), $tipo, $contactoValor);
                $dato->guardar();
            }
        }

        // Guardar habilidades
        if (isset($_POST['Habilidades']) && isset($_POST['Anios'])) {
            foreach ($_POST['Habilidades'] as $i => $habilidadNombre) {
                $anios = isset($_POST['Anios'][$i]) ? (int)$_POST['Anios'][$i] : 0;
                if (trim($habilidadNombre) === '') continue; // ignorar vacíos

                $habilidad = new habilidad($usuario->getIdUsuario(), $habilidadNombre, $anios);
                $habilidad->guardar();
            }
        }

        // Obtener los datos actualizados para devolverlos
        $contactosActualizados = dato::obtenerPorUsuario($usuario->getIdUsuario());
        $habilidadesActualizadas = habilidad::obtenerPorUsuario($usuario->getIdUsuario());

        // Obtener ubicación actualizada
        $ubicacionData = null;
        if ($usuario->getIdUbicacion()) {
            $ubicacion = ubicacion::obtenerPorId($usuario->getIdUbicacion());
            if ($ubicacion) {
                $ubicacionData = [
                    'pais' => $ubicacion->getPais(),
                    'ciudad' => $ubicacion->getCiudad(),
                    'calle' => $ubicacion->getCalle(),
                    'numero' => $ubicacion->getNumero()
                ];
            }
        }

        echo json_encode([
            "success" => true,
            "message" => "Perfil actualizado correctamente",
            "usuario" => [
                "nombre" => $usuario->getNombre(),
                "apellido" => $usuario->getApellido(),
                "email" => $usuario->getEmail(),
                "descripcion" => $usuario->getDescripcion(),
                "rutaFoto" => $usuario->getFotoPerfil()
            ],
            "ubicacion" => $ubicacionData,
            "contactos" => $contactosActualizados,
            "habilidades" => $habilidadesActualizadas
        ]);
        exit;
    }

    // Si no es POST → devolver datos actuales
    $contactos = dato::obtenerPorUsuario($usuario->getIdUsuario());
    $habilidades = habilidad::obtenerPorUsuario($usuario->getIdUsuario());

    echo json_encode([
        "success" => true,
        "usuario" => [
            "nombre" => $usuario->getNombre(),
            "apellido" => $usuario->getApellido(),
            "email" => $usuario->getEmail(),
            "descripcion" => $usuario->getDescripcion(),
            "rutaFoto" => $usuario->getFotoPerfil()
        ],
        "contactos" => $contactos,
        "habilidades" => $habilidades
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
