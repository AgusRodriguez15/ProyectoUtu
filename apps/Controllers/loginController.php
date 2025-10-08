<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $usuario = Usuario::obtenerPor('Email', $email);

    if ($usuario && password_verify($password, $usuario->getContrasenaHash())) {
        // Guardar ID y rol en sesión
        $_SESSION['IdUsuario'] = $usuario->getIdUsuario();
        $_SESSION['RolUsuario'] = $usuario->getRol(); // 'Cliente', 'Proveedor', 'Administrador'

        // Redirigir al único controlador
        header("Location: ../Controllers/servicioController.php");
        exit;
    }

    else{
        
    }
}
