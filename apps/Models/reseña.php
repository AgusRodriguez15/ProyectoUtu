<?php
require_once __DIR__ . '/ConexionDB.php';

class Resena {
    private $IdResena;
    private $Comentario;
    private $Puntuacion;
    private $Fecha;
    private $IdUsuario;
    private $IdServicio;

    public function __construct($IdResena, $Comentario, $Puntuacion, $Fecha, $IdUsuario, $IdServicio) {
        $this->IdResena = $IdResena;
        $this->Comentario = $Comentario;
        $this->Puntuacion = $Puntuacion;
        $this->Fecha = $Fecha;
        $this->IdUsuario = $IdUsuario;
        $this->IdServicio = $IdServicio;
    }

    // ===== GETTERS =====
    public function getIdResena() {
        return $this->IdResena;
    }

    public function getComentario() {
        return $this->Comentario;
    }

    public function getPuntuacion() {
        return $this->Puntuacion;
    }

    public function getFecha() {
        return $this->Fecha;
    }

    public function getIdUsuario() {
        return $this->IdUsuario;
    }

    public function getIdServicio() {
        return $this->IdServicio;
    }

    // ===== SETTERS =====
    public function setComentario($Comentario) {
        $this->Comentario = $Comentario;
    }

    public function setPuntuacion($Puntuacion) {
        $this->Puntuacion = $Puntuacion;
    }

    public function setFecha($Fecha) {
        $this->Fecha = $Fecha;
    }

    public function setIdUsuario($IdUsuario) {
        $this->IdUsuario = $IdUsuario;
    }

    public function setIdServicio($IdServicio) {
        $this->IdServicio = $IdServicio;
    }

    // ===== GUARDAR RESEÑA =====
    /**
     * Guarda una nueva reseña en la base de datos
     * @return bool true si se guardó correctamente, false en caso contrario
     */
    public function guardar(): bool {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        // Validaciones
        if (empty($this->Comentario) || empty($this->IdUsuario) || empty($this->IdServicio)) {
            error_log("Error: Faltan datos obligatorios para guardar la reseña");
            return false;
        }

        if ($this->Puntuacion < 1 || $this->Puntuacion > 5) {
            error_log("Error: La puntuación debe estar entre 1 y 5");
            return false;
        }

        // Si no hay fecha, usar la actual
        if (empty($this->Fecha)) {
            $this->Fecha = date('Y-m-d H:i:s');
        }

        $stmt = $conn->prepare("INSERT INTO Resenia (Comentario, Puntuacion, Fecha, IdUsuario, IdServicio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sisii', 
            $this->Comentario, 
            $this->Puntuacion, 
            $this->Fecha, 
            $this->IdUsuario, 
            $this->IdServicio
        );
        
        $resultado = $stmt->execute();

        if ($resultado) {
            $this->IdResena = $conn->insert_id;
            error_log("Reseña guardada exitosamente con ID: {$this->IdResena}");
        } else {
            error_log("Error al guardar reseña: " . $stmt->error);
        }

        $stmt->close();


        return $resultado;
    }

    // ===== OBTENER RESEÑAS POR SERVICIO =====
    /**
     * Obtiene todas las reseñas de un servicio específico
     * @param int $idServicio ID del servicio
     * @return array Array de reseñas con información del usuario
     */
    public static function obtenerPorServicio(int $idServicio): array {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $sql = "
            SELECT 
                r.IdResenia,
                r.Comentario,
                r.Puntuacion,
                r.Fecha,
                r.IdUsuario,
                r.IdServicio,
                u.Nombre,
                u.Apellido,
                u.FotoPerfil
            FROM Resenia r
            INNER JOIN Usuario u ON r.IdUsuario = u.IdUsuario
            WHERE r.IdServicio = ?
            ORDER BY r.Fecha DESC
        ";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Error en prepare: " . $conn->error);
            error_log("SQL: " . $sql);
            $conn->close();
            return [];
        }
        
        $stmt->bind_param('i', $idServicio);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $resenas = [];
        while ($fila = $resultado->fetch_assoc()) {
            // Normalizar tipos y formatos para facilitar el consumo en el frontend
            $puntuacion = isset($fila['Puntuacion']) ? (int)$fila['Puntuacion'] : 0;
            $fechaRaw = $fila['Fecha'] ?? null;
            // Convertir 'YYYY-MM-DD HH:MM:SS' a 'YYYY-MM-DDTHH:MM:SS' (ISO-ish) para JS
            $fechaIso = $fechaRaw ? str_replace(' ', 'T', $fechaRaw) : null;
            $fotoPerfil = $fila['FotoPerfil'] ?? '';

            $resenas[] = [
                'idResena' => $fila['IdResenia'],
                'comentario' => $fila['Comentario'],
                'puntuacion' => $puntuacion,
                'fecha' => $fechaIso,
                'idUsuario' => $fila['IdUsuario'],
                'idServicio' => $fila['IdServicio'],
                'usuario' => [
                    'nombre' => $fila['Nombre'],
                    'apellido' => $fila['Apellido'],
                    'nombreCompleto' => $fila['Nombre'] . ' ' . $fila['Apellido'],
                    'foto' => $fotoPerfil
                ]
            ];
        }

        $stmt->close();
        $conn->close();

        return $resenas;
    }

    // ===== OBTENER RESEÑA POR ID =====
    /**
     * Obtiene una reseña específica por su ID
     * @param int $idResena ID de la reseña
     * @return Resena|null Objeto Resena o null si no existe
     */
    public static function obtenerPorId(int $idResena): ?Resena {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("SELECT * FROM Resenia WHERE IdResenia = ?");
        $stmt->bind_param('i', $idResena);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        $stmt->close();
        $conn->close();

        if (!$fila) {
            return null;
        }

        return new Resena(
            $fila['IdResenia'],
            $fila['Comentario'],
            $fila['Puntuacion'],
            $fila['Fecha'],
            $fila['IdUsuario'],
            $fila['IdServicio']
        );
    }

    // ===== ELIMINAR RESEÑA =====
    /**
     * Elimina una reseña de la base de datos
     * @return bool true si se eliminó correctamente, false en caso contrario
     */
    public function eliminar(): bool {
        if (!$this->IdResena) {
            return false;
        }

    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("DELETE FROM Resenia WHERE IdResenia = ?");
        $stmt->bind_param('i', $this->IdResena);
        $resultado = $stmt->execute();

        $stmt->close();
        $conn->close();

        return $resultado;
    }

    // ===== VERIFICAR SI USUARIO YA RESEÑÓ EL SERVICIO =====
    /**
     * Verifica si un usuario ya dejó una reseña para un servicio
     * @param int $idUsuario ID del usuario
     * @param int $idServicio ID del servicio
     * @return bool true si ya existe una reseña, false si no
     */
    public static function existeResena(int $idUsuario, int $idServicio): bool {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Resenia WHERE IdUsuario = ? AND IdServicio = ?");
        
        if (!$stmt) {
            error_log("Error en prepare: " . $conn->error);
            $conn->close();
            return false;
        }
        
        $stmt->bind_param('ii', $idUsuario, $idServicio);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        $stmt->close();
        $conn->close();

        return $fila['total'] > 0;
    }

    // ===== OBTENER RESEÑA DE USUARIO PARA UN SERVICIO =====
    /**
     * Obtiene la reseña de un usuario específico para un servicio
     * @param int $idUsuario ID del usuario
     * @param int $idServicio ID del servicio
     * @return array|null Datos de la reseña o null si no existe
     */
    public static function obtenerResenaPorUsuarioYServicio(int $idUsuario, int $idServicio): ?array {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("
            SELECT 
                r.IdResenia,
                r.Comentario,
                r.Puntuacion,
                r.Fecha,
                r.IdUsuario,
                r.IdServicio
            FROM Resenia r
            WHERE r.IdUsuario = ? AND r.IdServicio = ?
        ");
        
        if (!$stmt) {
            error_log("Error en prepare: " . $conn->error);
            $conn->close();
            return null;
        }
        
        $stmt->bind_param('ii', $idUsuario, $idServicio);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        $stmt->close();
        $conn->close();

        if (!$fila) {
            return null;
        }

        return [
            'idResena' => $fila['IdResenia'],
            'comentario' => $fila['Comentario'],
            'puntuacion' => $fila['Puntuacion'],
            'fecha' => $fila['Fecha'],
            'idUsuario' => $fila['IdUsuario'],
            'idServicio' => $fila['IdServicio']
        ];
    }

    // ===== CALCULAR PROMEDIO DE PUNTUACIÓN =====
    /**
     * Calcula el promedio de puntuación de un servicio
     * @param int $idServicio ID del servicio
     * @return array Array con el promedio y total de reseñas
     */
    public static function calcularPromedioServicio(int $idServicio): array {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("
            SELECT 
                AVG(Puntuacion) as promedio,
                COUNT(*) as total
            FROM Resenia 
            WHERE IdServicio = ?
        ");
        
        $stmt->bind_param('i', $idServicio);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        $stmt->close();
        $conn->close();

        return [
            'promedio' => $fila['promedio'] ? round($fila['promedio'], 1) : 0,
            'total' => $fila['total']
        ];
    }

    // ===== ELIMINAR TODAS LAS RESEÑAS DE UN SERVICIO =====
    public static function eliminarPorServicio(int $idServicio): bool {
        $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("DELETE FROM Resenia WHERE IdServicio = ?");
        if (!$stmt) {
            error_log('Error en prepare eliminarPorServicio: ' . $conn->error);
            return false;
        }
        $stmt->bind_param('i', $idServicio);
        $res = $stmt->execute();
        if (!$res) error_log('Error al eliminar reseñas por servicio: ' . $stmt->error);
        $stmt->close();
        $conn->close();
        return (bool) $res;
    }
}
?>
