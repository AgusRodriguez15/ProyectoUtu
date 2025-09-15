<?php
class cliente {
	public $IdUsuario;

	public function __construct($IdUsuario) {
		$this->IdUsuario = $IdUsuario;
	}

	public function getIdUsuario() { return $this->IdUsuario; }
}
