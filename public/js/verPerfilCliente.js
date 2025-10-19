document.addEventListener("DOMContentLoaded", () => {
    // Configurar botón volver
    const btnVolver = document.getElementById('btnVolver');
    if (btnVolver) {
        btnVolver.addEventListener('click', () => {
            console.log('=== BOTÓN VOLVER PRESIONADO ===');
            
            const servicioId = sessionStorage.getItem('servicioId');
            const vistaOrigen = sessionStorage.getItem('vistaOrigen');
            
            console.log('ServicioId:', servicioId);
            console.log('Vista origen:', vistaOrigen);
            
            // Si hay vista de origen guardada, usarla
            if (vistaOrigen === 'cliente') {
                console.log('Volviendo a vista de cliente');
                if (servicioId) {
                    window.location.href = 'detalleServicioCliente.html';
                } else {
                    window.location.href = 'PANTALLA_CONTRATAR.html';
                }
            } else if (vistaOrigen === 'proveedor') {
                console.log('Volviendo a vista de proveedor');
                if (servicioId) {
                    window.location.href = 'detalleServicioProveedor.html';
                } else {
                    window.location.href = 'PANTALLA_PUBLICAR.html';
                }
            } else {
                // Fallback: consultar el rol del usuario
                console.log('No hay vista origen, consultando rol del usuario');
                // Obtener el rol del usuario logueado
                fetch('../../public/php/login_usuario.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.usuario) {
                            const rolUsuario = data.usuario.rol;
                            const servicioId = sessionStorage.getItem('servicioId');
                            
                            // Si el usuario logueado es un Cliente
                            if (rolUsuario === 'Cliente') {
                                if (servicioId) {
                                    window.location.href = 'detalleServicioCliente.html';
                                } else {
                                    window.location.href = 'PANTALLA_CONTRATAR.html';
                                }
                            } 
                            // Si el usuario logueado es un Proveedor
                            else if (rolUsuario === 'Proveedor') {
                                if (servicioId) {
                                    window.location.href = 'detalleServicioProveedor.html';
                                } else {
                                    window.location.href = 'PANTALLA_PUBLICAR.html';
                                }
                            } 
                            else {
                                // Fallback: intentar volver con history.back()
                                window.history.back();
                            }
                        } else {
                            // Si no hay sesión, usar history.back()
                            window.history.back();
                        }
                    })
                    .catch(error => {
                        console.error('Error al obtener rol del usuario:', error);
                        // En caso de error, intentar volver con history.back()
                        window.history.back();
                    });
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
            
            // Verificar que sea un cliente
            if (data.usuario.rol !== 'Cliente') {
                alert('Este perfil no es de un cliente');
                window.location.href = `perfilProveedor.html?id=${idUsuario}`;
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

    // Nombre completo
    document.getElementById('nombreCompleto').textContent = `${usuario.nombre} ${usuario.apellido}`;

    // Descripción
    if (usuario.descripcion) {
        document.getElementById('descripcion').textContent = usuario.descripcion;
    } else {
        document.getElementById('descripcion').textContent = 'Sin descripción';
    }

    // Foto de perfil
    if (usuario.rutaFoto) {
        let rutaFoto = usuario.rutaFoto;
        // Si NO empieza con /, es solo el nombre del archivo
        if (!rutaFoto.startsWith('/')) {
            rutaFoto = `/proyecto/public/recursos/imagenes/perfil/${rutaFoto}`;
        }
        document.querySelector('#fotoPerfil img').src = rutaFoto;
    }

    let hasInfo = false;

    // Ubicación
    if (data.ubicacion) {
        const seccionUbicacion = document.getElementById('seccionUbicacion');
        seccionUbicacion.style.display = 'block';
        hasInfo = true;
        
        document.getElementById('pais').textContent = data.ubicacion.pais || 'No especificado';
        document.getElementById('ciudad').textContent = data.ubicacion.ciudad || 'No especificado';
        
        const direccion = `${data.ubicacion.calle || ''} ${data.ubicacion.numero || ''}`.trim();
        document.getElementById('direccion').textContent = direccion || 'No especificado';
    }

    // Contactos
    if (data.contactos && data.contactos.length > 0) {
        const seccionContactos = document.getElementById('seccionContactos');
        seccionContactos.style.display = 'block';
        hasInfo = true;
        
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

    // Si no hay información adicional, mostrar mensaje
    if (!hasInfo) {
        document.getElementById('sinInfo').style.display = 'block';
    }
}
