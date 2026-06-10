// calificaciones.js

document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("listaCalificaciones");
    const filas = tabla.getElementsByTagName("tr");

    const buscar = document.getElementById("buscarCalificacion");
    const filtroMateria = document.getElementById("filtroMateria");
    const filtroSeccion = document.getElementById("filtroSeccion");
    const filtroEstado = document.getElementById("filtroEstado");

    const mensajeVacio = document.getElementById("mensajeVacio");

    const totalAprobados = document.getElementById("totalAprobados");
    const totalReprobados = document.getElementById("totalReprobados");
    const promedioGeneral = document.getElementById("promedioGeneral");


    /* ==================================
       FILTRAR TABLA
    ================================== */

    function filtrarTabla() {

        const textoBusqueda = buscar.value.toLowerCase();
        const materia = filtroMateria.value.toLowerCase();
        const seccion = filtroSeccion.value.toLowerCase();
        const estado = filtroEstado.value.toLowerCase();

        let visibles = 0;

        Array.from(filas).forEach(fila => {

            const textoFila = fila.textContent.toLowerCase();

            const coincideBusqueda =
                textoFila.includes(textoBusqueda);

            const coincideMateria =
                materia === "" ||
                textoFila.includes(materia);

            const coincideSeccion =
                seccion === "" ||
                textoFila.includes(seccion);

            const coincideEstado =
                estado === "" ||
                textoFila.includes(estado);

            const mostrar =
                coincideBusqueda &&
                coincideMateria &&
                coincideSeccion &&
                coincideEstado;

            fila.style.display = mostrar ? "" : "none";

            if (mostrar) visibles++;
        });

        mensajeVacio.style.display =
            visibles === 0 ? "block" : "none";

        actualizarEstadisticas();
    }

    buscar.addEventListener("input", filtrarTabla);
    filtroMateria.addEventListener("change", filtrarTabla);
    filtroSeccion.addEventListener("change", filtrarTabla);
    filtroEstado.addEventListener("change", filtrarTabla);


    /* ==================================
       ESTADÍSTICAS
    ================================== */

    function actualizarEstadisticas() {

        let aprobados = 0;
        let reprobados = 0;

        let sumaPromedios = 0;
        let cantidadPromedios = 0;

        Array.from(filas).forEach(fila => {

            if (fila.style.display === "none") return;

            const celdas = fila.querySelectorAll("td");

            if (celdas.length < 4) return;

            const promedioTexto = celdas[2].textContent.trim();
            const estadoTexto = celdas[3].textContent.trim().toLowerCase();

            const promedio = parseFloat(promedioTexto);

            if (!isNaN(promedio)) {

                sumaPromedios += promedio;
                cantidadPromedios++;
            }

            if (estadoTexto.includes("aprobado")) {
                aprobados++;
            }

            if (estadoTexto.includes("reprobado")) {
                reprobados++;
            }
        });

        totalAprobados.textContent = aprobados;
        totalReprobados.textContent = reprobados;

        promedioGeneral.textContent =
            cantidadPromedios > 0
                ? (sumaPromedios / cantidadPromedios).toFixed(1)
                : "0";
    }

    actualizarEstadisticas();


    /* ==================================
       VALIDACIÓN AGREGAR NOTA
    ================================== */

    const formNota =
        document.querySelector("#modalNota .modal-form");

    formNota.addEventListener("submit", function(e){

        const nie =
            document.getElementById("nota_nie");

        const nota =
            document.getElementById("nota_valor");

        const periodo =
            document.getElementById("nota_periodo");

        if(nie.value.trim() === ""){

            e.preventDefault();
            alert("Ingrese el NIE del estudiante.");
            nie.focus();
            return;
        }

        if(nota.value === ""){

            e.preventDefault();
            alert("Ingrese una calificación.");
            nota.focus();
            return;
        }

        const valorNota = parseFloat(nota.value);

        if(valorNota < 0 || valorNota > 100){

            e.preventDefault();
            alert("La calificación debe estar entre 0 y 100.");
            nota.focus();
            return;
        }

        if(periodo.value === ""){

            e.preventDefault();
            alert("Seleccione un período.");
            periodo.focus();
        }
    });


    /* ==================================
       VALIDACIÓN EDITAR NOTA
    ================================== */

    const formEditar =
        document.querySelector("#modalEditar .modal-form");

    formEditar.addEventListener("submit", function(e){

        const nota =
            document.getElementById("edit_valor");

        const valorNota = parseFloat(nota.value);

        if(valorNota < 0 || valorNota > 100){

            e.preventDefault();

            alert(
                "La calificación debe estar entre 0 y 100."
            );

            nota.focus();
        }
    });


    /* ==================================
       VALIDACIÓN PDF
    ================================== */

    const formPDF =
        document.querySelector("#modalPDF .modal-form");

    formPDF.addEventListener("submit", function(e){

        const tipo =
            document.getElementById("pdf_tipo");

        if(tipo.value === ""){

            e.preventDefault();

            alert(
                "Seleccione un tipo de reporte."
            );

            tipo.focus();
        }
    });


    /* ==================================
       CONFIRMAR ELIMINACIÓN
    ================================== */

    document.addEventListener("click", function(e){

        const botonEliminar =
            e.target.closest(".btn-action.delete");

        if(!botonEliminar) return;

        const confirmar = confirm(
            "¿Está seguro que desea eliminar esta calificación?"
        );

        if(!confirmar){

            e.preventDefault();
        }
    });


    /* ==================================
       CERRAR MODALES CON ESC
    ================================== */

    document.addEventListener("keydown", function(e){

        if(e.key === "Escape"){

            document.querySelectorAll("dialog")
                .forEach(modal => {

                    if(modal.open){
                        modal.close();
                    }
                });
        }
    });

});