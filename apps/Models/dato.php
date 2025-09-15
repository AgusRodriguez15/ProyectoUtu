<?php
require_once __DIR__ . '/ConexionDB.php';

class dato {
    public $IdUsuario;
    public $Tipo;
    public $Contacto;

    public function __construct($IdUsuario, $Tipo, $Contacto = null) {
        $this->IdUsuario = $IdUsuario;
        $this->Tipo = $Tipo;
        $this->Contacto = $Contacto; // puede ser null
    }

    public function getIdUsuario() { return $this->IdUsuario; }

    public function getTipo() { return $this->Tipo; }
    public function setTipo($Tipo) { $this->Tipo = $Tipo; }

    public function getContacto() { return $this->Contacto; }
    public function setContacto($Contacto) { $this->Contacto = $Contacto; }

    // ===== GUARDAR CONTACTO =====
    public function guardar(): bool {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("INSERT INTO DatosContacto (IdUsuario, Tipo, Contacto) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $this->IdUsuario, $this->Tipo, $this->Contacto);

        $resultado = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $resultado;
    }

    // ===== ELIMINAR CONTACTO =====
    public function eliminar(): bool {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("DELETE FROM DatosContacto WHERE IdUsuario = ? AND Tipo = ? AND (Contacto = ? OR (? IS NULL AND Contacto IS NULL))");
        $stmt->bind_param('isss', $this->IdUsuario, $this->Tipo, $this->Contacto, $this->Contacto);

        $resultado = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $resultado;
    }

    // ===== OBTENER CONTACTOS POR USUARIO =====
    public static function obtenerPorUsuario($IdUsuario): array {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("SELECT Tipo, Contacto FROM DatosContacto WHERE IdUsuario = ?");
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
