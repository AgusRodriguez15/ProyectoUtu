<?php
require_once '../../apps/Models/servicio.php';

class ServicioController {
    public static function obtenerServicios() {
        return Servicio::obtenerTodos();
    }
}
?>
