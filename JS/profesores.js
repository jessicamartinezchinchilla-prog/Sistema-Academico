// JS/profesores.js

document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('buscarProfesor');
    const filtroMateria = document.getElementById('filtroMateria');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filas = document.querySelectorAll('#listaProfesores tr');
    const mensajeVacio = document.getElementById('mensajeVacio');

    function filtrar() {
        const textoBusqueda = inputBuscar.value.toLowerCase();
        const materiaSeleccionada = filtroMateria.value.toLowerCase();
        const seccionSeleccionada = filtroSeccion.value.toLowerCase();
        
        let visibles = 0;

        filas.forEach(fila => {
            const nombre = fila.dataset.nombres ? (fila.dataset.nombres + ' ' + fila.dataset.apellidos).toLowerCase() : '';
            const dui = fila.dataset.dui ? fila.dataset.dui.toLowerCase() : '';
            const nip = fila.dataset.nip ? fila.dataset.nip.toLowerCase() : '';
            const materia = fila.dataset.materiaNombre || '';
            const seccion = fila.dataset.seccionNombre || '';

            // Busca en nombre, DUI o NIP
            const coincideBusqueda = nombre.includes(textoBusqueda) || dui.includes(textoBusqueda) || nip.includes(textoBusqueda);
            const coincideMateria = !materiaSeleccionada || materia.toLowerCase().includes(materiaSeleccionada);
            const coincideSeccion = !seccionSeleccionada || seccion.toLowerCase().includes(seccionSeleccionada);

            if (coincideBusqueda && coincideMateria && coincideSeccion) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        if (visibles === 0 && filas.length > 0) {
            mensajeVacio.classList.add('visible');
        } else {
            mensajeVacio.classList.remove('visible');
        }
    }

    inputBuscar.addEventListener('input', filtrar);
    filtroMateria.addEventListener('change', filtrar);
    filtroSeccion.addEventListener('change', filtrar);

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        let msg = '✅ Profesor agregado exitosamente!';
        if (urlParams.get('success') === 'editado') msg = '✅ Profesor actualizado exitosamente!';
        if (urlParams.get('success') === 'eliminado') msg = '🗑️ Profesor eliminado exitosamente!';
        alert(msg);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function verProfesor(btn) {
    const fila = btn.closest('tr');
    const d = fila.dataset;
    
    const contenido = `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Información Personal</h4>
            <p><strong>Nombre:</strong> ${d.nombres} ${d.apellidos}</p>
            <p><strong>DUI:</strong> ${d.dui || 'No registrado'}</p>
            <p><strong>NIP:</strong> ${d.nip || 'No registrado'}</p>
        </div>
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Contacto</h4>
            <p><strong>Correo:</strong> ${d.correo || 'No registrado'}</p>
            <p><strong>Teléfono:</strong> ${d.telefono || 'No registrado'}</p>
        </div>
        <div>
            <h4 style="color: #2647B8; margin-bottom: 10px;">Asignación Académica</h4>
            <p><strong>Materias:</strong> <span class="materia-badge">${d.materiaNombre || 'Sin asignar'}</span></p>
            <p style="margin-top: 10px;"><strong>Secciones:</strong></p>
            <div class="seccion-container" style="margin-top: 5px;">
                ${d.seccionNombre ? d.seccionNombre.split(', ').map(s => `<span class="seccion-badge">${s}</span>`).join('') : 'Sin asignar'}
            </div>
        </div>
    `;
    
    document.getElementById('contenidoVerProfesor').innerHTML = contenido;
    document.getElementById('modalVerProfesor').showModal();
}

function editarProfesor(btn) {
    const fila = btn.closest('tr');
    const d = fila.dataset;

    document.getElementById('edit_id').value = d.id;
    document.getElementById('edit_nombres').value = d.nombres;
    document.getElementById('edit_apellidos').value = d.apellidos;
    document.getElementById('edit_dui').value = d.dui;
    document.getElementById('edit_nip').value = d.nip;
    document.getElementById('edit_correo').value = d.correo;
    document.getElementById('edit_telefono').value = d.telefono;
    document.getElementById('edit_materia').value = d.materiaId;
    document.getElementById('edit_seccion').value = d.seccionId;

    document.getElementById('modalEditarProfesor').showModal();
}