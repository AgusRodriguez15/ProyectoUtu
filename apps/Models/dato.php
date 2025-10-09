<?php
require_once __DIR__ . '/ConexionDB.php';

class dato {
    public $IdUsuario;
    public $Tipo;
    public $Contacto;

    public function __construct($IdUsuario, $Tipo, $Contacto = null) {
        $this->IdUsuario = $IdUsuario;
        $this->Tipo = $Tipo;
        $this->Contacto = $Contacto;
    }

    public function guardar(): bool {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("INSERT INTO Dato (IdUsuario, Tipo, Contacto) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error prepare guardar dato: " . $conn->error);
        }

        $stmt->bind_param('iss', $this->IdUsuario, $this->Tipo, $this->Contacto);

        $resultado = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $resultado;
    }

    public static function obtenerPorUsuario(int $IdUsuario): array {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("SELECT Tipo, Contacto FROM Dato WHERE IdUsuario = ?");
        if (!$stmt) {
            throw new Exception("Error prepare obtenerPorUsuario dato: " . $conn->error);
        }

        $stmt->bind_param('i', $IdUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $contactos = [];
        while ($row = $result->fetch_assoc()) {
            $contactos[] = new dato($IdUsuario, $row['Tipo'], $row['Contacto']);
        }

        $stmt->close();
        $conn->close();
        return $contactos;
    }
}
?>
