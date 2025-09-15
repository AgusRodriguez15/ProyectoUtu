<?php
// Iniciar la sesión al principio del script
session_start();

// Cargar la clase de usuario desde el directorio correcto
require_once __DIR__ . '/../Models/usuario.php';

// Redirigir si el usuario no está autenticado
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php");
    exit;
}

// Obtener el objeto de usuario a partir del ID de la sesión
$usuario = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);

// Si el usuario no existe en la base de datos, terminar la ejecución
if (!$usuario) {
    // Si el usuario no se encuentra, destruimos la sesión por seguridad
    session_destroy();
    header("Location: login_usuario.php");
    exit;
}

// Inicializar variables para mensajes de estado
$mensaje_exito = '';
$errores = [];

// Procesar el formulario solo cuando se envían los datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar los datos del formulario antes de procesar
    $nombre = trim($_POST['Nombre'] ?? '');
    $apellido = trim($_POST['Apellido'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $descripcion = trim($_POST['Descripcion'] ?? '');

    // 1. Validaciones básicas de los campos
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    }
    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido.";
    }

    // Si no hay errores de validación
    if (empty($errores)) {
        // Actualizar los atributos del objeto usuario
        $usuario->setNombre($nombre);
        $usuario->setApellido($apellido);
        $usuario->setEmail($email);
        $usuario->setDescripcion($descripcion);

        // 2. Manejo de la subida de la foto de perfil
        if (isset($_FILES['FotoPerfil']) && $_FILES['FotoPerfil']['error'] === UPLOAD_ERR_OK) {
            $nombreArchivo = basename($_FILES['FotoPerfil']['name']);
            $rutaDestino = __DIR__ . '/../../public/uploads/' . $nombreArchivo;
            $tipoArchivo = mime_content_type($_FILES['FotoPerfil']['tmp_name']);

            // Validar que el archivo sea una imagen
            if (strpos($tipoArchivo, 'image/') === 0) {
                // Mover el archivo subido
                if (move_uploaded_file($_FILES['FotoPerfil']['tmp_name'], $rutaDestino)) {
                    $usuario->setFotoPerfil('/proyecto/public/uploads/' . $nombreArchivo);
                } else {
                    $errores[] = "Error al mover el archivo subido.";
                }
            } else {
                $errores[] = "El archivo subido no es una imagen válida.";
            }
        }

        // Si no hay errores después de la subida de la foto, guardar los cambios
        if (empty($errores)) {
            if ($usuario->guardar()) {
                $mensaje_exito = "¡Perfil actualizado con éxito!";
            } else {
                $errores[] = "No se pudo actualizar el perfil. Por favor, inténtalo de nuevo.";
            }
        }
    }
}
?>

---

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../../public/CSS/style_perfil.css">
    <style>
        .error {
            color: red;
        }
        .exito {
            color: green;
        }
    </style>
</head>
<body>
    <h2>Perfil de Usuario</h2>

    <?php if (!empty($errores)) : ?>
        <?php foreach ($errores as $error) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($mensaje_exito)) : ?>
        <p class="exito"><?= htmlspecialchars($mensaje_exito) ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="Nombre" value="<?= htmlspecialchars($usuario->getNombre()) ?>" required>

        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="Apellido" value="<?= htmlspecialchars($usuario->getApellido()) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="Email" value="<?= htmlspecialchars($usuario->getEmail()) ?>" required>

        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="Descripcion"><?= htmlspecialchars($usuario->getDescripcion() ?? '') ?></textarea>

        <label for="foto">Foto de perfil</label>
        <input type="file" id="foto" name="FotoPerfil" accept="image/*">

        <?php if ($usuario->getFotoPerfil()) : ?>
            <img src="<?= htmlspecialchars($usuario->getFotoPerfil()) ?>" alt="Foto de perfil" width="100">
        <?php endif; ?>

        <button type="submit">Guardar cambios</button>
    </form>

    <a href="logout.php">Cerrar sesión</a>
</body>
</html>