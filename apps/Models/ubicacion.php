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

    /**
     * Crea una ubicación y la asocia a un servicio
     * @param int $idServicio ID del servicio
     * @param array $datosUbicacion Array con los datos de la ubicación (todos opcionales)
     * @return int|false ID de la ubicación creada o false en caso de error
     */
    public static function crearYAsociarAServicio(int $idServicio, array $datosUbicacion)
    {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();
        
        try {
            // Iniciar transacción
            $conn->begin_transaction();
            
            // Normalizar valores (todos los campos son opcionales excepto país)
            $pais = isset($datosUbicacion['pais']) ? trim($datosUbicacion['pais']) : '';
            $ciudad = isset($datosUbicacion['ciudad']) ? trim($datosUbicacion['ciudad']) : '';
            $calle = isset($datosUbicacion['calle']) ? trim($datosUbicacion['calle']) : '';
            $numero = isset($datosUbicacion['numero']) ? trim($datosUbicacion['numero']) : '';
            
            // Validar que país no esté vacío
            if (empty($pais)) {
                throw new Exception("El país es obligatorio");
            }
            
            // Validar jerarquía: si un campo tiene valor, el anterior no puede estar vacío
            if (!empty($calle) && empty($ciudad)) {
                throw new Exception("Si especificas una calle, debes especificar la ciudad");
            }
            
            if (!empty($numero) && empty($calle)) {
                throw new Exception("Si especificas un número, debes especificar la calle");
            }
            
            // Construir la dirección completa
            $direccionCompleta = '';
            if (!empty($calle)) {
                $direccionCompleta = $calle;
                if (!empty($numero)) {
                    $direccionCompleta .= ' ' . $numero;
                }
            } else if (!empty($numero)) {
                $direccionCompleta = 'N° ' . $numero;
            }
            
            // Verificar si ya existe una ubicación idéntica para este servicio
            $sqlCheck = "SELECT us.IdUbicacion FROM ServicioUbicacion us 
                        INNER JOIN Ubicacion u ON us.IdUbicacion = u.IdUbicacion 
                        WHERE us.IdServicio = ? 
                        AND COALESCE(u.Pais, '') = ?
                        AND COALESCE(u.Calle, '') = ?
                        AND COALESCE(u.Ciudad, '') = ?
                        AND COALESCE(u.Numero, '') = ?";
            
            $stmtCheck = $conn->prepare($sqlCheck);
            $numeroInt = !empty($numero) ? intval($numero) : null;
            $stmtCheck->bind_param('isssi', 
                $idServicio, 
                $pais,
                $direccionCompleta,
                $ciudad,
                $numeroInt
            );
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            
            if ($resultCheck->num_rows > 0) {
                // Ya existe esta ubicación para este servicio
                $stmtCheck->close();
                $conn->rollback();
                $conn->close();
                error_log("Ubicación duplicada detectada para servicio {$idServicio}");
                return false;
            }
            $stmtCheck->close();
            
            // Buscar si existe una ubicación idéntica en la tabla Ubicacion
            $sqlFind = "SELECT IdUbicacion FROM Ubicacion 
                       WHERE COALESCE(Pais, '') = ?
                       AND COALESCE(Calle, '') = ?
                       AND COALESCE(Ciudad, '') = ?
                       AND COALESCE(Numero, '') = ?";
            
            $stmtFind = $conn->prepare($sqlFind);
            $stmtFind->bind_param('sssi', 
                $pais,
                $direccionCompleta,
                $ciudad,
                $numeroInt
            );
            $stmtFind->execute();
            $resultFind = $stmtFind->get_result();
            $stmtFind->close();
            
            if ($fila = $resultFind->fetch_assoc()) {
                // Si existe, usar ese ID
                $idUbicacion = $fila['IdUbicacion'];
            } else {
                // Si no existe, crear nueva ubicación
                // Convertir strings vacíos a NULL para la BD
                $calleNull = !empty($direccionCompleta) ? $direccionCompleta : null;
                $ciudadNull = !empty($ciudad) ? $ciudad : null;
                $numeroInt = !empty($numero) ? intval($numero) : null;
                
                $stmt = $conn->prepare("INSERT INTO Ubicacion (Pais, Ciudad, Calle, Numero) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('sssi', $pais, $ciudadNull, $calleNull, $numeroInt);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al crear ubicación: " . $stmt->error);
                }
                
                $idUbicacion = $conn->insert_id;
                $stmt->close();
            }
            
            // Asociar ubicación con servicio
            $stmt = $conn->prepare("INSERT INTO ServicioUbicacion (IdServicio, IdUbicacion) VALUES (?, ?)");
            $stmt->bind_param('ii', $idServicio, $idUbicacion);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al asociar ubicación con servicio: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Confirmar transacción
            $conn->commit();
            $conn->close();
            
            return $idUbicacion;
            
        } catch (Exception $e) {
            // Revertir en caso de error
            $conn->rollback();
            $conn->close();
            error_log("Error en crearYAsociarAServicio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todas las ubicaciones asociadas a un servicio
     * @param int $idServicio ID del servicio
     * @return array Array de ubicaciones
     */
    public static function obtenerPorServicio(int $idServicio): array
    {
        $conexionDB = new ClaseConexion();
        $conn = $conexionDB->getConexion();
        
        $sql = "SELECT u.* FROM Ubicacion u 
                INNER JOIN ServicioUbicacion us ON u.IdUbicacion = us.IdUbicacion 
                WHERE us.IdServicio = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $idServicio);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $ubicaciones = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ubicaciones[] = [
                'idUbicacion' => $fila['IdUbicacion'],
                'direccion' => $fila['Direccion'],
                'ciudad' => $fila['Ciudad'],
                'departamento' => $fila['Departamento'],
                'latitud' => $fila['Latitud'],
                'longitud' => $fila['Longitud']
            ];
        }
        
        $stmt->close();
        $conn->close();
        
        return $ubicaciones;
    }
}
