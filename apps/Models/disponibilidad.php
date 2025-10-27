	<?php
	require_once __DIR__ . '/ConexionDB.php';

	class disponibilidad {
		public $IdDisponibilidad;
		public $IdServicio;
		public $FechaInicio;
		public $FechaFin;
		public $estado;

		public function __construct($IdDisponibilidad, $IdServicio, $FechaInicio, $FechaFin, $estado) {
			$this->IdDisponibilidad = $IdDisponibilidad;
			$this->IdServicio = $IdServicio;
			$this->FechaInicio = $FechaInicio;
			$this->FechaFin = $FechaFin;
			$this->estado = $estado;
		}

		public function getIdDisponibilidad() { return $this->IdDisponibilidad; }

		public function getIdServicio() { return $this->IdServicio; }
		public function setIdServicio($IdServicio) { $this->IdServicio = $IdServicio; }

		public function getFechaInicio() { return $this->FechaInicio; }
		public function setFechaInicio($FechaInicio) { $this->FechaInicio = $FechaInicio; }

		public function getFechaFin() { return $this->FechaFin; }
		public function setFechaFin($FechaFin) { $this->FechaFin = $FechaFin; }

		public function getEstado() { return $this->estado; }
		public function setEstado($estado) { $this->estado = $estado; }

		/**
		 * Crea una disponibilidad para un servicio
		 * @param int $idServicio ID del servicio
		 * @param array $datosDisponibilidad Datos de la disponibilidad (fechaInicio, fechaFin, estado)
		 * @return int|false ID de la disponibilidad creada o false en caso de error
		 */
		public static function crearParaServicio($idServicio, $datosDisponibilidad) {
			$conexionDB = new ConexionDB();
			$conn = $conexionDB->getConexion();
			
			try {
				error_log("crearParaServicio - Servicio: {$idServicio}, Datos: " . json_encode($datosDisponibilidad));
				
				$fechaInicio = $datosDisponibilidad['fechaInicio'];
				$fechaFin = $datosDisponibilidad['fechaFin'];
				$estado = isset($datosDisponibilidad['estado']) ? $datosDisponibilidad['estado'] : 'disponible';
				
				// Validar que las fechas sean válidas
				if (empty($fechaInicio) || empty($fechaFin)) {
					error_log("ERROR: Fechas de inicio o fin vacías");
					return false;
				}
				
				// Insertar disponibilidad
				$stmt = $conn->prepare("INSERT INTO Disponibilidad (IdServicio, FechaInicio, FechaFin, Estado) VALUES (?, ?, ?, ?)");
				$stmt->bind_param('isss', $idServicio, $fechaInicio, $fechaFin, $estado);
				
				if (!$stmt->execute()) {
					throw new Exception("Error al crear disponibilidad: " . $stmt->error);
				}
				
				$idDisponibilidad = $conn->insert_id;
				error_log("Disponibilidad creada con ID: {$idDisponibilidad}");
				
				$stmt->close();

				
				return $idDisponibilidad;
				
			} catch (Exception $e) {
				$conn->close();
				error_log("Error en crearParaServicio: " . $e->getMessage());
				return false;
			}
		}

		/**
		 * Obtiene todas las disponibilidades de un servicio
		 * @param int $idServicio ID del servicio
		 * @return array Array de disponibilidades
		 */
		public static function obtenerPorServicio($idServicio) {
			$conexionDB = new ConexionDB();
			$conn = $conexionDB->getConexion();
			$disponibilidades = [];
			
			try {
				$stmt = $conn->prepare("SELECT * FROM Disponibilidad WHERE IdServicio = ? ORDER BY FechaInicio ASC");
				$stmt->bind_param('i', $idServicio);
				$stmt->execute();
				$result = $stmt->get_result();
				
				while ($row = $result->fetch_assoc()) {
					$disponibilidades[] = new disponibilidad(
						$row['IdDisponibilidad'],
						$row['IdServicio'],
						$row['FechaInicio'],
						$row['FechaFin'],
						$row['Estado']
					);
				}
				
				$stmt->close();
				$conn->close();
				
			} catch (Exception $e) {
				error_log("Error al obtener disponibilidades: " . $e->getMessage());
				$conn->close();
			}
			
			return $disponibilidades;
		}

		/**
		 * Actualiza el estado de una disponibilidad
		 * @param int $idDisponibilidad ID de la disponibilidad
		 * @param string $nuevoEstado Nuevo estado ('disponible', 'ocupado', 'cancelado')
		 * @return bool true si se actualizó correctamente, false en caso contrario
		 */
		public static function actualizarEstado($idDisponibilidad, $nuevoEstado) {
			$conexionDB = new ConexionDB();
			$conn = $conexionDB->getConexion();
			
			try {
				$stmt = $conn->prepare("UPDATE Disponibilidad SET Estado = ? WHERE IdDisponibilidad = ?");
				$stmt->bind_param('si', $nuevoEstado, $idDisponibilidad);
				
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
