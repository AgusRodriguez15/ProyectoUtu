<?php
require_once __DIR__ . '/ConexionDB.php';

class Categoria
{
    private $conexion;
    private $idCategoria;
    private $nombre;
    private $descripcion;

    public function __construct()
    {
    $db = new ConexionDB(); // según tu código original
        $this->conexion = $db->getConexion();
    }

    /**
     * Obtiene todas las categorías disponibles
     * @return array Array asociativo con IdCategoria como clave y datos de la categoría como valor
     */
    public static function obtenerTodasCategorias(): array
    {
    $db = new ConexionDB();
        $conexion = $db->getConexion();
        
        $sql = "SELECT IdCategoria, Nombre, Descripcion FROM Categoria";
        $result = $conexion->query($sql);
        
        if (!$result) {
            throw new Exception("Error al obtener categorías: " . $conexion->error);
        }
        
        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[$row['IdCategoria']] = $row;
        }
        
        return $categorias;
    }

    /**
     * Asocia un servicio con múltiples categorías
     * @param int $idServicio ID del servicio
     * @param array $idsCategorias Array con los IDs de las categorías a asociar
     * @throws Exception si hay un error en la base de datos
     */
    public static function asociarCategoriasAServicio(int $idServicio, array $idsCategorias): void
    {
    $db = new ConexionDB();
        $conexion = $db->getConexion();
        
        // Comenzar transacción
        $conexion->begin_transaction();
        
        try {
            // Preparar la consulta
            $sql = "INSERT INTO pertenece (IdServicio, IdCategoria) VALUES (?, ?)";
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
            
            // Insertar cada categoría
            foreach ($idsCategorias as $idCategoria) {
                $stmt->bind_param("ii", $idServicio, $idCategoria);
                if (!$stmt->execute()) {
                    throw new Exception("Error al asociar la categoría $idCategoria: " . $stmt->error);
                }
            }
            
            // Confirmar transacción
            $conexion->commit();
            
        } catch (Exception $e) {
            // Revertir cambios si hay error
            $conexion->rollback();
            throw new Exception("Error al asociar categorías: " . $e->getMessage());
        }
    }

    /**
     * Devuelve todas las IdCategoria que coincidan con un nombre
     */
    public function obtenerIdsPorNombre(string $nombre): array
    {
        // Usamos LIKE para buscar coincidencias parciales
        $sql = "SELECT IdCategoria FROM Categoria WHERE Nombre LIKE ?";
        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error en prepare(): " . $this->conexion->error);
        }

        // Agregamos comodines % antes y después del nombre de la categoría
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

    /**
     * Crea una nueva categoría y devuelve el Id insertado
     * @param string $nombre
     * @param string $descripcion
     * @return int Id insertado
     * @throws Exception
     */
    public function crear(string $nombre, string $descripcion = ''): int
    {
        $sql = "INSERT INTO Categoria (Nombre, Descripcion) VALUES (?, ?)";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error en prepare(): ' . $this->conexion->error);
        }
        $stmt->bind_param('ss', $nombre, $descripcion);
        if (!$stmt->execute()) {
            throw new Exception('Error al insertar categoría: ' . $stmt->error);
        }
        $insertId = (int)$this->conexion->insert_id;
        $stmt->close();
        return $insertId;
    }

    /**
     * Elimina una categoría por Id (no elimina servicios)
     * @param int $idCategoria
     * @return bool
     * @throws Exception
     */
    public function eliminar(int $idCategoria): bool
    {
        // Usamos transacción por seguridad
        $this->conexion->begin_transaction();
        try {
            // Eliminar relaciones en pertenece (por compatibilidad si no hay cascade)
            $sqlPert = "DELETE FROM pertenece WHERE IdCategoria = ?";
            $stmt = $this->conexion->prepare($sqlPert);
            if ($stmt) {
                $stmt->bind_param('i', $idCategoria);
                $stmt->execute();
                $stmt->close();
            }

            // Eliminar la categoría
            $sql = "DELETE FROM Categoria WHERE IdCategoria = ?";
            $stmt2 = $this->conexion->prepare($sql);
            if (!$stmt2) {
                throw new Exception('Error en prepare(): ' . $this->conexion->error);
            }
            $stmt2->bind_param('i', $idCategoria);
            if (!$stmt2->execute()) {
                throw new Exception('Error al eliminar categoría: ' . $stmt2->error);
            }
            $stmt2->close();

            $this->conexion->commit();
            return true;
        } catch (Exception $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }
}
?>