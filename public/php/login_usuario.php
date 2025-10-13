<?php
require_once '../../apps/Models/ConexionDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $conn = new ClaseConexion();
        $db = $conn->getConexion();
    $stmt = $db->prepare('SELECT IdUsuario, Nombre, Apellido, Email, ContrasenaHash, EstadoCuenta FROM Usuario WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['ContrasenaHash'])) {
                session_start();
                $_SESSION['usuario_id'] = $row['IdUsuario'];
                $_SESSION['usuario_nombre'] = $row['Nombre'];
                $_SESSION['usuario_estado'] = $row['EstadoCuenta'];

                // Consultar el rol
                $rol = '';
                $stmtRol = $db->prepare('SELECT 1 FROM Proveedor WHERE IdUsuario = ?');
                $stmtRol->bind_param('i', $row['IdUsuario']);
                $stmtRol->execute();
                $stmtRol->store_result();
                if ($stmtRol->num_rows > 0) {
                    $rol = 'Proveedor';
                } else {
                    $stmtRol = $db->prepare('SELECT 1 FROM Cliente WHERE IdUsuario = ?');
                    $stmtRol->bind_param('i', $row['IdUsuario']);
                    $stmtRol->execute();
                    $stmtRol->store_result();
                    if ($stmtRol->num_rows > 0) {
                        $rol = 'Cliente';
                    } else {
                        $stmtRol = $db->prepare('SELECT 1 FROM Administrador WHERE IdUsuario = ?');
                        $stmtRol->bind_param('i', $row['IdUsuario']);
                        $stmtRol->execute();
                        $stmtRol->store_result();
                        if ($stmtRol->num_rows > 0) {
                            $rol = 'Administrador';
                        }
                    }
                }
                $stmtRol->close();

                // Redirigir según el rol
                if ($rol === 'Proveedor') {
                    header('Location: ../../apps/PANTALLA_PUBLICAR.html');
                } elseif ($rol === 'Cliente') {
                    header('Location: ../../apps/PANTALLA_CONTRATAR.html');
                } elseif ($rol === 'Administrador') {
                    header('Location: ../../apps/PANTALLA_ADMIN.html');
                } else {
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