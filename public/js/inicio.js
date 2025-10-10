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
          card.innerHTML = `
            <img src="${s.foto}" alt="Imagen del servicio">
            <h3>${s.nombre}</h3>
            <p>${s.descripcion}</p>
          `;
          contenedor.appendChild(card);
        });
      })
      .catch(err => {
        console.error("Error:", err);
        contenedor.innerHTML = "<p>Error al cargar servicios.</p>";
      });
  }
});
