<?php
class ClaseConexion
{
    private $servidor ="127.0.0.1";
    private $usuario = "root";
    private $contrasena = "";
    private $baseDeDatos = "proyect";
    private $conexion;

    public function __construct()
    {
        $this->conexion = new mysqli($this->servidor, $this->usuario, $this->contrasena, $this->baseDeDatos);
        if ($this->conexion->connect_error) 
        {
            throw new Exception("Error de conexión: " . $this->conexion->connect_error);
        }
    }
    public function getConexion()
    {
        return $this->conexion;
    }
}
?>