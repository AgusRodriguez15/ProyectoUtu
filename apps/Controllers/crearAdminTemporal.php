<?php
header('Content-Type: application/json; charset=utf-8');

// Sólo permitir ejecución desde localhost por seguridad
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../Models/usuario.php';

$nombre = $_POST['nombre'] ?? 'Administrador';
$apellido = $_POST['apellido'] ?? 'Sistema';
$email = $_POST['email'] ?? 'admin@ejemplo.com';
$contrasena = $_POST['contrasena'] ?? 'admin';

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Evitar duplicados
if (usuario::obtenerPor('Email', $email) !== null) {
    echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
    exit;
}

// Crear usuario con rol Administrador (usa el método del modelo, que hará hash de la contraseña)
$user = new usuario(
    null,
    $nombre,
    $apellido,
    $email,
    $contrasena,
    null,
    null,
    null,
    null,
    null,
    usuario::ROL_ADMIN,
    null
);

$ok = $user->registrarUsuario();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Administrador creado', 'IdUsuario' => $user->getIdUsuario()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al crear administrador']);
}

?>
