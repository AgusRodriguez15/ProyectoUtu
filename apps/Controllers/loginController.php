<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Buscar el usuario por email
    $usuario = usuario::obtenerPor('Email', $email);

    if ($usuario && password_verify($password, $usuario->getContrasenaHash())) {
        // Guardar ID en sesión
        $_SESSION['IdUsuario'] = $usuario->getIdUsuario();

        // Determinar rol
        $rol = $usuario->getRol(); // debe devolver 'Cliente', 'Proveedor' o 'Administrador'

        // Redirigir según rol
        switch ($rol) {
            case 'Administrador':
                header("Location: ../Views/adminDashboard.php");
                break;
            case 'Proveedor':
                header("Location: ../Views/proveedorInicio.php");
                break;
            case 'Cliente':
            default:
                header("Location: ../Views/PANTALLA_CONTRATAR.php");
                break;
        }
        exit;
    } else {
        // Credenciales inválidas
        header("Location: ../Views/login_usuario.php?error=1");
        exit;
    }
}
?>
