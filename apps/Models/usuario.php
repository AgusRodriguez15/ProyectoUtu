<?php
require_once __DIR__ . '/ConexionDB.php';

class usuario
{
    // Roles como constantes
    public const ROL_CLIENTE = 'Cliente';
    public const ROL_PROVEEDOR = 'Proveedor';
    public const ROL_ADMIN = 'Administrador';

    // Atributos privados
    private ?int $IdUsuario;
    private string $Nombre;
    private string $Apellido;
    private string $Email;
    private string $ContrasenaHash;
    private ?string $FotoPerfil;
    private ?string $Descripcion;
    private ?string $FechaRegistro;
    private ?string $EstadoCuenta;
    private ?string $UltimoAcceso;
    private string $Rol;
    private ?int $IdUbicacion;

    // Constructor
    public function __construct(
        ?int $IdUsuario,
        string $Nombre,
        string $Apellido,
        string $Email,
        string $ContrasenaHash,
        ?string $FotoPerfil,
        ?string $Descripcion,
        ?string $FechaRegistro,
        ?string $EstadoCuenta,
        ?string $UltimoAcceso,
        string $Rol,
        ?int $IdUbicacion = null
    ) {
        $this->IdUsuario = $IdUsuario;
        $this->Nombre = $Nombre;
        $this->Apellido = $Apellido;
        $this->Email = $Email;
        $this->ContrasenaHash = $ContrasenaHash;
        $this->FotoPerfil = $FotoPerfil;
        $this->Descripcion = $Descripcion;
        $this->FechaRegistro = $FechaRegistro;
        $this->EstadoCuenta = $EstadoCuenta;
        $this->UltimoAcceso = $UltimoAcceso;
        $this->Rol = $Rol;
        $this->IdUbicacion = $IdUbicacion;
    }

    // ===== GETTERS =====
    public function getIdUsuario(): ?int
    {
        return $this->IdUsuario;
    }
    public function getNombre(): string
    {
        return $this->Nombre;
    }
    public function getApellido(): string
    {
        return $this->Apellido;
    }
    public function getEmail(): string
    {
        return $this->Email;
    }
    public function getContrasenaHash(): string
    {
        return $this->ContrasenaHash;
    }
    public function getFotoPerfil(): ?string
    {
        return $this->FotoPerfil;
    }
    public function getDescripcion(): ?string
    {
        return $this->Descripcion;
    }
    public function getFechaRegistro(): ?string
    {
        return $this->FechaRegistro;
    }
    public function getEstadoCuenta(): ?string
    {
        return $this->EstadoCuenta;
    }
    public function getUltimoAcceso(): ?string
    {
        return $this->UltimoAcceso;
    }
    public function getRol(): string
    {
        return $this->Rol;
    }
    public function getIdUbicacion(): ?int
    {
        return $this->IdUbicacion;
    }

    // ===== MÉTODOS ESTÁTICOS =====
    public static function obtenerRolPorEmail(string $email): ?string {
        $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        // Primero obtener el ID del usuario
        $stmt = $conn->prepare("SELECT IdUsuario FROM Usuario WHERE Email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $rol = null;
        if ($fila = $resultado->fetch_assoc()) {
            $idUsuario = $fila['IdUsuario'];
            $stmt->close();

            // Verificar si es Proveedor
            $stmt = $conn->prepare("SELECT 1 FROM Proveedor WHERE IdUsuario = ?");
            $stmt->bind_param('i', $idUsuario);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $rol = 'Proveedor';
            }
            $stmt->close();

            // Verificar si es Cliente
            if (!$rol) {
                $stmt = $conn->prepare("SELECT 1 FROM Cliente WHERE IdUsuario = ?");
                $stmt->bind_param('i', $idUsuario);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $rol = 'Cliente';
                }
                $stmt->close();
            }

            // Verificar si es Administrador
            if (!$rol) {
                $stmt = $conn->prepare("SELECT 1 FROM Administrador WHERE IdUsuario = ?");
                $stmt->bind_param('i', $idUsuario);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $rol = 'Administrador';
                }
                $stmt->close();
            }
        }

        return $rol;
    }

    // ===== SETTERS =====
    public function setNombre(string $Nombre): void
    {
        $this->Nombre = $Nombre;
    }
    public function setApellido(string $Apellido): void
    {
        $this->Apellido = $Apellido;
    }
    public function setEmail(string $Email): void
    {
        $this->Email = $Email;
    }
    public function setContrasenaHash(string $ContrasenaHash): void
    {
        $this->ContrasenaHash = $ContrasenaHash;
    }
    public function setFotoPerfil(?string $FotoPerfil): void
    {
        $this->FotoPerfil = $FotoPerfil;
    }
    public function setDescripcion(?string $Descripcion): void
    {
        $this->Descripcion = $Descripcion;
    }
    public function setFechaRegistro(?string $FechaRegistro): void
    {
        $this->FechaRegistro = $FechaRegistro;
    }
    public function setEstadoCuenta(?string $EstadoCuenta): void
    {
        $this->EstadoCuenta = $EstadoCuenta;
    }
    public function setUltimoAcceso(?string $UltimoAcceso): void
    {
        $this->UltimoAcceso = $UltimoAcceso;
    }
    public function setRol(string $Rol): void
    {
        $this->Rol = $Rol;
    }
    public function setIdUbicacion(?int $IdUbicacion): void
    {
        $this->IdUbicacion = $IdUbicacion;
    }

    // ===== SUBIR FOTO =====
    public function subirFotoPerfil(?array $archivo): void
    {
        if ($archivo && !empty($archivo['tmp_name'])) {
            $nombreArchivo = basename($archivo['name']);
            $rutaDestino = __DIR__ . '/../../public/recursos/imagenes/perfil/' . $nombreArchivo;
            move_uploaded_file($archivo['tmp_name'], $rutaDestino);
            // Guardar solo el nombre del archivo, no la ruta completa
            $this->FotoPerfil = $nombreArchivo;
        }
    }

    // ===== REGISTRAR USUARIO =====
    public function registrarUsuario(?int $AniosExperiencia = null): bool
    {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        if (empty($this->Nombre) || empty($this->Apellido) || empty($this->Email) || empty($this->ContrasenaHash) || empty($this->Rol)) {
            return false;
        }

        // Validar email único
        $stmt = $conn->prepare("SELECT IdUsuario FROM Usuario WHERE Email = ?");
        $stmt->bind_param('s', $this->Email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return false; // Email ya registrado
        }
        $stmt->close();

        // Fecha y estado
        $this->FechaRegistro = date('Y-m-d H:i:s');
        $this->EstadoCuenta = 'ACTIVO';

        // Hashear contraseña
        $ContrasenaHashDB = password_hash($this->ContrasenaHash, PASSWORD_DEFAULT);

        // Insertar usuario
        $stmt = $conn->prepare("INSERT INTO Usuario (Nombre, Apellido, Email, ContrasenaHash, FotoPerfil, Descripcion, FechaRegistro, EstadoCuenta, IdUbicacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'ssssssssi',
            $this->Nombre,
            $this->Apellido,
            $this->Email,
            $ContrasenaHashDB,
            $this->FotoPerfil,
            $this->Descripcion,
            $this->FechaRegistro,
            $this->EstadoCuenta,
            $this->IdUbicacion
        );
        $resultado = $stmt->execute();

        if ($resultado) {
            $IdUsuario = $conn->insert_id;

            // Registrar rol
            switch ($this->Rol) {
                case self::ROL_CLIENTE:
                    $conn->query("INSERT INTO Cliente (IdUsuario) VALUES ($IdUsuario)");
                    break;
                case self::ROL_PROVEEDOR:
                    $conn->query("INSERT INTO Proveedor (IdUsuario, AniosExperiencia) VALUES ($IdUsuario, " . ($AniosExperiencia ?? 0) . ")");
                    break;
                case self::ROL_ADMIN:
                    $conn->query("INSERT INTO Administrador (IdUsuario) VALUES ($IdUsuario)");
                    break;
            }

            $this->IdUsuario = $IdUsuario;
        }

        $stmt->close();
        $conn->close();
        return $resultado;
    }

    // ===== OBTENER USUARIO POR CAMPO =====
public static function obtenerPor(string $campo, $valor): ?usuario {
    $conexionDB = new ConexionDB();
    $conn = $conexionDB->getConexion();

    $stmt = $conn->prepare("SELECT * FROM Usuario WHERE $campo = ?");
    $stmt->bind_param('s', $valor);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();

    if (!$fila) {
        return null;
    }

    // Determinar rol dinámicamente
    $IdUsuario = $fila['IdUsuario'];
    $rol = 'Cliente'; // valor por defecto
    if ($conn->query("SELECT 1 FROM Proveedor WHERE IdUsuario = $IdUsuario")->num_rows > 0) {
        $rol = 'Proveedor';
    } elseif ($conn->query("SELECT 1 FROM Administrador WHERE IdUsuario = $IdUsuario")->num_rows > 0) {
        $rol = 'Administrador';
    }

    return new usuario(
        $fila['IdUsuario'],
        $fila['Nombre'],
        $fila['Apellido'],
        $fila['Email'],
        $fila['ContrasenaHash'],
        $fila['FotoPerfil'],
        $fila['Descripcion'],
        $fila['FechaRegistro'],
        $fila['EstadoCuenta'],
        $fila['UltimoAcceso'],
        $rol,
        $fila['IdUbicacion'] ?? null
    );
}


    // ===== ACTUALIZAR ÚLTIMO ACCESO =====
    public function actualizarUltimoAcceso(int $id): void
    {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE Usuario SET UltimoAcceso = ? WHERE IdUsuario = ?");
        $stmt->bind_param('si', $fecha, $id);
        $stmt->execute();

        $stmt->close();
        $conn->close();
    }

    // ===== CAMBIAR ESTADO DE CUENTA =====
    /**
     * Cambia el estado de la cuenta del usuario
     * @param bool $activo true para ACTIVO, false para INACTIVO
     * @return bool true si se actualizó correctamente, false en caso contrario
     */
    public function cambiarEstadoCuenta(bool $activo): bool
    {
        if (!$this->IdUsuario) {
            return false; // No hay usuario cargado
        }

    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        // Determinar el estado según el booleano
        $nuevoEstado = $activo ? 'ACTIVO' : 'INACTIVO';

        $stmt = $conn->prepare("UPDATE Usuario SET EstadoCuenta = ? WHERE IdUsuario = ?");
        $stmt->bind_param('si', $nuevoEstado, $this->IdUsuario);
        $resultado = $stmt->execute();

        if ($resultado) {
            // Actualizar el estado en el objeto actual
            $this->EstadoCuenta = $nuevoEstado;
        }

        $stmt->close();
        $conn->close();

        return $resultado;
    }

    /**
     * Cambia el estado de cuenta de un usuario por su ID (método estático)
     * @param int $idUsuario ID del usuario
     * @param bool $activo true para ACTIVO, false para INACTIVO
     * @return bool true si se actualizó correctamente, false en caso contrario
     */
    public static function cambiarEstadoCuentaPorId(int $idUsuario, bool $activo): bool
    {
    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        // Determinar el estado según el booleano
        $nuevoEstado = $activo ? 'ACTIVO' : 'INACTIVO';

        $stmt = $conn->prepare("UPDATE Usuario SET EstadoCuenta = ? WHERE IdUsuario = ?");
        $stmt->bind_param('si', $nuevoEstado, $idUsuario);
        $resultado = $stmt->execute();

        $stmt->close();
        $conn->close();

        return $resultado;
    }

    public function guardar(): bool
{
    if (!$this->IdUsuario) {
        return false; // No hay usuario cargado
    }

    $conexionDB = new ConexionDB();
    $conn = $conexionDB->getConexion();

    $stmt = $conn->prepare("
        UPDATE Usuario
        SET Nombre = ?, Apellido = ?, Email = ?, Descripcion = ?, FotoPerfil = ?, IdUbicacion = ?
        WHERE IdUsuario = ?
    ");

    $stmt->bind_param(
        'sssssii',
        $this->Nombre,
        $this->Apellido,
        $this->Email,
        $this->Descripcion,
        $this->FotoPerfil,
        $this->IdUbicacion,
        $this->IdUsuario
    );

    $resultado = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $resultado;
}

public static function autenticar(string $email, string $password): ?usuario {
    $conn = new ConexionDB();
    $db = $conn->getConexion();

    $stmt = $db->prepare("
        SELECT u.IdUsuario, u.Nombre, u.Apellido, u.Email, u.ContrasenaHash, 
               u.FotoPerfil, u.Descripcion, u.FechaRegistro, 
               u.EstadoCuenta, u.UltimoAcceso, u.IdUbicacion,
               CASE 
                   WHEN EXISTS (SELECT 1 FROM Proveedor p WHERE p.IdUsuario = u.IdUsuario) THEN 'Proveedor'
                   WHEN EXISTS (SELECT 1 FROM Cliente c WHERE c.IdUsuario = u.IdUsuario) THEN 'Cliente'
                   WHEN EXISTS (SELECT 1 FROM Administrador a WHERE a.IdUsuario = u.IdUsuario) THEN 'Administrador'
                   ELSE NULL
               END as Rol
        FROM Usuario u
        WHERE Email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['ContrasenaHash'])) {
            return new usuario(
                $row['IdUsuario'],
                $row['Nombre'],
                $row['Apellido'],
                $row['Email'],
                $row['ContrasenaHash'],
                $row['FotoPerfil'],
                $row['Descripcion'],
                $row['FechaRegistro'],
                $row['EstadoCuenta'],
                $row['UltimoAcceso'],
                $row['Rol'],
                $row['IdUbicacion'] ?? null
            );
        }
    }

    return null;
}

public function guardarCompleto(array $contactos = [], array $habilidades = []): bool
{
    if (!$this->IdUsuario) {
        return false; // No hay usuario cargado
    }

    // Guardar datos básicos
    $resultado = $this->guardar();

    if ($resultado) {
        require_once __DIR__ . '/dato.php';
        require_once __DIR__ . '/habilidad.php';

    $conexionDB = new ConexionDB();
        $conn = $conexionDB->getConexion();

        // Borrar contactos previos
        $stmt = $conn->prepare("DELETE FROM DatosContacto WHERE IdUsuario = ?");
        $stmt->bind_param('i', $this->IdUsuario);
        $stmt->execute();
        $stmt->close();

        // Guardar nuevos contactos
        foreach ($contactos as $contacto) {
            if (!empty($contacto['tipo']) && !empty($contacto['valor'])) {
                $dato = new dato($this->IdUsuario, $contacto['tipo'], $contacto['valor']);
                $dato->guardar();
            }
        }

        // Borrar habilidades previas
        $stmt = $conn->prepare("DELETE FROM Habilidades WHERE IdUsuario = ?");
        $stmt->bind_param('i', $this->IdUsuario);
        $stmt->execute();
        $stmt->close();

        // Guardar nuevas habilidades
        foreach ($habilidades as $habilidadData) {
            if (!empty($habilidadData['nombre']) && isset($habilidadData['anios'])) {
                $hab = new habilidad($this->IdUsuario, $habilidadData['nombre'], intval($habilidadData['anios']));
                $hab->guardar();
            }
        }

        $conn->close();
    }

    return $resultado;
}

    public static function obtenerFotoPerfil($idUsuario) {
        $db = new ConexionDB();
        $conn = $db->getConexion();
        $stmt = $conn->prepare("SELECT FotoPerfil FROM Usuario WHERE IdUsuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $foto = null;
        if ($fila = $resultado->fetch_assoc()) {
            $foto = $fila['FotoPerfil'] ?? null;
        }
        $stmt->close();
        return $foto;
    }
}
?>