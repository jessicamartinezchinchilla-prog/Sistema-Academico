document.addEventListener("DOMContentLoaded", () => {

    let historial =
        JSON.parse(localStorage.getItem("historialAcademico")) || [];

    const listaHistorial = document.getElementById("listaHistorial");
    const mensajeVacio = document.getElementById("mensajeVacio");

    const totalRegistros = document.getElementById("totalRegistros");
    const totalAprobados = document.getElementById("totalAprobadosHist");
    const totalReprobados = document.getElementById("totalReprobadosHist");
    const promedioGeneral = document.getElementById("promedioGeneralHist");

    const buscar = document.getElementById("buscarHistorial");
    const filtroSeccion = document.getElementById("filtroSeccionHist");
    const filtroAnio = document.getElementById("filtroAnioHist");
    const filtroPeriodo = document.getElementById("filtroPeriodoHist");
    const filtroEstado = document.getElementById("filtroEstadoHist");

    function actualizarEstadisticas() {

        totalRegistros.textContent = historial.length;

        const aprobados = historial.filter(
            r => r.estado === "Aprobado"
        ).length;

        const reprobados = historial.filter(
            r => r.estado === "Reprobado"
        ).length;

        totalAprobados.textContent = aprobados;
        totalReprobados.textContent = reprobados;

        if (historial.length > 0) {

            const suma = historial.reduce(
                (acc, item) => acc + Number(item.promedio),
                0
            );

            promedioGeneral.textContent =
                (suma / historial.length).toFixed(2);

        } else {

            promedioGeneral.textContent = "0";
        }
    }

    function cargarSecciones() {

        const secciones = [
            ...new Set(historial.map(r => r.seccion))
        ];

        secciones.forEach(seccion => {

            const option = document.createElement("option");

            option.value = seccion;
            option.textContent = seccion;

            filtroSeccion.appendChild(option);
        });
    }

    function mostrarHistorial(datos = historial) {

        listaHistorial.innerHTML = "";

        if (datos.length === 0) {

            mensajeVacio.style.display = "block";

            listaHistorial.innerHTML = `
                <tr>
                    <td colspan="8">
                        No se encontraron registros.
                    </td>
                </tr>
            `;

            return;
        }

        mensajeVacio.style.display = "none";

        datos.forEach((registro, index) => {

            const fila = document.createElement("tr");

            fila.innerHTML = `
                <td>${registro.nie}</td>
                <td>${registro.nombre}</td>
                <td>${registro.seccion}</td>
                <td>${registro.anio}</td>
                <td>${registro.periodo}</td>
                <td>${registro.promedio}</td>
                <td>${registro.estado}</td>
                <td class="actions-cell">
                    <button
                        type="button"
                        class="btn-action see"
                        data-index="${index}">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </td>
            `;

            listaHistorial.appendChild(fila);
        });

        activarBotonesDetalles(datos);
    }

    function activarBotonesDetalles(datos) {

        document.querySelectorAll(".see").forEach(btn => {

            btn.addEventListener("click", () => {

                const index = btn.dataset.index;
                const registro = datos[index];

                document.getElementById("detalleHistorial").innerHTML = `
                    <p><strong>NIE:</strong> ${registro.nie}</p>
                    <p><strong>Nombre:</strong> ${registro.nombre}</p>
                    <p><strong>Sección:</strong> ${registro.seccion}</p>
                    <p><strong>Año:</strong> ${registro.anio}</p>
                    <p><strong>Período:</strong> ${registro.periodo}</p>
                    <p><strong>Promedio:</strong> ${registro.promedio}</p>
                    <p><strong>Estado:</strong> ${registro.estado}</p>
                `;

                document
                    .getElementById("modalVerDetalles")
                    .showModal();
            });
        });
    }

    function aplicarFiltros() {

        const texto = buscar.value.toLowerCase();

        const filtrados = historial.filter(registro => {

            const coincideBusqueda =
                registro.nie.toLowerCase().includes(texto) ||
                registro.nombre.toLowerCase().includes(texto);

            const coincideSeccion =
                !filtroSeccion.value ||
                registro.seccion === filtroSeccion.value;

            const coincideAnio =
                !filtroAnio.value ||
                registro.anio == filtroAnio.value;

            const coincidePeriodo =
                !filtroPeriodo.value ||
                registro.periodo == filtroPeriodo.value;

            const coincideEstado =
                !filtroEstado.value ||
                registro.estado === filtroEstado.value;

            return (
                coincideBusqueda &&
                coincideSeccion &&
                coincideAnio &&
                coincidePeriodo &&
                coincideEstado
            );
        });

        mostrarHistorial(filtrados);
    }

    buscar.addEventListener("input", aplicarFiltros);
    filtroSeccion.addEventListener("change", aplicarFiltros);
    filtroAnio.addEventListener("change", aplicarFiltros);
    filtroPeriodo.addEventListener("change", aplicarFiltros);
    filtroEstado.addEventListener("change", aplicarFiltros);

    actualizarEstadisticas();
    cargarSecciones();
    mostrarHistorial();
});