<?php
session_start();

// Desactivar cuenta al cerrar sesión
if (isset($_SESSION['IdUsuario'])) {
    require_once '../../apps/Models/ConexionDB.php';
    require_once '../../apps/Models/usuario.php';
    
    $idUsuario = $_SESSION['IdUsuario'];
    usuario::cambiarEstadoCuentaPorId($idUsuario, false);
    error_log("Cuenta desactivada para usuario ID: $idUsuario");
}

session_unset();
session_destroy();
header("Location: ../../public/index.html");
exit;
?>