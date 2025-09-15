	public function getIdServicio() { return $this->IdServicio; }

	public function getIdCategoria() { return $this->IdCategoria; }
<?php
class pertenece {
	public $IdServicio;
	public $IdCategoria;

	public function __construct($IdServicio, $IdCategoria) {
		$this->IdServicio = $IdServicio;
		$this->IdCategoria = $IdCategoria;
	}
}
