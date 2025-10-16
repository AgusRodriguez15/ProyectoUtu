document.addEventListener("DOMContentLoaded", () => {
    const serviciosContainer = document.getElementById("serviciosContainer");
    
    // Cargar solo los servicios del proveedor actual
    cargarMisServicios();
});

function cargarMisServicios() {
    const serviciosContainer = document.getElementById("serviciosContainer");
    serviciosContainer.innerHTML = '<p class="loading">Cargando tus servicios...</p>';

    fetch("../../apps/Controllers/servicioController.php", {
        method: "POST",
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: "misServicios=true"
    })
    .then(response => response.json())
    .then(data => {
        serviciosContainer.innerHTML = '';
        
        if (data.length === 0) {
            serviciosContainer.innerHTML = `
                <div class="sin-servicios">
                    <h3>😊 Aún no has publicado servicios</h3>
                    <p>Comienza a ofrecer tus servicios profesionales</p>
                    <a href="publicarServicio.html" class="btn-publicar-grande">➕ Publicar mi primer servicio</a>
                </div>
            `;
            return;
        }

        data.forEach(servicio => {
            const card = crearTarjetaServicio(servicio);
            serviciosContainer.appendChild(card);
        });
    })
    .catch(error => {
        console.error("Error al cargar servicios:", error);
        serviciosContainer.innerHTML = '<p class="error">Error al cargar los servicios. Por favor, recarga la página.</p>';
    });
}

function crearTarjetaServicio(servicio) {
    const card = document.createElement("div");
    card.className = "service-card";
    
    // Determinar el estado visual
    const estadoClass = servicio.Estado === 'DISPONIBLE' ? 'disponible' : 'no-disponible';
    const estadoTexto = servicio.Estado === 'DISPONIBLE' ? '✅ Disponible' : '🔒 No disponible';
    
    // Primera foto o imagen por defecto
    const fotoUrl = (servicio.Fotos && servicio.Fotos.length > 0) 
        ? servicio.Fotos[0].Url 
        : "../../public/recursos/imagenes/default/servicio-default.jpg";

    card.innerHTML = `
        <div class="service-image">
            <img src="${fotoUrl}" alt="${servicio.Nombre}" onerror="this.src='../../public/recursos/imagenes/default/servicio-default.jpg'">
            <span class="estado-badge ${estadoClass}">${estadoTexto}</span>
        </div>
        <div class="service-info">
            <h3>${servicio.Nombre}</h3>
            <p class="service-description">${servicio.Descripcion || 'Sin descripción'}</p>
            <div class="service-actions">
                <button class="btn-ver" onclick="verServicio(${servicio.IdServicio})">👁️ Ver</button>
                <button class="btn-editar" onclick="editarServicio(${servicio.IdServicio})">✏️ Editar</button>
                <button class="btn-eliminar" onclick="confirmarEliminar(${servicio.IdServicio})">🗑️ Eliminar</button>
            </div>
        </div>
    `;

    return card;
}

function verServicio(idServicio) {
    sessionStorage.setItem('servicioId', idServicio);
    window.location.href = 'detalleServicio.html';
}

function editarServicio(idServicio) {
    // Redirigir a la página de edición (puedes crear esta página después)
    alert(`Función de editar servicio ${idServicio} - Por implementar`);
    // window.location.href = `editarServicio.html?id=${idServicio}`;
}

function confirmarEliminar(idServicio) {
    if (confirm('¿Estás seguro de que quieres eliminar este servicio? Esta acción no se puede deshacer.')) {
        eliminarServicio(idServicio);
    }
}

function eliminarServicio(idServicio) {
    fetch("../../apps/Controllers/servicioController.php", {
        method: "POST",
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `eliminar=true&idServicio=${idServicio}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Servicio eliminado correctamente');
            cargarMisServicios(); // Recargar la lista
        } else {
            alert('❌ Error al eliminar el servicio: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert('❌ Error al eliminar el servicio');
    });
}
