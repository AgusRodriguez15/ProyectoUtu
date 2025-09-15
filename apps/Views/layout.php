<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $titulo ?? "Plataforma de Oficios" ?></title>
	<link rel="stylesheet" href="../../public/CSS/estilos_generales.css">
</head>
<body>
	<!-- Header -->
	<header class="header">
		<div class="logo">
			<h1>Plataforma de Oficios</h1>
		</div>
		<nav class="nav">
			<a href="/proyecto/public/index.html">Inicio</a>
			<a href="/proyecto/apps/Controllers/servicioController.php">Servicios</a>
			<a href="/proyecto/apps/Controllers/usuarioController.php">Mi Perfil</a>
			<a href="/proyecto/apps/Controllers/mensajeController.php">Mensajes</a>
		</nav>
	</header>

	<!-- Contenido dinámico -->
	<main>
		<?= $contenido ?? "" ?>
	</main>

	<!-- Footer -->
	<footer class="footer">
		<p>&copy; <?= date("Y") ?> Plataforma de Oficios - Todos los derechos reservados.</p>
	</footer>
</body>
</html>
