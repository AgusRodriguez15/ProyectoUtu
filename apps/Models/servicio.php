<?php
require_once "ConexionDB.php";
require_once "Foto.php";
class Servicio {
    public $IdServicio;
    public $Nombre;
    public $Descripcion;
    public $FechaPublicacion;
    public $Estado;
    public $IdProveedor;
    public $Fotos = []; // Array de URLs de fotos

    private static $conexion;

    public function __construct($IdServicio, $Nombre, $Descripcion, $FechaPublicacion, $Estado, $IdProveedor, $Fotos = []) {
        $this->IdServicio = $IdServicio;
        $this->Nombre = $Nombre;
        $this->Descripcion = $Descripcion;
        $this->FechaPublicacion = $FechaPublicacion;
        $this->Estado = $Estado;
        $this->IdProveedor = $IdProveedor;
        $this->Fotos = $Fotos;
    }

    // Conexión usando ClaseConexion
    public static function conectar() {
        if (!isset(self::$conexion)) {
            $db = new ClaseConexion();
            self::$conexion = $db->getConexion();
        }
    }

    // Obtener todos los servicios
    public static function obtenerTodos() {
    self::conectar();
    $sql = "SELECT * FROM servicio ORDER BY FechaPublicacion DESC";
    $resultado = self::$conexion->query($sql);

    if (!$resultado) {
        die("Error en la consulta de servicio: " . self::$conexion->error);
    }

    $servicios = [];

    while ($fila = $resultado->fetch_assoc()) {
        $servicioTemp = new Servicio(
            $fila['IdServicio'],
            $fila['Nombre'],
            $fila['Descripcion'],
            $fila['FechaPublicacion'],
            $fila['Estado'],
            $fila['IdProveedor']
        );

        // Obtener fotos usando la clase Foto
        $servicioTemp->Fotos = Foto::obtenerPorServicio($servicioTemp);

        $servicios[] = $servicioTemp;
    }

    return $servicios;
}


    public function getFotoAleatoria() {
    $rutaBase = '/proyecto/public/recursos/imagenes/servicios/'; // ajusta según tu proyecto
    if (!empty($this->Fotos)) {
        return $rutaBase . $this->Fotos[array_rand($this->Fotos)];
    }
    return $rutaBase . 'default.jpg'; // foto genérica si no hay
}


    // Retorna todas las fotos del servicio
    public function getTodasFotos() {
        return $this->Fotos;
    }

    // Getters
    public function getIdServicio() { return $this->IdServicio; }
    public function getNombre() { return $this->Nombre; }
    public function getDescripcion() { return $this->Descripcion; }
    public function getFechaPublicacion() { return $this->FechaPublicacion; }
    public function getEstado() { return $this->Estado; }
    public function getIdProveedor() { return $this->IdProveedor; }
}
?>
