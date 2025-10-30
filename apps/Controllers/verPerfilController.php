<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/habilidad.php';
require_once __DIR__ . '/../Models/ubicacion.php';
require_once __DIR__ . '/../Models/proveedor.php';
require_once __DIR__ . '/../Models/servicio.php';

session_start();

try {
    // ✅ NUEVO: Validación si se pide verificar un email existente
    if (isset($_GET['action']) && $_GET['action'] === 'checkEmail') {
        if (!isset($_GET['email']) || empty($_GET['email'])) {
            throw new Exception("Email no proporcionado");
        }

        $email = trim($_GET['email']);

        // Verificar si el email ya existe en la base de datos
        $usuarioExistente = usuario::obtenerPor('Email', $email);
        $exists = $usuarioExistente ? true : false;

        echo json_encode(['exists' => $exists]);
        exit;
    }

    // Obtener el ID del usuario a ver desde GET
    if (!isset($_GET['id'])) {
        throw new Exception("ID de usuario no proporcionado");
    }

    $idUsuario = (int)$_GET['id'];
    
    // Obtener datos del usuario
    $usuario = usuario::obtenerPor('IdUsuario', $idUsuario);
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }

    // Datos básicos del usuario
    $response = [
        'success' => true,
        'usuario' => [
            'id' => $usuario->getIdUsuario(),
            'nombre' => $usuario->getNombre(),
            'apellido' => $usuario->getApellido(),
            'descripcion' => $usuario->getDescripcion(),
            'rutaFoto' => $usuario->getFotoPerfil(),
            'rol' => $usuario->getRol(),
            'fechaRegistro' => $usuario->getFechaRegistro()
        ]
    ];

    // Obtener ubicación si existe
    $ubicacionData = null;
    if ($usuario->getIdUbicacion()) {
        $ubicacion = ubicacion::obtenerPorId($usuario->getIdUbicacion());
        if ($ubicacion) {
            $ubicacionData = [
                'pais' => $ubicacion->getPais(),
                'ciudad' => $ubicacion->getCiudad(),
                'calle' => $ubicacion->getCalle(),
                'numero' => $ubicacion->getNumero()
            ];
        }
    }
    $response['ubicacion'] = $ubicacionData;

    // Obtener contactos
    $contactos = dato::obtenerPorUsuario($usuario->getIdUsuario());
    $response['contactos'] = $contactos;

    // Determinar si el perfil es editable por el usuario en sesión (dueño) o por un administrador
    $response['editable'] = false;
    if (isset($_SESSION['IdUsuario'])) {
        $sesId = $_SESSION['IdUsuario'];
        require_once __DIR__ . '/../Models/usuario.php';
        $usuarioSesion = usuario::obtenerPor('IdUsuario', $sesId);
        if ($usuarioSesion) {
            if ($usuarioSesion->getRol() === 'Administrador' || $sesId == $usuario->getIdUsuario()) {
                $response['editable'] = true;
            }
        }
    }

    // Si es proveedor, obtener habilidades y servicios
    if ($usuario->getRol() === 'Proveedor') {
        $habilidades = habilidad::obtenerPorUsuario($usuario->getIdUsuario());
        $response['habilidades'] = $habilidades;

        // Obtener servicios del proveedor
        $proveedor = proveedor::obtenerPorIdUsuario($usuario->getIdUsuario());
        if ($proveedor) {
            $servicios = Servicio::obtenerPorProveedor($proveedor->getIdUsuario());
            $response['servicios'] = $servicios;
            $response['aniosExperiencia'] = $proveedor->getAniosExperiencia();
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en verPerfilController: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
