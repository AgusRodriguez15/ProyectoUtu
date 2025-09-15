<?php
require_once "../../Models/servicio.php";
$servicios = Servicio::obtenerTodos();
?>

<div class="grid-container">
    <?php foreach($servicios as $servicio): ?>
        <div class="card">
            <img class="main-foto" id="main-<?php echo $servicio->IdServicio; ?>" src="<?php echo $servicio->getFotoAleatoria(); ?>" alt="<?php echo htmlspecialchars($servicio->getTitulo()); ?>">

            <h3><?php echo htmlspecialchars($servicio->getTitulo()); ?></h3>
            <p><?php echo htmlspecialchars($servicio->getDescripcion()); ?></p>

            <?php $fotos = $servicio->getTodasFotos(); ?>
            <?php if(count($fotos) > 1): ?>
                <div class="miniaturas">
                    <?php foreach($fotos as $foto): ?>
                        <img class="thumb" src="<?php echo $foto; ?>" alt="Miniatura" onclick="document.querySelector('#main-<?php echo $servicio->IdServicio; ?>').src='<?php echo $foto; ?>'">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
