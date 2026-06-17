// JS/calificaciones.js

document.addEventListener('DOMContentLoaded', () => {
// ==========================================
// VARIABLES GLOBALES
// ==========================================
let modoEdicion = false; // false = modo agregar, true = modo editar

    // ==========================================
    // 1. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX)
    // ==========================================
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            if (this.action.includes('generar_pdf.php')) return;
            
            e.preventDefault();
            
            if (!validarFormularioNota(this)) return;

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
                    
                    switch(errorType) {
                        case 'campos_incompletos': msg = '⚠️ Todos los campos son obligatorios'; break;
                        case 'sin_nota': msg = '⚠️ Debes ingresar una calificación'; break;
                        case 'nota_invalida': msg = '⚠️ La nota debe estar entre 0 y 10'; break;
                        case 'periodo_invalido': msg = '⚠️ El período debe ser entre 1 y 4'; break;
                        case 'duplicado': msg = '⚠️ Ya existe una calificación para este estudiante en esta materia, sección y período'; break;
                        case 'materia_no_pertenece': msg = '⚠️ La materia seleccionada no pertenece a la carrera/grado del estudiante'; break;
                        case 'sin_id': msg = '⚠️ No se pudo identificar la calificación'; break;
                        case 'bd': msg = '⚠️ Error en la base de datos'; break;
                    }
                    
                    alert(msg);
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Calificación guardada exitosamente');
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de conexión con el servidor');
            }
        });
    });

    // ==========================================
    // 2. FILTROS DE BÚSQUEDA
    // ==========================================
    const inputBuscar = document.getElementById('buscarCalificacion');
    const filtroMateria = document.getElementById('filtroMateria');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('#listaCalificaciones tr');

    function filtrarCalificaciones() {
        const texto = inputBuscar.value.toLowerCase();
        const materia = filtroMateria.value.toLowerCase();
        const seccion = filtroSeccion.value.toLowerCase();
        const estado = filtroEstado.value.toLowerCase();

        filas.forEach(fila => {
            if (!fila.dataset.id) return;

            const nie = fila.dataset.nie || '';
            const nombre = fila.dataset.nombre || '';
            const seccionFila = fila.dataset.seccion || '';
            const estadoFila = fila.dataset.estado || '';

            const coincideBusqueda = nie.toLowerCase().includes(texto) || nombre.toLowerCase().includes(texto);
            const coincideSeccion = !seccion || seccionFila.toLowerCase() === seccion;
            const coincideEstado = !estado || estadoFila.toLowerCase() === estado;

            if (coincideBusqueda && coincideSeccion && coincideEstado) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    if (inputBuscar) inputBuscar.addEventListener('input', filtrarCalificaciones);
    if (filtroMateria) filtroMateria.addEventListener('change', filtrarCalificaciones);
    if (filtroSeccion) filtroSeccion.addEventListener('change', filtrarCalificaciones);
    if (filtroEstado) filtroEstado.addEventListener('change', filtrarCalificaciones);

    // ==========================================
    // 3. MENSAJES DE URL
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('🗑️ Calificación eliminada exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function abrirModalNota(btn = null) {
    const form = document.getElementById('formNota');
    form.reset();
    document.getElementById('edit_calificacion_id').value = '';
    document.getElementById('tituloModalNota').textContent = 'Agregar Nueva Calificación';
    document.getElementById('btn_guardar_nota').textContent = 'Guardar Calificación';
    document.getElementById('nota_estado_msg').textContent = 'Nota mínima aprobatoria: 6.00';
    document.getElementById('nota_estado_msg').style.color = '#6b7280';
    
    // ✅ Resetear modo
    modoEdicion = false;
    
    // ✅ Limpiar campos de edición si existen
    limpiarCamposEdicion();
    
    // ✅ Resetear modo edición visual
    resetearModoEdicion();
    
    // Limpiar campos de carrera y sección
    document.getElementById('nota_carrera_display').value = '';
    document.getElementById('nota_seccion_display').value = '';
    document.getElementById('nota_carrera').value = '';
    document.getElementById('nota_seccion').value = '';
    document.getElementById('nota_grado').value = '';
    
    // Limpiar select de materias
    const selectMateria = document.getElementById('nota_materia');
    selectMateria.innerHTML = '<option value="">Seleccione Materia</option>';
    
    // ✅ Habilitar input de nota y botón por defecto
    const inputNota = document.getElementById('nota_valor');
    inputNota.disabled = false;
    inputNota.readOnly = false;
    document.getElementById('btn_guardar_nota').disabled = false;
    
    // ✅ Si se llama desde el botón de editar en una fila → MODO EDICIÓN
    if (btn) {
        const fila = btn.closest('tr');
        const idEstudiante = fila.dataset.id;
        if (idEstudiante) {
            document.getElementById('nota_estudiante').value = idEstudiante;
            actualizarDatosEstudiante();
            
            // ✅ BLOQUEAR el select de estudiante (modo edición)
            bloquearEstudiante();
            
            // ✅ Cambiar a modo edición
            modoEdicion = true;
            document.getElementById('tituloModalNota').textContent = 'Modificar/Editar Calificación';
            document.getElementById('btn_guardar_nota').textContent = 'Actualizar Calificación';
        }
    }
    
    document.getElementById('modalNota').showModal();
}

// ==========================================
// ✅ NUEVAS FUNCIONES: MODO EDICIÓN
// ==========================================

function bloquearEstudiante() {
    const selectEstudiante = document.getElementById('nota_estudiante');
    const displayReadonly = document.getElementById('estudiante_readonly_display');
    
    // Obtener el texto seleccionado
    const option = selectEstudiante.options[selectEstudiante.selectedIndex];
    const textoEstudiante = option.text;
    
    // Ocultar select, mostrar display readonly
    selectEstudiante.style.display = 'none';
    displayReadonly.textContent = textoEstudiante;
    displayReadonly.style.display = 'block';
}

function resetearModoEdicion() {
    const selectEstudiante = document.getElementById('nota_estudiante');
    const displayReadonly = document.getElementById('estudiante_readonly_display');
    
    // Mostrar select, ocultar display
    selectEstudiante.style.display = '';
    displayReadonly.style.display = 'none';
    displayReadonly.textContent = '';
}

// ==========================================
// ✅ NUEVA FUNCIÓN: BUSCAR NOTA EXISTENTE
// ==========================================
async function buscarNotaExistente() {
    const idEstudiante = document.getElementById('nota_estudiante').value;
    const idMateria = document.getElementById('nota_materia').value;
    const periodo = document.getElementById('nota_periodo').value;
    const inputNota = document.getElementById('nota_valor');
    const estadoMsg = document.getElementById('nota_estado_msg');
    const accionForm = document.getElementById('accion_form');
    const calificacionIdInput = document.getElementById('edit_calificacion_id');
    const btnGuardar = document.getElementById('btn_guardar_nota');
    
    // Resetear estado
    calificacionIdInput.value = '';
    accionForm.value = 'agregar';
    inputNota.value = '';
    inputNota.disabled = false;
    inputNota.readOnly = false;
    estadoMsg.textContent = 'Nota mínima aprobatoria: 6.00';
    estadoMsg.style.color = '#6b7280';
    btnGuardar.disabled = false;
    btnGuardar.textContent = modoEdicion ? 'Actualizar Calificación' : 'Guardar Calificación';
    
    // Solo buscar si hay estudiante, materia y período seleccionados
    if (!idEstudiante || !idMateria || !periodo) {
        return;
    }
    
    try {
        const response = await fetch(
            `../actions/calificaciones_action.php?accion=obtener_nota_existente&id_estudiante=${idEstudiante}&id_materia=${idMateria}&periodo=${periodo}`,
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        );
        
        const data = await response.json();
        
        if (data.existe) {
            // ✅ Precargar la nota existente
            inputNota.value = data.nota;
            calificacionIdInput.value = data.id;
            accionForm.value = 'editar';
            
            // ✅ SOLO EN MODO AGREGAR: Hacer solo lectura (no editable)
            if (!modoEdicion) {
                inputNota.readOnly = true;
                inputNota.disabled = false; // No disabled, solo readOnly para que se vea el valor
                estadoMsg.innerHTML = `<i class="fa-solid fa-lock"></i> Esta calificación ya existe. Para modificarla, usa el botón de editar (✏️) en la tabla.`;
                estadoMsg.style.color = '#dc2626';
                btnGuardar.textContent = 'No se puede guardar (usa el botón editar)';
                btnGuardar.disabled = true;
            } else {
                // ✅ EN MODO EDICIÓN: Permitir modificar completamente
                inputNota.readOnly = false;
                inputNota.disabled = false;
                estadoMsg.innerHTML = `<i class="fa-solid fa-pen"></i> Editando calificación existente (ID: ${data.id})`;
                estadoMsg.style.color = '#2563eb';
                btnGuardar.textContent = 'Actualizar Calificación';
                btnGuardar.disabled = false;
            }
        } else {
            // No existe, es una nota nueva
            accionForm.value = 'agregar';
            inputNota.readOnly = false;
            inputNota.disabled = false;
            
            if (!modoEdicion) {
                estadoMsg.textContent = '✨ Se creará una nueva calificación';
                estadoMsg.style.color = '#16a34a';
                btnGuardar.textContent = 'Guardar Calificación';
            } else {
                estadoMsg.textContent = 'Nueva calificación a crear';
                estadoMsg.style.color = '#16a34a';
                btnGuardar.textContent = 'Actualizar Calificación';
            }
            btnGuardar.disabled = false;
        }
        
    } catch (error) {
        console.error('Error al buscar nota:', error);
    }
}

function abrirModalPDF() {
    document.getElementById('modalPDF').showModal();
}

// ==========================================
// ACTUALIZAR DATOS DEL ESTUDIANTE
// ==========================================

function actualizarDatosEstudiante() {
    const selectEstudiante = document.getElementById('nota_estudiante');
    const option = selectEstudiante.options[selectEstudiante.selectedIndex];
    
    const idSeccion = option.dataset.seccion;
    const idCarrera = option.dataset.carrera;
    const idGrado = option.dataset.grado;
    const nombreSeccion = option.dataset.nombreSeccion;
    const nombreCarrera = option.dataset.nombreCarrera;
    
    document.getElementById('nota_carrera_display').value = nombreCarrera || '';
    document.getElementById('nota_seccion_display').value = nombreSeccion || '';
    document.getElementById('nota_carrera').value = idCarrera || '';
    document.getElementById('nota_seccion').value = idSeccion || '';
    document.getElementById('nota_grado').value = idGrado || '';
    
    filtrarMateriasPorCarrera(idCarrera, idGrado);
}

// ==========================================
// FILTRAR MATERIAS POR CARRERA Y GRADO
// ==========================================

async function filtrarMateriasPorCarrera(idCarrera, idGrado) {
    const selectMateria = document.getElementById('nota_materia');
    
    selectMateria.innerHTML = '<option value="">Cargando materias...</option>';
    
    if (!idCarrera || !idGrado) {
        selectMateria.innerHTML = '<option value="">Seleccione un estudiante primero</option>';
        return;
    }
    
    try {
        const response = await fetch(`../actions/calificaciones_action.php?accion=obtener_materias_carrera&id_carrera=${idCarrera}&id_grado=${idGrado}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const materias = await response.json();
        
        selectMateria.innerHTML = '<option value="">Seleccione Materia</option>';
        
        if (materias.length === 0) {
            selectMateria.innerHTML = '<option value="">No hay materias para esta carrera y grado</option>';
            return;
        }
        
        materias.forEach(materia => {
            const option = document.createElement('option');
            option.value = materia.id;
            option.textContent = materia.nombre;
            selectMateria.appendChild(option);
        });
        
    } catch (error) {
        console.error('Error al cargar materias:', error);
        selectMateria.innerHTML = '<option value="">Error al cargar materias</option>';
    }
}

// ==========================================
// EDITAR CALIFICACIÓN POR MATERIA (desde modal detalle)
// ==========================================

async function editarCalificacionMateria(idEstudiante, nombreMateria) {
    try {
        const response = await fetch(`../actions/calificaciones_action.php?accion=obtener_calificaciones_materia&id_estudiante=${idEstudiante}&materia=${encodeURIComponent(nombreMateria)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const calificaciones = await response.json();
        
        document.getElementById('modalDetalle').close();

        const form = document.getElementById('formNota');
        form.reset();
        
        document.getElementById('tituloModalNota').textContent = 'Editar Calificaciones - ' + nombreMateria;
        document.querySelector('[name="accion"]').value = 'editar_multiples';
        
        document.getElementById('nota_estudiante').value = idEstudiante;
        actualizarDatosEstudiante();
        
        setTimeout(() => {
            const selectMateria = document.getElementById('nota_materia');
            for (let option of selectMateria.options) {
                if (option.text === nombreMateria) {
                    selectMateria.value = option.value;
                    break;
                }
            }
            
            mostrarCamposEdicion(calificaciones);
            
            document.getElementById('modalNota').showModal();
        }, 300);
        
    } catch (error) {
        console.error('Error al cargar calificaciones:', error);
        alert('Error al cargar las calificaciones');
    }
}

// ==========================================
// MOSTRAR CAMPOS DE EDICIÓN PARA CADA PERÍODO
// ==========================================

function mostrarCamposEdicion(calificaciones) {
    const form = document.getElementById('formNota');
    
    const labelPeriodo = form.querySelector('label:nth-of-type(5)');
    const selectPeriodo = document.getElementById('nota_periodo');
    const labelNota = form.querySelector('label:nth-of-type(6)');
    const inputNota = document.getElementById('nota_valor');
    const smallNota = document.getElementById('nota_estado_msg');
    
    if (labelPeriodo) labelPeriodo.style.display = 'none';
    if (selectPeriodo) selectPeriodo.style.display = 'none';
    if (labelNota) labelNota.style.display = 'none';
    if (inputNota) inputNota.style.display = 'none';
    if (smallNota) smallNota.style.display = 'none';
    
    let contenedor = document.getElementById('contenedor_edicion_periodos');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'contenedor_edicion_periodos';
        contenedor.style.cssText = 'background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 10px;';
        form.insertBefore(contenedor, form.querySelector('.modal-actions'));
    }
    
    let html = '<h4 style="color: #2647B8; margin-bottom: 15px;">Editar notas por período</h4>';
    
    for (let periodo = 1; periodo <= 4; periodo++) {
        const calif = calificaciones.find(c => c.periodo == periodo);
        const nota = calif ? calif.nota : '';
        const califId = calif ? calif.id : '';
        
        html += `
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px; padding: 10px; background: white; border-radius: 6px;">
                <label style="min-width: 120px; font-weight: 600;">Período ${periodo}:</label>
                <input type="hidden" name="calificacion_ids[]" value="${califId}">
                <input type="hidden" name="periodos[]" value="${periodo}">
                <input type="number" name="notas[]" min="0" max="10" step="0.01" value="${nota}" 
                       placeholder="Sin calificación" 
                       style="flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                ${calif ? '<span style="color: #16a34a; font-size: 12px;"><i class="fa-solid fa-check"></i> Existente</span>' : '<span style="color: #9ca3af; font-size: 12px;">Nueva</span>'}
            </div>
        `;
    }
    
    contenedor.innerHTML = html;
    
    const btnSubmit = form.querySelector('.btn-save');
    btnSubmit.textContent = 'Guardar Cambios';
}

// ==========================================
// LIMPIAR CAMPOS DE EDICIÓN AL CERRAR MODAL
// ==========================================

function limpiarCamposEdicion() {
    const form = document.getElementById('formNota');
    
    const labelPeriodo = form.querySelector('label:nth-of-type(5)');
    const selectPeriodo = document.getElementById('nota_periodo');
    const labelNota = form.querySelector('label:nth-of-type(6)');
    const inputNota = document.getElementById('nota_valor');
    const smallNota = document.getElementById('nota_estado_msg');
    
    if (labelPeriodo) labelPeriodo.style.display = '';
    if (selectPeriodo) selectPeriodo.style.display = '';
    if (labelNota) labelNota.style.display = '';
    if (inputNota) inputNota.style.display = '';
    if (smallNota) smallNota.style.display = '';
    
    const contenedor = document.getElementById('contenedor_edicion_periodos');
    if (contenedor) contenedor.remove();
    
    document.querySelector('[name="accion"]').value = 'agregar';
    
    const btnSubmit = form.querySelector('.btn-save');
    btnSubmit.textContent = 'Guardar Calificación';
}

// ==========================================
// MOSTRAR/OCULTAR OPCIONES DEL PDF
// ==========================================

function mostrarOpcionesPDF() {
    const tipo = document.getElementById('pdf_tipo').value;
    
    document.getElementById('opcion_individual').style.display = 'none';
    document.getElementById('opcion_multiples').style.display = 'none';
    document.getElementById('opcion_seccion').style.display = 'none';
    
    if (tipo === 'individual') {
        document.getElementById('opcion_individual').style.display = 'block';
    } else if (tipo === 'multiples') {
        document.getElementById('opcion_multiples').style.display = 'block';
    } else if (tipo === 'seccion') {
        document.getElementById('opcion_seccion').style.display = 'block';
    }
}

// ==========================================
// VER DETALLE DEL ESTUDIANTE (AJAX)
// ==========================================

async function verDetalleEstudiante(btn) {
    const fila = btn.closest('tr');
    const idEstudiante = fila.dataset.id;
    const nombreEstudiante = fila.dataset.nombre;
    const seccion = fila.dataset.seccion;
    const promedio = fila.dataset.promedio;
    const estado = fila.dataset.estado;

    const contenedor = document.getElementById('detalleContenido');
    contenedor.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #2F6FED;"></i>
            <p style="margin-top: 10px; color: #6b7280;">Cargando calificaciones...</p>
        </div>
    `;
    document.getElementById('modalDetalle').showModal();

    try {
        const response = await fetch(`../actions/calificaciones_action.php?accion=obtener_detalle&id_estudiante=${idEstudiante}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const calificaciones = await response.json();
        
        if (calificaciones.length === 0) {
            contenedor.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 32px; color: #f59e0b;"></i>
                    <p style="margin-top: 10px; color: #6b7280;">No hay calificaciones registradas para este estudiante.</p>
                </div>
            `;
            return;
        }

        const materiasAgrupadas = {};
        calificaciones.forEach(cal => {
            if (!materiasAgrupadas[cal.materia]) {
                materiasAgrupadas[cal.materia] = { 1: null, 2: null, 3: null, 4: null };
            }
            materiasAgrupadas[cal.materia][cal.periodo] = cal.nota;
        });

        // ✅ SIN COLUMNA DE ACCIONES (más limpio)
        let html = `
            <div style="margin-bottom: 20px; padding: 15px; background: #f9fafb; border-radius: 10px;">
                <h4 style="color: #2647B8; margin-bottom: 8px;">${nombreEstudiante}</h4>
                <p style="font-size: 14px; color: #6b7280; margin: 0;">
                    <strong>Sección:</strong> ${seccion} | 
                    <strong>Promedio General:</strong> <span style="color: #2F6FED; font-weight: 700;">${parseFloat(promedio).toFixed(2)}</span> | 
                    <strong>Estado:</strong> 
                    <span class="${estado === 'Aprobado' ? 'estado-aprobado' : 'estado-reprobado'}">${estado}</span>
                </p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #2647B8; color: white;">
                        <th style="padding: 10px; text-align: left;">Materia</th>
                        <th style="padding: 10px; text-align: center;">P1</th>
                        <th style="padding: 10px; text-align: center;">P2</th>
                        <th style="padding: 10px; text-align: center;">P3</th>
                        <th style="padding: 10px; text-align: center;">P4</th>
                        <th style="padding: 10px; text-align: center;">Promedio</th>
                        <th style="padding: 10px; text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
        `;

        for (const [materia, periodos] of Object.entries(materiasAgrupadas)) {
            const notas = Object.values(periodos).filter(n => n !== null);
            const promedioMateria = notas.length > 0 
                ? (notas.reduce((a, b) => a + parseFloat(b), 0) / notas.length) 
                : 0;
            const estadoMateria = promedioMateria >= 6 ? 'Aprobado' : 'Reprobado';

            html += `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 10px; font-weight: 500;">${materia}</td>
                    <td style="padding: 10px; text-align: center;">${periodos[1] !== null ? parseFloat(periodos[1]).toFixed(2) : '-'}</td>
                    <td style="padding: 10px; text-align: center;">${periodos[2] !== null ? parseFloat(periodos[2]).toFixed(2) : '-'}</td>
                    <td style="padding: 10px; text-align: center;">${periodos[3] !== null ? parseFloat(periodos[3]).toFixed(2) : '-'}</td>
                    <td style="padding: 10px; text-align: center;">${periodos[4] !== null ? parseFloat(periodos[4]).toFixed(2) : '-'}</td>
                    <td style="padding: 10px; text-align: center; font-weight: 700; color: #2F6FED;">${promedioMateria.toFixed(2)}</td>
                    <td style="padding: 10px; text-align: center;">
                        <span class="${estadoMateria === 'Aprobado' ? 'estado-aprobado' : 'estado-reprobado'}">${estadoMateria}</span>
                    </td>
                </tr>
            `;
        }

        html += `</tbody></table>`;
        contenedor.innerHTML = html;
        
    } catch (error) {
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #dc2626;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px;"></i>
                <p style="margin-top: 10px;">Error al cargar las calificaciones</p>
            </div>
        `;
    }
}

// ==========================================
// VALIDACIÓN DEL FORMULARIO DE NOTA
// ==========================================

function validarFormularioNota(form) {
    const estudiante = form.querySelector('[name="id_estudiante"]')?.value;
    const materia = form.querySelector('[name="id_materia"]')?.value;
    const seccion = form.querySelector('[name="id_seccion"]')?.value;
    const carrera = form.querySelector('[name="id_carrera"]')?.value;
    const grado = form.querySelector('[name="id_grado"]')?.value;
    const periodo = form.querySelector('[name="periodo"]')?.value;
    const notaInput = form.querySelector('[name="nota"]');
    
    if (!estudiante) { alert('⚠️ Debes seleccionar un estudiante'); return false; }
    if (!carrera) { alert('⚠️ El estudiante no tiene una carrera asignada'); return false; }
    if (!grado) { alert('⚠️ El estudiante no tiene un grado asignado'); return false; }
    if (!seccion) { alert('⚠️ El estudiante no tiene una sección asignada'); return false; }
    if (!materia) { alert('⚠️ Debes seleccionar una materia'); return false; }
    if (!periodo) { alert('⚠️ Debes seleccionar un período'); return false; }
    
    if (notaInput) {
        const nota = parseFloat(notaInput.value);
        if (isNaN(nota) || nota < 0 || nota > 10) {
            alert('⚠️ La calificación debe estar entre 0 y 10');
            return false;
        }
    }
    
    return true;
}