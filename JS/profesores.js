document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector(".modal-form");
    const tabla = document.querySelector(".data-table tbody");
    const stats = document.querySelectorAll(".stat-number");

    // ==============================
    // 📦 CARGAR DATOS
    // ==============================
    let profesores = JSON.parse(localStorage.getItem("profesores")) || [];

    renderTabla();
    actualizarStats();

    // ==============================
    // ➕ AGREGAR PROFESOR
    // ==============================
    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const nombre = document.getElementById("prof_nombre").value;
        const email = document.getElementById("prof_email").value;
        const materia = document.getElementById("prof_materia").value;

        const nuevoProfesor = {
            id: Date.now(),
            nombre,
            email,
            materia,
            secciones: Math.floor(Math.random() * 3) + 1 // random para demo
        };

        profesores.push(nuevoProfesor);
        guardarDatos();

        form.reset();
        document.getElementById("modalProfesor").close();

        renderTabla();
        actualizarStats();
    });

    // ==============================
    // 🧱 RENDER TABLA
    // ==============================
    function renderTabla() {
        tabla.innerHTML = "";

        profesores.forEach((prof) => {
            const fila = document.createElement("tr");

            fila.innerHTML = `
                <td>${prof.nombre}</td>
                <td>${prof.email}</td>
                <td>${prof.materia}</td>
                <td>${prof.secciones}</td>
                <td class="actions-cell">
                    <button class="btn-action delete" data-id="${prof.id}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;

            tabla.appendChild(fila);
        });
    }

    // ==============================
    // ❌ ELIMINAR PROFESOR
    // ==============================
    tabla.addEventListener("click", (e) => {
        if (e.target.closest(".delete")) {
            const id = Number(e.target.closest("button").dataset.id);

            profesores = profesores.filter(p => p.id !== id);
            guardarDatos();

            renderTabla();
            actualizarStats();
        }
    });

    // ==============================
    // 📊 ACTUALIZAR STATS
    // ==============================
    function actualizarStats() {
        const totalProfesores = profesores.length;

        const materiasUnicas = new Set(profesores.map(p => p.materia));

        stats[0].textContent = totalProfesores;
        stats[1].textContent = materiasUnicas.size;
    }

    // ==============================
    // 💾 GUARDAR
    // ==============================
    function guardarDatos() {
        localStorage.setItem("profesores", JSON.stringify(profesores));
    }

});