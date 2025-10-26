<?php
class ConexionDB
{
    private $host = 'localhost';
    private $usuario = 'root';
    private $password = '';
    private $db = 'proyecto_utu';
    private static $conexion = null;

    public function __construct()
    {
        if (!self::$conexion) {
            self::$conexion = new mysqli($this->host, $this->usuario, $this->password, $this->db);
            if (self::$conexion->connect_errno) {
                error_log('MySQL connect error: ' . self::$conexion->connect_error);
                throw new Exception('Database connection error');
            }
            self::$conexion->set_charset('utf8mb4');
        }
    }

    public function getConexion()
    {
        return self::$conexion;
    }
}
?>
