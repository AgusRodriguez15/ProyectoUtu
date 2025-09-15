<?php
require_once __DIR__ . '/../Models/usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Get email and password from the login form
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 2. Authenticate the user using the static method
    if (\usuario::autenticar($email, $password)) {
        // 3. Redirect to the appropriate dashboard on success
        // You would need to retrieve the user's role from the database here to redirect correctly.
        // For simplicity, we'll redirect to a generic page for now.
        header("Location: /proyecto/apps/Views/PANTALLA_CONTRATAR.php   ");
        exit;
    } else {
        // 4. Handle failed login
        echo "Error: Correo electrónico o contraseña incorrectos.";
    }
}
?>