
<?php
class ubicacionServicio {
	public $IdServicio;
	public $Pais;
	public $Ciudad;
	public $Calle;
	public $Numero;
	public function getIdServicio() { return $this->IdServicio; }

	public function getPais() { return $this->Pais; }
	public function setPais($Pais) { $this->Pais = $Pais; }

	public function getCiudad() { return $this->Ciudad; }
	public function setCiudad($Ciudad) { $this->Ciudad = $Ciudad; }

	public function getCalle() { return $this->Calle; }
	public function setCalle($Calle) { $this->Calle = $Calle; }

	public function getNumero() { return $this->Numero; }
	public function setNumero($Numero) { $this->Numero = $Numero; }
	public function __construct($IdServicio, $Pais, $Ciudad, $Calle, $Numero) {
		$this->IdServicio = $IdServicio;
		$this->Pais = $Pais;
		$this->Ciudad = $Ciudad;
		$this->Calle = $Calle;
		$this->Numero = $Numero;
	}
}
