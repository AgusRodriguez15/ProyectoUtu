<?php
require_once __DIR__ . "/ConexionDB.php";

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

    private static function conectar()
    {
        if (!self::$conexion) {
            $db = new ConexionDB();
            self::$conexion = $db->getConexion();
        }
    }

    public static function obtenerPorConversacion($idUsuario1, $idUsuario2)
    {
        self::conectar();
        $sql = "SELECT * FROM mensaje 
                WHERE (IdUsuarioEmisor = ? AND IdUsuarioReceptor = ?)
                   OR (IdUsuarioEmisor = ? AND IdUsuarioReceptor = ?)
                ORDER BY Fecha ASC";
        $stmt = self::$conexion->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("iiii", $idUsuario1, $idUsuario2, $idUsuario2, $idUsuario1);
        if (!$stmt->execute()) {
            return [];
        }
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
        $stmt->close();
        return $mensajes;
    }

    public static function enviar($contenido, $idEmisor, $idReceptor)
    {
        self::conectar();
        $fecha = date('Y-m-d H:i:s');
        $sql = "INSERT INTO mensaje (Contenido, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
                VALUES (?, ?, 'ENVIADO', ?, ?)";
        $stmt = self::$conexion->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssii", $contenido, $fecha, $idEmisor, $idReceptor);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
?>
