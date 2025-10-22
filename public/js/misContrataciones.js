document.addEventListener('DOMContentLoaded', () => {
  let todasLasContrataciones = [];
  let filtroActual = 'todas';

  // Cargar contrataciones del cliente
  cargarContrataciones();

  // Configurar filtros
  document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      // Actualizar botón activo
      document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('activo'));
      btn.classList.add('activo');
      
      // Aplicar filtro
      filtroActual = btn.dataset.filtro;
      mostrarContrataciones(todasLasContrataciones);
    });
  });

  async function cargarContrataciones() {
    try {
      const response = await fetch('../../apps/Controllers/reservaController.php');
      const data = await response.json();

      if (data.success) {
        // Obtener información adicional de cada servicio
        const contratacionesConInfo = await Promise.all(
          data.reservas.map(async (reserva) => {
            try {
              const servicioResponse = await fetch('../../apps/Controllers/servicioController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${reserva.idServicio}`
              });
              const servicioData = await servicioResponse.json();
              
              return {
                ...reserva,
                nombreServicio: servicioData.nombre || 'Servicio',
                nombreProveedor: servicioData.proveedor?.nombre || 'Proveedor'
              };
            } catch (error) {
              console.error('Error al obtener info del servicio:', error);
              return {
                ...reserva,
                nombreServicio: 'Servicio',
                nombreProveedor: 'Proveedor'
              };
            }
          })
        );

        todasLasContrataciones = contratacionesConInfo;
        mostrarContrataciones(todasLasContrataciones);
      } else {
        mostrarError('No se pudieron cargar las contrataciones');
      }
    } catch (error) {
      console.error('Error al cargar contrataciones:', error);
      mostrarError('Error al conectar con el servidor');
    }
  }

  function mostrarContrataciones(contrataciones) {
    const contenedor = document.getElementById('contratacionesLista');
    
    // Filtrar según el filtro actual
    let contratacionesFiltradas = contrataciones;
    if (filtroActual !== 'todas') {
      contratacionesFiltradas = contrataciones.filter(c => c.estado === filtroActual);
    }

    if (contratacionesFiltradas.length === 0) {
      contenedor.innerHTML = `
        <div class="sin-contrataciones">
          <div class="sin-contrataciones-icono">🛒</div>
          <h3>No tienes contrataciones ${filtroActual !== 'todas' ? filtroActual + 's' : ''}</h3>
          <p>Explora nuestros servicios y contrata el que necesites.</p>
          <a href="PANTALLA_CONTRATAR.html" class="btn-explorar">Explorar Servicios</a>
        </div>
      `;
      return;
    }

    contenedor.innerHTML = '';
    contratacionesFiltradas.forEach(contratacion => {
      const card = crearCardContratacion(contratacion);
      contenedor.appendChild(card);
    });
  }

  function crearCardContratacion(contratacion) {
    const card = document.createElement('div');
    card.className = `contratacion-card ${contratacion.estado}`;
    
    const fechaInicio = new Date(contratacion.fechaInicio);
    const fechaFin = new Date(contratacion.fechaFin);
    
    const estadoClass = `estado-${contratacion.estado}`;
    const estadoTexto = contratacion.estado.charAt(0).toUpperCase() + contratacion.estado.slice(1);

    card.innerHTML = `
      <div class="contratacion-header">
        <h3 class="contratacion-servicio">🛠️ ${contratacion.nombreServicio}</h3>
        <span class="contratacion-estado ${estadoClass}">${estadoTexto}</span>
      </div>

      <div class="contratacion-info">
        <div class="info-item">
          <span class="info-label">📅 Fecha Inicio</span>
          <span class="info-value">${formatearFecha(fechaInicio)}</span>
        </div>
        <div class="info-item">
          <span class="info-label">🕐 Hora Inicio</span>
          <span class="info-value">${formatearHora(fechaInicio)}</span>
        </div>
        <div class="info-item">
          <span class="info-label">📅 Fecha Fin</span>
          <span class="info-value">${formatearFecha(fechaFin)}</span>
        </div>
        <div class="info-item">
          <span class="info-label">🕐 Hora Fin</span>
          <span class="info-value">${formatearHora(fechaFin)}</span>
        </div>
      </div>

      <div class="contratacion-proveedor">
        <div class="proveedor-label">👤 Proveedor del servicio:</div>
        <div class="proveedor-nombre">${contratacion.nombreProveedor}</div>
      </div>

      ${contratacion.observacion ? `
        <div class="contratacion-observacion">
          <div class="observacion-label">📝 Tus observaciones:</div>
          <div class="observacion-texto">${contratacion.observacion}</div>
        </div>
      ` : ''}

      <div class="contratacion-acciones">
        <button class="btn-accion btn-ver-servicio" onclick="verServicio(${contratacion.idServicio})">
          👁️ Ver Servicio
        </button>
        ${contratacion.estado === 'confirmada' ? `
          <button class="btn-accion btn-finalizar" onclick="finalizarContratacion(${contratacion.idReserva})">
            ✓ Finalizar Servicio
          </button>
        ` : ''}
        ${contratacion.estado === 'pendiente' ? `
          <button class="btn-accion btn-cancelar" onclick="cancelarContratacion(${contratacion.idReserva})">
            ✗ Cancelar
          </button>
        ` : ''}
      </div>
    `;

    return card;
  }

  function formatearFecha(fecha) {
    return fecha.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  }

  function formatearHora(fecha) {
    return fecha.toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function mostrarError(mensaje) {
    const contenedor = document.getElementById('contratacionesLista');
    contenedor.innerHTML = `
      <div class="sin-contrataciones">
        <div class="sin-contrataciones-icono">⚠️</div>
        <h3>Error</h3>
        <p>${mensaje}</p>
      </div>
    `;
  }

  // Función global para ver servicio
  window.verServicio = function(idServicio) {
    sessionStorage.setItem('servicioId', idServicio);
    window.location.href = 'detalleServicioCliente.html';
  };

  // Función global para finalizar contratación
  window.finalizarContratacion = async function(idReserva) {
    if (!confirm('¿Confirmas que el servicio ha sido completado satisfactoriamente?')) {
      return;
    }

    try {
      const response = await fetch('../../apps/Controllers/reservaController.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          idReserva: idReserva,
          nuevoEstado: 'finalizada'
        })
      });

      const data = await response.json();

      if (data.success) {
        alert('✓ Servicio finalizado correctamente\n\n¡Gracias por usar nuestros servicios!');
        cargarContrataciones(); // Recargar lista
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error al finalizar el servicio');
    }
  };

  // Función global para cancelar contratación
  window.cancelarContratacion = async function(idReserva) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta contratación?')) {
      return;
    }

    try {
      const response = await fetch('../../apps/Controllers/reservaController.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          idReserva: idReserva,
          nuevoEstado: 'cancelada'
        })
      });

      const data = await response.json();

      if (data.success) {
        alert('✓ Contratación cancelada correctamente');
        cargarContrataciones(); // Recargar lista
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error al cancelar la contratación');
    }
  };
});
