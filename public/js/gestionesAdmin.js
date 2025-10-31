// JS para gestionesAdmin.html
// Consulta las gestiones de administradores y las muestra en la tabla

document.addEventListener('DOMContentLoaded', async () => {
  const tbody = document.querySelector('#tablaGestiones tbody');
  if (!tbody) return;

  try {
    const res = await fetch('/proyecto/apps/Controllers/accionController.php?action=obtenerGestionesAdmin');
    const text = await res.text();
    console.log('Respuesta cruda del servidor:', text);
    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      console.log('Error al parsear JSON:', err);
      tbody.innerHTML = '<tr><td colspan="5">Respuesta inválida del servidor.</td></tr>';
      return;
    }
    console.log('Respuesta parseada:', data);
    if (!data.success || !Array.isArray(data.gestiones)) {
      console.log('No se pudieron cargar las gestiones:', data);
      tbody.innerHTML = '<tr><td colspan="5">No se pudieron cargar las gestiones.</td></tr>';
      return;
    }
    if (data.gestiones.length === 0) {
      console.log('No hay gestiones registradas.');
      tbody.innerHTML = '<tr><td colspan="5">No hay gestiones registradas.</td></tr>';
      return;
    }
    tbody.innerHTML = data.gestiones.map(g => `
      <tr>
        <td>${g.IdAccion}</td>
        <td>${g.IdUsuarioAdministrador || ''}</td>
        <td>${g.TipoAccion}</td>
        <td>${g.FechaAccion}</td>
        <td>${g.Descripcion}</td>
      </tr>
    `).join('');
    console.log('Gestiones renderizadas:', data.gestiones);
  } catch (err) {
    console.log('Error en try/catch principal:', err);
    tbody.innerHTML = '<tr><td colspan="5">Error al cargar gestiones.</td></tr>';
  }
});
