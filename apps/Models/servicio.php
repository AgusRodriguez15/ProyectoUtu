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

    public function __construct($IdServicio, $Nombre, $Descripcion, $FechaPublicacion, $Estado, $IdProveedor, $Fotos = [])
    {
        $this->IdServicio = $IdServicio;
        $this->Nombre = $Nombre;
        $this->Descripcion = $Descripcion;
        $this->FechaPublicacion = $FechaPublicacion;
        $this->Estado = $Estado;
        $this->IdProveedor = $IdProveedor;
        $this->Fotos = $Fotos;
    }

    public static function conectar()
    {
        if (!isset(self::$conexion)) {
            $db = new ClaseConexion();
            self::$conexion = $db->getConexion();
        }
    }

    // Obtener todos los servicios disponibles
    public static function obtenerTodosDisponibles()
    {
        self::conectar();
        $sql = "SELECT * FROM servicio WHERE Estado = 'DISPONIBLE' ORDER BY FechaPublicacion DESC";
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
        $rutaBase = '/proyecto/public/recursos/imagenes/servicios/';
        if (!empty($this->Fotos)) {
            return $rutaBase . $this->Fotos[array_rand($this->Fotos)];
        }
        return $rutaBase . 'default.jpg';
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
