// JS/auditoria.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // FILTROS Y BÚSQUEDA
    // ==========================================
    const inputBuscar = document.getElementById('buscarAuditoria');
    const filtroUsuario = document.getElementById('filtroUsuario');
    const filtroAccion = document.getElementById('filtroAccion');
    const filtroModulo = document.getElementById('filtroModulo');
    const filas = document.querySelectorAll('#listaAuditoria tr[data-usuario]');
    const mensajeVacio = document.getElementById('mensajeVacio');

    function filtrarAuditoria() {
        const texto = inputBuscar ? inputBuscar.value.toLowerCase().trim() : '';
        const usuario = filtroUsuario ? filtroUsuario.value.toLowerCase() : '';
        const accion = filtroAccion ? filtroAccion.value : '';
        const modulo = filtroModulo ? filtroModulo.value.toLowerCase() : '';

        let visibles = 0;

        filas.forEach(fila => {
            const dataUsuario = fila.dataset.usuario || '';
            const dataAccion = fila.dataset.accion || '';
            const dataModulo = fila.dataset.modulo || '';
            const dataDescripcion = fila.dataset.descripcion || '';

            const coincideBusqueda = !texto || 
                dataUsuario.includes(texto) || 
                dataAccion.includes(texto) || 
                dataDescripcion.includes(texto);
            
            const coincideUsuario = !usuario || dataUsuario === usuario;
            const coincideAccion = !accion || dataAccion === accion;
            const coincideModulo = !modulo || dataModulo === modulo;

            if (coincideBusqueda && coincideUsuario && coincideAccion && coincideModulo) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        // Mostrar mensaje si no hay resultados
        if (mensajeVacio) {
            mensajeVacio.style.display = visibles === 0 ? 'block' : 'none';
            mensajeVacio.textContent = visibles === 0 
                ? 'No se encontraron registros con los filtros aplicados.' 
                : '';
        }
    }

    if (inputBuscar) inputBuscar.addEventListener('input', filtrarAuditoria);
    if (filtroUsuario) filtroUsuario.addEventListener('change', filtrarAuditoria);
    if (filtroAccion) filtroAccion.addEventListener('change', filtrarAuditoria);
    if (filtroModulo) filtroModulo.addEventListener('change', filtrarAuditoria);
});