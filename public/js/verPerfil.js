document.addEventListener("DOMContentLoaded", () => {
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
            mostrarPerfil(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el perfil del usuario');
        });
}

function mostrarPerfil(data) {
    const usuario = data.usuario;

    // Nombre completo y tipo de usuario
    document.getElementById('nombreCompleto').textContent = `${usuario.nombre} ${usuario.apellido}`;
    
    const tipoUsuario = document.getElementById('tipoUsuario');
    tipoUsuario.textContent = usuario.rol;
    tipoUsuario.className = `tipo-usuario ${usuario.rol.toLowerCase()}`;

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

    // Habilidades (solo para proveedores)
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

    // Servicios (solo para proveedores)
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
    // Determinar qué archivo usar basándose en el rol del usuario
    const rolUsuario = sessionStorage.getItem('usuario_rol') || localStorage.getItem('usuario_rol');
    if (rolUsuario === 'proveedor') {
        window.location.href = 'detalleServicioProveedor.html';
    } else {
        window.location.href = 'detalleServicioCliente.html';
    }
}
