<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="../../public/CSS/style2.css">
</head>
<body>
  <div class="login-container">
    <h2>Iniciar Sesión</h2>
    <?php if (isset($_GET['error'])): ?>
        <p class="error">Credenciales inválidas</p>
    <?php endif; ?>
    <form action="../Controllers/loginController.php" method="POST">
      <label for="email">Correo electrónico</label>
      <input type="email" name="email" required>

      <label for="password">Contraseña</label>
      <input type="password" name="password" required>

      <button type="submit">Ingresar</button>
    </form>
  </div>
</body>
</html>
