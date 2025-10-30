<?php
class accion
{
	public $IdAccion;
	public $tipo;
	public $descripcion;
	public $fecha;
	public $IdUsuario;
	public $IdUsuarioAdministrador;

	public function __construct($IdAccion, $tipo, $descripcion, $fecha, $IdUsuario, $IdUsuarioAdministrador) {
		$this->IdAccion = $IdAccion;
		$this->tipo = $tipo;
		$this->descripcion = $descripcion;
		$this->fecha = $fecha;
		$this->IdUsuario = $IdUsuario;
		$this->IdUsuarioAdministrador = $IdUsuarioAdministrador;
	}

	public function getIdAccion() { return $this->IdAccion; }

	public function getTipo() { return $this->tipo; }
	public function setTipo($tipo) { $this->tipo = $tipo; }

	public function getDescripcion() { return $this->descripcion; }
	public function setDescripcion($descripcion) { $this->descripcion = $descripcion; }

	public function getFecha() { return $this->fecha; }
	public function setFecha($fecha) { $this->fecha = $fecha; }

	public function getIdUsuario() { return $this->IdUsuario; }
	public function setIdUsuario($IdUsuario) { $this->IdUsuario = $IdUsuario; }

	public function getIdUsuarioAdministrador() { return $this->IdUsuarioAdministrador; }
	public function setIdUsuarioAdministrador($IdUsuarioAdministrador) { $this->IdUsuarioAdministrador = $IdUsuarioAdministrador; }

	/**
	 * Crear una nueva fila en la tabla Accion.
	 * @param string $tipo
	 * @param string|null $descripcion
	 * @param int|null $idUsuario
	 * @param int|null $idUsuarioAdministrador
	 * @return int|false Devuelve el IdAccion insertado o false en error
	 */
	public static function crear($tipo, $descripcion = null, $idUsuario = null, $idUsuarioAdministrador = null) {
		require_once __DIR__ . '/ConexionDB.php';
		try {
			$db = new ConexionDB();
			$conn = $db->getConexion();
			$fecha = date('Y-m-d H:i:s');
			$idUsuarioInt = $idUsuario ? intval($idUsuario) : 0;
			$idAdminInt = $idUsuarioAdministrador ? intval($idUsuarioAdministrador) : 0;
			$stmt = $conn->prepare("INSERT INTO Accion (tipo, descripcion, fecha, IdUsuario, IdUsuarioAdministrador) VALUES (?, ?, ?, ?, ?)");
			if (!$stmt) return false;
			$stmt->bind_param('sssii', $tipo, $descripcion, $fecha, $idUsuarioInt, $idAdminInt);
			$ok = $stmt->execute();
			if (!$ok) { $stmt->close(); return false; }
			$insertId = $conn->insert_id;
			$stmt->close();
			return $insertId;
		} catch (Exception $e) {
			error_log('accion::crear error: ' . $e->getMessage());
			return false;
		}
	}
}
?>