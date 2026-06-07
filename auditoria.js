// ==============================
// DATOS DE PRUEBA
// ==============================
let registrosAuditoria = [
    {
        fecha: "07/06/2026 08:15",
        usuario: "Administrador",
        accion: "inicio_sesion",
        detalles: "Ingreso al sistema",
        modulo: "sistema",
        tipo: "Acceso"
    },
    {
        fecha: "07/06/2026 09:00",
        usuario: "Jessica",
        accion: "creacion",
        detalles: "Se registró un nuevo estudiante",
        modulo: "estudiantes",
        tipo: "Creación"
    },
    {
        fecha: "07/06/2026 10:30",
        usuario: "Carlos",
        accion: "modificacion",
        detalles: "Actualizó calificaciones",
        modulo: "calificaciones",
        tipo: "Modificación"
    },
    {
        fecha: "07/06/2026 11:20",
        usuario: "Administrador",
        accion: "eliminacion",
        detalles: "Eliminó una matrícula",
        modulo: "matriculas",
        tipo: "Eliminación"
    }
];

// ==============================
// INICIAR SISTEMA
// ==============================
document.addEventListener("DOMContentLoaded", () => {
    cargarUsuarios();
    mostrarRegistros(registrosAuditoria);
    actualizarEstadisticas();

    document.getElementById("buscarAuditoria")
        .addEventListener("input", filtrarRegistros);

    document.getElementById("filtroUsuario")
        .addEventListener("change", filtrarRegistros);

    document.getElementById("filtroAccion")
        .addEventListener("change", filtrarRegistros);

    document.getElementById("filtroModulo")
        .addEventListener("change", filtrarRegistros);
});

// ==============================
// CARGAR USUARIOS EN SELECT
// ==============================
function cargarUsuarios() {

    const usuarios = [...new Set(
        registrosAuditoria.map(r => r.usuario)
    )];

    const filtroUsuario =
        document.getElementById("filtroUsuario");

    const exportUsuario =
        document.getElementById("exp_usuario");

    usuarios.forEach(usuario => {

        let option1 = document.createElement("option");
        option1.value = usuario;
        option1.textContent = usuario;
        filtroUsuario.appendChild(option1);

        let option2 = document.createElement("option");
        option2.value = usuario;
        option2.textContent = usuario;
        exportUsuario.appendChild(option2);
    });
}

// ==============================
// MOSTRAR TABLA
// ==============================
function mostrarRegistros(registros) {

    const tbody =
        document.getElementById("listaAuditoria");

    const mensaje =
        document.getElementById("mensajeVacio");

    tbody.innerHTML = "";

    if (registros.length === 0) {

        mensaje.style.display = "block";

        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center;">
                    No se encontraron registros.
                </td>
            </tr>
        `;

        return;
    }

    mensaje.style.display = "none";

    registros.forEach(registro => {

        const fila = document.createElement("tr");

        fila.innerHTML = `
            <td>${registro.fecha}</td>
            <td>${registro.usuario}</td>
            <td>${registro.accion}</td>
            <td>${registro.detalles}</td>
            <td>${registro.modulo}</td>
            <td>${registro.tipo}</td>
        `;

        tbody.appendChild(fila);
    });
}

// ==============================
// FILTROS
// ==============================
function filtrarRegistros() {

    const texto =
        document.getElementById("buscarAuditoria")
        .value.toLowerCase();

    const usuario =
        document.getElementById("filtroUsuario")
        .value;

    const accion =
        document.getElementById("filtroAccion")
        .value;

    const modulo =
        document.getElementById("filtroModulo")
        .value;

    let resultado = registrosAuditoria.filter(registro => {

        const coincideBusqueda =
            registro.usuario.toLowerCase().includes(texto) ||
            registro.detalles.toLowerCase().includes(texto) ||
            registro.accion.toLowerCase().includes(texto);

        const coincideUsuario =
            usuario === "" ||
            registro.usuario === usuario;

        const coincideAccion =
            accion === "" ||
            registro.accion === accion;

        const coincideModulo =
            modulo === "" ||
            registro.modulo === modulo;

        return coincideBusqueda &&
               coincideUsuario &&
               coincideAccion &&
               coincideModulo;
    });

    mostrarRegistros(resultado);
}

// ==============================
// ESTADÍSTICAS
// ==============================
function actualizarEstadisticas() {

    document.getElementById("totalRegistrosAud")
        .textContent = registrosAuditoria.length;

    document.getElementById("totalIniciosSesion")
        .textContent = registrosAuditoria.filter(
            r => r.accion === "inicio_sesion"
        ).length;

    document.getElementById("totalAgregados")
        .textContent = registrosAuditoria.filter(
            r => r.accion === "creacion"
        ).length;

    document.getElementById("totalModificaciones")
        .textContent = registrosAuditoria.filter(
            r => r.accion === "modificacion"
        ).length;

    document.getElementById("totalEliminaciones")
        .textContent = registrosAuditoria.filter(
            r => r.accion === "eliminacion"
        ).length;
}