<?php
require_once "../../apps/Models/servicio.php";
$servicios = Servicio::obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Oficios</title>
    <link rel="stylesheet" href="../../public/CSS/estilos_generales.css">
    <link rel="stylesheet" href="../../public/CSS/pantalla.css"> <!-- CSS adicional -->
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <h1>Plataforma de Oficios</h1>
        </div>
        <nav class="nav">
            <a href="#">Servicios</a>
            <a href="perfilUsuario.php">Mi Perfil</a>
            <a href="#">Mensajes</a>
        </nav>
    </header>

    <!-- Hero con buscador -->
    <section class="hero">
        <div class="hero-content">
            <h2>Conecta Proveedores con Clientes</h2>
            <p>Encuentra el servicio que necesitas o publica el tuyo</p>
            <div class="search-box">
                <input type="text" placeholder="Buscar por oficio, categoría o ubicación...">
                <button>Buscar</button>
            </div>
        </div>
    </section>

    <!-- Sección de servicios destacados (grid) -->
    <section class="grid-section">
        <h2>Servicios Populares</h2>
        <div class="grid-container">
            <?php if (!empty($servicios)): ?>
                <?php foreach ($servicios as $servicio): ?>
                    <div class="card">
                        <img src="<?php echo $servicio->getFotoAleatoria(); ?>" alt="<?php echo htmlspecialchars($servicio->getNombre()); ?>">
                        <h3><?php echo htmlspecialchars($servicio->getNombre()); ?></h3>
                        <p><?php echo htmlspecialchars($servicio->getDescripcion()); ?></p>
                        <p><strong><?php echo $servicio->getEstado(); ?></strong></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay servicios para mostrar.</p>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
