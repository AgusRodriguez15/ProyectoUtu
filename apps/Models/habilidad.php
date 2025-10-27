<?php
require_once __DIR__ . '/ConexionDB.php';

class habilidad {
    public $IdUsuario;
    public $Habilidad;
    public $AniosExperiencia;

    public function __construct($IdUsuario, $Habilidad, $AniosExperiencia = 0) {
        $this->IdUsuario = $IdUsuario;
        $this->Habilidad = $Habilidad;
        $this->AniosExperiencia = $AniosExperiencia;
    }

    public function guardar(): bool {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        // Usamos REPLACE INTO que funciona como un UPDATE si existe o INSERT si no existe
        $stmt = $conn->prepare("REPLACE INTO Habilidad (IdUsuario, Habilidad, AniosExperiencia) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error prepare guardar habilidad: " . $conn->error);
        }

        $stmt->bind_param('isi', $this->IdUsuario, $this->Habilidad, $this->AniosExperiencia);
        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    public static function eliminarPorUsuario(int $IdUsuario): bool {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("DELETE FROM Habilidad WHERE IdUsuario = ?");
        if (!$stmt) {
            throw new Exception("Error prepare eliminarPorUsuario habilidad: " . $conn->error);
        }

        $stmt->bind_param('i', $IdUsuario);
        $resultado = $stmt->execute();

        $stmt->close();
        $conn->close();
        return $resultado;
    }

    public static function obtenerPorUsuario(int $IdUsuario): array {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("SELECT Habilidad, AniosExperiencia FROM Habilidad WHERE IdUsuario = ?");
        if (!$stmt) {
            throw new Exception("Error prepare obtenerPorUsuario habilidad: " . $conn->error);
        }

        $stmt->bind_param('i', $IdUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $habilidades = [];
        while ($row = $result->fetch_assoc()) {
            $habilidades[] = [
                'Habilidad' => $row['Habilidad'],
                'AniosExperiencia' => $row['AniosExperiencia']
            ];
        }

        $stmt->close();
        $conn->close();
        return $habilidades;
    }
}
?>