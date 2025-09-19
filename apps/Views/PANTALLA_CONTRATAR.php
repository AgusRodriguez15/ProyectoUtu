<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Oficios</title>
    <link rel="stylesheet" href="../../public/CSS/estilos_generales.css">
    <link rel="stylesheet" href="../../public/CSS/pantalla.css">
</head>
<body>
    <header class="header">
        <div class="logo"><h1>Plataforma de Oficios</h1></div>
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

        <form method="POST" action="../../apps/Controllers/servicioController.php">
            <div class="search-container">
                <div class="search-box">
                    <input type="text" name="q" id="search-input" placeholder="Buscar por título..."
                           value="<?php echo htmlspecialchars($termino ?? ''); ?>"> 
                    <button type="submit">Buscar</button>
                </div>

                <div class="advanced-search-menu" id="advanced-search-menu">
                    <div class="menu-section">
                        <h4>Categoría</h4>
                        <select name="cat">
                            <option value="">Todas las categorías</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat->Nombre); ?>"
                                        <?php echo ($categoriaNombre == $cat->Nombre) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat->Nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="menu-section">
                        <h4>Ubicación</h4>
                        <input type="text" name="ubicacion" placeholder="Ej. Montevideo">
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

    <!-- Grid de servicios -->
    <section class="grid-section">
    <h2>Servicios Populares</h2>
    <div class="grid-container">
        <?php if (!empty($servicios)): ?>
            <?php foreach ($servicios as $s): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($s->getFotoServicio()); ?>" alt="Imagen del servicio">
                    <h3><?php echo htmlspecialchars($s->Nombre); ?></h3>
                    <p><?php echo htmlspecialchars($s->Descripcion); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron servicios que coincidan con tu búsqueda.</p>
        <?php endif; ?>
    </div>
</section>
</body>
</html>