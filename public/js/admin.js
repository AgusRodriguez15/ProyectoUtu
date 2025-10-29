document.addEventListener('DOMContentLoaded', () => {
  // Navegación de secciones del admin
  document.querySelectorAll('.sidebar-menu a').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const section = a.dataset.section;
      if (!section) return;
      document.querySelectorAll('.panel-section').forEach(s => s.classList.remove('active'));
      const target = document.getElementById(section);
      if (target) target.classList.add('active');
      document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
      a.parentElement.classList.add('active');

      // acciones rápidas
      if (section === 'usuarios') cargarUsuarios();
      if (section === 'servicios') cargarServicios();
    });
  });

  // botones en cards
  document.querySelectorAll('[data-action="verUsuarios"]').forEach(btn => btn.addEventListener('click', () => {
    document.querySelector('[data-section="usuarios"]').click();
  }));
  document.querySelectorAll('[data-action="verServicios"]').forEach(btn => btn.addEventListener('click', () => {
    document.querySelector('[data-section="servicios"]').click();
  }));

  // submit crear admin
  const form = document.getElementById('crearAdminForm');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const msg = document.getElementById('adminMsg');
      msg.textContent = '';
      const fd = new FormData(form);
      // Normalizamos campos
      const payload = {
        nombre: fd.get('nombre') || '',
        apellido: fd.get('apellido') || '',
        email: fd.get('email') || '',
        contrasena: fd.get('contrasena') || ''
      };

      // Validaciones simples
      if (!payload.nombre || !payload.apellido || !payload.email || !payload.contrasena) {
        msg.textContent = 'Todos los campos son obligatorios.';
        msg.style.color = 'red';
        return;
      }

      try {
        const response = await fetch('/proyecto/apps/Controllers/usuarioController.php?action=crearAdmin', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new URLSearchParams(payload)
        });

        const texto = await response.text();
        let data;
        try { data = JSON.parse(texto); } catch (e) { throw new Error('Respuesta inválida del servidor: ' + texto); }

        if (data.success) {
          msg.textContent = data.message || 'Administrador creado correctamente.';
          msg.style.color = 'green';
          form.reset();
          // refrescar lista de usuarios en segundo plano
          cargarUsuarios();
        } else {
          msg.textContent = data.message || 'Error al crear administrador.';
          msg.style.color = 'red';
        }
      } catch (err) {
        msg.textContent = 'Error: ' + err.message;
        msg.style.color = 'red';
      }
    });
  }

  // Inicializar conteos simples (se pueden reemplazar por llamadas reales)
  actualizarContadores();
});

async function actualizarContadores() {
  try {
    const [uRes, sRes] = await Promise.all([
      fetch('/proyecto/apps/Controllers/usuarioController.php?action=contar'),
      fetch('/proyecto/apps/Controllers/servicioController.php?action=contar')
    ]);
    const uText = await uRes.text();
    const sText = await sRes.text();
    let uData, sData;
    try { uData = JSON.parse(uText); } catch { uData = null; }
    try { sData = JSON.parse(sText); } catch { sData = null; }

    document.getElementById('countUsuarios').textContent = (uData && uData.count) ? uData.count : '--';
    document.getElementById('countServicios').textContent = (sData && sData.count) ? sData.count : '--';
  } catch (e) {
    // silencioso
  }
}

async function cargarUsuarios() {
  const tabla = document.querySelector('#tablaUsuarios tbody');
  if (!tabla) return;
  tabla.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';
  try {
    const res = await fetch('/proyecto/apps/Controllers/usuarioController.php?action=listar');
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida: ' + text); }
    if (!Array.isArray(data)) {
      tabla.innerHTML = `<tr><td colspan="5">No hay usuarios o error: ${text}</td></tr>`;
      return;
    }
    tabla.innerHTML = data.map(u => `
      <tr>
        <td>${u.IdUsuario}</td>
        <td>${u.Nombre} ${u.Apellido}</td>
        <td>${u.Email}</td>
        <td>${u.Rol || 'N/A'}</td>
        <td>
          <button class="btn small" onclick="location.href='/proyecto/apps/Views/verPerfil.html?id=${u.IdUsuario}'">Ver</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tabla.innerHTML = `<tr><td colspan="5">Error cargando usuarios: ${err.message}</td></tr>`;
  }
}

async function cargarServicios() {
  const tabla = document.querySelector('#tablaServicios tbody');
  if (!tabla) return;
  tabla.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';
  try {
    const res = await fetch('/proyecto/apps/Controllers/servicioController.php?action=listarTodos');
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida: ' + text); }
    if (!Array.isArray(data)) {
      tabla.innerHTML = `<tr><td colspan="5">No hay servicios o error: ${text}</td></tr>`;
      return;
    }
    tabla.innerHTML = data.map(s => `
      <tr>
        <td>${s.IdServicio}</td>
        <td>${s.Nombre}</td>
        <td>${s.ProveedorNombre || '—'}</td>
        <td>${s.Estado || '—'}</td>
        <td>
          <button class="btn small" onclick="location.href='/proyecto/apps/Views/detalleServicioProveedor.html?id=${s.IdServicio}'">Ver</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tabla.innerHTML = `<tr><td colspan="5">Error cargando servicios: ${err.message}</td></tr>`;
  }
}
