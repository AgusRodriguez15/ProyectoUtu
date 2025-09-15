<?php
class Reserva {
    public $IdReserva;
    public $FechaHoraInicio;
    public $FechaHoraFin;
    public $Estado;
    public $Observacion;
    public $IdUsuario;
    public $IdServicio;

    public function __construct($IdReserva, $FechaHoraInicio, $FechaHoraFin, $Estado, $Observacion, $IdUsuario, $IdServicio) {
        $this->IdReserva = $IdReserva;
        $this->FechaHoraInicio = $FechaHoraInicio;
        $this->FechaHoraFin = $FechaHoraFin;
        $this->Estado = $Estado;
        $this->Observacion = $Observacion;
        $this->IdUsuario = $IdUsuario;
        $this->IdServicio = $IdServicio;
    }

    public function getIdReserva() {
        return $this->IdReserva;
    }

    public function getFechaHoraInicio() {
        return $this->FechaHoraInicio;
    }
    public function setFechaHoraInicio($FechaHoraInicio) {
        $this->FechaHoraInicio = $FechaHoraInicio;
    }

    public function getFechaHoraFin() {
        return $this->FechaHoraFin;
    }
    public function setFechaHoraFin($FechaHoraFin) {
        $this->FechaHoraFin = $FechaHoraFin;
    }

    public function getEstado() {
        return $this->Estado;
    }
    public function setEstado($Estado) {
        $this->Estado = $Estado;
    }

    public function getObservacion() {
        return $this->Observacion;
    }
    public function setObservacion($Observacion) {
        $this->Observacion = $Observacion;
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
