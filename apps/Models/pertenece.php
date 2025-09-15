<?php
class Pertenece {
    public $IdServicio;
    public $IdCategoria;

    public function __construct($IdServicio, $IdCategoria) {
        $this->IdServicio = $IdServicio;
        $this->IdCategoria = $IdCategoria;
    }

    public function getIdServicio() {
        return $this->IdServicio;
    }

    public function getIdCategoria() {
        return $this->IdCategoria;
    }
}
?>
