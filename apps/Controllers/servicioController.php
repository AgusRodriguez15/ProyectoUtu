<?php
// ===== CONFIGURACIÓN INICIAL =====
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

// Registrar handler de shutdown para capturar errores fatales y devolver JSON en vez de respuesta vacía
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        // Tipos fatales que normalmente no se capturan con try/catch
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (in_array($err['type'], $fatalTypes)) {
            error_log('[servicioController][FATAL] ' . $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line']);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            // Intentar emitir JSON legible para el frontend
            $out = ['success' => false, 'message' => 'Error interno del servidor (fatal): ' . ($err['message'] ?? 'desconocido')];
            echo json_encode($out);
            // Asegurar que se envíe salida
            flush();
        }
    }
});

// Log de entrada crudo para debug
try {
    $raw = file_get_contents('php://input');
    error_log('[servicioController] Raw POST body: ' . $raw);
} catch (Exception $e) {
    error_log('[servicioController] No se pudo leer php://input: ' . $e->getMessage());
}

// ===== INCLUDES =====
require_once __DIR__ . '/../Models/servicio.php';
require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/habilidad.php';
require_once __DIR__ . '/../Models/proveedor.php';
require_once __DIR__ . '/../Models/gestion.php';
require_once __DIR__ . '/../Models/palabraClave.php';
require_once __DIR__ . '/../Models/foto.php';
require_once __DIR__ . '/../Models/ubicacion.php';
require_once __DIR__ . '/../Models/reseña.php';
require_once __DIR__ . '/../Models/disponibilidad.php';

// ===== HELPERS =====
function obtenerUrlFoto($f) {
    // Devuelve una URL string o null. Maneja string, array y object de forma segura.
    if (is_string($f)) {
        return $f;
    }
    if (is_array($f)) {
        return $f['Url'] ?? $f['URL'] ?? $f['url'] ?? $f['Foto'] ?? null;
    }
    if (is_object($f)) {
        if (isset($f->Url) && is_string($f->Url)) return $f->Url;
        if (isset($f->URL) && is_string($f->URL)) return $f->URL;
        if (isset($f->url) && is_string($f->url)) return $f->url;
        if (isset($f->Foto) && is_string($f->Foto)) return $f->Foto;
    }
    return null;
}

function nombreArchivoDesdeUrl($url) {
    if (!$url) return null;
    // Soporta URLs completas o solo el nombre
    $parts = explode('/', $url);
    return end($parts);
}

// ===== BUSCAR SERVICIOS POR TÉRMINO (POST) =====
if (isset($_POST['q'])) {
    $termino = trim($_POST['q']);
    error_log('[POST buscarServicios] término recibido: ' . $termino);
    $servicios = Servicio::buscarPorCategoriaYTitulo($termino);
    error_log('[POST buscarServicios] cantidad servicios encontrados: ' . count($servicios));
    $respuesta = array_map(function ($s) {
        $foto = $s->getFotoServicio();
        error_log('[POST buscarServicios] Servicio: ' . $s->IdServicio . ' - Foto: ' . $foto);
        $outFotos = [];
        if (!empty($s->Fotos)) {
            foreach ($s->Fotos as $f) {
                $url = obtenerUrlFoto($f);
                if ($url) $outFotos[] = ['Url' => $url];
            }
        }
        // Obtener promedio y total de reseñas para este servicio
        $ratingInfo = Resena::calcularPromedioServicio($s->IdServicio);
        $ratingProm = $ratingInfo['promedio'] ?? 0;
        $ratingCount = $ratingInfo['total'] ?? 0;
        return [
            'IdServicio' => $s->IdServicio,
            'Nombre' => $s->Nombre,
            'Descripcion' => $s->Descripcion,
            'Estado' => $s->Estado,
            'IdProveedor' => $s->IdProveedor,
            'ProveedorNombre' => '', // Se puede completar si se necesita
            'Precio' => $s->Precio,
            'Divisa' => $s->Divisa,
            'foto' => $foto,
            'Fotos' => $outFotos
            ,
            'Rating' => $ratingProm,
            'RatingCount' => $ratingCount,
            // Alias en minúsculas para compatibilidad con frontend
            'id' => $s->IdServicio,
            'nombre' => $s->Nombre,
            'descripcion' => $s->Descripcion,
            'estado' => $s->Estado,
            'precio' => $s->Precio,
            'divisa' => $s->Divisa,
            'fotos' => $outFotos
            ,
            'rating' => $ratingProm,
            'ratingCount' => $ratingCount
        ];
    }, $servicios);
    error_log('[POST buscarServicios] Respuesta JSON: ' . json_encode($respuesta));
    echo json_encode($respuesta);
    exit;
}

// ===== LISTAR TODOS LOS SERVICIOS (GET) =====
if (isset($_GET['action']) && $_GET['action'] === 'listarTodos') {
    try {
        $db = new ConexionDB();
        $conn = $db->getConexion();
        $sql = "SELECT s.IdServicio, s.Nombre, s.Descripcion, s.Estado, s.IdProveedor, 
                       u.Nombre as ProveedorNombre, u.Apellido as ProveedorApellido, 
                       s.Precio, s.Divisa
                FROM Servicio s
                LEFT JOIN Usuario u ON s.IdProveedor = u.IdUsuario
                ORDER BY s.FechaPublicacion DESC";
        $res = $conn->query($sql);
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $servicioObj = Servicio::obtenerPorId($row['IdServicio']);
                $foto = $servicioObj ? $servicioObj->getFotoServicio() : null;
                $outFotos = [];
                if ($servicioObj && !empty($servicioObj->Fotos)) {
                    foreach ($servicioObj->Fotos as $f) {
                        $url = obtenerUrlFoto($f);
                        if ($url) $outFotos[] = ['Url' => $url];
                    }
                }
                // Obtener promedio y total de reseñas para este servicio
                $ratingInfo = Resena::calcularPromedioServicio($row['IdServicio']);
                $ratingProm = $ratingInfo['promedio'] ?? 0;
                $ratingCount = $ratingInfo['total'] ?? 0;
                $out[] = [
                    'IdServicio' => $row['IdServicio'],
                    'Nombre' => $row['Nombre'],
                    'Descripcion' => $row['Descripcion'],
                    'Estado' => $row['Estado'],
                    'IdProveedor' => $row['IdProveedor'],
                    'ProveedorNombre' => trim(($row['ProveedorNombre'] ?? '') . ' ' . ($row['ProveedorApellido'] ?? '')),
                    'Precio' => $row['Precio'],
                    'Divisa' => $row['Divisa'],
                    'foto' => $foto,
                    'Fotos' => $outFotos
                    ,
                    // Aliases en minúsculas para frontend
                    'id' => $row['IdServicio'],
                    'nombre' => $row['Nombre'],
                    'descripcion' => $row['Descripcion'],
                    'estado' => $row['Estado'],
                    'precio' => $row['Precio'],
                    'divisa' => $row['Divisa'],
                    'fotos' => $outFotos
                    ,
                    'Rating' => $ratingProm,
                    'RatingCount' => $ratingCount,
                    'rating' => $ratingProm,
                    'ratingCount' => $ratingCount
                ];
            }
        }
        error_log('[listarTodos] Servicios encontrados: ' . count($out));
        echo json_encode($out);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ===== ACTUALIZAR SERVICIO (PROVEEDOR) =====
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

    // Ejecutar actualización básica del servicio (crítica)
    $ok = false;
    try {
        $ok = Servicio::actualizarBasico($idServicio, $proveedor->getIdUsuario(), $nombre, $descripcion, $precio, $divisa, $estado);
    } catch (Exception $e) {
        error_log('[servicioController::actualizar] Error en actualizarBasico: ' . $e->getMessage());
    }

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico: no se pudo actualizar el servicio']);
        exit;
    }

    // Si llegamos acá, la actualización principal se aplicó. Procesar operaciones auxiliares
    $warnings = [];

    // Procesar palabras clave (reemplazar todas)
    if (isset($_POST['palabrasClave'])) {
        $pk = json_decode($_POST['palabrasClave'], true);
        if (is_array($pk)) {
            try {
                PalabraClave::actualizarPorServicio($idServicio, $pk);
            } catch (Exception $e) {
                $warnings[] = 'palabrasClave: ' . $e->getMessage();
                error_log('[servicioController::actualizar] palabrasClave error: ' . $e->getMessage());
            }
        }
    }

    // Procesar ubicaciones a eliminar
    if (isset($_POST['ubicacionesAEliminar'])) {
        $uids = json_decode($_POST['ubicacionesAEliminar'], true);
        if (is_array($uids)) {
            foreach ($uids as $uid) {
                $uidInt = intval($uid);
                if ($uidInt > 0) {
                    try {
                        ubicacion::eliminarDeServicio($idServicio, $uidInt);
                    } catch (Exception $e) {
                        $warnings[] = 'eliminarUbicacion ' . $uidInt . ': ' . $e->getMessage();
                        error_log('[servicioController::actualizar] eliminarUbicacion error: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    // Procesar nuevas ubicaciones
    if (isset($_POST['nuevasUbicaciones'])) {
        $nuevas = json_decode($_POST['nuevasUbicaciones'], true);
        if (is_array($nuevas)) {
            foreach ($nuevas as $n) {
                try {
                    ubicacion::crearYAsociarAServicio($idServicio, $n);
                } catch (Exception $e) {
                    $warnings[] = 'crearUbicacion: ' . $e->getMessage();
                    error_log('[servicioController::actualizar] crearUbicacion error: ' . $e->getMessage());
                }
            }
        }
    }

    // Procesar disponibilidades a eliminar
    if (isset($_POST['disponibilidadesAEliminar'])) {
        $dels = json_decode($_POST['disponibilidadesAEliminar'], true);
        if (is_array($dels)) {
            $db = new ConexionDB(); $conn = $db->getConexion();
            $stmtDel = $conn->prepare("DELETE FROM Disponibilidad WHERE IdDisponibilidad = ? AND IdServicio = ?");
            if ($stmtDel) {
                foreach ($dels as $did) {
                    $didInt = intval($did);
                    if ($didInt > 0) {
                        try {
                            $stmtDel->bind_param('ii', $didInt, $idServicio);
                            $stmtDel->execute();
                        } catch (Exception $e) {
                            $warnings[] = 'eliminarDisponibilidad ' . $didInt . ': ' . $e->getMessage();
                            error_log('[servicioController::actualizar] eliminarDisponibilidad error: ' . $e->getMessage());
                        }
                    }
                }
                $stmtDel->close();
            }
            $conn->close();
        }
    }

    // Procesar nuevas disponibilidades
    if (isset($_POST['nuevasDisponibilidades'])) {
        $nuevasDisp = json_decode($_POST['nuevasDisponibilidades'], true);
        if (is_array($nuevasDisp)) {
            foreach ($nuevasDisp as $nd) {
                try {
                    disponibilidad::crearParaServicio($idServicio, $nd);
                } catch (Exception $e) {
                    $warnings[] = 'crearDisponibilidad: ' . $e->getMessage();
                    error_log('[servicioController::actualizar] crearDisponibilidad error: ' . $e->getMessage());
                }
            }
        }
    }

    // Procesar fotos a eliminar (se envían como URLs; extraer nombre)
    if (isset($_POST['fotosAEliminar'])) {
        $fotosDel = json_decode($_POST['fotosAEliminar'], true);
        if (is_array($fotosDel)) {
            foreach ($fotosDel as $f) {
                $nombre = nombreArchivoDesdeUrl($f);
                if ($nombre) {
                    try { Foto::eliminarPorNombre($idServicio, $nombre); } catch (Exception $e) { $warnings[] = 'eliminarFoto ' . $nombre . ': ' . $e->getMessage(); error_log('Error eliminando foto: '.$e->getMessage()); }
                }
            }
        }
    }

    // Procesar nuevas fotos subidas
    if (isset($_FILES['nuevasFotos'])) {
        $files = $_FILES['nuevasFotos'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name' => $files['name'][$i]
                ];
                try {
                    Foto::guardarFoto($idServicio, $tmp);
                } catch (Exception $e) {
                    $warnings[] = 'guardarFoto ' . $files['name'][$i] . ': ' . $e->getMessage();
                    error_log('Error guardando nueva foto: ' . $e->getMessage());
                }
            }
        }
    }

    // Registrar en Gestion (no crítico)
    try {
        $tipoGestion = (isset($servicio->Estado) && strtoupper($servicio->Estado) !== 'DISPONIBLE' && strtoupper($estado) === 'DISPONIBLE')
            ? 'habilitar'
            : 'editar_datos_servicio';
        $detalle = 'Edición por proveedor: Nombre=' . addslashes($nombre) . '; Precio=' . $precio . '; Estado=' . $estado;
        if ($tipoGestion === 'habilitar') {
            Gestion::registrarHabilitar($_SESSION['IdUsuario'], $idServicio, $detalle);
        } else {
            Gestion::registrar($tipoGestion, $detalle, $_SESSION['IdUsuario'], $idServicio);
        }
    } catch (Exception $e) {
        $warnings[] = 'gestion: ' . $e->getMessage();
        error_log('servicioController::actualizar - error registrando en Gestion: ' . $e->getMessage());
    }

    // Responder al frontend: success aunque haya warnings no críticos
    if (empty($warnings)) {
        echo json_encode(['success' => true, 'message' => 'Servicio actualizado correctamente']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Servicio actualizado con advertencias', 'warnings' => $warnings]);
    }
    exit;
}

// ===== ELIMINAR SERVICIO (PROVEEDOR) =====
if (isset($_POST['eliminar']) && isset($_POST['idServicio'])) {
    if (!isset($_SESSION['IdUsuario'])) { echo json_encode(['success' => false, 'message' => 'No autorizado']); exit; }

    $idServicio = intval($_POST['idServicio']);
    $servicio = Servicio::obtenerPorId($idServicio);
    if (!$servicio) { echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']); exit; }

    $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
    if (!$proveedor || $servicio->IdProveedor !== $proveedor->getIdUsuario()) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este servicio']); exit;
    }

    $eliminado = Servicio::eliminar($idServicio, $proveedor->getIdUsuario());
    if (!$eliminado) { echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el servicio']); exit; }

    Gestion::registrar('borrar_servicio', 'Eliminado por propietario', $_SESSION['IdUsuario'], $idServicio);

    echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
    exit;
}

// ===== ELIMINAR SERVICIO (ADMIN) =====
if (isset($_POST['eliminarAdmin']) && isset($_POST['idServicio'])) {
    $usuarioSesion = $_SESSION['IdUsuario'] ?? null ? usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']) : null;
    if (!$usuarioSesion || $usuarioSesion->getRol() !== usuario::ROL_ADMIN) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']); exit;
    }

    $idServicio = intval($_POST['idServicio']);
    $servicio = Servicio::obtenerPorId($idServicio);
    if (!$servicio) { echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']); exit; }

    $idProveedor = $servicio->IdProveedor;
    $eliminado = Servicio::eliminar($idServicio, $idProveedor);
    if (!$eliminado) { echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el servicio']); exit; }

    $motivo = $_POST['motivo'] ?? '';
    Gestion::registrar('borrar_servicio', $motivo, $_SESSION['IdUsuario'], $idServicio);

    echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente (admin)']);
    exit;
}

// ===== CAMBIAR ESTADO (ADMIN) =====
if (isset($_POST['cambiarEstado']) && isset($_POST['idServicio']) && isset($_POST['Estado'])) {
    $usuarioSesion = $_SESSION['IdUsuario'] ?? null ? usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']) : null;
    if (!$usuarioSesion || $usuarioSesion->getRol() !== usuario::ROL_ADMIN) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']); exit;
    }

    $idServicio = intval($_POST['idServicio']);
    $nuevoEstado = trim($_POST['Estado']);
    $motivo = $_POST['motivo'] ?? '';
    $servicio = Servicio::obtenerPorId($idServicio);
    if (!$servicio) { echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']); exit; }

    $ok = strtoupper($nuevoEstado) === 'DISPONIBLE'
        ? $servicio->habilitar($_SESSION['IdUsuario'], $motivo)
        : $servicio->deshabilitar($_SESSION['IdUsuario'], $motivo);

    Gestion::registrar($ok ? 'habilitar' : 'deshabilitar', $motivo, $_SESSION['IdUsuario'], $idServicio);

    echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Estado actualizado' : 'No se pudo actualizar estado']);
    exit;
}

// ===== EDITAR SERVICIO (ADMIN) =====
if (isset($_POST['editarAdmin']) && isset($_POST['idServicio'])) {
    $usuarioSesion = $_SESSION['IdUsuario'] ?? null ? usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']) : null;
    if (!$usuarioSesion || $usuarioSesion->getRol() !== usuario::ROL_ADMIN) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']); exit;
    }

    $idServicio = intval($_POST['idServicio']);
    $nombre = trim($_POST['Nombre'] ?? '');
    $descripcion = trim($_POST['Descripcion'] ?? '');
    $precio = isset($_POST['Precio']) ? floatval($_POST['Precio']) : null;
    $divisa = $_POST['Divisa'] ?? null;
    $estado = $_POST['Estado'] ?? null;

    if ($nombre === '') { echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']); exit; }

    $db = new ConexionDB();
    $conn = $db->getConexion();
    $sql = "UPDATE Servicio SET Nombre = ?, Descripcion = ?, Precio = ?, Divisa = ?, Estado = ? WHERE IdServicio = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Error preparar consulta']); exit; }
    $stmt->bind_param('ssdsis', $nombre, $descripcion, $precio, $divisa, $estado, $idServicio);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        $motivo = $_POST['motivo'] ?? '';
        Gestion::registrar('editar_datos_servicio', $motivo, $_SESSION['IdUsuario'], $idServicio);
    }

    echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Servicio actualizado (admin)' : 'No se pudo actualizar']);
    exit;
}

// ===== OBTENER SERVICIOS DEL PROVEEDOR ACTUAL =====
if (isset($_POST['misServicios'])) {
    if (!isset($_SESSION['IdUsuario'])) { echo json_encode(['error' => 'No autorizado']); exit; }

    $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
    if (!$proveedor) { echo json_encode([]); exit; }

    $servicios = Servicio::obtenerPorProveedor($proveedor->getIdUsuario());

    $respuesta = array_map(function ($s) {
        $fotos = [];
        if (!empty($s->Fotos)) {
            foreach ($s->Fotos as $f) {
                $url = obtenerUrlFoto($f);
                if ($url) {
                    $fotos[] = [ 'Url' => $url ];
                } else {
                    error_log('[misServicios] Foto sin URL/Url: ' . print_r($f, true));
                }
            }
        } else {
            error_log('[misServicios] Servicio sin fotos: ' . print_r($s, true));
        }
        return [
            'IdServicio' => $s->IdServicio,
            'Nombre' => $s->Nombre,
            'Descripcion' => $s->Descripcion,
            'Precio' => $s->Precio,
            'Divisa' => $s->Divisa,
            'Estado' => $s->Estado,
            'Fotos' => $fotos,
            // Aliases en minúsculas
            'id' => $s->IdServicio,
            'nombre' => $s->Nombre,
            'descripcion' => $s->Descripcion,
            'precio' => $s->Precio,
            'divisa' => $s->Divisa,
            'estado' => $s->Estado,
            'fotos' => $fotos,
            // Añadir rating para misServicios
            'Rating' => isset($s->IdServicio) ? (Resena::calcularPromedioServicio($s->IdServicio)['promedio'] ?? 0) : 0,
            'RatingCount' => isset($s->IdServicio) ? (Resena::calcularPromedioServicio($s->IdServicio)['total'] ?? 0) : 0,
            'rating' => isset($s->IdServicio) ? (Resena::calcularPromedioServicio($s->IdServicio)['promedio'] ?? 0) : 0,
            'ratingCount' => isset($s->IdServicio) ? (Resena::calcularPromedioServicio($s->IdServicio)['total'] ?? 0) : 0,
        ];
    }, $servicios);

    echo json_encode($respuesta);
    exit;
}

// ===== RESPUESTA POR DEFECTO =====
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    error_log('[detalleServicio] id recibido: ' . $id);
    $servicio = Servicio::obtenerPorId($id);
    if ($servicio) {
        // Datos simulados para debug
        // Intentar obtener datos reales del proveedor
        // Obtener datos reales del proveedor desde usuario
        $usuarioProveedor = usuario::obtenerPor('IdUsuario', $servicio->IdProveedor);
        $nombreProveedor = $usuarioProveedor ? ($usuarioProveedor->getNombre() . ' ' . $usuarioProveedor->getApellido()) : 'Proveedor Ejemplo';
        $email = $usuarioProveedor && method_exists($usuarioProveedor, 'getEmail') ? $usuarioProveedor->getEmail() : 'proveedor@ejemplo.com';
        // Obtener teléfono desde DatosContacto si existe
        $telefono = '+598 1234 5678';
        if ($usuarioProveedor && $usuarioProveedor->getIdUsuario()) {
            $db = new ConexionDB();
            $conn = $db->getConexion();
            // Usar la tabla y columnas correctas: tabla `Dato`, columna `Contacto`
            // Seleccionar la columna correcta (Valor) y verificar prepare() antes de bind_param()
            $stmt = $conn->prepare("SELECT Contacto FROM Dato WHERE IdUsuario = ? AND Tipo = 'Teléfono' LIMIT 1");
            if ($stmt) {
                $idProv = $usuarioProveedor->getIdUsuario();
                $stmt->bind_param('i', $idProv);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $telefono = $row['Contacto'];
                }
                $stmt->close();
            } else {
                error_log('[detalleServicio] prepare() falló al obtener teléfono: ' . $conn->error);
            }
            $conn->close();
        }
        $contactos = [
            ['tipo' => 'Email', 'valor' => $email],
            ['tipo' => 'Teléfono', 'valor' => $telefono]
        ];
        $habilidades = ['Plomería', 'Electricidad', 'Carpintería'];
        $data = [
            'IdServicio' => $servicio->IdServicio,
            'Nombre' => $servicio->Nombre,
            'Descripcion' => $servicio->Descripcion,
            'Estado' => $servicio->Estado,
            'Precio' => $servicio->Precio,
            'Divisa' => $servicio->Divisa,
            'foto' => $servicio->getFotoServicio(),
            // Incluir id del proveedor dentro del objeto proveedor para que el frontend
            // pueda identificar al usuario/proveedor y abrir el chat/perfil.
            'proveedor' => [
                'IdProveedor' => $servicio->IdProveedor,
                'IdUsuario' => $usuarioProveedor ? $usuarioProveedor->getIdUsuario() : null,
                'idUsuario' => $usuarioProveedor ? $usuarioProveedor->getIdUsuario() : null,
                'nombre' => $nombreProveedor,
                'contactos' => $contactos,
                'habilidades' => $habilidades
            ]
            ,
            // Información de reseñas para el detalle
            'Rating' => Resena::calcularPromedioServicio($servicio->IdServicio)['promedio'] ?? 0,
            'RatingCount' => Resena::calcularPromedioServicio($servicio->IdServicio)['total'] ?? 0,
            // Aliases en minúsculas para compatibilidad con frontend
            'id' => $servicio->IdServicio,
            'nombre' => $servicio->Nombre,
            'descripcion' => $servicio->Descripcion,
            'estado' => $servicio->Estado,
            'precio' => $servicio->Precio,
            'divisa' => $servicio->Divisa,
            'fotos' => isset($servicio->Fotos) ? array_values(array_filter(array_map(function($f){ $u = obtenerUrlFoto($f); return $u ? $u : null; }, $servicio->Fotos))) : [],
            // Agregar datos que la vista de edición espera
            'palabrasClave' => PalabraClave::obtenerPorServicio($servicio->IdServicio),
            'ubicaciones' => ubicacion::obtenerPorServicio($servicio->IdServicio),
            'disponibilidades' => disponibilidad::obtenerPorServicio($servicio->IdServicio)
        ];
        error_log('[detalleServicio] Respuesta JSON: ' . json_encode($data));
        echo json_encode($data);
        exit;
    } else {
        error_log('[detalleServicio] Servicio no encontrado para id: ' . $id);
        echo json_encode(['success' => false, 'error' => 'Servicio no encontrado']);
        exit;
    }
}
error_log('[servicioController] Respuesta por defecto: Acción no reconocida o parámetros faltantes');
echo json_encode(['success' => false, 'error' => 'Acción no reconocida o parámetros faltantes']);
exit;
