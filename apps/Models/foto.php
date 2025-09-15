	public function getIdServicio() { return $this->IdServicio; }

	public function getFoto() { return $this->Foto; }
	public function setFoto($Foto) { $this->Foto = $Foto; }
<?php
class foto {
	public $IdServicio;
	public $Foto;

	public function __construct($IdServicio, $Foto) {
		$this->IdServicio = $IdServicio;
		$this->Foto = $Foto;
	}
}
