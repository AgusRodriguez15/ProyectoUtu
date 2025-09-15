<?php
class dato {
	public $IdUsuario;
	public $Tipo;
	public $Contacto;

	public function __construct($IdUsuario, $Tipo, $Contacto) {
		$this->IdUsuario = $IdUsuario;
		$this->Tipo = $Tipo;
		$this->Contacto = $Contacto;
	}

	public function getIdUsuario() { return $this->IdUsuario; }

	public function getTipo() { return $this->Tipo; }
	public function setTipo($Tipo) { $this->Tipo = $Tipo; }

	public function getContacto() { return $this->Contacto; }
	public function setContacto($Contacto) { $this->Contacto = $Contacto; }
}