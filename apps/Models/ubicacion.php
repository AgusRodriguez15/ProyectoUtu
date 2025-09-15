<?php
class ubicacion {
	public $IdUsuario;
	public $Pais;
	public $Ciudad;
	public $Calle;
	public $Numero;

	public function __construct($IdUsuario, $Pais, $Ciudad, $Calle, $Numero) {
		$this->IdUsuario = $IdUsuario;
		$this->Pais = $Pais;
		$this->Ciudad = $Ciudad;
		$this->Calle = $Calle;
		$this->Numero = $Numero;
	}

	public function getIdUsuario() { return $this->IdUsuario; }

	public function getPais() { return $this->Pais; }
	public function setPais($Pais) { $this->Pais = $Pais; }

	public function getCiudad() { return $this->Ciudad; }
	public function setCiudad($Ciudad) { $this->Ciudad = $Ciudad; }

	public function getCalle() { return $this->Calle; }
	public function setCalle($Calle) { $this->Calle = $Calle; }

	public function getNumero() { return $this->Numero; }
	public function setNumero($Numero) { $this->Numero = $Numero; }
}
