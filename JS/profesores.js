// JS/profesores.js

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. VALIDACIONES Y FORMATEO AUTOMÁTICO ---
    configurarFormateo('add_dui', 'dui');
    configurarFormateo('add_telefono', 'tel');
    configurarNombres('add_nombres');
    configurarNombres('add_apellidos');

    configurarFormateo('edit_dui', 'dui');
    configurarFormateo('edit_telefono', 'tel');
    configurarNombres('edit_nombres');
    configurarNombres('edit_apellidos');

    // --- 2. FILTROS DE BÚSQUEDA ---
    const inputBuscar = document.getElementById('buscarProfesor');
    const filtroMateria = document.getElementById('filtroMateria');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filas = document.querySelectorAll('#listaProfesores tr');
    const mensajeVacio = document.getElementById('mensajeVacio');

    function filtrar() {
        const texto = inputBuscar.value.toLowerCase();
        const mat = filtroMateria.value.toLowerCase();
        const sec = filtroSeccion.value.toLowerCase();
        let visibles = 0;

        filas.forEach(fila => {
            const nombre = (fila.dataset.nombres + ' ' + fila.dataset.apellidos).toLowerCase();
            const dui = fila.dataset.dui.toLowerCase();
            const nip = fila.dataset.nip.toLowerCase();
            const materia = fila.dataset.materiaNombre || '';
            const seccion = fila.dataset.seccionNombre || '';

            const coincide = (nombre.includes(texto) || dui.includes(texto) || nip.includes(texto)) &&
                             (!mat || materia.toLowerCase().includes(mat)) &&
                             (!sec || seccion.toLowerCase().includes(sec));

            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        mensajeVacio.classList.toggle('visible', visibles === 0 && filas.length > 0);
    }

    inputBuscar.addEventListener('input', filtrar);
    filtroMateria.addEventListener('change', filtrar);
    filtroSeccion.addEventListener('change', filtrar);

    // --- 3. MENSAJES DE ERROR ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        if (urlParams.get('error') === 'gmail') {
            alert('⚠️ El correo debe ser obligatoriamente @gmail.com');
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // --- 4. MENSAJES DE ÉXITO ---
    if (urlParams.has('success')) {
        let msg = '✅ Profesor agregado exitosamente!';
        if (urlParams.get('success') === 'editado') msg = '✅ Profesor actualizado!';
        if (urlParams.get('success') === 'eliminado') msg = '🗑️ Profesor eliminado!';
        alert(msg);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Función para formatear DUI (XXXXXXXX-X) y Teléfono (XXXX-XXXX)
function configurarFormateo(idInput, tipo) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, ''); // Solo números
        
        if (tipo === 'dui') {
            if (val.length > 8) val = val.slice(0, 8) + '-' + val.slice(8, 9);
        } else if (tipo === 'tel') {
            if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4, 8);
        }
        e.target.value = val;
    });
}

// Función para formatear nombres (Mayúsculas y excepciones como "de")
function configurarNombres(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('blur', (e) => {
        const palabras = e.target.value.split(' ');
        const excepciones = ['de', 'la', 'las', 'los', 'y', 'del', 'van', 'von'];
        
        const formateado = palabras.map(p => {
            if (p.length === 0) return p;
            if (excepciones.includes(p.toLowerCase())) return p.toLowerCase();
            return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
        }).join(' ');
        
        e.target.value = formateado;
    });
}

// Validación final antes de enviar el formulario
function validarFormulario(form) {
    const nombres = document.getElementById(form.querySelector('[name="nombres"]').id).value.trim().split(' ');
    const apellidos = document.getElementById(form.querySelector('[name="apellidos"]').id).value.trim().split(' ');
    const secciones = form.querySelectorAll('input[name="id_seccion[]"]:checked');
    const correo = form.querySelector('[name="correo"]').value.trim();

    // Validar nombres
    if (nombres.length < 2) {
        alert('⚠️ Debe ingresar al menos dos nombres.');
        return false;
    }
    
    // Validar apellidos
    if (apellidos.length < 2) {
        alert('⚠️ Debe ingresar al menos dos apellidos.');
        return false;
    }
    
    // Validar secciones
    if (secciones.length === 0) {
        alert('⚠️ Debe seleccionar al menos una sección.');
        return false;
    }
    
    // Validar que el correo sea Gmail
    const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    if (!emailRegex.test(correo)) {
        alert('⚠️ El correo debe ser obligatoriamente @gmail.com');
        return false; // Esto evita que el formulario se envíe
    }
    
    return true; // Si todo está bien, se envía
}

// --- FUNCIONES DE MODALES ---
function abrirModalAgregar() {
    // Limpiar formulario al abrir
    document.querySelector('#modalProfesor form[action*="action.php"]').reset();
    document.getElementById('modalProfesor').showModal();
}

function verProfesor(btn) {
    const d = btn.closest('tr').dataset;
    document.getElementById('contenidoVerProfesor').innerHTML = `
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
    document.getElementById('modalVerProfesor').showModal();
}

function editarProfesor(btn) {
    const d = btn.closest('tr').dataset;

    document.getElementById('edit_id').value = d.id;
    document.getElementById('edit_nombres').value = d.nombres;
    document.getElementById('edit_apellidos').value = d.apellidos;
    document.getElementById('edit_dui').value = d.dui;
    document.getElementById('edit_nip').value = d.nip;
    document.getElementById('edit_correo').value = d.correo;
    document.getElementById('edit_telefono').value = d.telefono;
    document.getElementById('edit_materia').value = d.materiaId;

    // Generar checkboxes de secciones y marcar las asignadas
    const container = document.getElementById('edit_secciones_container');
    container.innerHTML = '';
    const seccionesAsignadas = d.seccionIds ? d.seccionIds.split(',') : [];
    
    window.seccionesSistema.forEach(s => {
        const isChecked = seccionesAsignadas.includes(s.id) ? 'checked' : '';
        container.innerHTML += `
            <label style="display: flex; align-items: center; gap: 5px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="id_seccion[]" value="${s.id}" ${isChecked}>
                ${s.nombre}
            </label>
        `;
    });

    document.getElementById('modalEditarProfesor').showModal();
}