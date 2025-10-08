<?php
require_once '../Models/ConexionDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $tipo = $_POST['tipoRegistro'] ?? '';

    if ($nombre && $apellido && $email && $password && $tipo) {
        $conn = new ClaseConexion();
        $db = $conn->getConexion();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $estadoCuenta = 'ACTIVO';
        $stmt = $db->prepare('INSERT INTO usuario (Nombre, Apellido, Email, ContrasenaHash, FechaRegistro, EstadoCuenta) VALUES (?, ?, ?, ?, NOW(), ?)');
        if (!$stmt) {
            echo 'Error en prepare: ' . $db->error;
            exit();
        }
        if (!$stmt->bind_param('sssss', $nombre, $apellido, $email, $passwordHash, $estadoCuenta)) {
            echo 'Error en bind_param: ' . $stmt->error;
            exit();
        }
        if ($stmt->execute()) {
            header('Location: ../../index.html?registro=ok');
            exit();
        } else {
            echo 'Error al registrar usuario: ' . $stmt->error;
        }
        $stmt->close();
        $db->close();
    } else {
        echo 'Faltan datos obligatorios.';
    }
} else {
    echo 'Método no permitido.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Usuario</title>
  <link rel="stylesheet" href="../../CSS/style2.css">
  <!-- ...resto del head y código PHP... -->
