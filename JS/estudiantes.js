document.addEventListener("DOMContentLoaded", () => {

    let estudiantes = JSON.parse(localStorage.getItem("estudiantes")) || [];

    const tabla = document.querySelector(".data-table tbody");
    const buscador = document.getElementById("buscador-estudiantes");
    const estadisticas = document.querySelectorAll(".stat-number");
    const sectionGrid = document.querySelector(".section-grid");

    function actualizarEstadisticas() {

        const total = estudiantes.length;

        const activos = estudiantes.filter(
            e => e.estado?.toLowerCase() === "activo"
        ).length;

        const inactivos = estudiantes.filter(
            e => e.estado?.toLowerCase() === "inactivo"
        ).length;

        const secciones = [
            ...new Set(estudiantes.map(e => e.seccion))
        ].length;

        estadisticas[0].textContent = total;
        estadisticas[1].textContent = activos;
        estadisticas[2].textContent = inactivos;
        estadisticas[3].textContent = secciones;
    }

    function mostrarEstudiantes(lista = estudiantes) {

        tabla.innerHTML = "";

        if (lista.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="6">No hay estudiantes registrados.</td>
                </tr>
            `;
            return;
        }

        lista.forEach((estudiante, index) => {

            const fila = document.createElement("tr");

            fila.innerHTML = `
                <td>${estudiante.nie || ""}</td>
                <td>${estudiante.nombres || ""}</td>
                <td>${estudiante.apellidos || ""}</td>
                <td>${estudiante.seccion || ""}</td>
                <td>
                    <span class="badge ${
                        estudiante.estado?.toLowerCase() === "activo"
                            ? "active"
                            : "inactive"
                    }">
                        ${estudiante.estado || "Activo"}
                    </span>
                </td>
                <td class="actions-cell">
                    <button class="btn-action see">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    <button class="btn-action edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button class="btn-action delete" data-index="${index}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;

            tabla.appendChild(fila);
        });

        activarBotonesEliminar();
    }

    function activarBotonesEliminar() {

        document.querySelectorAll(".delete").forEach(btn => {

            btn.addEventListener("click", () => {

                const index = btn.dataset.index;

                if (confirm("¿Desea eliminar este estudiante?")) {

                    estudiantes.splice(index, 1);

                    localStorage.setItem(
                        "estudiantes",
                        JSON.stringify(estudiantes)
                    );

                    mostrarEstudiantes();
                    actualizarEstadisticas();
                    mostrarSecciones();
                }
            });
        });
    }

    function mostrarSecciones() {

        sectionGrid.innerHTML = "";

        if (estudiantes.length === 0) {

            sectionGrid.innerHTML = `
                <p class="empty-state">
                    Aún no hay secciones creadas para mostrar el desglose.
                </p>
            `;

            return;
        }

        const agrupadas = {};

        estudiantes.forEach(estudiante => {

            const seccion = estudiante.seccion || "Sin sección";

            agrupadas[seccion] = (agrupadas[seccion] || 0) + 1;
        });

        for (const seccion in agrupadas) {

            const card = document.createElement("article");

            card.classList.add("section-card");

            card.innerHTML = `
                <h3>${seccion}</h3>
                <p>${agrupadas[seccion]} estudiante(s)</p>
            `;

            sectionGrid.appendChild(card);
        }
    }

    buscador.addEventListener("input", () => {

        const texto = buscador.value.toLowerCase();

        const filtrados = estudiantes.filter(estudiante =>
            (estudiante.nie || "").toLowerCase().includes(texto) ||
            (estudiante.nombres || "").toLowerCase().includes(texto) ||
            (estudiante.apellidos || "").toLowerCase().includes(texto)
        );

        mostrarEstudiantes(filtrados);
    });

    actualizarEstadisticas();
    mostrarEstudiantes();
    mostrarSecciones();

});