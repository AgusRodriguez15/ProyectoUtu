<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $usuario = usuario::autenticar($email, $password);

    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario->getIdUsuario();
        $_SESSION['usuario_nombre'] = $usuario->getNombre();
        $_SESSION['usuario_rol'] = $usuario->getRol();
        $_SESSION['usuario_estado'] = $usuario->getEstadoCuenta();

        switch ($usuario->getRol()) {
            case usuario::ROL_PROVEEDOR:
                header('Location: ../Views/PANTALLA_PUBLICAR.php');
                break;
            case usuario::ROL_CLIENTE:
                header('Location: ../Views/PANTALLA_CONTRATAR.php');
                break;
            case usuario::ROL_ADMIN:
                header('Location: ../Views/PANTALLA_ADMIN.php');
                break;
            default:
                header('Location: ../../public/index.php?error=rol');
        }
        exit;
    } else {
        header('Location: ../Views/login_usuario.php?error=credenciales');
        exit;
    }
} else {
    header('Location: ../Views/login_usuario.php');
    exit;
}
