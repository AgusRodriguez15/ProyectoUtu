<?php

class Servicio {
    // Propiedades de la clase
    public $IdServicio;
    public $Titulo;
    public $Descripcion;
    public $Precio;
    public $divisa;
    public $FechaPublicacion;
    public $IdUsuario;

    // Conexión a la base de datos (propiedad estática)
    private static $conexion;

    public function __construct($IdServicio, $Titulo, $Descripcion, $Precio, $divisa, $FechaPublicacion, $IdUsuario) {
        $this->IdServicio = $IdServicio;
        $this->Titulo = $Titulo;
        $this->Descripcion = $Descripcion;
        $this->Precio = $Precio;
        $this->divisa = $divisa;
        $this->FechaPublicacion = $FechaPublicacion;
        $this->IdUsuario = $IdUsuario;
    }

    // Método estático para establecer la conexión a la base de datos
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

    // --- Métodos de CRUD (Crear, Leer, Actualizar, Borrar) ---

    // Método para guardar (insertar o actualizar) un servicio
    public function guardar() {
        self::conectar();
        
        if ($this->IdServicio == null) {
            // Es un nuevo servicio, se inserta
            $sql = "INSERT INTO servicios (Titulo, Descripcion, Precio, divisa, FechaPublicacion, IdUsuario) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = self::$conexion->prepare($sql);
            $stmt->bind_param("ssdissi",
                $this->Titulo,
                $this->Descripcion,
                $this->Precio,
                $this->divisa,
                $this->FechaPublicacion,
                $this->IdUsuario
            );
            $ejecutado = $stmt->execute();
            if ($ejecutado) {
                $this->IdServicio = self::$conexion->insert_id; // Obtener el ID generado
            }
            $stmt->close();
            return $ejecutado;
        } else {
            // El servicio ya existe, se actualiza
            // Aquí puedes agregar la lógica para actualizar si lo necesitas
            return false;
        }
    }
    
    // Método estático para obtener todos los servicios
    public static function obtenerTodos() {
        self::conectar();
        $sql = "SELECT * FROM servicios";
        $resultado = self::$conexion->query($sql);
        $servicios = array();
        
        if ($resultado->num_rows > 0) {
            while($fila = $resultado->fetch_assoc()) {
                $servicio = new Servicio(
                    $fila['IdServicio'],
                    $fila['Titulo'],
                    $fila['Descripcion'],
                    $fila['Precio'],
                    $fila['divisa'],
                    $fila['FechaPublicacion'],
                    $fila['IdUsuario']
                );
                $servicios[] = $servicio;
            }
        }
        
        return $servicios;
    }
    
    // --- Otros métodos (getters y setters) ---
    public function getIdServicio() { return $this->IdServicio; }
    public function getTitulo() { return $this->Titulo; }
    public function setTitulo($Titulo) { $this->Titulo = $Titulo; }
    public function getDescripcion() { return $this->Descripcion; }
    public function setDescripcion($Descripcion) { $this->Descripcion = $Descripcion; }
    public function getPrecio() { return $this->Precio; }
    public function setPrecio($Precio) { $this->Precio = $Precio; }
    public function getDivisa() { return $this->divisa; }
    public function setDivisa($divisa) { $this->divisa = $divisa; }
    public function getFechaPublicacion() { return $this->FechaPublicacion; }
    public function setFechaPublicacion($FechaPublicacion) { $this->FechaPublicacion = $FechaPublicacion; }
    public function getIdUsuario() { return $this->IdUsuario; }
}
?>