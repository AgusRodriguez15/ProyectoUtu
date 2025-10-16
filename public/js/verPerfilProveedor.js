document.addEventListener("DOMContentLoaded", () => {
    // Configurar botón volver
    const btnVolver = document.getElementById('btnVolver');
    if (btnVolver) {
        btnVolver.addEventListener('click', () => {
            // Intentar volver a la página anterior
            if (document.referrer && !document.referrer.includes('verPerfil.html')) {
                window.history.back();
            } else {
                // Si no hay referrer o viene de verPerfil, ir a detalleServicio
                const servicioId = sessionStorage.getItem('servicioId');
                if (servicioId) {
                    window.location.href = 'detalleServicio.html';
                } else {
                    // Si no hay servicio, ir al inicio
                    window.location.href = 'PANTALLA_CONTRATAR.html';
                }
            }
        });
    }

    // Obtener el ID del usuario desde la URL
    const urlParams = new URLSearchParams(window.location.search);
    const idUsuario = urlParams.get('id');

    if (!idUsuario) {
        alert('No se especificó un usuario para ver');
        window.location.href = '../../public/index.html';
        return;
    }

    cargarPerfil(idUsuario);
});

function cargarPerfil(idUsuario) {
    fetch(`../../apps/Controllers/verPerfilController.php?id=${idUsuario}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Error al cargar el perfil: ' + data.message);
                return;
            }
            
            // Verificar que sea un proveedor
            if (data.usuario.rol !== 'Proveedor') {
                alert('Este perfil no es de un proveedor');
                window.location.href = `perfilCliente.html?id=${idUsuario}`;
                return;
            }
            
            mostrarPerfil(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el perfil del proveedor');
        });
}

function mostrarPerfil(data) {
    const usuario = data.usuario;

    // Nombre completo
    document.getElementById('nombreCompleto').textContent = `${usuario.nombre} ${usuario.apellido}`;

    // Años de experiencia
    if (data.aniosExperiencia !== undefined) {
        const aniosText = data.aniosExperiencia === 1 ? 'año' : 'años';
        document.getElementById('aniosExperiencia').textContent = `⭐ ${data.aniosExperiencia} ${aniosText} de experiencia`;
    }

    // Descripción
    if (usuario.descripcion) {
        document.getElementById('descripcion').textContent = usuario.descripcion;
    } else {
        document.getElementById('descripcion').textContent = 'Sin descripción';
    }

    // Foto de perfil
    if (usuario.rutaFoto) {
        let rutaFoto = usuario.rutaFoto;
        if (rutaFoto.startsWith('/proyecto/')) {
            rutaFoto = rutaFoto.replace('/proyecto/', '../../');
        }
        document.querySelector('#fotoPerfil img').src = rutaFoto;
    }

    // Ubicación
    if (data.ubicacion) {
        const seccionUbicacion = document.getElementById('seccionUbicacion');
        seccionUbicacion.style.display = 'block';
        
        document.getElementById('pais').textContent = data.ubicacion.pais || 'No especificado';
        document.getElementById('ciudad').textContent = data.ubicacion.ciudad || 'No especificado';
        
        const direccion = `${data.ubicacion.calle || ''} ${data.ubicacion.numero || ''}`.trim();
        document.getElementById('direccion').textContent = direccion || 'No especificado';
    }

    // Contactos
    if (data.contactos && data.contactos.length > 0) {
        const seccionContactos = document.getElementById('seccionContactos');
        seccionContactos.style.display = 'block';
        
        const listaContactos = document.getElementById('listaContactos');
        listaContactos.innerHTML = '';
        
        data.contactos.forEach(contacto => {
            const div = document.createElement('div');
            div.className = 'contacto-item';
            div.innerHTML = `
                <span class="contacto-tipo">${contacto.Tipo}:</span>
                <span class="contacto-valor">${contacto.Contacto}</span>
            `;
            listaContactos.appendChild(div);
        });
    }

    // Habilidades
    if (data.habilidades && data.habilidades.length > 0) {
        const seccionHabilidades = document.getElementById('seccionHabilidades');
        seccionHabilidades.style.display = 'block';
        
        const listaHabilidades = document.getElementById('listaHabilidades');
        listaHabilidades.innerHTML = '';
        
        data.habilidades.forEach(habilidad => {
            const div = document.createElement('div');
            div.className = 'habilidad-item';
            div.innerHTML = `
                <span class="habilidad-nombre">${habilidad.Habilidad}</span>
                <span class="habilidad-experiencia">${habilidad.AniosExperiencia} ${habilidad.AniosExperiencia === 1 ? 'año' : 'años'}</span>
            `;
            listaHabilidades.appendChild(div);
        });
    }

    // Servicios
    if (data.servicios && data.servicios.length > 0) {
        const seccionServicios = document.getElementById('seccionServicios');
        seccionServicios.style.display = 'block';
        
        const listaServicios = document.getElementById('listaServicios');
        listaServicios.innerHTML = '';
        
        data.servicios.forEach(servicio => {
            const div = document.createElement('div');
            div.className = 'servicio-card';
            
            const estado = servicio.Estado === 'DISPONIBLE' ? 
                '<span class="estado-disponible">Disponible</span>' : 
                '<span class="estado-no-disponible">No disponible</span>';
            
            div.innerHTML = `
                <h3>${servicio.Nombre}</h3>
                <p>${servicio.Descripcion || 'Sin descripción'}</p>
                <div class="servicio-detalles">
                    ${estado}
                    <button class="btn-ver-servicio" onclick="verServicio(${servicio.IdServicio})">Ver más</button>
                </div>
            `;
            listaServicios.appendChild(div);
        });
    }
}

function verServicio(idServicio) {
    sessionStorage.setItem('servicioId', idServicio);
    window.location.href = 'detalleServicio.html';
}
