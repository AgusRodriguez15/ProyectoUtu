<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Buscar el usuario por email usando el método genérico
    $usuario = usuario::obtenerPor('Email', $email);

    if ($usuario && password_verify($password, $usuario->getContrasenaHash())) {
        // Guardar ID en sesión
        $_SESSION['IdUsuario'] = $usuario->getIdUsuario();

        // Redirigir a perfil
        header("Location: ../Views/perfilUsuario.php");
        exit;
    } else {
        // Redirigir con error
        header("Location: ../Views/login_usuario.php?error=1");
        exit;
    }
}
?>