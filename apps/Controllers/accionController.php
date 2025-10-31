
<?php
require_once __DIR__ . '/../Models/accion.php';
require_once __DIR__ . '/../Models/usuario.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($action === 'obtenerGestionesAdmin') {
	try {
		// Obtener todas las acciones realizadas por administradores
	$gestiones = accion::obtenerAccionesAdmin();
		echo json_encode(['success' => true, 'gestiones' => $gestiones], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'message' => 'Error al consultar gestiones']);
	}
	exit;
}
// Si no es la acción esperada
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción inválida']);
exit;
