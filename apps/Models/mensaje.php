<?php
require_once __DIR__ . "/ClaseConexion.php";

class Mensaje
{
    public $IdMensaje;
    public $Contenido;
    public $Fecha;
    public $Estado;
    public $IdUsuarioEmisor;
    public $IdUsuarioReceptor;

    private static $conexion;

    public function __construct($IdMensaje, $Contenido, $Fecha, $Estado, $IdUsuarioEmisor, $IdUsuarioReceptor)
    {
        $this->IdMensaje = $IdMensaje;
        $this->Contenido = $Contenido;
        $this->Fecha = $Fecha;
        $this->Estado = $Estado;
        $this->IdUsuarioEmisor = $IdUsuarioEmisor;
        $this->IdUsuarioReceptor = $IdUsuarioReceptor;
    }

    /* ==== Getters & Setters ==== */
    public function getIdMensaje() { return $this->IdMensaje; }

    public function getContenido() { return $this->Contenido; }
    public function setContenido($Contenido) { $this->Contenido = $Contenido; }

    public function getFecha() { return $this->Fecha; }
    public function setFecha($Fecha) { $this->Fecha = $Fecha; }

    public function getEstado() { return $this->Estado; }
    public function setEstado($Estado) { $this->Estado = $Estado; }

    public function getIdUsuarioEmisor() { return $this->IdUsuarioEmisor; }
    public function setIdUsuarioEmisor($IdUsuarioEmisor) { $this->IdUsuarioEmisor = $IdUsuarioEmisor; }

    public function getIdUsuarioReceptor() { return $this->IdUsuarioReceptor; }
    public function setIdUsuarioReceptor($IdUsuarioReceptor) { $this->IdUsuarioReceptor = $IdUsuarioReceptor; }

    /* ==== Conexión ==== */
    private static function conectar()
    {
        if (!self::$conexion) {
            $db = new ClaseConexion();
            self::$conexion = $db->getConexion();
        }
    }

    /* ==== Métodos estáticos ==== */
    public static function obtenerPorConversacion($idUsuario1, $idUsuario2)
    {
        self::conectar();
        $sql = "SELECT * FROM mensaje 
                WHERE (IdUsuarioEmisor = ? AND IdUsuarioReceptor = ?)
                   OR (IdUsuarioEmisor = ? AND IdUsuarioReceptor = ?)
                ORDER BY Fecha ASC";

        $stmt = self::$conexion->prepare($sql);
        $stmt->bind_param("iiii", $idUsuario1, $idUsuario2, $idUsuario2, $idUsuario1);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $mensajes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mensajes[] = new Mensaje(
                $fila['IdMensaje'],
                $fila['Contenido'],
                $fila['Fecha'],
                $fila['Estado'],
                $fila['IdUsuarioEmisor'],
                $fila['IdUsuarioReceptor']
            );
        }
        return $mensajes;
    }

    public static function enviar($contenido, $idEmisor, $idReceptor)
    {
        self::conectar();
        $sql = "INSERT INTO mensaje (Contenido, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
                VALUES (?, NOW(), 'ENVIADO', ?, ?)";
        $stmt = self::$conexion->prepare($sql);
        $stmt->bind_param("sii", $contenido, $idEmisor, $idReceptor);
        return $stmt->execute();
    }
}
?>