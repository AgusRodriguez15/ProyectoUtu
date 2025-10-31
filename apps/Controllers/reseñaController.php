<?php
// Iniciar buffer de salida para capturar cualquier output
ob_start();

// Desactivar la salida de errores para evitar contaminar el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../Models/reseña.php';
require_once __DIR__ . '/../Models/accion.php';
require_once __DIR__ . '/../Models/servicio.php';

// Limpiar cualquier salida previa
ob_clean();

header('Content-Type: application/json');

// Función para enviar respuesta JSON
function enviarRespuesta($success, $mensaje, $data = null) {
    // Limpiar cualquier buffer de salida
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode([
        'success' => $success,
        'mensaje' => $mensaje,
        'data' => $data
    ]);
    exit;
}

// Log para debugging (temporal)
error_log("=== Inicio reseñaController ===");
error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'no definido'));
error_log("Acción solicitada: " . ($_GET['accion'] ?? 'no definido'));

// Verificar que sea una petición AJAX (comentado temporalmente para debugging)
// if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
//     enviarRespuesta(false, 'Petición no válida');
// }

// Obtener la acción solicitada
$accion = $_GET['accion'] ?? '';

if (empty($accion)) {
    enviarRespuesta(false, 'No se especificó ninguna acción');
}

switch ($accion) {
    case 'obtenerPorServicio':
        obtenerReseñasPorServicio();
        break;
    
    case 'agregar':
        agregarResena();
        break;
    
    case 'eliminar':
        eliminarResena();
        break;
    case 'cancelarPorServicio':
        cancelarResenasPorServicio();
        break;
    
    case 'obtenerPromedio':
        obtenerPromedioServicio();
        break;
    
    case 'verificarResena':
        verificarResenaUsuario();
        break;
    
    default:
        enviarRespuesta(false, 'Acción no válida');
}

// ===== ELIMINAR TODAS LAS RESEÑAS DE UN SERVICIO (ADMIN) =====
function cancelarResenasPorServicio() {
    // Verificar que el usuario esté logueado y sea administrador
    if (!isset($_SESSION['IdUsuario']) || !isset($_SESSION['TipoUsuario']) || $_SESSION['TipoUsuario'] !== 'Administrador') {
        enviarRespuesta(false, 'No tienes permiso para realizar esta acción');
    }

    $idServicio = intval($_POST['idServicio'] ?? $_GET['idServicio'] ?? 0);
    if ($idServicio <= 0) {
        enviarRespuesta(false, 'ID de servicio inválido');
    }

    try {
        require_once __DIR__ . '/../Models/reseña.php';
        error_log('[cancelarResenasPorServicio] Llamando a Resena::eliminarPorServicio con idServicio=' . $idServicio);
        $ok = Resena::eliminarPorServicio($idServicio);
        error_log('[cancelarResenasPorServicio] Resultado eliminarPorServicio: ' . var_export($ok, true));
        if ($ok) {
            // Registrar la acción en Accion (cancelar_reseñas)
            try {
                require_once __DIR__ . '/../Models/servicio.php';
                require_once __DIR__ . '/../Models/gestion.php';
                $serv = Servicio::obtenerPorId($idServicio);
                $idProv = $serv ? $serv->IdProveedor : 0;
                $motivo = $_POST['motivo'] ?? '';
                error_log('[cancelarResenasPorServicio] Registrando en Accion: tipo=cancelar_reseñas, motivo=' . $motivo . ', idProv=' . $idProv . ', idAdmin=' . $_SESSION['IdUsuario']);
                $accionId = accion::crear('cancelar_reseñas', $motivo, $idProv ? intval($idProv) : 0, $_SESSION['IdUsuario']);
                error_log('[cancelarResenasPorServicio] Resultado accion::crear: ' . var_export($accionId, true));
                // Registrar en Gestion (auditoría admin)
                error_log('[cancelarResenasPorServicio] Registrando en Gestion: idAdmin=' . $_SESSION['IdUsuario'] . ', idServicio=' . $idServicio . ', motivo=' . $motivo);
                $gestionId = Gestion::registrarCancelarResenias($_SESSION['IdUsuario'], $idServicio, $motivo);
                error_log('[cancelarResenasPorServicio] Resultado Gestion::registrarCancelarResenias: ' . var_export($gestionId, true));
            } catch (Exception $e) {
                error_log('Error registrando Accion/Gestion (cancelar_reseñas): ' . $e->getMessage());
            }

            enviarRespuesta(true, 'Reseñas canceladas/eliminadas para el servicio');
        } else {
            error_log('[cancelarResenasPorServicio] No se pudieron eliminar las reseñas o no había reseñas');
            enviarRespuesta(false, 'No se pudieron eliminar las reseñas o no había reseñas');
        }
    } catch (Exception $e) {
        error_log('Error en cancelarResenasPorServicio: ' . $e->getMessage());
        enviarRespuesta(false, 'Error al procesar la solicitud');
    }
}

// ===== OBTENER RESEÑAS DE UN SERVICIO =====
function obtenerReseñasPorServicio() {
    if (!isset($_GET['idServicio'])) {
        enviarRespuesta(false, 'Falta el ID del servicio');
    }

    $idServicio = intval($_GET['idServicio']);
    
    try {
        $resenas = Resena::obtenerPorServicio($idServicio);
        enviarRespuesta(true, 'Reseñas obtenidas exitosamente', $resenas);
    } catch (Exception $e) {
        error_log("Error al obtener reseñas: " . $e->getMessage());
        enviarRespuesta(false, 'Error al obtener las reseñas');
    }
}

// ===== AGREGAR NUEVA RESEÑA =====
function agregarResena() {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['IdUsuario'])) {
        enviarRespuesta(false, 'Debes iniciar sesión para dejar una reseña');
    }

    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        enviarRespuesta(false, 'Datos no válidos');
    }

    $comentario = trim($input['comentario'] ?? '');
    $puntuacion = intval($input['puntuacion'] ?? 0);
    $idServicio = intval($input['idServicio'] ?? 0);
    $idUsuario = $_SESSION['IdUsuario'];

    // Validaciones
    if (empty($comentario)) {
        enviarRespuesta(false, 'El comentario no puede estar vacío');
    }

    if (strlen($comentario) < 10) {
        enviarRespuesta(false, 'El comentario debe tener al menos 10 caracteres');
    }

    if (strlen($comentario) > 1000) {
        enviarRespuesta(false, 'El comentario no puede exceder 1000 caracteres');
    }

    if ($puntuacion < 1 || $puntuacion > 5) {
        enviarRespuesta(false, 'La puntuación debe estar entre 1 y 5');
    }

    if ($idServicio <= 0) {
        enviarRespuesta(false, 'ID de servicio no válido');
    }

    // Verificar que el usuario no sea el proveedor del servicio
    try {
        $servObj = Servicio::obtenerPorId($idServicio);
        if ($servObj && isset($servObj->IdProveedor) && intval($servObj->IdProveedor) === intval($idUsuario)) {
            enviarRespuesta(false, 'No puedes dejar una reseña en tu propio servicio');
        }
    } catch (Exception $e) {
        // Si falla la comprobación no bloqueamos por seguridad, pero lo logueamos
        error_log('advertencia: no se pudo verificar propietario del servicio: ' . $e->getMessage());
    }

    // Verificar que el usuario no haya reseñado ya este servicio
    if (Resena::existeResena($idUsuario, $idServicio)) {
        enviarRespuesta(false, 'Ya has dejado una reseña para este servicio. Solo se permite una reseña por usuario.');
    }

    // Verificar que el usuario no sea el proveedor del servicio (opcional)
    // Puedes descomentar esto si no quieres que los proveedores comenten sus propios servicios
    /*
    require_once __DIR__ . '/../Models/servicio.php';
    $servicio = Servicio::obtenerServicioPorId($idServicio);
    if ($servicio && $servicio->getIdProveedor() == $idUsuario) {
        enviarRespuesta(false, 'No puedes dejar una reseña en tu propio servicio');
    }
    */

    try {
        // Crear objeto Resena
        $resena = new Resena(
            null, // IdResena (se asigna automáticamente)
            $comentario,
            $puntuacion,
            date('Y-m-d H:i:s'), // Fecha actual
            $idUsuario,
            $idServicio
        );

        // Guardar en la base de datos
        if ($resena->guardar()) {
            // Obtener la reseña recién creada con los datos del usuario
            $resenas = Resena::obtenerPorServicio($idServicio);
            $nuevaResena = null;
            
            // Buscar la reseña recién agregada
            foreach ($resenas as $r) {
                if ($r['idResena'] == $resena->getIdResena()) {
                    $nuevaResena = $r;
                    break;
                }
            }

            enviarRespuesta(true, 'Reseña agregada exitosamente', $nuevaResena);
        } else {
            enviarRespuesta(false, 'Error al guardar la reseña');
        }
    } catch (Exception $e) {
        error_log("Error al agregar reseña: " . $e->getMessage());
        enviarRespuesta(false, 'Error al procesar la reseña');
    }
}

// ===== ELIMINAR RESEÑA =====
function eliminarResena() {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['IdUsuario'])) {
        enviarRespuesta(false, 'Debes iniciar sesión');
    }

    if (!isset($_POST['idResena'])) {
        enviarRespuesta(false, 'Falta el ID de la reseña');
    }

    $idResena = intval($_POST['idResena']);
    
    try {
        // Obtener la reseña
        $resena = Resena::obtenerPorId($idResena);
        
        if (!$resena) {
            enviarRespuesta(false, 'Reseña no encontrada');
        }

        // Verificar que el usuario sea administrador
        // NOTA: Por ahora solo administradores pueden eliminar
        // TODO: Permitir que el dueño de la reseña también pueda eliminar
        if (!isset($_SESSION['TipoUsuario']) || $_SESSION['TipoUsuario'] !== 'Administrador') {
            enviarRespuesta(false, 'No tienes permiso para eliminar reseñas. Solo administradores pueden realizar esta acción.');
        }

        /* LÓGICA FUTURA: Permitir al dueño eliminar su propia reseña
        if ($resena->getIdUsuario() != $_SESSION['IdUsuario'] && $_SESSION['TipoUsuario'] !== 'Administrador') {
            enviarRespuesta(false, 'No tienes permiso para eliminar esta reseña');
        }
        */

        // Eliminar
        if ($resena->eliminar()) {
            enviarRespuesta(true, 'Reseña eliminada exitosamente');
        } else {
            enviarRespuesta(false, 'Error al eliminar la reseña');
        }
    } catch (Exception $e) {
        error_log("Error al eliminar reseña: " . $e->getMessage());
        enviarRespuesta(false, 'Error al procesar la solicitud');
    }
}

// ===== OBTENER PROMEDIO DE PUNTUACIÓN =====
function obtenerPromedioServicio() {
    if (!isset($_GET['idServicio'])) {
        enviarRespuesta(false, 'Falta el ID del servicio');
    }

    $idServicio = intval($_GET['idServicio']);
    
    try {
        $promedio = Resena::calcularPromedioServicio($idServicio);
        enviarRespuesta(true, 'Promedio obtenido exitosamente', $promedio);
    } catch (Exception $e) {
        error_log("Error al obtener promedio: " . $e->getMessage());
        enviarRespuesta(false, 'Error al obtener el promedio');
    }
}

// ===== VERIFICAR SI USUARIO YA RESEÑÓ =====
function verificarResenaUsuario() {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['IdUsuario'])) {
        enviarRespuesta(false, 'Debes iniciar sesión', ['yaReseno' => false]);
    }

    if (!isset($_GET['idServicio'])) {
        enviarRespuesta(false, 'Falta el ID del servicio');
    }

    $idServicio = intval($_GET['idServicio']);
    $idUsuario = $_SESSION['IdUsuario'];
    
    try {
        $yaReseno = Resena::existeResena($idUsuario, $idServicio);
        $resenaExistente = null;
        
        if ($yaReseno) {
            $resenaExistente = Resena::obtenerResenaPorUsuarioYServicio($idUsuario, $idServicio);
        }
        
        enviarRespuesta(true, $yaReseno ? 'El usuario ya reseñó este servicio' : 'El usuario no ha reseñado este servicio', [
            'yaReseno' => $yaReseno,
            'resena' => $resenaExistente
        ]);
    } catch (Exception $e) {
        error_log("Error al verificar reseña: " . $e->getMessage());
        enviarRespuesta(false, 'Error al verificar la reseña');
    }
}
?>