<?php
class ConexionDB
{
    private $host = 'localhost';
    private $usuario = 'root';
    private $password = '';
    private $db = 'proyecto_utu';
    // Eliminado el uso de conexión estática

    public function __construct()
    {
        // El constructor ya no inicializa la conexión
    }

    public function getConexion()
    {
        $conexion = new mysqli($this->host, $this->usuario, $this->password, $this->db);
        if ($conexion->connect_errno) {
            error_log('MySQL connect error: ' . $conexion->connect_error);
            throw new Exception('Database connection error');
        }
        $conexion->set_charset('utf8mb4');
        return $conexion;
    }
}
?>
