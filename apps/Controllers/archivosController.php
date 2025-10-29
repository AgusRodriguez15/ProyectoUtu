<?php
header('Content-Type: application/json; charset=utf-8');

// Seguridad simple: sólo localhost
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$baseDir = __DIR__ . '/../../public/recursos/archivos/';
if (!file_exists($baseDir)) mkdir($baseDir, 0777, true);

action:
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
    $files = array_values(array_filter(scandir($baseDir), function($f) use ($baseDir) {
        return is_file($baseDir . $f) && $f !== '.' && $f !== '..';
    }));
    echo json_encode(['success' => true, 'files' => $files]);
    exit;
}

if ($action === 'upload') {
    if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Fallo en la subida']);
        exit;
    }
    $name = basename($_FILES['archivo']['name']);
    // Sanitizar nombre
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    $dest = $baseDir . $name;
    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $dest)) {
        echo json_encode(['success' => true, 'file' => $name]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo mover el archivo']);
    }
    exit;
}

if ($action === 'delete') {
    $file = $_POST['file'] ?? '';
    if (!$file) { echo json_encode(['success' => false, 'message' => 'Falta file']); exit; }
    // Sanitizar
    $file = basename($file);
    $path = $baseDir . $file;
    if (!file_exists($path)) { echo json_encode(['success' => false, 'message' => 'No existe']); exit; }
    if (unlink($path)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar']);
    }
    exit;
}

// Acción desconocida
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción desconocida']);

?>