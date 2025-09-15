<?php
class proveedor {
	public $IdUsuario;
	public $aniosExperiencia;

	public function __construct($IdUsuario, $aniosExperiencia) {
		$this->IdUsuario = $IdUsuario;
		$this->aniosExperiencia = $aniosExperiencia;
	}

	public function getIdUsuario() { return $this->IdUsuario; }

	public function getAniosExperiencia() { return $this->aniosExperiencia; }
	public function setAniosExperiencia($aniosExperiencia) { $this->aniosExperiencia = $aniosExperiencia; }
}
