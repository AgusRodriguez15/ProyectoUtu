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
}