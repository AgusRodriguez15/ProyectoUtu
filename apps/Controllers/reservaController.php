<?php
require_once __DIR__ . '/../Models/reserva.php';
require_once __DIR__ . '/../Models/disponibilidad.php';

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['IdUsuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión para realizar una reserva']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'POST') {
        // Crear nueva reserva
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos requeridos
        if (!isset($data['idServicio']) || !isset($data['idDisponibilidad'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
            exit;
        }
        
        $idUsuario = $_SESSION['IdUsuario'];
        $idServicio = intval($data['idServicio']);
        $idDisponibilidad = intval($data['idDisponibilidad']);
        $observacion = isset($data['observacion']) ? $data['observacion'] : null;
        
        // Verificar que la disponibilidad existe y está disponible
        $disponibilidades = Disponibilidad::obtenerPorServicio($idServicio);
        $disponibilidadValida = false;
        
        foreach ($disponibilidades as $disp) {
            if ($disp->getIdDisponibilidad() == $idDisponibilidad) {
                if ($disp->getEstado() !== 'disponible') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'El horario seleccionado ya no está disponible']);
                    exit;
                }
                $disponibilidadValida = true;
                break;
            }
        }
        
        if (!$disponibilidadValida) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Disponibilidad no válida']);
            exit;
        }
        
        // Crear la reserva
        $datosReserva = [
            'idDisponibilidad' => $idDisponibilidad,
            'estado' => 'pendiente',
            'observacion' => $observacion,
            'idUsuario' => $idUsuario,
            'idServicio' => $idServicio
        ];
        
        $idReserva = Reserva::crear($datosReserva);
        
        if ($idReserva) {
            // Actualizar el estado de la disponibilidad a 'ocupado'
            try {
                Disponibilidad::actualizarEstado($idDisponibilidad, 'ocupado');
                error_log("Disponibilidad {$idDisponibilidad} marcada como ocupada");
            } catch (Exception $e) {
                error_log("Error al actualizar disponibilidad: " . $e->getMessage());
            }
            
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Reserva creada exitosamente',
                'idReserva' => $idReserva
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear la reserva']);
        }
        
    } elseif ($metodo === 'GET') {
        // Obtener reservas del usuario o proveedor
        $idUsuario = $_SESSION['IdUsuario'];
        
        // Verificar si se solicitan las reservas como proveedor
        if (isset($_GET['tipo']) && $_GET['tipo'] === 'proveedor') {
            require_once __DIR__ . '/../Models/proveedor.php';
            
            $proveedor = proveedor::obtenerPorIdUsuario($idUsuario);
            if (!$proveedor) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No eres un proveedor']);
                exit;
            }
            
            $reservas = Reserva::obtenerPorProveedor($proveedor->getIdUsuario());
            echo json_encode(['success' => true, 'reservas' => $reservas]);
            
        } else {
            // Reservas como cliente (ya viene con los datos de disponibilidad)
            $reservas = Reserva::obtenerPorUsuario($idUsuario);
            echo json_encode(['success' => true, 'reservas' => $reservas]);
        }
        
    } elseif ($metodo === 'PUT') {
        // Actualizar estado de una reserva (para proveedores y clientes)
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['idReserva']) || !isset($data['nuevoEstado'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
            exit;
        }
        
        $idReserva = intval($data['idReserva']);
        $nuevoEstado = $data['nuevoEstado'];
        
        // Validar estados permitidos
        $estadosPermitidos = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
        if (!in_array($nuevoEstado, $estadosPermitidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            exit;
        }
        
        // Obtener información de la reserva antes de actualizar
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();
        $stmt = $conn->prepare("SELECT IdDisponibilidad, Estado FROM Reserva WHERE IdReserva = ?");
        $stmt->bind_param('i', $idReserva);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservaActual = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        
        if (!$reservaActual) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
            exit;
        }
        
        // Actualizar el estado de la reserva
        if (Reserva::actualizarEstado($idReserva, $nuevoEstado)) {
            // Manejar el estado de la disponibilidad según el cambio
            $idDisponibilidad = $reservaActual['IdDisponibilidad'];
            
            // Si se cancela, liberar la disponibilidad
            if ($nuevoEstado === 'cancelada' && in_array($reservaActual['Estado'], ['pendiente', 'confirmada'])) {
                try {
                    Disponibilidad::actualizarEstado($idDisponibilidad, 'disponible');
                    error_log("Disponibilidad {$idDisponibilidad} liberada por cancelación");
                } catch (Exception $e) {
                    error_log("Error al liberar disponibilidad: " . $e->getMessage());
                }
            }
            
            // Si se finaliza, la disponibilidad queda como ocupado (completado)
            // No se hace nada adicional, ya está ocupado
            
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    error_log("Error en reservaController: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>