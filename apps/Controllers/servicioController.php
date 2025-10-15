<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Models/Servicio.php';

try {
    // Capturar término de búsqueda
    $termino = isset($_POST['q']) ? trim($_POST['q']) : '';

    // Buscar servicios
    $servicios = Servicio::buscarPorCategoriaYTitulo($termino);

    // Armar respuesta para el front (solo datos necesarios)
    $respuesta = array_map(function ($s) {
        return [
            'id' => $s->IdServicio,
            'nombre' => $s->Nombre,
            'descripcion' => $s->Descripcion,
            'foto' => $s->getFotoServicio()
        ];
    }, $servicios);

    echo json_encode($respuesta);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
