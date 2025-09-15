<?php

// Incluimos la clase modelo 'Servicio' para poder usar sus métodos
require_once '../../apps/Models/servicio.php';

// Definimos el controlador como una clase, aunque por ahora solo contendrá un método estático
class ServicioController {
    
    // Método para obtener todos los servicios y pasarlos a la vista
    public static function obtenerServicios() {
        // Llamamos al método estático 'obtenerTodos' de la clase 'Servicio'
        // Esto recupera los datos de la base de datos
        $servicios = Servicio::obtenerTodos();
        
        // Retornamos la lista de servicios. La vista será la encargada de usar estos datos
        return $servicios;
    }
}

?>