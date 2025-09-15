<!DOCTYPE html>
<html lang="es">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panel Administrador</title>
        <link rel="stylesheet" href="../../public/CSS/style2.css">
        
</head>
<body>
    <div class="admin-container">
        <h1>Bienvenido, Administrador</h1>
        <nav>
            <div class="admin-nav">
                <a class="admin-btn" href="../public/index.html">Cerrar sesión</a>
            </div>
        </nav>
        <section>
            <h2>Gestión de la plataforma</h2>
            <div class="admin-section">
                <h3>Ver servicios</h3>
                <ul id="servicios-lista">
                    <li>Electricista - Instalación de lámparas <span style="color:#bfa100;font-weight:bold;">(En proceso)</span></li>
                    <li>Plomero - Reparación de cañerías <span style="color:#bfa100;font-weight:bold;">(En proceso)</span></li>
                    <li>Jardinero - Corte de césped <span style="color:#bfa100;font-weight:bold;">(En proceso)</span></li>
                </ul>
            </div>
            <div class="admin-section">
                <h3>Buscar servicios</h3>
                <input type="text" id="buscar-servicio" placeholder="Buscar por nombre o tipo">
                <button>Buscar</button>
            </div>
            <div class="admin-section">
                <h3>Añadir administrador</h3>
                <input type="text" id="nuevo-admin" placeholder="Email del nuevo admin">
                <button>Añadir</button>
            </div>
            <div class="admin-section">
                <h3>Buscar usuarios</h3>
                <input type="text" id="buscar-usuario" placeholder="Nombre, email o rol">
                <button>Buscar</button>
            </div>
        </section>
    </div>
</body>
</html>
