	public function getIdServicio() { return $this->IdServicio; }

	public function getTitulo() { return $this->Titulo; }
	public function setTitulo($Titulo) { $this->Titulo = $Titulo; }

	public function getDescripcion() { return $this->Descripcion; }
	public function setDescripcion($Descripcion) { $this->Descripcion = $Descripcion; }

	public function getPrecio() { return $this->Precio; }
	public function setPrecio($Precio) { $this->Precio = $Precio; }

	public function getDivisa() { return $this->divisa; }
	public function setDivisa($divisa) { $this->divisa = $divisa; }

	public function getFechaPublicacion() { return $this->FechaPublicacion; }
	public function setFechaPublicacion($FechaPublicacion) { $this->FechaPublicacion = $FechaPublicacion; }

	public function getIdUsuario() { return $this->IdUsuario; }
	public function setIdUsuario($IdUsuario) { $this->IdUsuario = $IdUsuario; }
<?php
class servicio {
	public $IdServicio;
	public $Titulo;
	public $Descripcion;
	public $Precio;
	public $divisa;
	public $FechaPublicacion;
	public $IdUsuario;

	public function __construct($IdServicio, $Titulo, $Descripcion, $Precio, $divisa, $FechaPublicacion, $IdUsuario) {
		$this->IdServicio = $IdServicio;
		$this->Titulo = $Titulo;
		$this->Descripcion = $Descripcion;
		$this->Precio = $Precio;
		$this->divisa = $divisa;
		$this->FechaPublicacion = $FechaPublicacion;
		$this->IdUsuario = $IdUsuario;
	}
}
