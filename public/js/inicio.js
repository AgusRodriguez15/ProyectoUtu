document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("buscadorForm");
  const input = document.getElementById("search-input");
  const contenedor = document.getElementById("serviciosContainer");

  // Cargar todos los servicios al inicio
  cargarServicios("");

  form.addEventListener("submit", e => {
    e.preventDefault();
    cargarServicios(input.value);
  });

  function cargarServicios(termino) {
    fetch("../../apps/Controllers/servicioController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "q=" + encodeURIComponent(termino)
    })
      .then(res => res.json())
      .then(data => {
        contenedor.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
          contenedor.innerHTML = "<p>No se encontraron servicios.</p>";
          return;
        }

        data.forEach(s => {
          const card = document.createElement("div");
          card.className = "card";
          
          // Crear el elemento img programáticamente para evitar problemas con comillas
          const img = document.createElement('img');
          img.src = s.foto;
          img.alt = "Imagen del servicio";
          img.onerror = function() {
            // SVG simple como fallback
            this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect width="400" height="300" fill="%23ccc"/%3E%3Ctext x="200" y="150" text-anchor="middle" fill="%23666" font-size="20"%3ESin Imagen%3C/text%3E%3C/svg%3E';
            this.onerror = null; // Evitar loop infinito
          };
          
          const h3 = document.createElement('h3');
          h3.textContent = s.nombre;
          
          const p = document.createElement('p');
          p.textContent = s.descripcion;
          
          card.appendChild(img);
          card.appendChild(h3);
          card.appendChild(p);
          contenedor.appendChild(card);
        });
      })
      .catch(err => {
        console.error("Error:", err);
        contenedor.innerHTML = "<p>Error al cargar servicios.</p>";
      });
  }
});
