// =====================================
// DATOS DE PRUEBA
// =====================================

let calificaciones = [
    {
        id: 1,
        nie: "2025001",
        nombre: "Juan Pérez",
        promedio: 8.5,
        estado: "Aprobado",
        materia: "Matemática",
        seccion: "A"
    },
    {
        id: 2,
        nie: "2025002",
        nombre: "María López",
        promedio: 5.8,
        estado: "Reprobado",
        materia: "Ciencias",
        seccion: "B"
    },
    {
        id: 3,
        nie: "2025003",
        nombre: "Carlos Ramírez",
        promedio: 9.2,
        estado: "Aprobado",
        materia: "Lenguaje",
        seccion: "A"
    }
];

// =====================================
// INICIO
// =====================================

document.addEventListener("DOMContentLoaded", () => {

    cargarFiltros();

    mostrarCalificaciones(calificaciones);

    actualizarEstadisticas();

    document.getElementById("buscarCalificacion")
        .addEventListener("input", filtrarCalificaciones);

    document.getElementById("filtroMateria")
        .addEventListener("change", filtrarCalificaciones);

    document.getElementById("filtroSeccion")
        .addEventListener("change", filtrarCalificaciones);

    document.getElementById("filtroEstado")
        .addEventListener("change", filtrarCalificaciones);

    document.querySelector("#modalNota form.modal-form")
        .addEventListener("submit", agregarCalificacion);

    document.querySelector("#modalEditar form.modal-form")
        .addEventListener("submit", actualizarCalificacion);
});

// =====================================
// MOSTRAR TABLA
// =====================================

function mostrarCalificaciones(datos) {

    const tabla = document.getElementById("listaCalificaciones");

    const mensaje = document.getElementById("mensajeVacio");

    tabla.innerHTML = "";

    if (datos.length === 0) {

        mensaje.style.display = "block";

        tabla.innerHTML = `
        <tr>
            <td colspan="5">No se encontraron registros.</td>
        </tr>
        `;

        return;
    }

    mensaje.style.display = "none";

    datos.forEach(calificacion => {

        tabla.innerHTML += `
        <tr>
            <td>${calificacion.nie}</td>
            <td>${calificacion.nombre}</td>
            <td>${calificacion.promedio}</td>
            <td>${calificacion.estado}</td>
            <td class="actions-cell">

                <button
                    class="btn-action edit"
                    onclick="editarRegistro(${calificacion.id})">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>

                <button
                    class="btn-action delete"
                    onclick="eliminarRegistro(${calificacion.id})">
                    <i class="fa-solid fa-trash"></i>
                </button>

            </td>
        </tr>
        `;
    });
}

// =====================================
// ESTADÍSTICAS
// =====================================

function actualizarEstadisticas() {

    const aprobados = calificaciones.filter(
        c => c.estado === "Aprobado"
    ).length;

    const reprobados = calificaciones.filter(
        c => c.estado === "Reprobado"
    ).length;

    let suma = 0;

    calificaciones.forEach(c => {
        suma += parseFloat(c.promedio);
    });

    let promedioGeneral = 0;

    if (calificaciones.length > 0) {
        promedioGeneral =
            (suma / calificaciones.length).toFixed(2);
    }

    document.getElementById("totalAprobados")
        .textContent = aprobados;

    document.getElementById("totalReprobados")
        .textContent = reprobados;

    document.getElementById("promedioGeneral")
        .textContent = promedioGeneral;
}

// =====================================
// FILTROS
// =====================================

function filtrarCalificaciones() {

    const texto =
        document.getElementById("buscarCalificacion")
        .value.toLowerCase();

    const materia =
        document.getElementById("filtroMateria")
        .value;

    const seccion =
        document.getElementById("filtroSeccion")
        .value;

    const estado =
        document.getElementById("filtroEstado")
        .value;

    const resultado = calificaciones.filter(c => {

        const coincideTexto =
            c.nie.toLowerCase().includes(texto) ||
            c.nombre.toLowerCase().includes(texto);

        const coincideMateria =
            materia === "" ||
            c.materia === materia;

        const coincideSeccion =
            seccion === "" ||
            c.seccion === seccion;

        const coincideEstado =
            estado === "" ||
            c.estado === estado;

        return coincideTexto &&
               coincideMateria &&
               coincideSeccion &&
               coincideEstado;
    });

    mostrarCalificaciones(resultado);
}

// =====================================
// CARGAR FILTROS
// =====================================

function cargarFiltros() {

    const materias = [...new Set(
        calificaciones.map(c => c.materia)
    )];

    const secciones = [...new Set(
        calificaciones.map(c => c.seccion)
    )];

    const filtroMateria =
        document.getElementById("filtroMateria");

    const filtroSeccion =
        document.getElementById("filtroSeccion");

    materias.forEach(materia => {

        filtroMateria.innerHTML += `
        <option value="${materia}">
            ${materia}
        </option>`;
    });

    secciones.forEach(seccion => {

        filtroSeccion.innerHTML += `
        <option value="${seccion}">
            ${seccion}
        </option>`;
    });
}

// =====================================
// AGREGAR
// =====================================

function agregarCalificacion(e) {

    e.preventDefault();

    const nie =
        document.getElementById("nota_nie").value;

    const nota =
        parseFloat(
            document.getElementById("nota_valor").value
        );

    const nuevo = {
        id: Date.now(),
        nie: nie,
        nombre: "Nuevo Estudiante",
        promedio: nota,
        estado: nota >= 6 ? "Aprobado" : "Reprobado",
        materia: "Pendiente",
        seccion: "Pendiente"
    };

    calificaciones.push(nuevo);

    mostrarCalificaciones(calificaciones);

    actualizarEstadisticas();

    document.getElementById("modalNota").close();

    e.target.reset();
}

// =====================================
// ELIMINAR
// =====================================

function eliminarRegistro(id) {

    if (!confirm("¿Desea eliminar esta calificación?")) {
        return;
    }

    calificaciones =
        calificaciones.filter(c => c.id !== id);

    mostrarCalificaciones(calificaciones);

    actualizarEstadisticas();
}

// =====================================
// EDITAR
// =====================================

function editarRegistro(id) {

    const registro =
        calificaciones.find(c => c.id === id);

    if (!registro) return;

    document.getElementById("edit_calificacion_id").value =
        registro.id;

    document.getElementById("edit_nie").value =
        registro.nie;

    document.getElementById("edit_valor").value =
        registro.promedio;

    document.getElementById("modalEditar")
        .showModal();
}

// =====================================
// ACTUALIZAR
// =====================================

function actualizarCalificacion(e) {

    e.preventDefault();

    const id =
        Number(
            document.getElementById(
                "edit_calificacion_id"
            ).value
        );

    const nota =
        parseFloat(
            document.getElementById(
                "edit_valor"
            ).value
        );

    const registro =
        calificaciones.find(c => c.id === id);

    if (!registro) return;

    registro.promedio = nota;

    registro.estado =
        nota >= 6
            ? "Aprobado"
            : "Reprobado";

    mostrarCalificaciones(calificaciones);

    actualizarEstadisticas();

    document.getElementById("modalEditar")
        .close();
}