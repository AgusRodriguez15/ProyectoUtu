<?php
require_once __DIR__ . '/apps/Models/dato.php';
require_once __DIR__ . '/apps/Models/habilidad.php';

$idUsuario = 34; // El ID del proveedor que estamos probando

echo "<h2>Probando datos para usuario ID: $idUsuario</h2>";

echo "<h3>Contactos:</h3>";
try {
    $contactos = dato::obtenerPorUsuario($idUsuario);
    echo "Total contactos encontrados: " . count($contactos) . "<br>";
    foreach ($contactos as $c) {
        echo "- Tipo: " . $c->Tipo . ", Contacto: " . $c->Contacto . "<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Habilidades:</h3>";
try {
    $habilidades = habilidad::obtenerPorUsuario($idUsuario);
    echo "Total habilidades encontradas: " . count($habilidades) . "<br>";
    foreach ($habilidades as $h) {
        if (is_array($h)) {
            echo "- Habilidad: " . $h['Habilidad'] . ", Años: " . $h['AniosExperiencia'] . "<br>";
        } else {
            echo "- Habilidad: " . $h->Habilidad . ", Años: " . $h->AniosExperiencia . "<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Verificar en base de datos:</h3>";
require_once __DIR__ . '/apps/Models/ConexionDB.php';
$db = new ClaseConexion();
$conn = $db->getConexion();

echo "<h4>Tabla Dato:</h4>";
$result = $conn->query("SELECT * FROM Dato WHERE IdUsuario = $idUsuario");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- " . print_r($row, true) . "<br>";
    }
} else {
    echo "No hay registros en la tabla Dato para este usuario.<br>";
}

echo "<h4>Tabla Habilidad:</h4>";
$result = $conn->query("SELECT * FROM Habilidad WHERE IdUsuario = $idUsuario");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- " . print_r($row, true) . "<br>";
    }
} else {
    echo "No hay registros en la tabla Habilidad para este usuario.<br>";
}

$conn->close();
?>
