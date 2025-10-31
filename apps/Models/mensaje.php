<?php
require_once __DIR__ . '/ConexionDB.php';

class Mensaje
{
    private static $conexion;

    public $IdMensaje;
    public $Contenido;
    public $Imagen; // Nueva propiedad
    public $Fecha;
    public $Estado;
    public $IdUsuarioEmisor;
    public $IdUsuarioReceptor;

    public function __construct($IdMensaje, $Contenido, $Fecha, $Estado, $IdUsuarioEmisor, $IdUsuarioReceptor, $Imagen = null)
    {
        $this->IdMensaje = $IdMensaje;
        $this->Contenido = $Contenido;
        $this->Fecha = $Fecha;
        $this->Estado = $Estado;
        $this->IdUsuarioEmisor = $IdUsuarioEmisor;
        $this->IdUsuarioReceptor = $IdUsuarioReceptor;
        $this->Imagen = $Imagen;
    }

    private static function conectar()
    {
        if (!self::$conexion) {
            $db = new ConexionDB();
            self::$conexion = $db->getConexion();
        }
    }

    public static function enviar($contenido, $idEmisor, $idReceptor, $imagenNombre = null)
    {
        self::conectar();
        $fecha = date('Y-m-d H:i:s');
        $sql = "INSERT INTO mensaje (Contenido, Imagen, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
                VALUES (?, ?, ?, 'enviado', ?, ?)";
        $stmt = self::$conexion->prepare($sql);

        // Si falla la preparación, detectamos si es por columna desconocida 'Imagen' y hacemos fallback
        if (!$stmt) {
            $err = self::$conexion->error ?? 'desconocido';
            // Mensaje típico: "Unknown column 'Imagen' in 'field list'"
            if (stripos($err, "Unknown column 'Imagen'") !== false || stripos($err, 'unknown column') !== false) {
                // Fallback: insertar sin la columna Imagen
                $sql2 = "INSERT INTO mensaje (Contenido, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
                        VALUES (?, ?, 'enviado', ?, ?)";
                $stmt2 = self::$conexion->prepare($sql2);
                if (!$stmt2) {
                    return ['ok' => false, 'error' => 'Error preparando consulta (fallback): ' . (self::$conexion->error ?? 'desconocido')];
                }
                $bindOk = $stmt2->bind_param("ssii", $contenido, $fecha, $idEmisor, $idReceptor);
                if ($bindOk === false) {
                    $stmt2->close();
                    return ['ok' => false, 'error' => 'Error al enlazar parámetros (fallback)'];
                }
                $ok = $stmt2->execute();
                $insertId = $ok ? self::$conexion->insert_id : null;
                if ($ok === false) {
                    $err2 = self::$conexion->error ?? 'ejecución fallida (fallback)';
                    $stmt2->close();
                    return ['ok' => false, 'error' => 'Error ejecutando consulta (fallback): ' . $err2];
                }
                $stmt2->close();
                return ['ok' => true, 'insertId' => $insertId, 'imagenGuardada' => false];
            }

            return ['ok' => false, 'error' => 'Error preparando consulta: ' . $err];
        }

        $bindOk = $stmt->bind_param("sssii", $contenido, $imagenNombre, $fecha, $idEmisor, $idReceptor);
        if ($bindOk === false) {
            $stmt->close();
            return ['ok' => false, 'error' => 'Error al enlazar parámetros'];
        }
        $ok = $stmt->execute();
        $insertId = $ok ? self::$conexion->insert_id : null;
        if ($ok === false) {
            $err = self::$conexion->error ?? 'ejecución fallida';
            $stmt->close();
            return ['ok' => false, 'error' => 'Error ejecutando consulta: ' . $err];
        }
        $stmt->close();
        return ['ok' => true, 'insertId' => $insertId, 'imagenGuardada' => ($imagenNombre ? true : false)];
    }

    public static function obtenerPorConversacion($idUsuario1, $idUsuario2)
    {
        self::conectar();
        $sql = "SELECT * FROM mensaje 
                WHERE (IdUsuarioEmisor = ? AND IdUsuarioReceptor = ?) 
                   OR (IdUsuarioEmisor = ? AND IdUsuarioReceptor = ?)
                ORDER BY Fecha ASC";
    $stmt = self::$conexion->prepare($sql);
    if (!$stmt) return [];
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
                $fila['IdUsuarioReceptor'],
                $fila['Imagen'] ?? null
            );
        }
        $stmt->close();
        return $mensajes;
    }

    public static function obtenerChats($idUsuario)
    {
        self::conectar();
        $sql = "SELECT DISTINCT
                    CASE WHEN IdUsuarioEmisor = ? THEN IdUsuarioReceptor ELSE IdUsuarioEmisor END AS OtroUsuario
                FROM mensaje
                WHERE IdUsuarioEmisor = ? OR IdUsuarioReceptor = ?";
    $stmt = self::$conexion->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("iii", $idUsuario, $idUsuario, $idUsuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
        $chats = [];
        while ($fila = $resultado->fetch_assoc()) {
            $chats[] = $fila['OtroUsuario'];
        }
        $stmt->close();
        return $chats;
    }
}
