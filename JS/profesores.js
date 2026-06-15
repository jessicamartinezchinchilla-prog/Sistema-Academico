// JS/profesores.js

document.addEventListener('DOMContentLoaded', () => {
    configurarFormateoDUI('prof_dui');
    configurarFormateoTelefono('prof_telefono');
    configurarFormateoNIP('prof_nip');
    configurarCapitalizacion('prof_nombres');
    configurarCapitalizacion('prof_apellidos');
    configurarValidacionEmail('prof_email');
    
    configurarFormateoDUI('edit_dui');
    configurarFormateoTelefono('edit_telefono');
    configurarFormateoNIP('edit_nip');
    configurarCapitalizacion('edit_nombres');
    configurarCapitalizacion('edit_apellidos');
    configurarValidacionEmail('edit_email');

    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validarFormulario(this)) return;

            const formData = new FormData(this);
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.text();
                
                if (result.startsWith('ERROR:')) {
                    const errorType = result.split(':')[1];
                    let msg = '⚠️ Error al procesar la solicitud';
                    
                    if (errorType === 'gmail') msg = '⚠️ El correo debe ser obligatoriamente @gmail.com';
                    else if (errorType === 'dui_duplicado') msg = '⚠️ Este DUI ya está registrado en el sistema';
                    else if (errorType === 'dui_profesor_existe_estudiante') msg = '⚠️ Este DUI ya está registrado como estudiante';
                    else if (errorType === 'dui_profesor_existe_responsable') msg = '⚠️ Este DUI ya está registrado como responsable';
                    else if (errorType === 'nip_duplicado') msg = '⚠️ El NIP ingresado ya está registrado';
                    else if (errorType === 'correo_duplicado') msg = '⚠️ El correo ya está registrado';
                    else if (errorType === 'telefono_duplicado') msg = '⚠️ El teléfono ingresado ya está registrado';
                    else if (errorType === 'sin_materias') msg = '⚠️ Debe seleccionar al menos una materia';
                    else if (errorType === 'duplicado') msg = '⚠️ Ya existen datos duplicados';
                    else if (errorType === 'bd') msg = '⚠️ Error en la base de datos';
                    
                    alert(msg);
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Operación realizada con éxito');
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de conexión con el servidor');
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('🗑️ Profesor eliminado exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    const inputBuscar = document.getElementById('buscarProfesor');
    const filas = document.querySelectorAll('#listaProfesores tr');

    function filtrar() {
        const texto = inputBuscar.value.toLowerCase();
        let visibles = 0;

        filas.forEach(fila => {
            const nombre = (fila.dataset.nombres + ' ' + fila.dataset.apellidos).toLowerCase();
            const dui = fila.dataset.dui.toLowerCase();
            const materias = (fila.dataset.materias || '').toLowerCase();

            const coincide = nombre.includes(texto) || dui.includes(texto) || materias.includes(texto);

            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });
    }

    if (inputBuscar) inputBuscar.addEventListener('input', filtrar);
});

function configurarFormateoDUI(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 8) val = val.slice(0, 8) + '-' + val.slice(8, 9);
        val = val.slice(0, 10);
        e.target.value = val;
    });
}

function configurarFormateoTelefono(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4, 8);
        val = val.slice(0, 9);
        e.target.value = val;
    });
}

function configurarFormateoNIP(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        val = val.slice(0, 10);
        e.target.value = val;
    });
}

function configurarCapitalizacion(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        const valor = e.target.value;
        const cursorPos = e.target.selectionStart;
        
        const palabras = valor.split(' ');
        const excepciones = ['de', 'la', 'las', 'los', 'y', 'del', 'van', 'von'];
        
        const formateado = palabras.map((p, index) => {
            if (p.length === 0) return p;
            if (index === 0) return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
            if (excepciones.includes(p.toLowerCase())) return p.toLowerCase();
            return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
        }).join(' ');
        
        if (valor !== formateado) {
            e.target.value = formateado;
            e.target.setSelectionRange(cursorPos, cursorPos);
        }
    });
}

function configurarValidacionEmail(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        const valor = e.target.value;
        const emailRegex = /^[a-zA-Z0-9._%+-]*@gmail\.com$/;
        
        if (valor === '') {
            e.target.style.borderColor = '';
            return;
        }
        
        if (emailRegex.test(valor)) {
            e.target.style.borderColor = '#16a34a';
        } else if (valor.includes('@')) {
            e.target.style.borderColor = '#dc2626';
        } else {
            e.target.style.borderColor = '';
        }
    });
}

function validarFormulario(form) {
    const nombresInput = form.querySelector('[name="nombres"]');
    const apellidosInput = form.querySelector('[name="apellidos"]');
    const emailInput = form.querySelector('[name="email"]');
    const materiasCheckboxes = form.querySelectorAll('input[name="id_materias[]"]:checked');
    
    const nombres = nombresInput.value.trim().split(' ');
    const apellidos = apellidosInput.value.trim().split(' ');

    if (nombres.length < 2) { alert('⚠️ Debe ingresar al menos dos nombres.'); return false; }
    if (apellidos.length < 2) { alert('⚠️ Debe ingresar al menos dos apellidos.'); return false; }
    
    if (materiasCheckboxes.length === 0) { 
        alert('⚠️ Debe seleccionar al menos una materia'); 
        return false; 
    }
    
    const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    if (!emailRegex.test(emailInput.value.trim())) { 
        alert('⚠️ El correo debe ser obligatoriamente @gmail.com'); 
        return false; 
    }
    
    return true;
}

function abrirModalAgregar() {
    const form = document.querySelector('#modalProfesor form.modal-form');
    if (form) form.reset();
    document.getElementById('modalProfesor').showModal();
}

function verProfesor(btn) {
    const d = btn.closest('tr').dataset;
    document.getElementById('detalleProfesor').innerHTML = `
        <div style="padding: 10px;">
            <h4 style="color: #2647B8; margin-bottom: 15px;">Información Personal</h4>
            <p><strong>Nombre:</strong> ${d.nombres} ${d.apellidos}</p>
            <p><strong>DUI:</strong> ${d.dui || 'No registrado'}</p>
            <p><strong>NIP:</strong> ${d.nip || 'No registrado'}</p>
            <p><strong>Materias:</strong> ${d.materias || 'Sin asignar'}</p>
            
            <hr style="margin: 20px 0;">
            
            <h4 style="color: #2647B8; margin-bottom: 15px;">Contacto</h4>
            <p><strong>Teléfono:</strong> ${d.telefono || 'No registrado'}</p>
            <p><strong>Email:</strong> ${d.email || 'No registrado'}</p>
        </div>
    `;
    document.getElementById('modalVer').showModal();
}

function editarProfesor(btn) {
    const d = btn.closest('tr').dataset;

    document.getElementById('edit_profesor_id').value = d.id;
    document.getElementById('edit_nombres').value = d.nombres;
    document.getElementById('edit_apellidos').value = d.apellidos;
    document.getElementById('edit_dui').value = d.dui || '';
    document.getElementById('edit_nip').value = d.nip || '';
    document.getElementById('edit_telefono').value = d.telefono || '';
    document.getElementById('edit_email').value = d.email || '';

    const materiasAsignadas = d.materiasIds ? d.materiasIds.split(',') : [];
    document.querySelectorAll('.edit_materia_checkbox').forEach(checkbox => {
        checkbox.checked = materiasAsignadas.includes(checkbox.value);
    });

    document.getElementById('modalEditar').showModal();
}