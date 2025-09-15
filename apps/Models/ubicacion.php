<?php
require_once __DIR__ . '/ConexionDB.php';

class ubicacion
{
    private ?int $IdUbicacion;
    private string $Pais;
    private string $Ciudad;
    private string $Calle;
    private ?int $Numero;

    public function __construct(
        ?int $IdUbicacion,
        string $Pais,
        string $Ciudad,
        string $Calle,
        ?int $Numero
    ) {
        $this->IdUbicacion = $IdUbicacion;
        $this->Pais = $Pais;
        $this->Ciudad = $Ciudad;
        $this->Calle = $Calle;
        $this->Numero = $Numero;
    }

    // Getters y setters para todos los atributos
    public function getIdUbicacion(): ?int
    {
        return $this->IdUbicacion;
    }

    public function getPais(): string
    {
        return $this->Pais;
    }
    public function setPais(string $Pais): void
    {
        $this->Pais = $Pais;
    }

    public function getCiudad(): string
    {
        return $this->Ciudad;
    }
    public function setCiudad(string $Ciudad): void
    {
        $this->Ciudad = $Ciudad;
    }

    public function getCalle(): string
    {
        return $this->Calle;
    }
    public function setCalle(string $Calle): void
    {
        $this->Calle = $Calle;
    }

    public function getNumero(): ?int
    {
        return $this->Numero;
    }
    public function setNumero(?int $Numero): void
    {
        $this->Numero = $Numero;
    }

    // Método para guardar una nueva ubicación en la base de datos
    public function guardar(): bool
    {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();
        $stmt = null;
        $resultado = false;

        // Si ya tiene un IdUbicacion, es una actualización
        if ($this->IdUbicacion !== null) {
            $stmt = $conn->prepare("UPDATE Ubicacion SET Pais = ?, Ciudad = ?, Calle = ?, Numero = ? WHERE IdUbicacion = ?");
            $stmt->bind_param('sssii', $this->Pais, $this->Ciudad, $this->Calle, $this->Numero, $this->IdUbicacion);
        } else {
            // Si no, es una nueva inserción
            $stmt = $conn->prepare("INSERT INTO Ubicacion (Pais, Ciudad, Calle, Numero) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $this->Pais, $this->Ciudad, $this->Calle, $this->Numero);
        }
        
        $resultado = $stmt->execute();

        // Si la inserción fue exitosa, guardamos el nuevo ID
        if ($resultado && $this->IdUbicacion === null) {
            $this->IdUbicacion = $conn->insert_id;
        }

        $stmt->close();
        $conn->close();
        return $resultado;
    }

    // Método estático para obtener una ubicación por su ID
    public static function obtenerPorId(int $IdUbicacion): ?ubicacion
    {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();

        $stmt = $conn->prepare("SELECT * FROM Ubicacion WHERE IdUbicacion = ?");
        $stmt->bind_param('i', $IdUbicacion);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        $stmt->close();
        $conn->close();

        if ($fila) {
            return new ubicacion(
                $fila['IdUbicacion'],
                $fila['Pais'],
                $fila['Ciudad'],
                $fila['Calle'],
                $fila['Numero']
            );
        }
        return null;
    }
}