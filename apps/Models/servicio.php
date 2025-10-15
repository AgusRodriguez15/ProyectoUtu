<?php
require_once "ConexionDB.php";
require_once "Foto.php";
require_once "Categoria.php";

class Servicio
{
    public $IdServicio;
    public $Nombre;
    public $Descripcion;
    public $FechaPublicacion;
    public $Estado;
    public $IdProveedor;
    public $Fotos = []; // Array de URLs de fotos

    private static $conexion;

    public function __construct($idServicio = null, $nombre = null, $descripcion = null, $fechaPublicacion = null, $estado = null, $idProveedor = null)
    {
        $db = new ClaseConexion();
        self::$conexion = $db->getConexion();
        
        // Asignar los parámetros a las propiedades si se proporcionan
        $this->IdServicio = $idServicio;
        $this->Nombre = $nombre;
        $this->Descripcion = $descripcion;
        $this->FechaPublicacion = $fechaPublicacion;
        $this->Estado = $estado;
        $this->IdProveedor = $idProveedor;
    }

    /**
     * Crea un nuevo servicio en la base de datos
     * @param array $datos Datos del servicio (nombre, descripcion, idProveedor)
     * @return int ID del servicio creado
     * @throws Exception si hay un error en la base de datos
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO Servicio (Nombre, Descripcion, IdProveedor, FechaPublicacion, Estado) 
                VALUES (?, ?, ?, NOW(), 'DISPONIBLE')";
        
        $stmt = self::$conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . self::$conexion->error);
        }

        $stmt->bind_param("ssi", 
            $datos['nombre'],
            $datos['descripcion'],
            $datos['idProveedor']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al crear el servicio: " . $stmt->error);
        }

        return $stmt->insert_id;
    }

    /**
     * Guarda los cambios de un servicio existente en la base de datos
     * @return bool true si se guardó correctamente
     * @throws Exception si hay un error en la base de datos
     */
    public function guardar(): bool
    {
        if (!isset($this->IdServicio)) {
            throw new Exception("No se puede guardar un servicio sin ID");
        }

        $sql = "UPDATE Servicio SET 
                Nombre = ?, 
                Descripcion = ?, 
                Estado = ?,
                FechaPublicacion = ?
                WHERE IdServicio = ?";
        
        $stmt = self::$conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . self::$conexion->error);
        }

        $stmt->bind_param("ssssi", 
            $this->Nombre,
            $this->Descripcion,
            $this->Estado,
            $this->FechaPublicacion,
            $this->IdServicio
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el servicio: " . $stmt->error);
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

    /**
     * Obtiene un servicio por su ID
     * @param int $id ID del servicio a obtener
     * @return Servicio|null El servicio encontrado o null si no existe
     * @throws Exception si hay un error en la base de datos
     */
    public static function obtenerPorId(int $id): ?Servicio
    {
        self::conectar();
        
        $sql = "SELECT * FROM Servicio WHERE IdServicio = ?";
        $stmt = self::$conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . self::$conexion->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al obtener el servicio: " . $stmt->error);
        }
        
        $resultado = $stmt->get_result();
        if ($fila = $resultado->fetch_assoc()) {
            $servicio = new Servicio();
            $servicio->IdServicio = $fila['IdServicio'];
            $servicio->Nombre = $fila['Nombre'];
            $servicio->Descripcion = $fila['Descripcion'];
            $servicio->FechaPublicacion = $fila['FechaPublicacion'];
            $servicio->Estado = $fila['Estado'];
            $servicio->IdProveedor = $fila['IdProveedor'];
            $servicio->Fotos = Foto::obtenerPorServicio($servicio);
            return $servicio;
        }
        
        return null;
    }

    // Obtener todos los servicios disponibles
    public static function obtenerTodosDisponibles()
    {
        self::conectar();
        $sql = "SELECT * FROM Servicio WHERE Estado = 'DISPONIBLE' ORDER BY FechaPublicacion DESC";
        $resultado = self::$conexion->query($sql);
        if (!$resultado) {
            die("Error en la consulta de servicios: " . self::$conexion->error);
        }

        $servicios = [];
        while ($fila = $resultado->fetch_assoc()) {
            $servicio = new Servicio(
                $fila['IdServicio'],
                $fila['Nombre'],
                $fila['Descripcion'],
                $fila['FechaPublicacion'],
                $fila['Estado'],
                $fila['IdProveedor']
            );
            $servicio->Fotos = Foto::obtenerPorServicio($servicio);
            $servicios[] = $servicio;
        }
        return $servicios;
    }

    // Obtener foto aleatoria
    public function getFotoServicio()
    {
        // En la BD solo se guarda el nombre del archivo: 'servicio_123_abc.jpg'
        if (!empty($this->Fotos)) {
            // Si hay varias fotos, seleccionar una aleatoria
            $nombreFoto = $this->Fotos[array_rand($this->Fotos)];
            // Construir la ruta completa
            return '/proyecto/public/recursos/imagenes/servicios/' . $nombreFoto;
        }
        // SVG placeholder codificado para URL
        return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect width="400" height="300" fill="%23ccc"/%3E%3Ctext x="200" y="150" text-anchor="middle" fill="%23666" font-size="20"%3ESin Imagen%3C/text%3E%3C/svg%3E';
    }

    // Retorna todas las fotos
    public function getTodasFotos()
    {
        return $this->Fotos;
    }

    // Buscar por término y/o categoría (N:N)
    // En la clase Servicio.php

public static function buscarPorCategoriaYTitulo(?string $termino): array
{
    self::conectar();

    // Si el término de búsqueda está vacío, devuelve todos los servicios disponibles.
    if (empty($termino)) {
        return self::obtenerTodosDisponibles();
    }

    $sql = "SELECT DISTINCT s.*
            FROM Servicio s
            LEFT JOIN Pertenece p ON s.IdServicio = p.IdServicio
            LEFT JOIN Categoria c ON p.IdCategoria = c.IdCategoria
            WHERE s.Estado = 'DISPONIBLE'
            AND (LOWER(s.Nombre) LIKE ? 
            OR LOWER(s.Descripcion) LIKE ? 
            OR LOWER(c.Nombre) LIKE ?)
            ORDER BY s.FechaPublicacion DESC";

    $stmt = self::$conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare(): " . self::$conexion->error . "\nSQL: $sql");
    }

    $terminoBusqueda = "%" . strtolower($termino) . "%";

    $stmt->bind_param("sss", $terminoBusqueda, $terminoBusqueda, $terminoBusqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    $servicios = [];
    while ($fila = $result->fetch_assoc()) {
        $servicio = new Servicio(
            $fila['IdServicio'],
            $fila['Nombre'],
            $fila['Descripcion'],
            $fila['FechaPublicacion'],
            $fila['Estado'],
            $fila['IdProveedor']
        );
        $servicio->Fotos = Foto::obtenerPorServicio($servicio);
        $servicios[] = $servicio;
    }

    return $servicios;
}
    
}
