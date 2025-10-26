<?php
<?php
require_once __DIR__ . '/../Models/mensaje.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $contenido = $input['contenido'] ?? null;
    $idEmisor = isset($input['idEmisor']) ? (int)$input['idEmisor'] : null;
    $idReceptor = isset($input['idReceptor']) ? (int)$input['idReceptor'] : null;
    header('Content-Type: application/json');
    if (!$contenido || !$idEmisor || !$idReceptor) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    $ok = Mensaje::enviar($contenido, $idEmisor, $idReceptor);
    if ($ok) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'No se pudo enviar']);
    }
    exit;
}
if ($method === 'GET') {
    $id1 = isset($_GET['id1']) ? (int)$_GET['id1'] : null;
    $id2 = isset($_GET['id2']) ? (int)$_GET['id2'] : null;
    header('Content-Type: application/json');
    if (!$id1 || !$id2) {
        http_response_code(400);
        echo json_encode([]);
        exit;
    }
    $mensajes = Mensaje::obtenerPorConversacion($id1, $id2);
    $salida = array_map(function ($m) {
        return [
            'IdMensaje' => $m->IdMensaje,
            'Contenido' => $m->Contenido,
            'Fecha' => $m->Fecha,
            'Estado' => $m->Estado,
            'IdUsuarioEmisor' => $m->IdUsuarioEmisor,
            'IdUsuarioReceptor' => $m->IdUsuarioReceptor,
        ];
    }, $mensajes);
    echo json_encode($salida);
    exit;
}
?>
