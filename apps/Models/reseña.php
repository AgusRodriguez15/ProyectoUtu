<?php
class Resena {
    public $IdResena;
    public $Comentario;
    public $Puntuacion;
    public $Fecha;
    public $IdUsuario;
    public $IdServicio;

    public function __construct($IdResena, $Comentario, $Puntuacion, $Fecha, $IdUsuario, $IdServicio) {
        $this->IdResena = $IdResena;
        $this->Comentario = $Comentario;
        $this->Puntuacion = $Puntuacion;
        $this->Fecha = $Fecha;
        $this->IdUsuario = $IdUsuario;
        $this->IdServicio = $IdServicio;
    }

    public function getIdResena() {
        return $this->IdResena;
    }

    public function getComentario() {
        return $this->Comentario;
    }
    public function setComentario($Comentario) {
        $this->Comentario = $Comentario;
    }

    public function getPuntuacion() {
        return $this->Puntuacion;
    }
    public function setPuntuacion($Puntuacion) {
        $this->Puntuacion = $Puntuacion;
    }

    public function getFecha() {
        return $this->Fecha;
    }
    public function setFecha($Fecha) {
        $this->Fecha = $Fecha;
    }

    public function getIdUsuario() {
        return $this->IdUsuario;
    }
    public function setIdUsuario($IdUsuario) {
        $this->IdUsuario = $IdUsuario;
    }

    public function getIdServicio() {
        return $this->IdServicio;
    }
    public function setIdServicio($IdServicio) {
        $this->IdServicio = $IdServicio;
    }
}
?>
