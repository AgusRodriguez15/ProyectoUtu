document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("buscadorForm");
  const input = document.getElementById("search-input");
  const contenedor = document.getElementById("serviciosContainer");

  // Si alguno de los elementos clave no existe, hacer que el script sea no-op.
  // Esto evita errores en páginas que no tienen buscador/servicios (por ejemplo: PANTALLA_ADMIN.html).
  if (!form || !input || !contenedor) {
    // No hay buscador en esta página; salimos silenciosamente.
    return;
  }

  // Cargar todos los servicios al inicio
  cargarServicios("");

  form.addEventListener("submit", e => {
    e.preventDefault();
    cargarServicios(input.value);
  });

  function cargarServicios(termino) {
    console.log('[cargarServicios] término:', termino);
    fetch("../../apps/Controllers/servicioController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "q=" + encodeURIComponent(termino)
    })
      .then(res => {
        console.log('[cargarServicios] response:', res);
        return res.json();
      })
      .then(data => {
        console.log('[cargarServicios] data recibida:', data);
        contenedor.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
          console.warn('[cargarServicios] No se encontraron servicios. Data:', data);
          contenedor.innerHTML = "<p>No se encontraron servicios.</p>";
          return;
        }

        data.forEach((s, idx) => {
          console.log(`[cargarServicios] Renderizando servicio #${idx}:`, s);
          const card = document.createElement("div");
          card.className = "card";
          card.style.cursor = "pointer";
          const img = document.createElement('img');
          // Si vienen varias fotos, elegir una aleatoria; si no, usar la propiedad 'foto' o valor por defecto
          let imgSrc = s.foto || '../../public/recursos/imagenes/default/servicio-default.jpg';
          if (s.Fotos && Array.isArray(s.Fotos) && s.Fotos.length > 0) {
            const rnd = Math.floor(Math.random() * s.Fotos.length);
            imgSrc = s.Fotos[rnd].Url || s.Fotos[rnd].URL || imgSrc;
            console.log(`[cargarServicios] foto aleatoria para servicio ${s.IdServicio}:`, imgSrc);
          }
          img.src = imgSrc;
          img.alt = "Imagen del servicio";
          img.onerror = function() {
            console.warn('[cargarServicios] Imagen no encontrada para servicio:', s);
            this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect width="400" height="300" fill="%23ccc"/%3E%3Ctext x="200" y="150" text-anchor="middle" fill="%23666" font-size="20"%3ESin Imagen%3C/text%3E%3C/svg%3E';
            this.onerror = null;
          };
          const h3 = document.createElement('h3');
          h3.textContent = s.Nombre;
          const p = document.createElement('p');
          p.textContent = s.Descripcion;
          card.onclick = function() {
            console.log('[cargarServicios] Click en servicio:', s.IdServicio);
            sessionStorage.setItem('servicioId', s.IdServicio);
            sessionStorage.setItem('vistaOrigen', 'cliente');
            window.location.href = '../../apps/Views/detalleServicioCliente.html';
          };
          card.appendChild(img);
          card.appendChild(h3);
          card.appendChild(p);
          contenedor.appendChild(card);
        });
      })
      .catch(err => {
        console.error("[cargarServicios] Error en fetch:", err);
        contenedor.innerHTML = "<p>Error al cargar servicios.</p>";
      });
  }
});