<?php
session_start();
require_once __DIR__ . '/../Models/Servicio.php';
require_once __DIR__ . '/../Models/Categoria.php';

class ServicioController
{
    public function index()
    {
        $rol = $_SESSION['RolUsuario'] ?? 'Cliente';

        switch ($rol) {
            case 'Administrador':
                include __DIR__ . '/../Views/PANTALLA_ADMIN.php';
                break;
            case 'Proveedor':
                include __DIR__ . '/../Views/PANTALLA_PUBLICA.php';
                break;
            case 'Cliente':
            default:
                // Para Cliente, manejamos búsqueda y carga inicial
                $termino = $_POST['q'] ?? null;
                $termino = trim($termino);

                if ($termino) {
                    $servicios = Servicio::buscarPorCategoriaYTitulo($termino);
                } else {
                    $servicios = Servicio::obtenerTodosDisponibles();
                }


                include __DIR__ . '/../Views/PANTALLA_CONTRATAR.php';
                break;
        }
    }
}

// Ejecutar si se llama directo
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $controller = new ServicioController();
    $controller->index();
}
