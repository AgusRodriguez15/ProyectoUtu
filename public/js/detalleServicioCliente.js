document.addEventListener("DOMContentLoaded", () => {
  const contenedor = document.getElementById("contenidoDetalle");
  
  // Obtener el ID del servicio desde sessionStorage
  const servicioId = sessionStorage.getItem('servicioId');
  
  if (!servicioId) {
    contenedor.innerHTML = `
      <div class="error">
        <h2>Error</h2>
        <p>No se ha seleccionado ningún servicio.</p>
        <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
          Volver a Servicios
        </button>
      </div>
    `;
    return;
  }
  
  // Cargar los detalles del servicio
  cargarDetalleServicio(servicioId);
  
  function cargarDetalleServicio(id) {
    fetch("../../apps/Controllers/servicioController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id)
    })
      .then(res => {
        // Primero obtener el texto de la respuesta
        return res.text();
      })
      .then(text => {
        console.log('Respuesta del servidor:', text);
        
        // Intentar parsear como JSON
        try {
          const data = JSON.parse(text);
          
          console.log('Datos parseados:', data);
          console.log('Proveedor:', data.proveedor);
          console.log('Contactos:', data.proveedor?.contactos);
          console.log('Habilidades:', data.proveedor?.habilidades);
          
          if (data.error) {
            contenedor.innerHTML = `
              <div class="error">
                <h2>Error</h2>
                <p>${data.error}</p>
                <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
                  Volver a Servicios
                </button>
              </div>
            `;
            return;
          }
          
          mostrarDetalle(data);
        } catch (e) {
          console.error('Error al parsear JSON:', e);
          console.error('Respuesta recibida:', text);
          contenedor.innerHTML = `
            <div class="error">
              <h2>Error del Servidor</h2>
              <p>El servidor devolvió una respuesta inválida.</p>
              <details>
                <summary>Ver detalles técnicos</summary>
                <pre style="max-height: 300px; overflow: auto; background: #f5f5f5; padding: 1rem; border-radius: 4px;">${text}</pre>
              </details>
              <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
                Volver a Servicios
              </button>
            </div>
          `;
        }
      })
      .catch(err => {
        console.error("Error:", err);
        contenedor.innerHTML = `
          <div class="error">
            <h2>Error</h2>
            <p>No se pudo cargar el detalle del servicio.</p>
            <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
              Volver a Servicios
            </button>
          </div>
        `;
      });
  }
  
  function mostrarDetalle(servicio) {
    // Para clientes: siempre mostrar botones de contratar
    
    // Crear galería de fotos si hay múltiples imágenes
    let galeriaHTML = '';
    if (servicio.fotos && servicio.fotos.length > 0) {
      galeriaHTML = '<div class="galeria-fotos">';
      servicio.fotos.forEach(foto => {
        galeriaHTML += `<img src="${foto}" alt="Foto del servicio" class="foto-miniatura">`;
      });
      galeriaHTML += '</div>';
    }
    
    // Formatear fecha
    let fechaFormateada = 'Fecha no disponible';
    if (servicio.fechaPublicacion) {
      const fecha = new Date(servicio.fechaPublicacion);
      fechaFormateada = fecha.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    }
    
    contenedor.innerHTML = `
      <div class="servicio-detalle">
        <div class="servicio-imagen">
          <img src="${servicio.foto}" alt="${servicio.nombre}" 
               onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'600\\' height=\\'400\\'%3E%3Crect width=\\'600\\' height=\\'400\\' fill=\\'%23ccc\\'/%3E%3Ctext x=\\'300\\' y=\\'200\\' text-anchor=\\'middle\\' fill=\\'%23666\\' font-size=\\'24\\'%3ESin Imagen%3C/text%3E%3C/svg%3E'">
          ${galeriaHTML}
        </div>
        
        <div class="servicio-info">
          <h1>${servicio.nombre}</h1>
          
          <div class="servicio-meta">
            <span class="badge estado-${servicio.estado?.toLowerCase() || 'disponible'}">
              ${servicio.estado || 'DISPONIBLE'}
            </span>
            <span class="fecha-publicacion">
              📅 Publicado: ${fechaFormateada}
            </span>
          </div>
          
          ${servicio.precio !== undefined && servicio.precio !== null ? `
            <div class="servicio-precio">
              <h3>💰 Precio</h3>
              <div class="precio-display">
                ${servicio.precio > 0 ? 
                  `<span class="precio-valor">$${parseFloat(servicio.precio).toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${servicio.divisa || 'UYU'}</span>` 
                  : '<span class="precio-gratuito">🆓 Servicio Gratuito</span>'}
              </div>
            </div>
          ` : ''}
          
          <div class="servicio-descripcion-container">
            <h3>Descripción</h3>
            <p class="servicio-descripcion">${servicio.descripcion || 'Sin descripción disponible'}</p>
          </div>
          
          <div class="servicio-proveedor">
            <h3>👤 Proveedor</h3>
            <div class="proveedor-card">
              ${servicio.proveedor?.foto ? `
                <div class="proveedor-foto">
                  <img src="../../${servicio.proveedor.foto}" alt="Foto del proveedor" 
                       onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'100\\' height=\\'100\\'%3E%3Crect width=\\'100\\' height=\\'100\\' fill=\\'%23ddd\\'/%3E%3Ctext x=\\'50\\' y=\\'50\\' text-anchor=\\'middle\\' fill=\\'%23666\\' font-size=\\'40\\'%3E👤%3C/text%3E%3C/svg%3E'">
                </div>
              ` : ''}
              
              <div class="proveedor-info">
                <p class="proveedor-nombre"><strong>${servicio.proveedor?.nombre || 'Información no disponible'}</strong></p>
                ${servicio.proveedor?.descripcion ? `<p class="proveedor-descripcion">${servicio.proveedor.descripcion}</p>` : ''}
                
                ${servicio.proveedor?.contactos && servicio.proveedor.contactos.length > 0 ? `
                  <div class="proveedor-contactos">
                    <h4>📞 Datos de Contacto:</h4>
                    <ul>
                      ${servicio.proveedor.contactos.map(c => `
                        <li><strong>${c.tipo}:</strong> ${c.contacto}</li>
                      `).join('')}
                    </ul>
                  </div>
                ` : ''}
                
                ${servicio.proveedor?.habilidades && servicio.proveedor.habilidades.length > 0 ? `
                  <div class="proveedor-habilidades">
                    <h4>💼 Habilidades:</h4>
                    <div class="habilidades-tags">
                      ${servicio.proveedor.habilidades.map(h => `
                        <span class="habilidad-tag">
                          ${h.habilidad} ${h.experiencia > 0 ? `(${h.experiencia} ${h.experiencia === 1 ? 'año' : 'años'})` : ''}
                        </span>
                      `).join('')}
                    </div>
                  </div>
                ` : ''}
              </div>
            </div>
          </div>
          
          <div class="servicio-acciones">
            <button class="btn-primary btn-contratar"> Contratar Servicio</button>
            <button class="btn-secondary btn-mensaje">💬 Enviar Mensaje</button>
            <button class="btn-terciary btn-perfil">👤 Ver Perfil</button>
          </div>
        </div>
      </div>
    `;
    
    // Event listeners para clientes
    document.querySelector('.btn-contratar')?.addEventListener('click', () => {
      alert('Función de contratación en desarrollo');
      // Aquí puedes agregar la lógica para contratar el servicio
    });
    
    document.querySelector('.btn-mensaje')?.addEventListener('click', () => {
      alert('Función de mensajería en desarrollo');
      // Aquí puedes agregar la lógica para enviar mensaje
    });
    
    document.querySelector('.btn-perfil')?.addEventListener('click', () => {
      if (servicio.proveedor?.idUsuario) {
        window.location.href = `../../apps/Views/verPerfil.html?id=${servicio.proveedor.idUsuario}`;
      } else {
        alert('No se puede ver el perfil en este momento');
      }
    });
    
    // Event listeners para la galería de fotos
    const miniaturas = document.querySelectorAll('.foto-miniatura');
    const imagenPrincipal = document.querySelector('.servicio-imagen > img');
    
    miniaturas.forEach(miniatura => {
      miniatura.addEventListener('click', () => {
        if (imagenPrincipal) {
          imagenPrincipal.src = miniatura.src;
        }
      });
    });
  }
});
