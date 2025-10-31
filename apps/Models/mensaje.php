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
        // Log local para depuración
        $logFile = __DIR__ . '/../../logs/mensaje_model.log';
        $log = function($msg) use ($logFile) {
            @error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, 3, $logFile);
        };
        $sql = "INSERT INTO mensaje (Contenido, Imagen, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
                VALUES (?, ?, ?, 'enviado', ?, ?)";
        $stmt = self::$conexion->prepare($sql);

        if (!$stmt) {
            $err = self::$conexion->error ?? 'desconocido';
            $log("[enviar] Error preparando consulta principal: " . $err);
            if (stripos($err, "Unknown column 'Imagen'") !== false || stripos($err, 'unknown column') !== false) {
                // Fallback: insertar sin la columna Imagen
                $sql2 = "INSERT INTO mensaje (Contenido, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
                        VALUES (?, ?, 'enviado', ?, ?)";
                $stmt2 = self::$conexion->prepare($sql2);
                if (!$stmt2) {
                    $log("[enviar] Error preparando consulta fallback: " . (self::$conexion->error ?? 'desconocido'));
                    return ['ok' => false, 'error' => 'Error preparando consulta (fallback): ' . (self::$conexion->error ?? 'desconocido')];
                }
                $bindOk = $stmt2->bind_param("ssii", $contenido, $fecha, $idEmisor, $idReceptor);
                if ($bindOk === false) {
                    $log("[enviar] Error al enlazar parámetros (fallback)");
                    $stmt2->close();
                    return ['ok' => false, 'error' => 'Error al enlazar parámetros (fallback)'];
                }
                $ok = $stmt2->execute();
                $insertId = $ok ? self::$conexion->insert_id : null;
                if ($ok === false) {
                    $err2 = self::$conexion->error ?? 'ejecución fallida (fallback)';
                    $log("[enviar] Error ejecutando consulta (fallback): " . $err2);
                    $stmt2->close();
                    return ['ok' => false, 'error' => 'Error ejecutando consulta (fallback): ' . $err2];
                }
                $log("[enviar] Fallback INSERT ejecutado. insertId=" . $insertId);
                $stmt2->close();
                return ['ok' => true, 'insertId' => $insertId, 'imagenGuardada' => false];
            }

            return ['ok' => false, 'error' => 'Error preparando consulta: ' . $err];
        }

        $bindOk = $stmt->bind_param("sssii", $contenido, $imagenNombre, $fecha, $idEmisor, $idReceptor);
        if ($bindOk === false) {
            $log("[enviar] Error al enlazar parámetros");
            $stmt->close();
            return ['ok' => false, 'error' => 'Error al enlazar parámetros'];
        }
        $ok = $stmt->execute();
        $insertId = $ok ? self::$conexion->insert_id : null;
        if ($ok === false) {
            $err = self::$conexion->error ?? 'ejecución fallida';
            $log("[enviar] Error ejecutando consulta principal: " . $err);
            $stmt->close();
            return ['ok' => false, 'error' => 'Error ejecutando consulta: ' . $err];
        }
        $log("[enviar] INSERT ejecutado. insertId=" . $insertId . ", contenido=" . $contenido . ", imagenNombre=" . $imagenNombre);
        $stmt->close();

        // Si la inserción fue exitosa y se proporcionó nombre de imagen, intentar asegurar que
        // la columna Imagen se actualice (por si el driver/DB se comportó de forma inconsistente)
        if ($insertId && $imagenNombre) {
            $updateResult = self::guardarImagen($insertId, $imagenNombre);
            $log("[enviar] guardarImagen ejecutado para insertId=" . $insertId . ", resultado=" . ($updateResult ? 'true' : 'false'));
            return ['ok' => true, 'insertId' => $insertId, 'imagenGuardada' => $updateResult];
        }

        return ['ok' => true, 'insertId' => $insertId, 'imagenGuardada' => ($imagenNombre ? true : false)];
    }

    // Intentar actualizar la columna Imagen para un mensaje existente.
    // Devuelve true si la actualización se hizo correctamente, false en caso contrario.
    public static function guardarImagen($idMensaje, $imagenNombre)
    {
        self::conectar();
        // Log local para depuración
        $logFile = __DIR__ . '/../../logs/mensaje_model.log';
        $log = function($msg) use ($logFile) {
            @error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, 3, $logFile);
        };
        $sql = "UPDATE mensaje SET Imagen = ? WHERE IdMensaje = ?";
        $stmt = self::$conexion->prepare($sql);
        if (!$stmt) {
            $log("[guardarImagen] Error preparando UPDATE: " . (self::$conexion->error ?? 'desconocido'));
            return false;
        }
        $bindOk = $stmt->bind_param("si", $imagenNombre, $idMensaje);
        if ($bindOk === false) {
            $log("[guardarImagen] Error al enlazar parámetros: " . (self::$conexion->error ?? 'desconocido'));
            $stmt->close();
            return false;
        }
        $ok = $stmt->execute();
        if ($ok === false) {
            $log("[guardarImagen] Error ejecutando UPDATE: " . (self::$conexion->error ?? 'desconocido'));
            $stmt->close();
            return false;
        }
        $stmt->close();
        $log("[guardarImagen] UPDATE exitoso para IdMensaje=$idMensaje, Imagen=$imagenNombre");
        return true;
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
