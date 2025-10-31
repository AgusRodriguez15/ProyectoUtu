<?php
class Gestion {
    public $IdGestion;
    public $tipo;
    public $descripcion;
    public $fecha;
    public $IdAdministrador;
    public $IdServicio;

    public function __construct($tipo, $descripcion, $IdAdministrador, $IdServicio, $fecha = null, $IdGestion = null) {
        $this->IdGestion = $IdGestion;
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
        $this->fecha = $fecha ?? date('Y-m-d H:i:s');
        $this->IdAdministrador = $IdAdministrador;
        $this->IdServicio = $IdServicio;
    }

    /**
     * Guarda la instancia actual en la base de datos
     * @return int|false IdGestion insertado o false si hubo error
     */
    public function guardar() {
        require_once __DIR__ . '/ConexionDB.php';
        $db = new ConexionDB();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "INSERT INTO Gestion (tipo, descripcion, fecha, IdAdministrador, IdServicio) VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;

        $stmt->bind_param(
            'sssii',
            $this->tipo,
            $this->descripcion,
            $this->fecha,
            $this->IdAdministrador,
            $this->IdServicio
        );

        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            return false;
        }

        $this->IdGestion = $conn->insert_id;
        $stmt->close();
        return $this->IdGestion;
    }

    /**
     * Helpers estáticos para conveniencia: crean la instancia y guardan
     */
    public static function registrar(string $tipo, ?string $descripcion, ?int $IdAdministrador, ?int $IdServicio)
    {
        $g = new Gestion($tipo, $descripcion, $IdAdministrador ?? 0, $IdServicio ?? 0);
        return $g->guardar();
    }

    public static function registrarDeshabilitar(int $IdAdministrador, int $IdServicio, ?string $descripcion = null)
    {
        return self::registrar('desabilitar', $descripcion, $IdAdministrador, $IdServicio);
    }

    public static function registrarBorrarComentario(int $IdAdministrador, int $IdServicio, ?string $descripcion = null)
    {
        return self::registrar('borrar_comentario', $descripcion, $IdAdministrador, $IdServicio);
    }

    public static function registrarCancelarResenias(int $IdAdministrador, int $IdServicio, ?string $descripcion = null)
    {
        return self::registrar('cancelar_resenias', $descripcion, $IdAdministrador, $IdServicio);
    }

    public static function registrarHabilitar(int $IdAdministrador, int $IdServicio, ?string $descripcion = null)
    {
        return self::registrar('habilitar', $descripcion, $IdAdministrador, $IdServicio);
    }
}
?>