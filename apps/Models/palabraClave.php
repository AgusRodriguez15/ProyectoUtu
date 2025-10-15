<?php
require_once __DIR__ . '/ConexionDB.php';

class PalabraClave {
    private static $conexion;
    public $Palabra;
    public $IdServicio;

    public function __construct($Palabra = '', $IdServicio = null) {
        $db = new ClaseConexion();
        self::$conexion = $db->getConexion();
        $this->Palabra = $Palabra;
        $this->IdServicio = $IdServicio;
    }

    /**
     * Guarda las palabras clave asociadas a un servicio
     * @param int $idServicio ID del servicio
     * @param array $palabrasClave Array de strings con las palabras clave
     * @throws Exception si hay un error en la base de datos
     */
    public static function guardarPalabrasClaveServicio(int $idServicio, array $palabrasClave): void {
        $db = new ClaseConexion();
        $conexion = $db->getConexion();
        
        $conexion->begin_transaction();
        
        try {
            $sql = "INSERT INTO PalabraClave (IdServicio, Palabra) VALUES (?, ?)";
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
            
            foreach ($palabrasClave as $palabra) {
                $palabraLimpia = trim($palabra);
                if (!empty($palabraLimpia)) {
                    $stmt->bind_param("is", $idServicio, $palabraLimpia);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al guardar la palabra clave '$palabraLimpia': " . $stmt->error);
                    }
                }
            }
            
            $conexion->commit();
            
        } catch (Exception $e) {
            $conexion->rollback();
            throw new Exception("Error al guardar las palabras clave: " . $e->getMessage());
        }
    }

    public function getPalabra() {
        return $this->Palabra;
    }

    public function getIdServicio() {
        return $this->IdServicio;
    }

    /**
     * Obtiene una palabra clave por su ID
     * @param int $idPalabra ID de la palabra clave
     * @param int $idServicio ID del servicio
     * @return PalabraClave|null La palabra clave encontrada o null si no existe
     * @throws Exception si hay un error en la base de datos
     */
    public static function obtenerPorId(int $idPalabra, int $idServicio): ?PalabraClave
    {
        $db = new ClaseConexion();
        $conexion = $db->getConexion();
        
        $sql = "SELECT * FROM PalabraClave WHERE IdPalabraClave = ? AND IdServicio = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("ii", $idPalabra, $idServicio);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al obtener la palabra clave: " . $stmt->error);
        }
        
        $resultado = $stmt->get_result();
        if ($fila = $resultado->fetch_assoc()) {
            return new PalabraClave($fila['Palabra'], $fila['IdServicio']);
        }
        
        return null;
    }

    /**
     * Guarda los cambios de una palabra clave existente en la base de datos
     * @return bool true si se guardó correctamente
     * @throws Exception si hay un error en la base de datos
     */
    public function guardar(): bool
    {
        if (!isset($this->IdServicio) || empty($this->Palabra)) {
            throw new Exception("Faltan datos obligatorios de la palabra clave");
        }

        $db = new ClaseConexion();
        $conexion = $db->getConexion();

        $sql = "UPDATE PalabraClave SET Palabra = ? WHERE IdServicio = ? AND Palabra = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        $stmt->bind_param("sis", $this->Palabra, $this->IdServicio, $this->Palabra);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la palabra clave: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }
}
