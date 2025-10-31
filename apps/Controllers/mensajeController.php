<?php
// Asegurar salida JSON y suprimir mensajes HTML de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

$mensajeModelPath = __DIR__ . '/../Models/mensaje.php';
$usuarioModelPath = __DIR__ . '/../Models/usuario.php';

if (!file_exists($mensajeModelPath) || !file_exists($usuarioModelPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Faltan archivos del modelo en el servidor']);
    exit;
}

require_once $mensajeModelPath;
require_once $usuarioModelPath;

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
error_log('[mensajeController] acción solicitada: ' . $accion . ' - método: ' . $_SERVER['REQUEST_METHOD']);

// Log básico de POST (no incluir grandes contenidos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logPost = $_POST;
    if (isset($logPost['contenido'])) {
        $logPost['contenido'] = substr($logPost['contenido'], 0, 200); // truncar por seguridad
    }
    error_log('[mensajeController] POST: ' . json_encode($logPost));
}

// Log local dentro del proyecto para depuración cuando el administrador del sistema
// no permite acceso a los logs de Apache/PHP. Archivo: proyecto/logs/mensaje_controller.log
$localLogDir = __DIR__ . '/../../logs';
if (!is_dir($localLogDir)) {
    @mkdir($localLogDir, 0755, true);
}
$localLogFile = $localLogDir . '/mensaje_controller.log';

function _mc_log_local($msg) {
    global $localLogFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @error_log($line, 3, $localLogFile);
}

// Registrar shutdown handler para capturar errores fatales y volcarlos al log local
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (in_array($err['type'], $fatalTypes)) {
            $msg = '[SHUTDOWN][FATAL] ' . ($err['message'] ?? '') . ' in ' . ($err['file'] ?? '') . ' on line ' . ($err['line'] ?? '');
            _mc_log_local($msg);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['ok' => false, 'error' => 'Error interno del servidor (fatal)']);
            flush();
        }
    }
});

switch ($accion) {

    case 'enviarMensaje':
        $contenido = $_POST['contenido'] ?? '';
        $emisor = $_POST['emisor'] ?? null;
        $receptor = $_POST['receptor'] ?? null;
        $imagenNombre = null;

        // Manejo de imagen: guardar en public/recursos/mensajes/
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $imagenNombre = 'msg_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $destDir = __DIR__ . '/../../public/recursos/mensajes/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $destino = $destDir . $imagenNombre;
            // Intentar mover el archivo; si falla, dejar $imagenNombre = null y continuar
            if (!@move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                _mc_log_local('[mensajeController] fallo move_uploaded_file para ' . ($_FILES['imagen']['name'] ?? 'archivo desconocido'));
                $imagenNombre = null;
            }
        }

        if (!$contenido && !$imagenNombre) {
            echo json_encode(['ok' => false, 'error' => 'Mensaje vacío']);
            exit;
        }

        try {
            $res = Mensaje::enviar($contenido, (int)$emisor, (int)$receptor, $imagenNombre);
            if (!is_array($res)) {
                echo json_encode(['ok' => false, 'error' => 'Respuesta inesperada del modelo']);
            } else {
                // Si subimos un archivo y existe en disco, añadir la URL directa para que el cliente la use
                if (!empty($imagenNombre)) {
                    $possiblePath = __DIR__ . '/../../public/recursos/mensajes/' . $imagenNombre;
                    if (file_exists($possiblePath)) {
                        $res['imagenUrl'] = '/proyecto/public/recursos/mensajes/' . $imagenNombre;
                    } else {
                        // Registrar en log local si el archivo no está donde esperamos
                        _mc_log_local('[mensajeController] Imagen esperada no encontrada en disco: ' . $possiblePath);
                    }
                }
                echo json_encode($res);
            }
        } catch (Throwable $t) {
            http_response_code(500);
            _mc_log_local('[mensajeController] Excepción: ' . $t->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Excepción en servidor: ' . $t->getMessage()]);
        }
        break;

    case 'obtenerMensajes':
        $emisor = $_GET['emisor'] ?? null;
        $receptor = $_GET['receptor'] ?? null;
        if (!$emisor || !$receptor) exit(json_encode([]));
        $mensajes = Mensaje::obtenerPorConversacion((int)$emisor, (int)$receptor);
        $res = [];
        foreach ($mensajes as $m) {
            $res[] = [
                'id' => $m->IdMensaje,
                'Contenido' => $m->Contenido,
                'Imagen' => $m->Imagen ? "/proyecto/public/recursos/mensajes/" . $m->Imagen : null,
                'Fecha' => $m->Fecha,
                'Estado' => $m->Estado,
                'Emisor' => $m->IdUsuarioEmisor,
                'Receptor' => $m->IdUsuarioReceptor
            ];
        }
        echo json_encode($res);
        break;

    case 'obtenerChats':
        $idUsuario = $_GET['id'] ?? null;
        if (!$idUsuario) exit(json_encode([]));
        $chatsIds = Mensaje::obtenerChats((int)$idUsuario);
        $chats = [];
        foreach ($chatsIds as $otroId) {
            $u = usuario::obtenerPor('IdUsuario', $otroId);
            if ($u) {
                $chats[] = [
                    'id' => $u->getIdUsuario(),
                    'nombre' => $u->getNombre() . ' ' . $u->getApellido(),
                    'foto' => $u->getFotoPerfil() ? "/proyecto/public/recursos/imagenes/perfil/" . $u->getFotoPerfil() : "/proyecto/public/recursos/default/user-default.png"
                ];
            }
        }
        echo json_encode($chats);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción inválida']);
}
