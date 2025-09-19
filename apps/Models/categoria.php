<?php
require_once __DIR__ . '/ConexionDB.php';

class Categoria
{
    private $conexion;

    public function __construct()
    {
        $db = new ClaseConexion(); // según tu código original
        $this->conexion = $db->getConexion();
    }

    /**
     * Devuelve todas las IdCategoria que coincidan con un nombre exacto
     */
    public function obtenerIdsPorNombre(string $nombre): array
{
    // Usamos LIKE para buscar coincidencias parciales
    $sql = "SELECT IdCategoria FROM Categoria WHERE Nombre LIKE ?";
    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error en prepare(): " . $this->conexion->error);
    }

    // 1. Agregamos comodines % antes y después del nombre de la categoría.
    // Esto asegura que la búsqueda encuentre el término en cualquier parte del nombre.
    $nombreBusqueda = "%" . $nombre . "%";

    // 2. Vinculamos la variable de búsqueda.
    $stmt->bind_param("s", $nombreBusqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['IdCategoria'];
    }

    return $ids;
}

    /**
     * Devuelve todas las categorías
     */
    public function obtenerTodas(): array
    {
        $sql = "SELECT IdCategoria, Nombre, Descripcion FROM Categoria ORDER BY Nombre ASC";
        $result = $this->conexion->query($sql);

        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }

        return $categorias;
    }
}
?>