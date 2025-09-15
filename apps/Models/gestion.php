	public function getIdGestion() { return $this->IdGestion; }

	public function getTipo() { return $this->tipo; }
	public function setTipo($tipo) { $this->tipo = $tipo; }

	public function getDescripcion() { return $this->descripcion; }
	public function setDescripcion($descripcion) { $this->descripcion = $descripcion; }

	public function getFecha() { return $this->fecha; }
	public function setFecha($fecha) { $this->fecha = $fecha; }

	public function getIdAdministrador() { return $this->IdAdministrador; }
	public function setIdAdministrador($IdAdministrador) { $this->IdAdministrador = $IdAdministrador; }

	public function getIdServicio() { return $this->IdServicio; }
	public function setIdServicio($IdServicio) { $this->IdServicio = $IdServicio; }
<?php
class gestion {
	public $IdGestion;
	public $tipo;
	public $descripcion;
	public $fecha;
	public $IdAdministrador;
	public $IdServicio;

	public function __construct($IdGestion, $tipo, $descripcion, $fecha, $IdAdministrador, $IdServicio) {
		$this->IdGestion = $IdGestion;
		$this->tipo = $tipo;
		$this->descripcion = $descripcion;
		$this->fecha = $fecha;
		$this->IdAdministrador = $IdAdministrador;
		$this->IdServicio = $IdServicio;
	}
}
