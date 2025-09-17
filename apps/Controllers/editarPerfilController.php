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

$error = '';

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario->setNombre($_POST['Nombre'] ?? $usuario->getNombre());
    $usuario->setApellido($_POST['Apellido'] ?? $usuario->getApellido());
    $usuario->setEmail($_POST['Email'] ?? $usuario->getEmail());
    $usuario->setDescripcion($_POST['Descripcion'] ?? $usuario->getDescripcion());

    // Subir foto de perfil si se seleccionó
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
