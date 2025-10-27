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
    $conexionDB = new ConexionDB();
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
    $conexionDB = new ConexionDB();
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
        error_log("🚀 INICIO crearYAsociarAServicio - Servicio ID: {$idServicio}");
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();
        
        try {
            error_log("📦 crearYAsociarAServicio - Servicio: {$idServicio}, Datos: " . json_encode($datosUbicacion));
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            // Normalizar valores (todos los campos son opcionales excepto país)
            $pais = isset($datosUbicacion['pais']) ? trim($datosUbicacion['pais']) : '';
            $ciudad = isset($datosUbicacion['ciudad']) ? trim($datosUbicacion['ciudad']) : '';
            $calle = isset($datosUbicacion['calle']) ? trim($datosUbicacion['calle']) : '';
            $numero = isset($datosUbicacion['numero']) ? trim($datosUbicacion['numero']) : '';
            
            error_log("Valores normalizados - País: '{$pais}', Ciudad: '{$ciudad}', Calle: '{$calle}', Número: '{$numero}'");
            
            // Validar que país no esté vacío
            if (empty($pais)) {
                error_log("ERROR: El país es obligatorio pero está vacío");
                throw new Exception("El país es obligatorio");
            }
            
            // Validar jerarquía: si un campo tiene valor, el anterior no puede estar vacío
            if (!empty($calle) && empty($ciudad)) {
                error_log("ERROR: Se especificó calle sin ciudad");
                throw new Exception("Si especificas una calle, debes especificar la ciudad");
            }
            
            if (!empty($numero) && empty($calle)) {
                error_log("ERROR: Se especificó número sin calle");
                throw new Exception("Si especificas un número, debes especificar la calle");
            }
            
            // Construir la dirección completa (se guarda en el campo Calle)
            $direccionCompleta = '';
            if (!empty($calle)) {
                $direccionCompleta = $calle;
                if (!empty($numero)) {
                    $direccionCompleta .= ' ' . $numero;
                }
            }
            
            error_log("Dirección completa construida: '{$direccionCompleta}'");
            
            // Convertir valores para comparación en BD (vacíos a NULL)
            $calleComparar = !empty($direccionCompleta) ? $direccionCompleta : null;
            $ciudadComparar = !empty($ciudad) ? $ciudad : null;
            $numeroComparar = !empty($numero) ? intval($numero) : null;
            
            // Verificar si ya existe una ubicación idéntica para este servicio
            $sqlCheck = "SELECT us.IdUbicacion FROM ServicioUbicacion us 
                        INNER JOIN Ubicacion u ON us.IdUbicacion = u.IdUbicacion 
                        WHERE us.IdServicio = ? 
                        AND u.Pais = ?
                        AND (u.Calle <=> ?)
                        AND (u.Ciudad <=> ?)
                        AND (u.Numero <=> ?)";
            
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('isssi', 
                $idServicio, 
                $pais,
                $calleComparar,
                $ciudadComparar,
                $numeroComparar
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
            
            // Buscar si existe una ubicación idéntica en la tabla Ubicacion (usando operador NULL-safe)
            $sqlFind = "SELECT IdUbicacion FROM Ubicacion 
                       WHERE Pais = ?
                       AND (Calle <=> ?)
                       AND (Ciudad <=> ?)
                       AND (Numero <=> ?)";
            
            $stmtFind = $conn->prepare($sqlFind);
            $stmtFind->bind_param('sssi', 
                $pais,
                $calleComparar,
                $ciudadComparar,
                $numeroComparar
            );
            $stmtFind->execute();
            $resultFind = $stmtFind->get_result();
            
            if ($fila = $resultFind->fetch_assoc()) {
                // Si existe, usar ese ID
                $idUbicacion = $fila['IdUbicacion'];
                error_log("Ubicación existente encontrada con ID: {$idUbicacion}");
                $stmtFind->close();
            } else {
                // Si no existe, crear nueva ubicación
                $stmtFind->close();
                
                error_log("Creando nueva ubicación: País='{$pais}', Ciudad='" . ($ciudadComparar ?? 'NULL') . "', Calle='" . ($calleComparar ?? 'NULL') . "', Número=" . ($numeroComparar ?? 'NULL'));
                
                $stmt = $conn->prepare("INSERT INTO Ubicacion (Pais, Ciudad, Calle, Numero) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('sssi', $pais, $ciudadComparar, $calleComparar, $numeroComparar);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al crear ubicación: " . $stmt->error);
                }
                
                $idUbicacion = $conn->insert_id;
                error_log("Nueva ubicación creada con ID: {$idUbicacion}");
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
    $conexionDB = new ConexionDB();
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
                'pais' => $fila['Pais'],
                'ciudad' => $fila['Ciudad'],
                'calle' => $fila['Calle'],
                'numero' => $fila['Numero']
            ];
        }
        
        $stmt->close();
        $conn->close();
        
        return $ubicaciones;
    }
    
    /**
     * Elimina la asociación de una ubicación con un servicio
     * @param int $idServicio ID del servicio
     * @param int $idUbicacion ID de la ubicación
     * @return bool True si se eliminó correctamente
     */
    public static function eliminarDeServicio(int $idServicio, int $idUbicacion): bool
    {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();
        
        try {
            // Eliminar la asociación en ServicioUbicacion
            $stmt = $conn->prepare("DELETE FROM ServicioUbicacion WHERE IdServicio = ? AND IdUbicacion = ?");
            $stmt->bind_param('ii', $idServicio, $idUbicacion);
            $resultado = $stmt->execute();
            $stmt->close();
            
            // Verificar si esta ubicación está asociada a otros servicios
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ServicioUbicacion WHERE IdUbicacion = ?");
            $stmt->bind_param('i', $idUbicacion);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            // Si no está asociada a ningún otro servicio, eliminar la ubicación
            if ($row['total'] == 0) {
                $stmt = $conn->prepare("DELETE FROM Ubicacion WHERE IdUbicacion = ?");
                $stmt->bind_param('i', $idUbicacion);
                $stmt->execute();
                $stmt->close();
                error_log("Ubicación {$idUbicacion} eliminada de la tabla Ubicacion (no tenía más servicios asociados)");
            }
            
            $conn->close();
            return $resultado;
            
        } catch (Exception $e) {
            $conn->close();
            error_log("Error en eliminarDeServicio: " . $e->getMessage());
            return false;
        }
    }
}
