<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/accion.php';

// Soporta peticiones GET para acciones de consulta (lista de usuarios)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'listar') {
        header('Content-Type: application/json; charset=utf-8');

        // Permitir que el propio usuario vea su información o un administrador
        if (empty($_SESSION['IdUsuario'])) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        $usuarioActual = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
        $requestedId = intval($_GET['id'] ?? 0);
        if (!$usuarioActual) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso restringido']);
            exit;
        }
        if ($usuarioActual->getRol() !== usuario::ROL_ADMIN && $usuarioActual->getIdUsuario() !== $requestedId) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso restringido']);
            exit;
        }

        // Obtener lista de usuarios con rol calculado
        $db = new ConexionDB();
        $conn = $db->getConexion();

        // Excluir administradores de la lista pública
        $sql = "SELECT u.IdUsuario, u.Nombre, u.Apellido, u.Email, u.EstadoCuenta,
                        CASE
                            WHEN EXISTS (SELECT 1 FROM Proveedor p WHERE p.IdUsuario = u.IdUsuario) THEN 'Proveedor'
                            WHEN EXISTS (SELECT 1 FROM Cliente c WHERE c.IdUsuario = u.IdUsuario) THEN 'Cliente'
                            ELSE 'Usuario'
                        END as Rol
                 FROM Usuario u
                 WHERE NOT EXISTS (SELECT 1 FROM Administrador a WHERE a.IdUsuario = u.IdUsuario)
                 ORDER BY u.IdUsuario DESC";

        $result = $conn->query($sql);
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Error en la consulta a la base de datos']);
            exit;
        }

        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }

        echo json_encode($usuarios, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'obtener') {
        header('Content-Type: application/json; charset=utf-8');

        // Verificar sesión y rol de administrador
        if (empty($_SESSION['IdUsuario'])) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $usuarioActual = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
        if (!$usuarioActual || $usuarioActual->getRol() !== usuario::ROL_ADMIN) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso restringido']);
            exit;
        }

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Id inválido']);
            exit;
        }

        $db = new ConexionDB();
        $conn = $db->getConexion();
        $sql = "SELECT u.IdUsuario, u.Nombre, u.Apellido, u.Email, u.FotoPerfil, u.Descripcion, u.IdUbicacion, u.EstadoCuenta,
                        CASE
                            WHEN EXISTS (SELECT 1 FROM Proveedor p WHERE p.IdUsuario = u.IdUsuario) THEN 'Proveedor'
                            WHEN EXISTS (SELECT 1 FROM Cliente c WHERE c.IdUsuario = u.IdUsuario) THEN 'Cliente'
                            WHEN EXISTS (SELECT 1 FROM Administrador a WHERE a.IdUsuario = u.IdUsuario) THEN 'Administrador'
                            ELSE 'Usuario'
                        END as Rol
                 FROM Usuario u
                 WHERE u.IdUsuario = ? LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
            http_response_code(500);
            echo json_encode(['error' => 'Error en la consulta']);
            exit;
        }
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado']);
            exit;
        }
        // Obtener datos de contacto y habilidades
        $stmt2 = $conn->prepare("SELECT Tipo, Contacto FROM Dato WHERE IdUsuario = ?");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $contactos = [];
        while ($r = $res2->fetch_assoc()) { $contactos[] = $r; }
        $stmt2->close();

        $stmt3 = $conn->prepare("SELECT Habilidad, AniosExperiencia FROM Habilidad WHERE IdUsuario = ?");
        $stmt3->bind_param('i', $id);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        $habilidades = [];
        while ($h = $res3->fetch_assoc()) { $habilidades[] = $h; }
        $stmt3->close();

        $row['contactos'] = $contactos;
        $row['habilidades'] = $habilidades;

        // Si tiene IdUbicacion, obtener los detalles de la ubicación
        if (!empty($row['IdUbicacion'])) {
            require_once __DIR__ . '/../Models/ubicacion.php';
            $ubiObj = ubicacion::obtenerPorId(intval($row['IdUbicacion']));
            if ($ubiObj) {
                $row['ubicacion'] = [
                    'Pais' => $ubiObj->getPais(),
                    'Ciudad' => $ubiObj->getCiudad(),
                    'Calle' => $ubiObj->getCalle(),
                    'Numero' => $ubiObj->getNumero()
                ];
            } else {
                $row['ubicacion'] = null;
            }
        } else {
            $row['ubicacion'] = null;
        }

        echo json_encode($row, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Si se envía una acción administrativa, atenderla y devolver JSON
    if (!empty($action)) {
        header('Content-Type: application/json; charset=utf-8');

        // Permitir editarCompleto tanto al dueño del perfil como a administradores.
        if (in_array($action, ['editarCompleto','eliminarDato','eliminarHabilidad','eliminarUbicacion'])) {
            if (empty($_SESSION['IdUsuario'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            $usuarioActual = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
            $targetId = intval($_POST['id'] ?? 0);
            if (!$usuarioActual) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso restringido']);
                exit;
            }
            if ($usuarioActual->getRol() !== usuario::ROL_ADMIN && $usuarioActual->getIdUsuario() !== $targetId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso restringido']);
                exit;
            }
            // continuar y manejar editarCompleto en el switch más abajo
        } else {
            // Para el resto de acciones, exigir rol ADMIN
            if (empty($_SESSION['IdUsuario'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            $usuarioActual = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
            if (!$usuarioActual || $usuarioActual->getRol() !== usuario::ROL_ADMIN) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso restringido']);
                exit;
            }
        }

        $db = new ConexionDB();
        $conn = $db->getConexion();

        switch ($action) {
            case 'eliminarDato':
                // Eliminar dato puntual por tipo+contacto
                $id = intval($_POST['id'] ?? 0);
                $tipo = $_POST['Tipo'] ?? null;
                $contacto = $_POST['Contacto'] ?? null;
                if ($id <= 0 || empty($tipo)) {
                    http_response_code(400);
                    echo json_encode(['success'=>false,'message'=>'Parametros inválidos']);
                    exit;
                }
                $stmt = $conn->prepare("DELETE FROM Dato WHERE IdUsuario = ? AND Tipo = ?" . (!is_null($contacto) ? " AND Contacto = ?" : ""));
                if ($stmt === false) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error en preparación']); exit; }
                if (is_null($contacto)) { $stmt->bind_param('is', $id, $tipo); }
                else { $stmt->bind_param('iss', $id, $tipo, $contacto); }
                $ok = $stmt->execute(); $stmt->close();
                if ($ok) echo json_encode(['success'=>true,'message'=>'Dato eliminado']); else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error al eliminar dato']); }
                exit;

            case 'eliminarHabilidad':
                // Eliminar habilidad puntual por nombre
                $id = intval($_POST['id'] ?? 0);
                $habil = $_POST['Habilidad'] ?? null;
                if ($id <= 0 || empty($habil)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Parametros inválidos']); exit; }
                $stmt = $conn->prepare("DELETE FROM Habilidad WHERE IdUsuario = ? AND Habilidad = ?");
                $stmt->bind_param('is', $id, $habil);
                $ok = $stmt->execute(); $stmt->close();
                if ($ok) echo json_encode(['success'=>true,'message'=>'Habilidad eliminada']); else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error al eliminar habilidad']); }
                exit;

            case 'eliminarUbicacion':
                // Quitar referencia a la ubicación principal del usuario
                $id = intval($_POST['id'] ?? 0);
                $idUbic = intval($_POST['IdUbicacion'] ?? 0);
                if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Parametros inválidos']); exit; }
                // Solo quitar la referencia (no eliminamos la fila de Ubicacion por seguridad)
                $stmt = $conn->prepare("UPDATE Usuario SET IdUbicacion = NULL WHERE IdUsuario = ? AND (IdUbicacion = ? OR ? = 0)");
                $stmt->bind_param('iii', $id, $idUbic, $idUbic);
                $ok = $stmt->execute(); $stmt->close();
                if ($ok) echo json_encode(['success'=>true,'message'=>'Ubicación desvinculada']); else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error al desvincular ubicación']); }
                exit;
            case 'banear':
                $id = intval($_POST['id'] ?? 0);
                $motivo = $_POST['motivo'] ?? '';
                // Marcar como BANEADO
                $stmt = $conn->prepare("UPDATE Usuario SET EstadoCuenta = 'BANEADO' WHERE IdUsuario = ?");
                $stmt->bind_param('i', $id);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) {
                    $fecha = date('Y-m-d H:i:s');
                    // Registrar en Accion (migrado desde Gestion) vía modelo
                    try {
                        accion::crear('desabilitar', $motivo, $id, $_SESSION['IdUsuario']);
                    } catch (Exception $e) {
                        error_log('Error registrando Accion (banear -> accion): ' . $e->getMessage());
                    }
                    echo json_encode(['success' => true, 'message' => 'Usuario baneado']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al banear usuario']);
                }
                exit;

            case 'desbanear':
                $id = intval($_POST['id'] ?? 0);
                $motivo = $_POST['motivo'] ?? '';
                $stmt = $conn->prepare("UPDATE Usuario SET EstadoCuenta = 'ACTIVO' WHERE IdUsuario = ?");
                $stmt->bind_param('i', $id);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) {
                    $fecha = date('Y-m-d H:i:s');
                    // Registrar en Accion (migrado desde Gestion) vía modelo
                    try {
                        accion::crear('desabilitar', $motivo, $id, $_SESSION['IdUsuario']);
                    } catch (Exception $e) {
                        error_log('Error registrando Accion (desbanear -> accion): ' . $e->getMessage());
                    }
                    echo json_encode(['success' => true, 'message' => 'Usuario desbaneado']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al desbanear usuario']);
                }
                exit;

            case 'eliminar':
                // Acción eliminar deshabilitada: no permitimos borrados desde el frontend
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acción eliminar deshabilitada. Contacte al administrador.']);
                exit;

            case 'editar':
                $id = intval($_POST['id'] ?? 0);
                $nombre = $_POST['Nombre'] ?? '';
                $apellido = $_POST['Apellido'] ?? '';
                $email = $_POST['Email'] ?? '';
                $stmt = $conn->prepare("UPDATE Usuario SET Nombre = ?, Apellido = ?, Email = ? WHERE IdUsuario = ?");
                $stmt->bind_param('sssi', $nombre, $apellido, $email, $id);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Usuario actualizado']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
                }
                exit;

            case 'cambiarContrasena':
                $id = intval($_POST['id'] ?? 0);
                $nueva = $_POST['nueva'] ?? '';
                $motivo = $_POST['motivo'] ?? '';
                if (empty($nueva)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Contraseña vacía']);
                    exit;
                }
                // Hashear la nueva contraseña antes de guardar
                $hash = password_hash($nueva, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE Usuario SET ContrasenaHash = ? WHERE IdUsuario = ?");
                $stmt->bind_param('si', $hash, $id);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) {
                    // Registrar en Gestion el cambio de contraseña (auditoría)
                    $fecha = date('Y-m-d H:i:s');
                    $descripcion = !empty($motivo) ? $motivo : 'Cambio de contraseña por administrador';
                    // Registrar en Accion (migrado desde Gestion) vía modelo
                    try {
                        accion::crear('editar_datos_servicio', $descripcion, $id, $_SESSION['IdUsuario']);
                    } catch (Exception $e) {
                        error_log('Error registrando Accion (cambiarContrasena -> accion): ' . $e->getMessage());
                    }

                    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña']);
                }
                exit;

            case 'cambiarEmail':
                $id = intval($_POST['id'] ?? 0);
                $email = $_POST['Email'] ?? '';
                if (empty($email)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Email vacío']);
                    exit;
                }
                // Verificar que el email no esté asociado a otro usuario
                $check = $conn->prepare("SELECT IdUsuario FROM Usuario WHERE Email = ? AND IdUsuario != ?");
                $check->bind_param('si', $email, $id);
                $check->execute();
                $resCheck = $check->get_result();
                if ($resCheck && $resCheck->num_rows > 0) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Email ya en uso por otro usuario']);
                    $check->close();
                    exit;
                }
                $check->close();

                $stmt = $conn->prepare("UPDATE Usuario SET Email = ? WHERE IdUsuario = ?");
                $stmt->bind_param('si', $email, $id);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Email actualizado']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar email']);
                }
                exit;

            case 'editarCompleto':
                // Editar perfil completo: Nombre, Apellido, Descripcion, Foto (subida o eliminar), IdUbicacion, Contactos, Habilidades
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Id inválido']); exit; }

                $nombre = $_POST['Nombre'] ?? '';
                $apellido = $_POST['Apellido'] ?? '';
                $descripcion = $_POST['Descripcion'] ?? null;
                $removeFoto = !empty($_POST['removeFoto']);
                // reset flags
                $resetNombre = !empty($_POST['resetNombre']);
                $resetApellido = !empty($_POST['resetApellido']);
                $resetDescripcion = !empty($_POST['resetDescripcion']);
                $resetFoto = !empty($_POST['resetFoto']);
                $resetUbicacion = !empty($_POST['resetUbicacion']);
                $resetContactos = !empty($_POST['resetContactos']);
                $resetHabilidades = !empty($_POST['resetHabilidades']);

                // Contactos y habilidades vienen como JSON en campos 'contactos' y 'habilidades'
                $contactosJson = $_POST['contactos'] ?? '[]';
                $habilidadesJson = $_POST['habilidades'] ?? '[]';

                $contactos = json_decode($contactosJson, true);
                $habilidades = json_decode($habilidadesJson, true);

                // Buscar usuario
                $usuarioObj = usuario::obtenerPor('IdUsuario', $id);
                if (!$usuarioObj) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Usuario no encontrado']); exit; }

                // Actualizar campos básicos
                // Aplicar resets si se solicitaron
                if ($resetNombre) { $usuarioObj->setNombre(''); } else { $usuarioObj->setNombre($nombre); }
                if ($resetApellido) { $usuarioObj->setApellido(''); } else { $usuarioObj->setApellido($apellido); }
                if ($resetDescripcion) { $usuarioObj->setDescripcion(''); } else { $usuarioObj->setDescripcion($descripcion); }

                // Manejar foto: eliminar o subir nueva (si se envía archivo en $_FILES['FotoPerfil'])
                if ($removeFoto || $resetFoto) {
                    // Borrar archivo anterior si existe
                    $foto = $usuarioObj->getFotoPerfil();
                    if (!empty($foto)) {
                        $ruta = __DIR__ . '/../../public/recursos/imagenes/perfil/' . $foto;
                        if (file_exists($ruta)) @unlink($ruta);
                    }
                    $usuarioObj->setFotoPerfil(null);
                } elseif (!empty($_FILES['FotoPerfil']) && !empty($_FILES['FotoPerfil']['tmp_name'])) {
                    // Subir nuevo archivo
                    $archivo = $_FILES['FotoPerfil'];
                    $nombreArchivo = basename($archivo['name']);
                    $rutaDestino = __DIR__ . '/../../public/recursos/imagenes/perfil/' . $nombreArchivo;
                    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                        $usuarioObj->setFotoPerfil($nombreArchivo);
                    }
                }

                // Manejar ubicación primaria si viene (pais, ciudad, calle, numero)
                $pais = $_POST['pais'] ?? null;
                if (!empty($resetUbicacion)) {
                    // Reiniciar ubicación: quitar referencia
                    $usuarioObj->setIdUbicacion(null);
                } else {
                    if (!empty($pais)) {
                        $ciudad = $_POST['ciudad'] ?? '';
                        $calle = $_POST['calle'] ?? '';
                        $numero = $_POST['numero'] ?? null;
                        require_once __DIR__ . '/../Models/ubicacion.php';
                        $ubi = new ubicacion(null, $pais, $ciudad, $calle, $numero === '' ? null : intval($numero));
                        if ($ubi->guardar()) {
                            $usuarioObj->setIdUbicacion($ubi->getIdUbicacion());
                        }
                    }
                }

                // Guardar usuario básico
                $dbOk = $usuarioObj->guardar();

                // Reemplazar contactos y habilidades
                require_once __DIR__ . '/../Models/dato.php';
                require_once __DIR__ . '/../Models/habilidad.php';
                dato::eliminarPorUsuario($id);
                habilidad::eliminarPorUsuario($id);
                if (is_array($contactos)) {
                    foreach ($contactos as $c) {
                        $t = $c['Tipo'] ?? null; $val = $c['Contacto'] ?? null;
                        if ($t) { $d = new dato($id, $t, $val); $d->guardar(); }
                    }
                }
                if (is_array($habilidades)) {
                    foreach ($habilidades as $h) {
                        $nom = $h['Habilidad'] ?? null; $an = isset($h['AniosExperiencia']) ? intval($h['AniosExperiencia']) : 0;
                        if ($nom) { $hb = new habilidad($id, $nom, $an); $hb->guardar(); }
                    }
                }

                if ($dbOk) {
                    echo json_encode(['success'=>true,'message'=>'Perfil actualizado']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success'=>false,'message'=>'Error al guardar perfil']);
                }
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
                exit;
        }
    }

    // Si no se pasó acción, se trata de un registro (flujo original)
    // Capturar datos del formulario
    $datos = [
        'Nombre' => $_POST['Nombre'] ?? '',
        'Apellido' => $_POST['Apellido'] ?? '',
        'Email' => $_POST['Email'] ?? '',
        'ContrasenaHash' => $_POST['ContrasenaHash'] ?? '',
        'Rol' => $_POST['Rol'] ?? '',
        'FotoPerfil' => $_FILES['FotoPerfil'] ?? null,
        'Descripcion' => $_POST['Descripcion'] ?? '',
        'IdUbicacion' => $_POST['IdUbicacion'] ?? null
    ];

    // Validar campos obligatorios
    $requeridos = ['Nombre', 'Apellido', 'Email', 'ContrasenaHash', 'Rol'];
    foreach ($requeridos as $campo) {
        if (empty($datos[$campo])) {
            die("Error: Falta el campo $campo");
        }
    }

    // Validar contraseña y confirmación
    if ($datos['ContrasenaHash'] !== ($_POST['ConfirmarContrasena'] ?? '')) {
        die("Error: Las contraseñas no coinciden");
    }

    // Validar rol
    $rolesValidos = [
        usuario::ROL_CLIENTE,
        usuario::ROL_PROVEEDOR,
        usuario::ROL_ADMIN
    ];

    if (!in_array($datos['Rol'], $rolesValidos)) {
        die("Error: Rol inválido");
    }

    // Crear objeto usuario
    $usuario = new usuario(
        null,
        $datos['Nombre'],
        $datos['Apellido'],
        $datos['Email'],
        $datos['ContrasenaHash'],
        null,
        $datos['Descripcion'],
        null,
        null,
        null,
        $datos['Rol'],
        !empty($datos['IdUbicacion']) ? $datos['IdUbicacion'] : null
    );

    // Subir foto de perfil
    $usuario->subirFotoPerfil($datos['FotoPerfil']);

    // Registrar usuario
    $ok = $usuario->registrarUsuario($_POST['AniosExperiencia'] ?? null);

    if ($ok) {
        // Guardar ID en sesión
        $_SESSION['IdUsuario'] = $usuario->getIdUsuario();

        // Redirección según rol
        switch ($usuario->getRol()) {
            case usuario::ROL_CLIENTE:
                header("Location: /proyecto/apps/Views/PANTALLA_CONTRATAR.php");
                break;
            case usuario::ROL_PROVEEDOR:
                header("Location: /proyecto/apps/Views/PANTALLA_PUBLICAR.php");
                break;
            case usuario::ROL_ADMIN:
            default:
                header("Location: /proyecto/public/index.php");
        }
        exit;
    } else {
        die("Error: No se pudo registrar el usuario. ¿Email ya registrado?");
    }
}
