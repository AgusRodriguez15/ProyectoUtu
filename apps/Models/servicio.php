<?php
class Servicio {
    public $IdServicio;
    public $Titulo;
    public $Descripcion;
    public $Precio;
    public $divisa;
    public $FechaPublicacion;
    public $IdUsuario;
    public $Fotos = []; // Array de fotos

    private static $conexion;

    public function __construct($IdServicio, $Titulo, $Descripcion, $Precio, $divisa, $FechaPublicacion, $IdUsuario, $Fotos = []) {
        $this->IdServicio = $IdServicio;
        $this->Titulo = $Titulo;
        $this->Descripcion = $Descripcion;
        $this->Precio = $Precio;
        $this->divisa = $divisa;
        $this->FechaPublicacion = $FechaPublicacion;
        $this->IdUsuario = $IdUsuario;
        $this->Fotos = $Fotos;
    }

    public static function conectar() {
        if (!isset(self::$conexion)) {
            $host = "localhost";
            $usuario = "tu_usuario";
            $contrasena = "tu_contrasena";
            $base_de_datos = "tu_base_de_datos";

            self::$conexion = new mysqli($host, $usuario, $contrasena, $base_de_datos);
            if (self::$conexion->connect_error) {
                die("Error de conexión: " . self::$conexion->connect_error);
            }
        }
    }

    // Obtener todos los servicios con sus fotos
    public static function obtenerTodos() {
        self::conectar();

        // Primero traemos todos los servicios
        $sql = "SELECT * FROM servicios ORDER BY FechaPublicacion DESC";
        $resultado = self::$conexion->query($sql);
        $servicios = [];

        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $idServicio = $fila['IdServicio'];

                // Traer todas las fotos del servicio
                $sqlFotos = "SELECT Foto FROM fotos WHERE IdServicio = $idServicio";
                $resFotos = self::$conexion->query($sqlFotos);
                $fotosArray = [];
                if ($resFotos->num_rows > 0) {
                    while ($fotoFila = $resFotos->fetch_assoc()) {
                        $fotosArray[] = $fotoFila['Foto'];
                    }
                }

                $servicio = new Servicio(
                    $fila['IdServicio'],
                    $fila['Titulo'],
                    $fila['Descripcion'],
                    $fila['Precio'],
                    $fila['divisa'],
                    $fila['FechaPublicacion'],
                    $fila['IdUsuario'],
                    $fotosArray
                );
                $servicios[] = $servicio;
            }
        }

        return $servicios;
    }

    // Método para obtener una foto aleatoria
    public function getFotoAleatoria() {
        if (!empty($this->Fotos)) {
            return $this->Fotos[array_rand($this->Fotos)];
        }
        return 'https://picsum.photos/300/200?random=' . rand(1,100);
    }

    public static function obtenerFotos($IdServicio) {
    self::conectar();

    $sql = "SELECT Foto FROM fotos WHERE IdServicio = ?";
    $stmt = self::$conexion->prepare($sql);
    $stmt->bind_param("i", $IdServicio);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $fotos = [];
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $fotos[] = $fila['Foto'];
        }
    }

    $stmt->close();
    return $fotos;
}

    // Getters básicos
    public function getTitulo() { return $this->Titulo; }
    public function getDescripcion() { return $this->Descripcion; }
    public function getPrecio() { return $this->Precio; }
    public function getDivisa() { return $this->divisa; }

}
?>