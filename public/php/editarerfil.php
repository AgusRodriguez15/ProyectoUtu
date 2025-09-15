<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php");
    exit;
}

// Obtener usuario como objeto
$usuario = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
if (!$usuario) {
    die("Usuario no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Perfil</title>
<link rel="stylesheet" href="../../public/CSS/perfil.css">
</head>
<body>

<h2>Editar Perfil</h2>

<form action="editarPerfilController.php" method="POST" enctype="multipart/form-data">
    <label>Nombre:</label>
    <input type="text" name="Nombre" value="<?= htmlspecialchars($usuario->getNombre()) ?>" required><br>

    <label>Apellido:</label>
    <input type="text" name="Apellido" value="<?= htmlspecialchars($usuario->getApellido()) ?>" required><br>

    <label>Email:</label>
    <input type="email" name="Email" value="<?= htmlspecialchars($usuario->getEmail()) ?>" required><br>

    <label>Descripción:</label>
    <textarea name="Descripcion"><?= htmlspecialchars($usuario->getDescripcion() ?? '') ?></textarea><br>

    <label>Foto de Perfil:</label>
    <input type="file" name="FotoPerfil" accept="image/*"><br>

    <?php if ($usuario->getFotoPerfil()): ?>
        <img src="<?= htmlspecialchars($usuario->getFotoPerfil()) ?>" alt="Foto de perfil" width="100">
    <?php endif; ?>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
?>