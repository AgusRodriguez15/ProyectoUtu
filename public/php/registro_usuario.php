<?php
require_once '../../apps/Models/usuario.php';

class usuarioController {

    public function registrarUsuario() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre   = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $tipo     = trim($_POST['tipoRegistro'] ?? '');

            if ($nombre && $apellido && $email && $password && $tipo) {
                // Crear usuario
                $usuario = new usuario(
                    null,
                    $nombre,
                    $apellido,
                    $email,
                    $password,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $tipo
                );

                // Registrar usuario
                if ($usuario->registrarUsuario()) {
                    session_start();
                    $_SESSION['Email'] = $usuario->getEmail();
                    $_SESSION['Rol']   = $usuario->getRol();
                    $_SESSION['IdUsuario'] = $usuario->getIdUsuario();

                    // Redirigir según rol
                    $this->redirigirPorRol($usuario->getRol());
                    exit();
                } else {
                    echo 'Error: no se pudo registrar el usuario o ya existe.';
                }
            } else {
                echo 'Faltan datos obligatorios.';
            }
        } else {
            echo 'Método no permitido. Debe ser POST.';
        }
    }

    private function redirigirPorRol(string $rol) {
        switch (strtoupper($rol)) {
            case strtoupper(usuario::ROL_ADMIN):
                header('Location: /proyecto/apps/Views/PANTALLA_ADMIN.html');
                break;
            case strtoupper(usuario::ROL_PROVEEDOR):
                header('Location: /proyecto/apps/Views/PANTALLA_PUBLICAR.html');
                break;
            case strtoupper(usuario::ROL_CLIENTE):
                header('Location: /proyecto/apps/Views/PANTALLA_CONTRATAR.html');
                break;
            default:
                header('Location: /proyecto/public/php/login_usuario.php');
        }
    }
}

// Ejecutar registro si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new usuarioController();
    $controller->registrarUsuario();
}
?>