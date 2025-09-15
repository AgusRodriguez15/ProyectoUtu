<?php
require_once "../Models/servicio.php"; // Ajustá la ruta según tu proyecto

class ServicioController {

    // Método para mostrar todos los servicios en la vista
    public function index() {
        // Obtener todos los servicios
        $servicios = Servicio::obtenerTodos();

        // Cargar la vista
        // Usamos include, pero podés usar tu sistema de layout
        include "../Views/PANTALLA_CONTRATAR.php";
    }
}

// Ejecutar el controlador si se llama directamente
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $controller = new ServicioController();
    $controller->index();
}
?>
