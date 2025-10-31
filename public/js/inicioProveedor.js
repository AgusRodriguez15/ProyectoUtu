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
    let fotoUrl = "../../public/recursos/imagenes/default/servicio-default.jpg";
    if (servicio.Fotos && Array.isArray(servicio.Fotos) && servicio.Fotos.length > 0) {
        console.log('[crearTarjetaServicio] Fotos disponibles:', servicio.Fotos);
        // Elegir foto aleatoria
        const idx = Math.floor(Math.random() * servicio.Fotos.length);
        const primera = servicio.Fotos[idx];
        if (primera && (primera.Url || primera.URL)) {
            fotoUrl = primera.Url || primera.URL;
            console.log(`[crearTarjetaServicio] Usando foto aleatoria index=${idx}:`, fotoUrl);
        } else {
            console.warn('[crearTarjetaServicio] Elemento de foto sin Url/URL en index', idx, primera);
        }
    } else {
        console.warn('[crearTarjetaServicio] No hay fotos para el servicio (usar default):', servicio.IdServicio);
    }

    card.innerHTML = `
        <div class="service-image">
            <img src="${fotoUrl}" alt="${servicio.Nombre}" onerror="this.src='../../public/recursos/imagenes/default/servicio-default.jpg'">
            <span class="estado-badge ${estadoClass}">${estadoTexto}</span>
        </div>
        <div class="service-info">
            <h3>${servicio.Nombre}</h3>
            <div class="service-meta">
                <p class="service-description">${servicio.Descripcion || 'Sin descripción'}</p>
                <div class="service-rating">
                    ${(() => {
                        // Mostrar estrellas y cantidad de reseñas si existen
                        const r = (servicio.rating !== undefined) ? servicio.rating : (servicio.Rating !== undefined ? servicio.Rating : 0);
                        const rc = (servicio.ratingCount !== undefined) ? servicio.ratingCount : (servicio.RatingCount !== undefined ? servicio.RatingCount : 0);
                        if (!r || rc === 0) return '<span class="no-reseñas">Sin reseñas</span>';
                        const rounded = Math.round(r);
                        const stars = '★'.repeat(Math.min(5, Math.max(0, rounded))) + '☆'.repeat(5 - Math.min(5, Math.max(0, rounded)));
                        return `<span class="estrellas">${stars}</span> <span class="rating-count">(${rc})</span>`;
                    })()}
                </div>
            </div>
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
    sessionStorage.setItem('vistaOrigen', 'proveedor');
    window.location.href = 'detalleServicioProveedor.html';
}

function editarServicio(idServicio) {
    // Guardar también en sessionStorage por compatibilidad
    sessionStorage.setItem('servicioId', idServicio);
    // Redirigir a la página de edición
    window.location.href = `editarServicio.html?id=${idServicio}`;
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
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        return response.text().then(text => {
            console.log('Response text:', text);
            
            if (!text.trim()) {
                throw new Error('El servidor devolvió una respuesta vacía');
            }
            
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Respuesta no válida del servidor: ' + text.substring(0, 500));
            }
        });
    })
    .then(data => {
        console.log('Data recibida:', data);
        if (!data.success) {
            const errorMsg = data.message || data.error || 'Error desconocido';
            throw new Error(errorMsg);
        }
        alert('✅ Servicio eliminado correctamente');
        cargarMisServicios(); // Recargar la lista
    })
    .catch(error => {
        console.error("Error:", error);
        alert('❌ Error al eliminar el servicio: ' + error.message);
    });
}
