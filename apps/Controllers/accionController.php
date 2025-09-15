<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php"); // Redirigir a login si no hay sesión
    exit;
}

// Crear modelo usuario
$usuarioModel = new usuario('', '', '', '', '', '', '', '', '', '', '');

// Obtener datos del usuario logueado
$usuario = $usuarioModel->obtenerPorId($_SESSION['IdUsuario']);

if (!$usuario) {
    die("Usuario no encontrado.");
}

// Actualizar último acceso
$usuarioModel->actualizarUltimoAcceso($_SESSION['IdUsuario']);

// Incluir vista
require_once __DIR__ . '/../Views/perfilUsuario.php';
