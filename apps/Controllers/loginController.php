<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

        // Debug: loguear los valores recibidos
        error_log('Login recibido - Email: ' . $email . ' | Password: ' . $password);
    
    try {

        // Autenticar usuario (verifica email y contraseña)
        $usuario = Usuario::autenticar($email, $password);
        if (!$usuario) {
            echo json_encode([
                'success' => false,
                'message' => 'Credenciales inválidas',
                'debug_email' => $email,
                'debug_password' => $password
            ]);
            exit;
        }

        // Activar cuenta al iniciar sesión (sin importar el estado anterior)
        $usuario->cambiarEstadoCuenta(true);
        error_log("Cuenta activada para usuario ID: " . $usuario->getIdUsuario());

        // Obtener el rol usando el nuevo método
        $rol = Usuario::obtenerRolPorEmail($email);
        
        if (!$rol) {
            echo json_encode(['success' => false, 'message' => 'No se pudo determinar el rol del usuario']);
            exit;
        }

        // Guardar datos en sesión
        $_SESSION['IdUsuario'] = $usuario->getIdUsuario();
        $_SESSION['usuario_nombre'] = $usuario->getNombre();
        $_SESSION['usuario_rol'] = $rol;
        $_SESSION['usuario_estado'] = 'ACTIVO';
        
        // Determinar ruta de redirección según el rol
        $ruta = '';
        switch ($rol) {
            case 'Proveedor':
                $ruta = '/proyecto/apps/Views/PANTALLA_PUBLICAR.html';
                break;
            case 'Cliente':
                $ruta = '/proyecto/apps/Views/PANTALLA_CONTRATAR.html';
                break;
            case 'Administrador':
                $ruta = '/proyecto/apps/Views/PANTALLA_ADMIN.html';
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Rol no válido']);
                exit;
        }

        // Enviar respuesta exitosa con la ruta de redirección
        echo json_encode([
            'success' => true,
            'redirect' => $ruta,
            'rol' => $rol,
            'nombre' => $usuario->getNombre(),
            'idUsuario' => $usuario->getIdUsuario()
        ]);
        exit();
        
    } catch (Exception $e) {
        error_log("Error en login: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}