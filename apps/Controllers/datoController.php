<?php
session_start();
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/usuario.php';

// Verificar sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login_usuario.php");
    exit;
}

$IdUsuario = $_SESSION['IdUsuario'];
$error = '';
$mensaje = '';

// ===== AGREGAR CONTACTO =====
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $tipo = $_POST['Tipo'] ?? '';
    $contacto = $_POST['Contacto'] ?? null; // ahora puede ser null

    if (!empty($tipo)) {
        $dato = new dato($IdUsuario, $tipo, $contacto);
        if ($dato->guardar()) {
            $mensaje = "Contacto agregado correctamente";
        } else {
            $error = "No se pudo agregar el contacto";
        }
    } else {
        $error = "El campo Tipo es obligatorio";
    }
}

// ===== ELIMINAR CONTACTO =====
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $tipo = $_GET['tipo'] ?? '';
    $contacto = $_GET['contacto'] ?? null;

    if (!empty($tipo)) {
        $dato = new dato($IdUsuario, $tipo, $contacto);
        if ($dato->eliminar()) {
            $mensaje = "Contacto eliminado correctamente";
        } else {
            $error = "No se pudo eliminar el contacto";
        }
    }
}

// ===== LISTAR CONTACTOS =====
$contactos = dato::obtenerPorUsuario($IdUsuario);

?>
