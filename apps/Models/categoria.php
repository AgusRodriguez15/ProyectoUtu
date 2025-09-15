	public function getIdCategoria() { return $this->IdCategoria; }

	public function getNombre() { return $this->Nombre; }
	public function setNombre($Nombre) { $this->Nombre = $Nombre; }

	public function getDescripcion() { return $this->Descripcion; }
	public function setDescripcion($Descripcion) { $this->Descripcion = $Descripcion; }
<?php
class categoria {
	public $IdCategoria;
	public $Nombre;
	public $Descripcion;

	public function __construct($IdCategoria, $Nombre, $Descripcion) {
		$this->IdCategoria = $IdCategoria;
		$this->Nombre = $Nombre;
		$this->Descripcion = $Descripcion;
	}
}
