<?php
// Archivo de prueba para verificar el sistema de reseñas
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../Models/reseña.php';

header('Content-Type: application/json');

try {
    // Prueba 1: Verificar conexión
    echo json_encode([
        'test' => 'conexion',
        'success' => true,
        'mensaje' => 'Conexión exitosa',
        'session' => [
            'IdUsuario' => $_SESSION['IdUsuario'] ?? 'No logueado',
            'TipoUsuario' => $_SESSION['TipoUsuario'] ?? 'N/A'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
