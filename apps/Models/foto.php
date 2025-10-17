<?php
require_once __DIR__ . '/ConexionDB.php';

class Foto
{
    public $IdServicio;
    public $Foto;
    private static $conexion;

    public function __construct($IdServicio = null, $Foto = null)
    {
        $db = new ClaseConexion();
        self::$conexion = $db->getConexion();
        $this->IdServicio = $IdServicio;
        $this->Foto = $Foto;
    }

    /**
     * Guarda una foto para un servicio
     * @param int $idServicio ID del servicio
     * @param array $foto Array con los datos del archivo de $_FILES (debe tener 'tmp_name' y 'name')
     * @return string Ruta relativa donde se guardó la foto
     * @throws Exception si hay un error al guardar la foto
     */
    public static function guardarFoto(int $idServicio, array $foto): string
    {
        $db = new ClaseConexion();
        $conexion = $db->getConexion();
        
        // Definir directorio absoluto desde el modelo
        // __DIR__ está en c:\xampp\htdocs\proyecto\apps\Models
        // Necesitamos ir a c:\xampp\htdocs\proyecto\public\recursos\imagenes\servicios\
        $directorioBase = __DIR__ . '/../../public/recursos/imagenes/servicios/';
        
        // Crear directorio si no existe
        if (!file_exists($directorioBase)) {
            if (!mkdir($directorioBase, 0777, true)) {
                throw new Exception('Error al crear el directorio de fotos');
            }
        }
        
        // Validar que el directorio sea escribible
        if (!is_writable($directorioBase)) {
            throw new Exception('El directorio de fotos no tiene permisos de escritura');
        }
        
        // Generar nombre único para el archivo
        $extension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        $nombreArchivo = uniqid("servicio_{$idServicio}_") . '.' . $extension;
        $rutaCompleta = $directorioBase . $nombreArchivo;
        
        // Mover el archivo desde la ubicación temporal
        if (!move_uploaded_file($foto['tmp_name'], $rutaCompleta)) {
            throw new Exception("Error al mover el archivo subido. Directorio: {$directorioBase}");
        }
        
        // Guardar solo el nombre del archivo en la base de datos (sin ruta)
        $sql = "INSERT INTO Foto (IdServicio, Foto) VALUES (?, ?)";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            unlink($rutaCompleta); // Eliminar archivo si hay error
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("is", $idServicio, $nombreArchivo);
        
        if (!$stmt->execute()) {
            unlink($rutaCompleta); // Eliminar archivo si hay error
            throw new Exception("Error al guardar la foto en la base de datos: " . $stmt->error);
        }
        
        return $nombreArchivo;
    }

    public function getIdServicio()
    {
        return $this->IdServicio;
    }

    public function getFoto()
    {
        return $this->Foto;
    }

    public function setFoto($Foto)
    {
        $this->Foto = $Foto;
    }

    /**
     * Obtiene una foto por su ID
     * @param int $idFoto ID de la foto a obtener
     * @return Foto|null La foto encontrada o null si no existe
     * @throws Exception si hay un error en la base de datos
     */
    public static function obtenerPorId(int $idFoto): ?Foto
    {
        $db = new ClaseConexion();
        $conexion = $db->getConexion();
        
        $sql = "SELECT * FROM Foto WHERE IdFoto = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("i", $idFoto);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al obtener la foto: " . $stmt->error);
        }
        
        $resultado = $stmt->get_result();
        if ($fila = $resultado->fetch_assoc()) {
            return new Foto($fila['IdServicio'], $fila['Foto']);
        }
        
        return null;
    }

    /**
     * Guarda los cambios de una foto existente en la base de datos
     * @return bool true si se guardó correctamente
     * @throws Exception si hay un error en la base de datos
     */
    public function guardar(): bool
    {
        if (!isset($this->IdServicio) || !isset($this->Foto)) {
            throw new Exception("Faltan datos obligatorios de la foto");
        }

        $db = new ClaseConexion();
        $conexion = $db->getConexion();

        $sql = "UPDATE Foto SET Foto = ? WHERE IdServicio = ? AND Foto = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        $stmt->bind_param("sis", $this->Foto, $this->IdServicio, $this->Foto);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la foto: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }

    public static function conectar()
    {
        if (!isset(self::$conexion)) {
            $db = new ClaseConexion();
            self::$conexion = $db->getConexion();
        }
    }

    public static function obtenerPorServicio($servicio)
    {
        self::conectar();
        $id = intval($servicio->IdServicio); // evita inyección
        $sql = "SELECT Foto FROM Foto WHERE IdServicio = $id";
        $resultado = self::$conexion->query($sql);

        if (!$resultado) {
            error_log("Error en la consulta de fotos: " . self::$conexion->error);
            return [];
        }

        $fotos = [];
        while ($fila = $resultado->fetch_assoc()) {
            // Devolver la ruta completa para usar en el frontend
            $fotos[] = '/proyecto/public/recursos/imagenes/servicios/' . $fila['Foto'];
        }

        return $fotos;
    }

    /**
     * Obtiene todas las fotos asociadas a un ID de servicio
     * @param int $idServicio ID del servicio
     * @return Foto[] Array de objetos Foto
     */
    public static function obtenerFotosPorIdServicio(int $idServicio): array
    {
        self::conectar();
        $id = intval($idServicio); // evita inyección SQL
        $sql = "SELECT IdServicio, Foto FROM Foto WHERE IdServicio = $id";
        $resultado = self::$conexion->query($sql);

        if (!$resultado) {
            die("Error en la consulta de fotos: " . self::$conexion->error);
        }

        $fotos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $fotos[] = new Foto($fila['IdServicio'], $fila['Foto']);
        }

        return $fotos;
    }

    /**
     * Crea una nueva foto en la base de datos
     * @param int $idServicio ID del servicio
     * @param string $nombreArchivo Nombre del archivo de foto
     * @return bool true si se creó correctamente
     * @throws Exception si hay un error en la base de datos
     */
    public static function crear(int $idServicio, string $nombreArchivo): bool
    {
        self::conectar();
        
        $sql = "INSERT INTO Foto (IdServicio, Foto) VALUES (?, ?)";
        $stmt = self::$conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . self::$conexion->error);
        }
        
        $stmt->bind_param("is", $idServicio, $nombreArchivo);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar la foto: " . $stmt->error);
        }
        
        return $stmt->affected_rows > 0;
    }

    /**
     * Elimina una foto por nombre de archivo
     * @param int $idServicio ID del servicio
     * @param string $nombreArchivo Nombre del archivo a eliminar
     * @return bool true si se eliminó correctamente
     * @throws Exception si hay un error en la base de datos
     */
    public static function eliminarPorNombre(int $idServicio, string $nombreArchivo): bool
    {
        self::conectar();
        
        // Eliminar archivo físico
        $rutaArchivo = __DIR__ . '/../../public/recursos/imagenes/servicios/' . $nombreArchivo;
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
        
        // Eliminar de la base de datos
        $sql = "DELETE FROM Foto WHERE IdServicio = ? AND Foto = ?";
        $stmt = self::$conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . self::$conexion->error);
        }
        
        $stmt->bind_param("is", $idServicio, $nombreArchivo);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar la foto: " . $stmt->error);
        }
        
        return $stmt->affected_rows > 0;
    }
}
