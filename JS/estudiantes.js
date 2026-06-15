// JS/estudiantes.js

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. VALIDACIONES Y FORMATEO AUTOMÁTICO ---
    configurarFormateo('edit_nie', 'nie');
    configurarNombres('edit_nombres');
    configurarNombres('edit_apellidos');

    // --- 2. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX) ---
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validarFormularioEstudiante(this)) return;

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
                    
                    if (errorType === 'nie_duplicado') msg = '⚠️ El NIE ingresado ya está registrado';
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

    // --- 3. FILTROS DE BÚSQUEDA ---
    const inputBuscar = document.getElementById('buscador-estudiantes');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('#listaEstudiantes tr');

    function filtrarEstudiantes() {
        const texto = inputBuscar.value.toLowerCase();
        const seccion = filtroSeccion.value.toLowerCase();
        const estado = filtroEstado.value.toLowerCase();

        filas.forEach(fila => {
            const nie = fila.dataset.nie || '';
            const nombres = fila.dataset.nombres || '';
            const apellidos = fila.dataset.apellidos || '';
            const seccionFila = fila.dataset.seccion || '';
            const estadoFila = fila.dataset.estado || '';

            const coincideBusqueda = nie.toLowerCase().includes(texto) || 
                                     nombres.toLowerCase().includes(texto) || 
                                     apellidos.toLowerCase().includes(texto);
            const coincideSeccion = !seccion || seccionFila.toLowerCase() === seccion;
            const coincideEstado = !estado || estadoFila.toLowerCase() === estado;

            if (coincideBusqueda && coincideSeccion && coincideEstado) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    inputBuscar.addEventListener('input', filtrarEstudiantes);
    filtroSeccion.addEventListener('change', filtrarEstudiantes);
    filtroEstado.addEventListener('change', filtrarEstudiantes);

    // --- 4. MENSAJES DE ÉXITO/ELIMINACIÓN ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('🗑️ Estudiante eliminado exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// --- FUNCIONES DE FORMATEO ---

function configurarFormateo(idInput, tipo) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (tipo === 'nie') {
            val = val.slice(0, 10);
        }
        e.target.value = val;
    });
}

// ✅ CAMBIO: ahora usa 'input' en vez de 'blur' (tiempo real)
function configurarNombres(idInput) {
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

// --- VALIDACIÓN DEL FORMULARIO ---

function validarFormularioEstudiante(form) {
    const nieInput = form.querySelector('[name="nie"]');
    if (nieInput) {
        const nie = nieInput.value.trim();
        if (nie.length === 0 || nie.length > 10 || !/^\d+$/.test(nie)) {
            alert('⚠️ El NIE debe contener entre 1 y 10 dígitos numéricos.');
            return false;
        }
    }

    const nombresInput = form.querySelector('[name="nombres"]');
    if (nombresInput) {
        const nombres = nombresInput.value.trim().split(/\s+/);
        if (nombres.length < 2) {
            alert('⚠️ Debe ingresar al menos dos nombres.');
            return false;
        }
    }

    const apellidosInput = form.querySelector('[name="apellidos"]');
    if (apellidosInput) {
        const apellidos = apellidosInput.value.trim().split(/\s+/);
        if (apellidos.length < 2) {
            alert('⚠️ Debe ingresar al menos dos apellidos.');
            return false;
        }
    }

    return true;
}

// --- FUNCIONES DE MODALES ---

function verEstudiante(btn) {
    const d = btn.closest('tr').dataset;
    document.getElementById('contenidoVerEstudiante').innerHTML = `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Información Personal</h4>
            <p><strong>NIE:</strong> ${d.nie || 'No registrado'}</p>
            <p><strong>Nombre:</strong> ${d.nombres} ${d.apellidos}</p>
            <p><strong>DUI:</strong> ${d.dui || 'No registrado'}</p>
            <p><strong>Edad:</strong> ${d.edad || 'No registrada'} años</p>
            <p><strong>Fecha de Nacimiento:</strong> ${d.fechaNac || 'No registrada'}</p>
        </div>
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Información Académica</h4>
            <p><strong>Sección:</strong> ${d.seccion || 'Sin matrícula'}</p>
            <p><strong>Estado:</strong> 
                <span class="badge ${d.estado === 'activo' ? 'active' : 'inactive'}">
                    ${d.estado === 'activo' ? 'Activo' : 'Inactivo'}
                </span>
            </p>
        </div>
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Contacto</h4>
            <p><strong>Teléfono:</strong> ${d.telefono || 'No registrado'}</p>
            <p><strong>Dirección:</strong> ${d.direccion || 'No registrada'}</p>
            <p><strong>Email:</strong> ${d.email || 'No registrado'}</p>
        </div>
    `;
    document.getElementById('modalVerEstudiante').showModal();
}

function editarEstudiante(btn) {
    const d = btn.closest('tr').dataset;

    document.getElementById('edit_id').value = d.id;
    document.getElementById('edit_nie').value = d.nie;
    document.getElementById('edit_nombres').value = d.nombres;
    document.getElementById('edit_apellidos').value = d.apellidos;
    document.getElementById('edit_estado').value = d.estado;
    
    document.getElementById('edit_seccion_display').value = d.seccion || 'Sin matrícula';

    document.getElementById('modalEditarEstudiante').showModal();
}