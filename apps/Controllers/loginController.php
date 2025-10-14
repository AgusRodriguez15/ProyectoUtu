<?php
session_start();
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Obtener usuario y verificar credenciales
        $usuario = Usuario::obtenerPor('Email', $email);
        
        if (!$usuario) {
            $_SESSION['error'] = 'Credenciales inválidas';
            header('Location: /proyecto/public/index.html');
            exit;
        }

        if (!password_verify($password, $usuario->getContrasenaHash())) {
            $_SESSION['error'] = 'Credenciales inválidas';
            header('Location: /proyecto/public/index.html');
            exit;
        }

        // Verificar si la cuenta está activa
        if ($usuario->getEstadoCuenta() !== 'ACTIVO') {
            $_SESSION['error'] = 'Cuenta inactiva';
            header('Location: /proyecto/public/index.html');
            exit;
        }

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
        
        switch ($rol) {
            case 'Proveedor':
                header('Location: /proyecto/apps/Views/PANTALLA_PUBLICAR.html');
                break;
            case 'Cliente':
                 header('Location: /proyecto/apps/Views/PANTALLA_CONTRATAR.html');
                break;
            case 'Administrador':
                header('Location: /proyecto/apps/Views/PANTALLA_ADMIN.html');
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Rol no válido']);
                exit;
        }

        // Enviar respuesta exitosa con la ruta de redirección
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'redirect' => $ruta,
            'rol' => $rol,
            'nombre' => $usuario->getNombre()
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