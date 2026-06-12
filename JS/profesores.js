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

    // --- 2. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX) ---
    // Esto hace que los formularios no recarguen la página si hay error
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Evita el envío tradicional
            
            // 1. Validaciones básicas
            if (!validarFormulario(this)) return;

            // 2. Enviar datos con Fetch
            const formData = new FormData(this);
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.text();
                
                // 3. Manejar respuesta
                if (result.startsWith('ERROR:')) {
                    const errorType = result.split(':')[1];
                    let msg = '⚠️ Error al procesar la solicitud';
                    
                    if (errorType === 'gmail') msg = '⚠️ El correo debe ser obligatoriamente @gmail.com';
                    else if (errorType === 'dui_duplicado') msg = '⚠️ El DUI ingresado ya está registrado';
                    else if (errorType === 'nip_duplicado') msg = '⚠️ El NIP ingresado ya está registrado';
                    else if (errorType === 'correo_duplicado') msg = '⚠️ El correo ya está registrado';
                    // Agrega esta línea:
                    else if (errorType === 'telefono_duplicado') msg = '⚠️ El teléfono ingresado ya está registrado';
                    else if (errorType === 'duplicado') msg = '⚠️ Ya existen datos duplicados';
                    else if (errorType === 'bd') msg = '⚠️ Error en la base de datos';
                    
                    alert(msg);
                    // ¡El modal se queda abierto y los datos intactos!
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Operación realizada con éxito');
                    window.location.reload(); // Recargar para ver los cambios
                }
            } catch (error) {
                alert('Error de conexión con el servidor');
            }
        });
    });

    // --- 3. INTERCEPTAR ELIMINAR (Para que también use AJAX si quieres, o dejarlo como link) ---
    // Dejamos el eliminar como link tradicional por simplicidad, pero recargamos al volver
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('🗑️ Profesor eliminado exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // --- 4. FILTROS DE BÚSQUEDA ---
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
});

// Función para formatear DUI y Teléfono
function configurarFormateo(idInput, tipo) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (tipo === 'dui') {
            if (val.length > 8) val = val.slice(0, 8) + '-' + val.slice(8, 9);
        } else if (tipo === 'tel') {
            if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4, 8);
        }
        e.target.value = val;
    });
}

// Función para formatear nombres
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

// Validación final
function validarFormulario(form) {
    const nombresInput = form.querySelector('[name="nombres"]');
    const apellidosInput = form.querySelector('[name="apellidos"]');
    
    const nombres = nombresInput.value.trim().split(' ');
    const apellidos = apellidosInput.value.trim().split(' ');
    const secciones = form.querySelectorAll('input[name="id_seccion[]"]:checked');
    const correo = form.querySelector('[name="correo"]').value.trim();

    if (nombres.length < 2) { alert('⚠️ Debe ingresar al menos dos nombres.'); return false; }
    if (apellidos.length < 2) { alert('️ Debe ingresar al menos dos apellidos.'); return false; }
    if (secciones.length === 0) { alert('⚠️ Debe seleccionar al menos una sección.'); return false; }
    
    const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    if (!emailRegex.test(correo)) { alert('️ El correo debe ser obligatoriamente @gmail.com'); return false; }
    
    return true;
}

// --- FUNCIONES DE MODALES ---
function abrirModalAgregar() {
    document.querySelector('#modalProfesor form.modal-form').reset();
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