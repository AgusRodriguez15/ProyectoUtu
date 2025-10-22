<?php
require_once __DIR__ . '/../Models/disponibilidad.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {
        // Obtener disponibilidades de un servicio
        if (!isset($_GET['idServicio'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Falta el ID del servicio']);
            exit;
        }
        
        $idServicio = intval($_GET['idServicio']);
        $disponibilidades = Disponibilidad::obtenerPorServicio($idServicio);
        
        $dispArray = [];
        foreach ($disponibilidades as $disp) {
            $dispArray[] = [
                'idDisponibilidad' => $disp->getIdDisponibilidad(),
                'fechaInicio' => $disp->getFechaInicio(),
                'fechaFin' => $disp->getFechaFin(),
                'estado' => $disp->getEstado()
            ];
        }
        
        echo json_encode(['success' => true, 'disponibilidades' => $dispArray]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    error_log("Error en disponibilidadController: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>