<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
$response = ['ok' => false];
if (isset($_SESSION['IdUsuario']) && !empty($_SESSION['IdUsuario'])) {
    $response = ['ok' => true, 'id' => (int)$_SESSION['IdUsuario']];
    // Si existe un target para mensajería en la sesión, devolverlo y eliminarlo
    if (isset($_SESSION['mensajeria_chat_target_id']) && !empty($_SESSION['mensajeria_chat_target_id'])) {
        $response['chatTarget'] = (int)$_SESSION['mensajeria_chat_target_id'];
        unset($_SESSION['mensajeria_chat_target_id']);
    }
}
echo json_encode($response);
