<?php
/**
 * Script de migración: añade la columna `Imagen` a la tabla `mensaje` si no existe.
 * Úsalo desde CLI: php migrate_add_imagen_column.php
 * O desde navegador (temporal) accediendo a /proyecto/scripts/migrate_add_imagen_column.php
 */
require_once __DIR__ . '/../apps/Models/ConexionDB.php';

try {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    $dbName = $conn->real_escape_string('proyecto_utu');

    $sql = "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $dbName . "' AND TABLE_NAME = 'mensaje' AND COLUMN_NAME = 'Imagen'";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        if (isset($row['cnt']) && intval($row['cnt']) > 0) {
            echo "Columna Imagen ya existe en tabla mensaje.\n";
            exit(0);
        }
    }

    // Añadir columna
    $alter = "ALTER TABLE mensaje ADD COLUMN Imagen VARCHAR(255) NULL AFTER Contenido";
    if ($conn->query($alter) === TRUE) {
        echo "Columna Imagen añadida correctamente.\n";
        exit(0);
    } else {
        echo "Error al añadir columna Imagen: " . $conn->error . "\n";
        exit(2);
    }
} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
    exit(3);
}

