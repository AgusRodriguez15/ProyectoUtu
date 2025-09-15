<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php");
    exit;
}

$usuarioModel = new usuario('', '', '', '', '', '', '', '', '', '', '');
$usuario = $usuarioModel->obtenerPorId($_SESSION['IdUsuario']);
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
    <input type="text" name="Nombre" value="<?= htmlspecialchars($usuario['Nombre'] ?? '') ?>" required><br>

    <label>Apellido:</label>
    <input type="text" name="Apellido" value="<?= htmlspecialchars($usuario['Apellido'] ?? '') ?>" required><br>

    <label>Email:</label>
    <input type="email" name="Email" value="<?= htmlspecialchars($usuario['Email'] ?? '') ?>" required><br>

    <label>Descripción:</label>
    <textarea name="Descripcion"><?= htmlspecialchars($usuario['Descripcion'] ?? '') ?></textarea><br>

    <label>Foto de Perfil:</label>
    <input type="file" name="FotoPerfil" accept="image/*"><br>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
