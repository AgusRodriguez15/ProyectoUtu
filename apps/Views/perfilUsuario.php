<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

// Redirigir si no hay sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php");
    exit;
}

// Cargar usuario
$usuarioModel = new usuario('', '', '', '', '', '', '', '', '', '', '');
$usuario = $usuarioModel->obtenerPorId($_SESSION['IdUsuario']);

if (!$usuario) {
    die("Usuario no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../../public/CSS/estilos_generales.css">
    <link rel="stylesheet" href="../../public/CSS/pantalla.css">
    <link rel="stylesheet" href="../../public/CSS/perfil.css">
</head>
<body>

<header class="header">
    <div class="logo"><h1>Mi Plataforma</h1></div>
    <nav class="nav">
        <a href="PANTALLA_CONTRATAR.php">Servicios</a>
        <a href="#">Mi Perfil</a>
        <a href="#">Mensajes</a>
    </nav>
</header>

<div class="perfil-container">
    <!-- Foto y nombre -->
    <div class="perfil-header">
        <img src="<?= htmlspecialchars($usuario->getFotoPerfil() ?? '/proyecto/public/imagenes/default.png') ?>" alt="Foto de perfil">
        <h2><?= htmlspecialchars($usuario->getNombre() . ' ' . $usuario->getApellido()) ?></h2>
    </div>

    <!-- Información general -->
    <div class="perfil-info">
        <div><strong>Email:</strong> <?= htmlspecialchars($usuario->getEmail()) ?></div>
        <div><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($usuario->getDescripcion() ?? 'Sin descripción')) ?></div>
        <div><strong>Estado:</strong> <?= htmlspecialchars($usuario->getEstadoCuenta()) ?></div>
        <div><strong>Registrado:</strong> <?= htmlspecialchars($usuario->getFechaRegistro()) ?></div>
        <div><strong>Último acceso:</strong> <?= htmlspecialchars($usuario->getUltimoAcceso() ?? 'Nunca') ?></div>
        <div><strong>Rol:</strong> <?= htmlspecialchars($usuario->getRol()) ?></div>
        <div><strong>Ubicación ID:</strong> <?= htmlspecialchars($usuario->getIdUbicacion() ?? '-') ?></div>
    </div>

    <!-- Botón para editar perfil -->
    <div class="perfil-boton-editar">
        <a href="editarPerfil.php">Editar Perfil</a>
    </div>
</div>

</body>
</html>
