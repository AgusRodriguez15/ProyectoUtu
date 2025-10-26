<?php
<?php
require_once __DIR__ . '/../Models/mensaje.php';
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json; charset=utf-8');

if ($method === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $contenido = $input['contenido'] ?? null;
        $idEmisor = isset($input['idEmisor']) ? (int)$input['idEmisor'] : null;
        $idReceptor = isset($input['idReceptor']) ? (int)$input['idReceptor'] : null;
    } else {
        $contenido = $_POST['contenido'] ?? null;
        $idEmisor = isset($_POST['idEmisor']) ? (int)$_POST['idEmisor'] : null;
        $idReceptor = isset($_POST['idReceptor']) ? (int)$_POST['idReceptor'] : null;
    }
    if (!$contenido || !$idEmisor || !$idReceptor) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    $res = Mensaje::enviar($contenido, $idEmisor, $idReceptor);
    if ($res['ok']) {
        echo json_encode(['ok' => true, 'insertId' => $res['insertId']]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'error desconocido']);
    }
    exit;
}

if ($method === 'GET') {
    $id1 = isset($_GET['id1']) ? (int)$_GET['id1'] : null;
    $id2 = isset($_GET['id2']) ? (int)$_GET['id2'] : null;
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
