<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

// Verificar sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php");
    exit;
}

// Obtener usuario por Id usando el método genérico
$usuario = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);

if (!$usuario) {
    die("Usuario no encontrado");
}

// Inicializar variable de error
$error = '';

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario->setNombre($_POST['Nombre'] ?? $usuario->getNombre());
    $usuario->setApellido($_POST['Apellido'] ?? $usuario->getApellido());
    $usuario->setEmail($_POST['Email'] ?? $usuario->getEmail());
    $usuario->setDescripcion($_POST['Descripcion'] ?? $usuario->getDescripcion());

    // Subir foto si se seleccionó
    if (!empty($_FILES['FotoPerfil']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../../public/uploads/';
        $filename = basename($_FILES['FotoPerfil']['name']);
        $rutaDestino = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['FotoPerfil']['tmp_name'], $rutaDestino)) {
            $usuario->setFotoPerfil('/proyecto/public/uploads/' . $filename);
        } else {
            $error = "Error al subir la foto de perfil";
        }
    }

    // Guardar cambios
    if (empty($error) && $usuario->guardar()) {
        header("Location: perfilUsuario.php");
        exit;
    } elseif (empty($error)) {
        $error = "No se pudo actualizar el perfil";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../../public/CSS/perfil.css">
</head>
<body>
    <h2>Perfil de Usuario</h2>

    <?php if (!empty($error)) : ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label>Nombre</label>
        <input type="text" name="Nombre" value="<?= htmlspecialchars($usuario->getNombre()) ?>">

        <label>Apellido</label>
        <input type="text" name="Apellido" value="<?= htmlspecialchars($usuario->getApellido()) ?>">

        <label>Email</label>
        <input type="email" name="Email" value="<?= htmlspecialchars($usuario->getEmail()) ?>">

        <label>Descripción</label>
        <textarea name="Descripcion"><?= htmlspecialchars($usuario->getDescripcion() ?? '') ?></textarea>

        <label>Foto de perfil</label>
        <input type="file" name="FotoPerfil">

        <?php if ($usuario->getFotoPerfil()) : ?>
            <img src="<?= htmlspecialchars($usuario->getFotoPerfil()) ?>" alt="Foto de perfil" width="100">
        <?php endif; ?>

        <button type="submit">Guardar cambios</button>
    </form>

    <a href="logout.php">Cerrar sesión</a>
</body>
</html>
