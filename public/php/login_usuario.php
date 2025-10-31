<?php
require_once '../../apps/Models/ConexionDB.php';
require_once '../../apps/Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $conn = new ConexionDB();
        $db = $conn->getConexion();
        
        // Obtener datos básicos del usuario
        $stmt = $db->prepare('SELECT IdUsuario, Nombre, Apellido, ContrasenaHash, EstadoCuenta FROM Usuario WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Obtener el rol usando el nuevo método
        $rol = usuario::obtenerRolPorEmail($email);
        error_log("Rol obtenido para el email $email: " . ($rol ?? 'no encontrado'));
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['ContrasenaHash'])) {
                if (!$rol) {
                    error_log("Error: No se pudo determinar el rol para el usuario {$row['IdUsuario']}");
                    header('Location: ../index.html?error=rol');
                    exit();
                }

                // Activar cuenta al iniciar sesión (sin importar el estado anterior)
                usuario::cambiarEstadoCuentaPorId($row['IdUsuario'], true);
                error_log("Cuenta activada para usuario ID: {$row['IdUsuario']}");

                session_start();
                $_SESSION['IdUsuario'] = $row['IdUsuario'];
                $_SESSION['usuario_nombre'] = $row['Nombre'];
                $_SESSION['usuario_estado'] = 'ACTIVO'; // Siempre ACTIVO al iniciar sesión
                $_SESSION['usuario_rol'] = $rol;

                // Debug log
                error_log("Iniciando sesión - Usuario: {$row['IdUsuario']}, Nombre: {$row['Nombre']}, Rol: $rol");

                // Redirigir según el rol
                switch ($rol) {
                    case 'Proveedor':
                        error_log("Redirigiendo a proveedor: {$row['IdUsuario']}");
                        header('Location: ../../apps/Views/PANTALLA_PUBLICAR.html');
                        break;
                    case 'Cliente':
                        error_log("Redirigiendo a cliente: {$row['IdUsuario']}");
                        header('Location: ../../apps/Views/PANTALLA_CONTRATAR.html');
                        break;
                    case 'Administrador':
                        error_log("Redirigiendo a administrador: {$row['IdUsuario']}");
                        header('Location: ../../apps/Views/PANTALLA_ADMIN.html');
                        break;
                    default:
                        error_log("Error en redirección - rol no válido: $rol");
                        header('Location: ../index.html?error=rol');
                }
                exit();
            } else {
                header('Location: ../index.html?error=credenciales');
                exit();
            }
        } else {
            header('Location: ../index.html?error=credenciales');
            exit();
        }
        $stmt->close();
        $db->close();
    } else {
        header('Location: ../index.html?error=datos');
        exit();
    }
} else {
    header('Location: ../index.html');
    exit();
}
?>