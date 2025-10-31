<?php
// Recibe POST { id } y lo guarda en la sesión como chat target, luego redirige a la pantalla de mensajería
session_start();
header('Content-Type: text/plain; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}
$id = null;
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
} else {
    // también aceptar JSON
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['id'])) $id = intval($input['id']);
    }
}
if ($id) {
    $_SESSION['mensajeria_chat_target_id'] = $id;
}
// Redirigir a la pantalla de mensajería sin parámetros
header('Location: /proyecto/apps/Views/PANTALLA_MENSAJERIA.html');
exit;
