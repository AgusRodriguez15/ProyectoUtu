<?php
require_once __DIR__ . '/../Models/ConexionDB.php';
require_once __DIR__ . '/../Models/Mensaje.php';

$conexion = (new ConexionDB())->getConexion(); // ✅ Corregido
$action = $_GET['action'] ?? '';

if ($action === 'listar') {
    $idUsuario = intval($_GET['idUsuario'] ?? 0);
    $idReceptor = intval($_GET['idReceptor'] ?? 0);

    // Seguridad básica
    if (!$idUsuario || !$idReceptor) {
        exit('Faltan parámetros');
    }

    $sql = "SELECT * FROM mensaje 
            WHERE (IdUsuarioEmisor = $idUsuario AND IdUsuarioReceptor = $idReceptor)
               OR (IdUsuarioEmisor = $idReceptor AND IdUsuarioReceptor = $idUsuario)
            ORDER BY Fecha ASC";
    $result = $conexion->query($sql);

    while ($row = $result->fetch_assoc()) {
        $clase = ($row['IdUsuarioEmisor'] == $idUsuario) ? "emisor" : "receptor";
        echo "<div class='mensaje $clase'>";
        echo htmlspecialchars($row['Contenido']);
        echo "<br><small>" . htmlspecialchars($row['Fecha']) . "</small>";
        echo "</div>";
    }
    exit;
}

if ($action === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenido = trim($_POST['Contenido'] ?? '');
    $emisor = intval($_POST['IdUsuarioEmisor'] ?? 0);
    $receptor = intval($_POST['IdUsuarioReceptor'] ?? 0);

    if (!$contenido || !$emisor || !$receptor) {
        exit('Datos incompletos');
    }

    $contenidoEscapado = $conexion->real_escape_string($contenido);
    $sql = "INSERT INTO mensaje (Contenido, Fecha, Estado, IdUsuarioEmisor, IdUsuarioReceptor)
            VALUES ('$contenidoEscapado', NOW(), 'ENVIADO', $emisor, $receptor)";
    $conexion->query($sql);

    exit("OK");
}

exit("Acción no válida");
?>
