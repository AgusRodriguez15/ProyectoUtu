<?php
require_once '../../apps/Models/Servicio.php';

// Traer todos los servicios
$servicios = Servicio::obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratar Servicios</title>
    <link rel="stylesheet" href="../../public/CSS/estilos_generales.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <h1>Plataforma de Oficios</h1>
        </div>
        <nav class="nav">
            <a href="#">Inicio</a>
            <a href="#">Servicios</a>
            <a href="#">Mi Perfil</a>
            <a href="#">Mensajes</a>
        </nav>
    </header>

    <section class="grid-section">
        <h2>Servicios Populares</h2>
        <div class="grid-container">
            <?php if (!empty($servicios)): ?>
                <?php foreach($servicios as $s): ?>
                    <div class="card">
                        <img src="https://picsum.photos/300/200?random=<?php echo $s->getIdServicio(); ?>" 
                             alt="<?php echo htmlspecialchars($s->getTitulo()); ?>">
                        <h3><?php echo htmlspecialchars($s->getTitulo()); ?></h3>
                        <p><?php echo htmlspecialchars($s->getDescripcion()); ?></p>
                        <p><strong>Precio:</strong> <?php echo $s->getPrecio() . " " . $s->getDivisa(); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay servicios disponibles.</p>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
?>