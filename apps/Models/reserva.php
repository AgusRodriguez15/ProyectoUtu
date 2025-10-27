<?php
require_once __DIR__ . '/ConexionDB.php';

class Reserva {
    public $IdReserva;
    public $IdDisponibilidad;
    public $Estado;
    public $Observacion;
    public $IdUsuario;
    public $IdServicio;

    public function __construct($IdReserva, $IdDisponibilidad, $Estado, $Observacion, $IdUsuario, $IdServicio) {
        $this->IdReserva = $IdReserva;
        $this->IdDisponibilidad = $IdDisponibilidad;
        $this->Estado = $Estado;
        $this->Observacion = $Observacion;
        $this->IdUsuario = $IdUsuario;
        $this->IdServicio = $IdServicio;
    }

    public function getIdReserva() {
        return $this->IdReserva;
    }

    public function getIdDisponibilidad() {
        return $this->IdDisponibilidad;
    }
    public function setIdDisponibilidad($IdDisponibilidad) {
        $this->IdDisponibilidad = $IdDisponibilidad;
    }

    public function getEstado() {
        return $this->Estado;
    }
    public function setEstado($Estado) {
        $this->Estado = $Estado;
    }

    public function getObservacion() {
        return $this->Observacion;
    }
    public function setObservacion($Observacion) {
        $this->Observacion = $Observacion;
    }

    public function getIdUsuario() {
        return $this->IdUsuario;
    }
    public function setIdUsuario($IdUsuario) {
        $this->IdUsuario = $IdUsuario;
    }

    public function getIdServicio() {
        return $this->IdServicio;
    }
    public function setIdServicio($IdServicio) {
        $this->IdServicio = $IdServicio;
    }

    /**
     * Crea una nueva reserva
     * @param array $datos Datos de la reserva (idDisponibilidad, estado, observacion, idUsuario, idServicio)
     * @return int|false ID de la reserva creada o false en caso de error
     */
    public static function crear($datos) {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();
        
        try {
            error_log("Creando reserva: " . json_encode($datos));
            
            $stmt = $conn->prepare("INSERT INTO Reserva (IdDisponibilidad, Estado, Observacion, IdUsuario, IdServicio) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('issii', 
                $datos['idDisponibilidad'],
                $datos['estado'],
                $datos['observacion'],
                $datos['idUsuario'],
                $datos['idServicio']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear reserva: " . $stmt->error);
            }
            
            $idReserva = $conn->insert_id;
            error_log("Reserva creada con ID: {$idReserva}");
            
            $stmt->close();

            
            return $idReserva;
            
        } catch (Exception $e) {
            $conn->close();
            error_log("Error en crear reserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las reservas de un usuario con información de disponibilidad
     * @param int $idUsuario ID del usuario
     * @return array Array de reservas con fechas desde Disponibilidad
     */
    public static function obtenerPorUsuario($idUsuario) {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();
        $reservas = [];
        
        try {
            $sql = "SELECT r.*, d.FechaInicio, d.FechaFin, s.Nombre as NombreServicio
                    FROM Reserva r
                    INNER JOIN Disponibilidad d ON r.IdDisponibilidad = d.IdDisponibilidad
                    INNER JOIN Servicio s ON r.IdServicio = s.IdServicio
                    WHERE r.IdUsuario = ?
                    ORDER BY d.FechaInicio DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idUsuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $reservas[] = [
                    'idReserva' => $row['IdReserva'],
                    'idDisponibilidad' => $row['IdDisponibilidad'],
                    'fechaInicio' => $row['FechaInicio'],
                    'fechaFin' => $row['FechaFin'],
                    'estado' => $row['Estado'],
                    'observacion' => $row['Observacion'],
                    'idUsuario' => $row['IdUsuario'],
                    'idServicio' => $row['IdServicio'],
                    'nombreServicio' => $row['NombreServicio']
                ];
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            error_log("Error al obtener reservas: " . $e->getMessage());
            $conn->close();
        }
        
        return $reservas;
    }

    /**
     * Obtiene todas las reservas de los servicios de un proveedor
     * @param int $idProveedor ID del proveedor
     * @return array Array de reservas con información del servicio, cliente y disponibilidad
     */
    public static function obtenerPorProveedor($idProveedor) {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();
        $reservas = [];
        
        try {
            $sql = "SELECT r.*, d.FechaInicio, d.FechaFin, s.Nombre as NombreServicio, u.Nombre as NombreCliente, u.Apellido as ApellidoCliente
                    FROM Reserva r
                    INNER JOIN Disponibilidad d ON r.IdDisponibilidad = d.IdDisponibilidad
                    INNER JOIN Servicio s ON r.IdServicio = s.IdServicio
                    INNER JOIN Usuario u ON r.IdUsuario = u.IdUsuario
                    WHERE s.IdProveedor = ?
                    ORDER BY d.FechaInicio DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idProveedor);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $reservas[] = [
                    'idReserva' => $row['IdReserva'],
                    'idDisponibilidad' => $row['IdDisponibilidad'],
                    'fechaInicio' => $row['FechaInicio'],
                    'fechaFin' => $row['FechaFin'],
                    'estado' => $row['Estado'],
                    'observacion' => $row['Observacion'],
                    'idUsuario' => $row['IdUsuario'],
                    'idServicio' => $row['IdServicio'],
                    'nombreServicio' => $row['NombreServicio'],
                    'nombreCliente' => $row['NombreCliente'] . ' ' . $row['ApellidoCliente']
                ];
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            error_log("Error al obtener reservas del proveedor: " . $e->getMessage());
            $conn->close();
        }
        
        return $reservas;
    }

    /**
     * Actualiza el estado de una reserva
     * @param int $idReserva ID de la reserva
     * @param string $nuevoEstado Nuevo estado
     * @return bool true si se actualizó correctamente
     */
    public static function actualizarEstado($idReserva, $nuevoEstado) {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();
        
        try {
            $stmt = $conn->prepare("UPDATE Reserva SET Estado = ? WHERE IdReserva = ?");
            $stmt->bind_param('si', $nuevoEstado, $idReserva);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar estado: " . $stmt->error);
            }
            
            $exito = $stmt->affected_rows > 0;
            
            $stmt->close();
            $conn->close();
            
            return $exito;
            
        } catch (Exception $e) {
            error_log("Error en actualizarEstado: " . $e->getMessage());
            $conn->close();
            return false;
        }
    }
}
?>
