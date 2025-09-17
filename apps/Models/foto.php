<?php
class Foto
{
    public $IdServicio;
    public $Foto;
    private static $conexion;

    public function __construct($IdServicio, $Foto)
    {
        $this->IdServicio = $IdServicio;
        $this->Foto = $Foto;
    }

    public function getIdServicio()
    {
        return $this->IdServicio;
    }

    public function getFoto()
    {
        return $this->Foto;
    }

    public function setFoto($Foto)
    {
        $this->Foto = $Foto;
    }

    public static function conectar()
    {
        if (!isset(self::$conexion)) {
            $db = new ClaseConexion();
            self::$conexion = $db->getConexion();
        }
    }

    public static function obtenerPorServicio($servicio)
    {
        self::conectar();
        $id = intval($servicio->IdServicio); // evita inyección
        $sql = "SELECT Foto FROM foto WHERE IdServicio = $id";
        $resultado = self::$conexion->query($sql);

        if (!$resultado) {
            die("Error en la consulta de fotos: " . self::$conexion->error);
        }

        $fotos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $fotos[] = $fila['Foto'];
        }

        return $fotos;
    }
}
