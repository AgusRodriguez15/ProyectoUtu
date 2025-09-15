<?php
class habilidad {
	public $IdUsuario;
	public $Habilidad;

	public function __construct($IdUsuario, $Habilidad) {
		$this->IdUsuario = $IdUsuario;
		$this->Habilidad = $Habilidad;
	}

	public function getIdUsuario() { return $this->IdUsuario; }

	public function getHabilidad() { return $this->Habilidad; }
	public function setHabilidad($Habilidad) { $this->Habilidad = $Habilidad; }
}
