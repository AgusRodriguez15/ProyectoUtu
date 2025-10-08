<?php
require_once "ClaseConexion.php";
require_once "mensaje.php";

$conexion = (new ClaseConexion())->getConexion();

$action = $_GET['action'] ?? '';

if ($action === 'listar') {
    $idUsuario = intval($_GET['idUsuario']);
    $sql = "SELECT * FROM mensaje WHERE IdUsuarioEmisor = $idUsuario OR IdUsuarioReceptor = $idUsuario ORDER BY Fecha ASC";
    $result = $conexion->query($sql);

    while ($row = $result->fetch_assoc()) {
        $clase = ($row['IdUsuarioEmisor'] == $idUsuario) ? "emisor" : "receptor";
        echo "<div class='mensaje $clase'>";
        echo htmlspecialchars($row['Contenido']);
        echo "<small>{$row['Fecha']}</small>";
        echo "</div>";
    }
    exit;
}

if ($action === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenido = $conexion->real_escape_string($_POST['Contenido']);
    $emisor = intval($_POST['IdUsuarioEmisor']);
    $receptor = intval($_POST['IdUsuarioReceptor']);

    $sql = "INSERT INTO mensaje (Contenido, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor) 
            VALUES ('$contenido', NOW(), 'enviado', $emisor, $receptor)";
    $conexion->query($sql);
    exit("OK");
}
