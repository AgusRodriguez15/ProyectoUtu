<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Models/categoria.php';

// Verificar si se especificó una acción
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'obtenerTodas':
            $categoria = new Categoria();
            $categorias = $categoria->obtenerTodas();
            echo json_encode([
                'success' => true,
                'data' => $categorias
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Acción no especificada o inválida'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
