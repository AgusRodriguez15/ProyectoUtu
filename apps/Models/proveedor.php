<?php
require_once __DIR__ . '/ConexionDB.php';

class proveedor {
	private $IdUsuario;
	private $aniosExperiencia;

	public function __construct($IdUsuario, $aniosExperiencia) {
		$this->IdUsuario = $IdUsuario;
		$this->aniosExperiencia = $aniosExperiencia;
	}

	public function getIdUsuario() { return $this->IdUsuario; }
	public function getAniosExperiencia() { return $this->aniosExperiencia; }
	public function setAniosExperiencia($aniosExperiencia) { $this->aniosExperiencia = $aniosExperiencia; }

	// Obtener proveedor por IdUsuario
	public static function obtenerPorIdUsuario(int $idUsuario): ?proveedor {
		$conexionDB = new ConexionDB();
		$conn = $conexionDB->getConexion();

		$stmt = $conn->prepare("SELECT * FROM Proveedor WHERE IdUsuario = ?");
		$stmt->bind_param('i', $idUsuario);
		$stmt->execute();
		$resultado = $stmt->get_result();
		$fila = $resultado->fetch_assoc();

		$stmt->close();


		if ($fila) {
			return new proveedor(
				$fila['IdUsuario'],
				$fila['AniosExperiencia']
			);
		}
		return null;
	}
}
