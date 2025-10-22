document.addEventListener('DOMContentLoaded', () => {
  let todasLasReservas = [];
  let filtroActual = 'todas';

  // Cargar reservas del proveedor
  cargarReservas();

  // Configurar filtros
  document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      // Actualizar botón activo
      document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('activo'));
      btn.classList.add('activo');
      
      // Aplicar filtro
      filtroActual = btn.dataset.filtro;
      mostrarReservas(todasLasReservas);
    });
  });

  async function cargarReservas() {
    try {
      const response = await fetch('../../apps/Controllers/reservaController.php?tipo=proveedor');
      const data = await response.json();

      if (data.success) {
        todasLasReservas = data.reservas;
        mostrarReservas(todasLasReservas);
      } else {
        mostrarError('No se pudieron cargar las reservas');
      }
    } catch (error) {
      console.error('Error al cargar reservas:', error);
      mostrarError('Error al conectar con el servidor');
    }
  }

  function mostrarReservas(reservas) {
    const contenedor = document.getElementById('reservasLista');
    
    // Filtrar según el filtro actual
    let reservasFiltradas = reservas;
    if (filtroActual !== 'todas') {
      reservasFiltradas = reservas.filter(r => r.estado === filtroActual);
    }

    if (reservasFiltradas.length === 0) {
      contenedor.innerHTML = `
        <div class="sin-reservas">
          <div class="sin-reservas-icono">📭</div>
          <h3>No hay reservas ${filtroActual !== 'todas' ? filtroActual + 's' : ''}</h3>
          <p>Cuando los clientes contraten tus servicios, aparecerán aquí.</p>
        </div>
      `;
      return;
    }

    contenedor.innerHTML = '';
    reservasFiltradas.forEach(reserva => {
      const card = crearCardReserva(reserva);
      contenedor.appendChild(card);
    });
  }

  function crearCardReserva(reserva) {
    const card = document.createElement('div');
    card.className = `reserva-card ${reserva.estado}`;
    
    const fechaInicio = new Date(reserva.fechaInicio);
    const fechaFin = new Date(reserva.fechaFin);
    
    const estadoClass = `estado-${reserva.estado}`;
    const estadoTexto = reserva.estado.charAt(0).toUpperCase() + reserva.estado.slice(1);

    card.innerHTML = `
      <div class="reserva-header">
        <h3 class="reserva-servicio">🛠️ ${reserva.nombreServicio}</h3>
        <span class="reserva-estado ${estadoClass}">${estadoTexto}</span>
      </div>

      <div class="reserva-info">
        <div class="info-item">
          <span class="info-label">👤 Cliente</span>
          <span class="info-value">${reserva.nombreCliente}</span>
        </div>
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

      ${reserva.observacion ? `
        <div class="reserva-observacion">
          <div class="observacion-label">📝 Observaciones del cliente:</div>
          <div class="observacion-texto">${reserva.observacion}</div>
        </div>
      ` : ''}

      <div class="reserva-acciones">
        ${reserva.estado === 'pendiente' ? `
          <button class="btn-accion btn-confirmar" onclick="cambiarEstado(${reserva.idReserva}, 'confirmada')">
            ✓ Confirmar
          </button>
          <button class="btn-accion btn-cancelar-reserva" onclick="cambiarEstado(${reserva.idReserva}, 'cancelada')">
            ✗ Rechazar
          </button>
        ` : ''}
        ${reserva.estado === 'confirmada' ? `
          <button class="btn-accion btn-finalizar" onclick="cambiarEstado(${reserva.idReserva}, 'finalizada')">
            ✓ Marcar como Finalizada
          </button>
          <button class="btn-accion btn-cancelar-reserva" onclick="cambiarEstado(${reserva.idReserva}, 'cancelada')">
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
    const contenedor = document.getElementById('reservasLista');
    contenedor.innerHTML = `
      <div class="sin-reservas">
        <div class="sin-reservas-icono">⚠️</div>
        <h3>Error</h3>
        <p>${mensaje}</p>
      </div>
    `;
  }

  // Función global para cambiar estado
  window.cambiarEstado = async function(idReserva, nuevoEstado) {
    const mensajes = {
      'confirmada': '¿Confirmar esta reserva?',
      'cancelada': '¿Rechazar/Cancelar esta reserva?',
      'finalizada': '¿Marcar esta reserva como finalizada?'
    };

    if (!confirm(mensajes[nuevoEstado])) {
      return;
    }

    try {
      const response = await fetch('../../apps/Controllers/reservaController.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          idReserva: idReserva,
          nuevoEstado: nuevoEstado
        })
      });

      const data = await response.json();

      if (data.success) {
        alert('✓ Estado actualizado correctamente');
        cargarReservas(); // Recargar lista
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error al actualizar el estado');
    }
  };
});
