<?php
// Controlador para Usuario
require_once __DIR__ . '/../Models/usuario.php';

class UsuarioController {
    // Método para registrar usuario con validaciones
    public function registrarUsuario($datos, $contrasena2, $aniosExperiencia = null) {
        // Validar campos obligatorios
        $requeridos = ['Nombre', 'Apellido', 'Email', 'ContrasenaHash', 'Rol'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['ok' => false, 'error' => 'Falta el campo: ' . $campo];
            }
        }
        // Validar doble contraseña
        if ($datos['ContrasenaHash'] !== $contrasena2) {
            return ['ok' => false, 'error' => 'Las contraseñas no coinciden'];
        }
        // Validar email válido
        if (!filter_var($datos['Email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Email no válido'];
        }
        // Validar email no repetido
        require_once __DIR__ . '/../Models/ConexionDB.php';
        $conexionDB = new \ClaseConexion();
        $conn = $conexionDB->getConexion();
        $stmt = $conn->prepare('SELECT IdUsuario FROM Usuario WHERE Email = ?');
        $stmt->bind_param('s', $datos['Email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return ['ok' => false, 'error' => 'El email ya está registrado'];
        }
        $stmt->close();
        $conn->close();
        // Crear objeto usuario y registrar
        $usuario = new \usuario(
            null,
            $datos['Nombre'],
            $datos['Apellido'],
            $datos['Email'],
            $datos['ContrasenaHash'],
            $datos['FotoPerfil'] ?? null,
            $datos['Descripcion'] ?? null,
            null, // FechaRegistro
            null, // EstadoCuenta
            null, // UltimoAcceso
            $datos['Rol'] ?? null
        );
        if (isset($datos['IdUbicacion'])) {
            $usuario->IdUbicacion = $datos['IdUbicacion'];
        }
        $ok = $usuario->registrarUsuario($aniosExperiencia);
        if ($ok) {
            return ['ok' => true];
        } else {
            return ['ok' => false, 'error' => 'Error al registrar usuario'];
        }
    }
}
