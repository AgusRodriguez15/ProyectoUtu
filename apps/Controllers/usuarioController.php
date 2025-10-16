<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Capturar datos del formulario
    $datos = [
        'Nombre' => $_POST['Nombre'] ?? '',
        'Apellido' => $_POST['Apellido'] ?? '',
        'Email' => $_POST['Email'] ?? '',
        'ContrasenaHash' => $_POST['ContrasenaHash'] ?? '',
        'Rol' => $_POST['Rol'] ?? '',
        'FotoPerfil' => $_FILES['FotoPerfil'] ?? null,
        'Descripcion' => $_POST['Descripcion'] ?? '',
        'IdUbicacion' => $_POST['IdUbicacion'] ?? null
    ];

    // Validar campos obligatorios
    $requeridos = ['Nombre', 'Apellido', 'Email', 'ContrasenaHash', 'Rol'];
    foreach ($requeridos as $campo) {
        if (empty($datos[$campo])) {
            die("Error: Falta el campo $campo");
        }
    }

    // Validar contraseña y confirmación
    if ($datos['ContrasenaHash'] !== ($_POST['ConfirmarContrasena'] ?? '')) {
        die("Error: Las contraseñas no coinciden");
    }

    // Validar rol
    $rolesValidos = [
        usuario::ROL_CLIENTE,
        usuario::ROL_PROVEEDOR,
        usuario::ROL_ADMIN
    ];

    if (!in_array($datos['Rol'], $rolesValidos)) {
        die("Error: Rol inválido");
    }

    // Crear objeto usuario
    $usuario = new usuario(
        null,
        $datos['Nombre'],
        $datos['Apellido'],
        $datos['Email'],
        $datos['ContrasenaHash'],
        null,
        $datos['Descripcion'],
        null,
        null,
        null,
        $datos['Rol'],
        !empty($datos['IdUbicacion']) ? $datos['IdUbicacion'] : null
    );

    // Subir foto de perfil
    $usuario->subirFotoPerfil($datos['FotoPerfil']);

    // Registrar usuario
    $ok = $usuario->registrarUsuario($_POST['AniosExperiencia'] ?? null);

    if ($ok) {
        // Guardar ID en sesión
        $_SESSION['IdUsuario'] = $usuario->getIdUsuario();

        // Redirección según rol
        switch ($usuario->getRol()) {
            case usuario::ROL_CLIENTE:
                header("Location: /proyecto/apps/Views/PANTALLA_CONTRATAR.php");
                break;
            case usuario::ROL_PROVEEDOR:
                header("Location: /proyecto/apps/Views/PANTALLA_PUBLICAR.php");
                break;
            case usuario::ROL_ADMIN:
            default:
                header("Location: /proyecto/public/index.php");
        }
        exit;
    } else {
        die("Error: No se pudo registrar el usuario. ¿Email ya registrado?");
    }
}
