<?php
// apps/Controllers/usuarioController.php
require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/ConexionDB.php';

class UsuarioController {

    // Método centralizado para registrar usuario
    public function registrarUsuario($datos, $contrasena2, $aniosExperiencia = null) {
        // Campos obligatorios
        $requeridos = ['Nombre', 'Apellido', 'Email', 'ContrasenaHash', 'Rol'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['ok' => false, 'error' => "Falta el campo: $campo"];
            }
        }

        // Validar doble contraseña
        if ($datos['ContrasenaHash'] !== $contrasena2) {
            return ['ok' => false, 'error' => 'Las contraseñas no coinciden'];
        }

        // Validar email
        if (!filter_var($datos['Email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Email no válido'];
        }

        // Validar email único
        $conexionDB = new \ClaseConexion();
        $conn = $conexionDB->getConexion();
        $stmt = $conn->prepare('SELECT IdUsuario FROM Usuario WHERE Email = ?');
        $stmt->bind_param('s', $datos['Email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return ['ok' => false, 'error' => 'El email ya está registrado'];
        }
        $stmt->close();
        $conn->close();

        // Crear objeto usuario
        $usuario = new \usuario(
            null,
            $datos['Nombre'],
            $datos['Apellido'],
            $datos['Email'],
            $datos['ContrasenaHash'],
            $datos['FotoPerfil'] ?? null,
            $datos['Descripcion'] ?? null,
            null,
            null,
            null,
            $datos['Rol']
        );

        if (isset($datos['IdUbicacion'])) {
            $usuario->IdUbicacion = $datos['IdUbicacion'];
        }

        // Registrar en la base
        $ok = $usuario->registrarUsuario($aniosExperiencia);

        if ($ok) {
            return ['ok' => true];
        } else {
            return ['ok' => false, 'error' => 'Error al registrar usuario'];
        }
    }
}

// --- Código para recibir POST directamente desde el formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new UsuarioController();

    $datos = [
        'Nombre' => $_POST['Nombre'] ?? '',
        'Apellido' => $_POST['Apellido'] ?? '',
        'Email' => $_POST['Email'] ?? '',
        'ContrasenaHash' => $_POST['ContrasenaHash'] ?? '',
        'Rol' => $_POST['Rol'] ?? '',
        'FotoPerfil' => $_FILES['FotoPerfil']['name'] ?? null,
        'Descripcion' => $_POST['Descripcion'] ?? ''
    ];

    // Llamada al método
    $resultado = $controller->registrarUsuario($datos, $_POST['password2'] ?? '');

    if ($resultado['ok']) {
    // Redirección según el rol
    switch ($datos['Rol']) {
        case 'contratar':  // Cliente
            header("Location: /proyecto/apps/Views/PANTALLA_CONTRATAR.html");
            break;
        case 'publicar':   // Proveedor
            header("Location: /proyecto/apps/Views/PANTALLA_PUBLICAR.html");
            break;
        default:
            header("Location: /proyecto/public/index.html");
    }
    exit;
} else  {
        echo "Error: " . $resultado['error'];
    }
}
?>
