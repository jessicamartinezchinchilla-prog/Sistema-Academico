document.addEventListener("DOMContentLoaded", () => {

    const listaMaterias = document.getElementById("listaMaterias");
    const mensajeVacio = document.getElementById("mensajeVacio");

    const totalMaterias = document.getElementById("totalMaterias");
    const totalDocentes = document.getElementById("totalDocentes");

    const formularioMateria =
        document.querySelector("#modalMateria .modal-form");

    let materias = [];

    actualizarEstadisticas();

    formularioMateria.addEventListener("submit", function (e) {

        e.preventDefault();

        const codigo =
            document.getElementById("mat_codigo").value;

        const nombre =
            document.getElementById("mat_nombre").value;

        const descripcion =
            document.getElementById("mat_descripcion").value;

        const docente =
            document.getElementById("mat_docente").value || "Sin asignar";

        const nuevaMateria = {
            id: Date.now(),
            codigo,
            nombre,
            descripcion,
            docente
        };

        materias.push(nuevaMateria);

        renderizarMaterias();

        formularioMateria.reset();

        document.getElementById("modalMateria").close();
    });

    function renderizarMaterias() {

        listaMaterias.innerHTML = "";

        if (materias.length === 0) {
            mensajeVacio.style.display = "block";
        } else {
            mensajeVacio.style.display = "none";
        }

        materias.forEach(materia => {

            const tarjeta = document.createElement("article");

            tarjeta.classList.add("subject-card");

            tarjeta.innerHTML = `
                <h3>${materia.nombre}</h3>
                <p><strong>Código:</strong> ${materia.codigo}</p>
                <p>${materia.descripcion}</p>
                <p><strong>Docente:</strong> ${materia.docente}</p>

                <div class="card-actions">
                    <button class="editar-btn">
                        <i class="fa-solid fa-pen"></i> Editar
                    </button>

                    <button class="eliminar-btn">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </div>
            `;

            tarjeta.querySelector(".eliminar-btn")
                .addEventListener("click", () => {

                    if (confirm("¿Desea eliminar esta materia?")) {

                        materias = materias.filter(
                            m => m.id !== materia.id
                        );

                        renderizarMaterias();
                    }
                });

            tarjeta.querySelector(".editar-btn")
                .addEventListener("click", () => {

                    document.getElementById("edit_materia_id").value =
                        materia.id;

                    document.getElementById("edit_codigo").value =
                        materia.codigo;

                    document.getElementById("edit_nombre").value =
                        materia.nombre;

                    document.getElementById("edit_descripcion").value =
                        materia.descripcion;

                    document.getElementById("modalEditar").showModal();
                });

            listaMaterias.appendChild(tarjeta);
        });

        actualizarEstadisticas();
    }

    function actualizarEstadisticas() {

        totalMaterias.textContent = materias.length;

        const docentesUnicos = new Set(
            materias.map(m => m.docente)
        );

        totalDocentes.textContent =
            materias.length === 0
                ? 0
                : docentesUnicos.size;
    }

});