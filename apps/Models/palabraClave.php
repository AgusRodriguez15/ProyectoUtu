<?php
class PalabraClave {
    public $Palabra;
    public $IdServicio;

    public function __construct($Palabra, $IdServicio) {
        $this->Palabra = $Palabra;
        $this->IdServicio = $IdServicio;
    }

    public function getPalabra() {
        return $this->Palabra;
    }

    public function getIdServicio() {
        return $this->IdServicio;
    }
}
?>
