document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector("#modalSeccion .modal-form");
    const lista = document.getElementById("listaSecciones");
    const mensajeVacio = document.getElementById("mensajeVacio");

    let secciones = JSON.parse(localStorage.getItem("secciones")) || [];

    renderSecciones();
    actualizarStats();

    // ==============================
    // ➕ AGREGAR SECCIÓN
    // ==============================
    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const nombre = document.getElementById("sec_nombre").value;
        const anio = document.getElementById("sec_anio").value;
        const capacidad = document.getElementById("sec_capacidad").value;

        const nuevaSeccion = {
            id: Date.now(),
            nombre,
            anio,
            capacidad,
            estudiantes: Math.floor(Math.random() * capacidad),
            docentes: Math.floor(Math.random() * 3) + 1
        };

        secciones.push(nuevaSeccion);
        guardar();

        form.reset();
        document.getElementById("modalSeccion").close();

        renderSecciones();
        actualizarStats();
    });

    // ==============================
    // 🧱 RENDER
    // ==============================
    function renderSecciones() {
        lista.innerHTML = "";

        if (secciones.length === 0) {
            mensajeVacio.style.display = "block";
            return;
        }

        mensajeVacio.style.display = "none";

        secciones.forEach(sec => {
            const card = document.createElement("article");
            card.classList.add("subject-card");

            card.innerHTML = `
                <h4>${sec.nombre}</h4>
                <p><strong>Año:</strong> ${sec.anio == 1 ? "Primer año" : "Segundo año"}</p>
                <p><strong>Capacidad:</strong> ${sec.capacidad}</p>
                <p><strong>Estudiantes:</strong> ${sec.estudiantes}</p>
                <p><strong>Docentes:</strong> ${sec.docentes}</p>
                
                <div class="actions">
                    <button class="btn-action see" data-id="${sec.id}">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="btn-action edit" data-id="${sec.id}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn-action delete" data-id="${sec.id}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;

            lista.appendChild(card);
        });
    }

    // ==============================
    // 🎯 EVENTOS (VER, EDITAR, ELIMINAR)
    // ==============================
    lista.addEventListener("click", (e) => {

        const btn = e.target.closest("button");
        if (!btn) return;

        const id = Number(btn.dataset.id);
        const seccion = secciones.find(s => s.id === id);

        // 👁️ VER
        if (btn.classList.contains("see")) {
            document.getElementById("detalleSeccion").innerHTML = `
                <p><strong>Nombre:</strong> ${seccion.nombre}</p>
                <p><strong>Año:</strong> ${seccion.anio == 1 ? "Primer año" : "Segundo año"}</p>
                <p><strong>Capacidad:</strong> ${seccion.capacidad}</p>
                <p><strong>Estudiantes:</strong> ${seccion.estudiantes}</p>
                <p><strong>Docentes:</strong> ${seccion.docentes}</p>
            `;
            document.getElementById("modalVer").showModal();
        }

        // ✏️ EDITAR
        if (btn.classList.contains("edit")) {
            document.getElementById("edit_seccion_id").value = seccion.id;
            document.getElementById("edit_nombre").value = seccion.nombre;
            document.getElementById("edit_anio").value = seccion.anio;
            document.getElementById("edit_capacidad").value = seccion.capacidad;

            document.getElementById("modalEditar").showModal();
        }

        // ❌ ELIMINAR
        if (btn.classList.contains("delete")) {
            secciones = secciones.filter(s => s.id !== id);
            guardar();
            renderSecciones();
            actualizarStats();
        }

    });

    // ==============================
    // 💾 EDITAR GUARDAR
    // ==============================
    const formEditar = document.querySelector("#modalEditar .modal-form");

    formEditar.addEventListener("submit", (e) => {
        e.preventDefault();

        const id = Number(document.getElementById("edit_seccion_id").value);

        const seccion = secciones.find(s => s.id === id);

        seccion.nombre = document.getElementById("edit_nombre").value;
        seccion.anio = document.getElementById("edit_anio").value;
        seccion.capacidad = document.getElementById("edit_capacidad").value;

        guardar();

        document.getElementById("modalEditar").close();

        renderSecciones();
        actualizarStats();
    });

    // ==============================
    // 📊 STATS
    // ==============================
    function actualizarStats() {
        document.getElementById("totalSecciones").textContent = secciones.length;

        const totalEstudiantes = secciones.reduce((acc, s) => acc + s.estudiantes, 0);
        const totalDocentes = secciones.reduce((acc, s) => acc + s.docentes, 0);

        document.getElementById("totalEstudiantesSeccion").textContent = totalEstudiantes;
        document.getElementById("totalDocentesSeccion").textContent = totalDocentes;
    }

    // ==============================
    // 💾 LOCALSTORAGE
    // ==============================
    function guardar() {
        localStorage.setItem("secciones", JSON.stringify(secciones));
    }

});