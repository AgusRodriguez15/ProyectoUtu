<?php
session_start();

echo "<h2>Información de Sesión</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "IdUsuario: " . ($_SESSION['IdUsuario'] ?? 'NO DEFINIDO') . "\n";
echo "usuario_nombre: " . ($_SESSION['usuario_nombre'] ?? 'NO DEFINIDO') . "\n";
echo "usuario_rol: " . ($_SESSION['usuario_rol'] ?? 'NO DEFINIDO') . "\n";
echo "\n--- Todas las variables de sesión ---\n";
print_r($_SESSION);
echo "</pre>";
?>
