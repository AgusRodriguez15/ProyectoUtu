<?php
// Crea el directorio logs y un archivo mensaje_controller.log si no existen.
$logDir = __DIR__ . '/../logs';
$logFile = $logDir . '/mensaje_controller.log';

if (!is_dir($logDir)) {
    if (@mkdir($logDir, 0755, true)) {
        echo "Directorio de logs creado: $logDir\n";
    } else {
        echo "No se pudo crear el directorio de logs: $logDir (comprueba permisos)\n";
    }
}

if (!file_exists($logFile)) {
    $ok = @file_put_contents($logFile, "# Log de mensaje_controller - creado el " . date('Y-m-d H:i:s') . "\n");
    if ($ok !== false) echo "Archivo de log creado: $logFile\n";
    else echo "No se pudo crear el archivo de log: $logFile (comprueba permisos)\n";
} else {
    echo "Archivo de log ya existe: $logFile\n";
}

?>
