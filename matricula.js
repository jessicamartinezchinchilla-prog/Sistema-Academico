document.addEventListener("DOMContentLoaded", () => {

    const formulario =
        document.querySelector("#modalMatricula .modal-form");

    const listaEstudiantes =
        document.getElementById("listaEstudiantes");

    const totalMatriculas =
        document.getElementById("totalMatriculas");

    const matriculasActivas =
        document.getElementById("matriculasActivas");

    const matriculasInactivas =
        document.getElementById("matriculasInactivas");

    const matriculasAnio =
        document.getElementById("matriculasAnio");

    const detalleEstudiante =
        document.getElementById("detalleEstudiante");

    let matriculas = [];

    formulario.addEventListener("submit", function (e) {

        e.preventDefault();

        const nuevaMatricula = {
            id: Date.now(),
            nie: document.getElementById("mat_nie").value,
            nombre: document.getElementById("mat_nombre").value,
            grado: document.getElementById("mat_grado").value,
            seccion: document.getElementById("mat_seccion").value,
            responsable: document.getElementById("mat_responsable").value,
            telefono: document.getElementById("mat_telefono").value,
            estado: document.getElementById("mat_estado").value
        };

        matriculas.push(nuevaMatricula);

        renderizarTabla();

        formulario.reset();

        document.getElementById("modalMatricula").close();
    });

    function renderizarTabla() {

        listaEstudiantes.innerHTML = "";

        matriculas.forEach(estudiante => {

            const fila = document.createElement("tr");

            fila.innerHTML = `
                <td>${estudiante.nie}</td>
                <td>${estudiante.nombre}</td>
                <td>${estudiante.grado}</td>
                <td>${estudiante.seccion}</td>
                <td>${estudiante.responsable}</td>
                <td>${estudiante.telefono}</td>

                <td>
                    <span class="badge ${estudiante.estado === 'Activo' ? 'active' : 'inactive'}">
                        ${estudiante.estado}
                    </span>
                </td>

                <td class="actions-cell">

                    <button class="btn-action see">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    <button class="btn-action edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button class="btn-action delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>

                </td>
            `;

            fila.querySelector(".see")
                .addEventListener("click", () => {

                    detalleEstudiante.innerHTML = `
                        <p><strong>NIE:</strong> ${estudiante.nie}</p>
                        <p><strong>Nombre:</strong> ${estudiante.nombre}</p>
                        <p><strong>Grado:</strong> ${estudiante.grado}</p>
                        <p><strong>Sección:</strong> ${estudiante.seccion}</p>
                        <p><strong>Responsable:</strong> ${estudiante.responsable}</p>
                        <p><strong>Teléfono:</strong> ${estudiante.telefono}</p>
                        <p><strong>Estado:</strong> ${estudiante.estado}</p>
                    `;

                    document.getElementById("modalVer").showModal();
                });

            fila.querySelector(".edit")
                .addEventListener("click", () => {

                    document.getElementById("edit_matricula_id").value =
                        estudiante.id;

                    document.getElementById("edit_nombre").value =
                        estudiante.nombre;

                    document.getElementById("modalEditar").showModal();
                });

            fila.querySelector(".delete")
                .addEventListener("click", () => {

                    if (confirm("¿Desea eliminar esta matrícula?")) {

                        matriculas = matriculas.filter(
                            m => m.id !== estudiante.id
                        );

                        renderizarTabla();
                    }
                });

            listaEstudiantes.appendChild(fila);
        });

        actualizarEstadisticas();
    }

    function actualizarEstadisticas() {

        totalMatriculas.textContent =
            matriculas.length;

        const activas =
            matriculas.filter(
                m => m.estado === "Activo"
            ).length;

        const inactivas =
            matriculas.filter(
                m => m.estado === "Inactivo"
            ).length;

        matriculasActivas.textContent =
            activas;

        matriculasInactivas.textContent =
            inactivas;

        matriculasAnio.textContent =
            matriculas.length;
    }

    document
        .getElementById("formEditar")
        .addEventListener("submit", function (e) {

            e.preventDefault();

            const id =
                document.getElementById("edit_matricula_id").value;

            const nombre =
                document.getElementById("edit_nombre").value;

            const estudiante =
                matriculas.find(
                    m => m.id == id
                );

            if (estudiante) {

                estudiante.nombre = nombre;

                renderizarTabla();

                document
                    .getElementById("modalEditar")
                    .close();
            }
        });
});