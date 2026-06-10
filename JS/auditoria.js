document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("listaAuditoria");
    const filas = tabla.getElementsByTagName("tr");

    const buscar = document.getElementById("buscarAuditoria");
    const filtroUsuario = document.getElementById("filtroUsuario");
    const filtroAccion = document.getElementById("filtroAccion");
    const filtroModulo = document.getElementById("filtroModulo");

    const mensajeVacio = document.getElementById("mensajeVacio");

    const totalRegistros = document.getElementById("totalRegistrosAud");
    const totalInicios = document.getElementById("totalIniciosSesion");
    const totalAgregados = document.getElementById("totalAgregados");
    const totalModificaciones = document.getElementById("totalModificaciones");
    const totalEliminaciones = document.getElementById("totalEliminaciones");


    /* ==================================
       FILTROS DE TABLA
    ================================== */

    function filtrarTabla() {

        const textoBusqueda = buscar.value.toLowerCase();
        const usuario = filtroUsuario.value.toLowerCase();
        const accion = filtroAccion.value.toLowerCase();
        const modulo = filtroModulo.value.toLowerCase();

        let visibles = 0;

        Array.from(filas).forEach(fila => {

            const textoFila = fila.textContent.toLowerCase();

            const coincideBusqueda =
                textoFila.includes(textoBusqueda);

            const coincideUsuario =
                usuario === "" ||
                textoFila.includes(usuario);

            const coincideAccion =
                accion === "" ||
                textoFila.includes(accion);

            const coincideModulo =
                modulo === "" ||
                textoFila.includes(modulo);

            const mostrar =
                coincideBusqueda &&
                coincideUsuario &&
                coincideAccion &&
                coincideModulo;

            fila.style.display = mostrar ? "" : "none";

            if (mostrar) visibles++;
        });

        mensajeVacio.style.display =
            visibles === 0 ? "block" : "none";
    }


    buscar.addEventListener("input", filtrarTabla);
    filtroUsuario.addEventListener("change", filtrarTabla);
    filtroAccion.addEventListener("change", filtrarTabla);
    filtroModulo.addEventListener("change", filtrarTabla);


    /* ==================================
       ESTADÍSTICAS
    ================================== */

    function actualizarEstadisticas() {

        let registros = 0;
        let inicios = 0;
        let agregados = 0;
        let modificaciones = 0;
        let eliminaciones = 0;

        Array.from(filas).forEach(fila => {

            if (fila.style.display === "none") return;

            registros++;

            const texto = fila.textContent.toLowerCase();

            if (texto.includes("inicio")) {
                inicios++;
            }

            if (
                texto.includes("creacion") ||
                texto.includes("agregado")
            ) {
                agregados++;
            }

            if (
                texto.includes("modificacion")
            ) {
                modificaciones++;
            }

            if (
                texto.includes("eliminacion")
            ) {
                eliminaciones++;
            }
        });

        totalRegistros.textContent = registros;
        totalInicios.textContent = inicios;
        totalAgregados.textContent = agregados;
        totalModificaciones.textContent = modificaciones;
        totalEliminaciones.textContent = eliminaciones;
    }

    actualizarEstadisticas();

    buscar.addEventListener("input", actualizarEstadisticas);
    filtroUsuario.addEventListener("change", actualizarEstadisticas);
    filtroAccion.addEventListener("change", actualizarEstadisticas);
    filtroModulo.addEventListener("change", actualizarEstadisticas);


    /* ==================================
       VALIDACIÓN EXPORTAR
    ================================== */

    const formularioExportar =
        document.querySelector("#modalExportar .modal-form");

    formularioExportar.addEventListener("submit", function(e){

        const formato =
            document.getElementById("exp_formato");

        if(formato.value === ""){

            e.preventDefault();

            alert(
                "Seleccione un formato para exportar."
            );

            formato.focus();
        }
    });


    /* ==================================
       CERRAR MODAL CON ESC
    ================================== */

    document.addEventListener("keydown", function(e){

        if(e.key === "Escape"){

            const modal =
                document.getElementById("modalExportar");

            if(modal.open){
                modal.close();
            }
        }
    });

});