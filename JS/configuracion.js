// ====================================
// CONFIGURACIÓN DEL SISTEMA
// ====================================

document.addEventListener("DOMContentLoaded", () => {

    cargarConfiguracion();

    cargarEstadisticas();

    activarFormularios();

    activarPeriodos();

    activarCerrarSesion();

});

// ====================================
// CARGAR CONFIGURACIÓN
// ====================================

function cargarConfiguracion() {

    const nombreSistema =
        localStorage.getItem("nombreSistema");

    const anioLectivo =
        localStorage.getItem("anioLectivo");

    const notaMinima =
        localStorage.getItem("notaMinima");

    const escalaMaxima =
        localStorage.getItem("escalaMaxima");

    if (nombreSistema) {
        document.getElementById(
            "conf_nombre_sistema"
        ).value = nombreSistema;
    }

    if (anioLectivo) {
        document.getElementById(
            "conf_anio_lectivo"
        ).value = anioLectivo;
    }

    if (notaMinima) {
        document.getElementById(
            "conf_nota_minima"
        ).value = notaMinima;
    }

    if (escalaMaxima) {
        document.getElementById(
            "conf_escala_maxima"
        ).value = escalaMaxima;
    }
}

// ====================================
// ESTADÍSTICAS
// ====================================

function cargarEstadisticas() {

    document.getElementById(
        "totalEstudiantesConf"
    ).textContent = 150;

    document.getElementById(
        "totalProfesoresConf"
    ).textContent = 18;

    document.getElementById(
        "totalSeccionesConf"
    ).textContent = 12;

    document.getElementById(
        "totalMateriasConf"
    ).textContent = 25;
}

// ====================================
// FORMULARIOS
// ====================================

function activarFormularios() {

    const formularios =
        document.querySelectorAll(".config-form");

    formularios.forEach(formulario => {

        formulario.addEventListener(
            "submit",
            function(e) {

                e.preventDefault();

                guardarConfiguracion();

            }
        );
    });
}

// ====================================
// GUARDAR CONFIGURACIÓN
// ====================================

function guardarConfiguracion() {

    localStorage.setItem(
        "nombreSistema",
        document.getElementById(
            "conf_nombre_sistema"
        ).value
    );

    localStorage.setItem(
        "anioLectivo",
        document.getElementById(
            "conf_anio_lectivo"
        ).value
    );

    localStorage.setItem(
        "notaMinima",
        document.getElementById(
            "conf_nota_minima"
        ).value
    );

    localStorage.setItem(
        "escalaMaxima",
        document.getElementById(
            "conf_escala_maxima"
        ).value
    );

    alert("Configuración guardada correctamente.");
}

// ====================================
// PERÍODOS ACADÉMICOS
// ====================================

function activarPeriodos() {

    const botonesEditar =
        document.querySelectorAll(
            ".period-item .edit"
        );

    botonesEditar.forEach((boton, indice) => {

        boton.addEventListener("click", () => {

            const nuevoNombre = prompt(
                "Editar nombre del período:",
                `Período ${indice + 1}`
            );

            if (
                nuevoNombre &&
                nuevoNombre.trim() !== ""
            ) {

                boton.parentElement.querySelector(
                    "span"
                ).textContent = nuevoNombre;
            }
        });
    });
}

// ====================================
// CERRAR SESIÓN
// ====================================

function activarCerrarSesion() {

    const formularioCerrar =
        document.querySelector(
            '.danger-zone form'
        );

    formularioCerrar.addEventListener(
        "submit",
        function(e) {

            const confirmar = confirm(
                "¿Desea cerrar sesión?"
            );

            if (!confirmar) {
                e.preventDefault();
            }
        }
    );
}