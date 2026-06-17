// JS/calificaciones.js

let modoEdicion = false;
let estudianteSeleccionado = null;

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // AUTOCOMPLETE DE ESTUDIANTES
    // ==========================================
    const buscadorEstudiante = document.getElementById('buscador_estudiante');
    const autocompleteResults = document.getElementById('autocomplete_results');
    
    if (buscadorEstudiante) {
        buscadorEstudiante.addEventListener('input', function() {
            const texto = this.value.toLowerCase().trim();
            if (texto.length < 2) {
                autocompleteResults.style.display = 'none';
                return;
            }
            
            const estudiantes = window.estudiantesData || [];
            const resultados = estudiantes.filter(e => 
                e.nombre_completo.toLowerCase().includes(texto) || 
                e.nie.toLowerCase().includes(texto)
            );
            
            if (resultados.length === 0) {
                autocompleteResults.innerHTML = '<div class="autocomplete-item">No se encontraron estudiantes</div>';
                autocompleteResults.style.display = 'block';
                return;
            }
            
            autocompleteResults.innerHTML = resultados.map(e => `
                <div class="autocomplete-item" onclick="seleccionarEstudiante(${e.id}, '${e.nombre_completo.replace(/'/g, "\\'")}', '${e.nie}', ${e.id_seccion}, ${e.id_carrera}, ${e.id_grado}, '${e.nombre_seccion.replace(/'/g, "\\'")}', '${e.nombre_carrera.replace(/'/g, "\\'")}')">
                    <div class="nombre">${e.nombre_completo}</div>
                    <div class="nie">NIE: ${e.nie} | ${e.nombre_seccion}</div>
                </div>
            `).join('');
            
            autocompleteResults.style.display = 'block';
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.autocomplete-container')) {
                autocompleteResults.style.display = 'none';
            }
        });
    }

    // ✅ NUEVO: Cargar notas al cambiar materia
    const notaMateriaSelect = document.getElementById('nota_materia');
    if (notaMateriaSelect) {
        notaMateriaSelect.addEventListener('change', function() {
            if (estudianteSeleccionado && this.value) {
                cargarNotasExistentes(estudianteSeleccionado.id, this.value);
            }
        });
    }
    
    // ==========================================
    // INTERCEPTAR ENVÍO DE FORMULARIOS
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
                        case 'campos_incompletos': msg = '️ Todos los campos son obligatorios'; break;
                        case 'sin_nota': msg = '️ Debes ingresar al menos una calificación'; break;
                        case 'nota_invalida': msg = '⚠️ La nota debe estar entre 0 y 10'; break;
                        case 'materia_no_pertenece': msg = '⚠️ La materia no pertenece a la carrera/grado'; break;
                        case 'bd': msg = '️ Error en la base de datos'; break;
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
    // FILTROS DE BÚSQUEDA
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

            if ((nie.toLowerCase().includes(texto) || nombre.toLowerCase().includes(texto)) &&
                (!seccion || seccionFila.toLowerCase() === seccion) &&
                (!estado || estadoFila.toLowerCase() === estado)) {
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
});

// ==========================================
// FUNCIONES DE AUTOCOMPLETE
// ==========================================

function seleccionarEstudiante(id, nombre, nie, idSeccion, idCarrera, idGrado, nombreSeccion, nombreCarrera) {
    estudianteSeleccionado = { id, nombre, nie, idSeccion, idCarrera, idGrado, nombreSeccion, nombreCarrera };
    
    document.getElementById('buscador_estudiante').value = nombre;
    document.getElementById('nota_estudiante').value = id;
    document.getElementById('estudiante_nombre').textContent = `${nombre} (NIE: ${nie})`;
    document.getElementById('estudiante_seleccionado').style.display = 'block';
    document.getElementById('autocomplete_results').style.display = 'none';
    
    document.getElementById('nota_carrera_display').value = nombreCarrera;
    document.getElementById('nota_seccion_display').value = nombreSeccion;
    document.getElementById('nota_carrera').value = idCarrera;
    document.getElementById('nota_seccion').value = idSeccion;
    document.getElementById('nota_grado').value = idGrado;
    
    // Resetear materia y notas al cambiar estudiante
    document.getElementById('nota_materia').innerHTML = '<option value="">Seleccione Materia</option>';
    document.getElementById('nota_materia').value = '';
    resetearPeriodos();
    
    filtrarMateriasPorCarrera(idCarrera, idGrado);
}

function limpiarEstudiante() {
    estudianteSeleccionado = null;
    document.getElementById('buscador_estudiante').value = '';
    document.getElementById('nota_estudiante').value = '';
    document.getElementById('estudiante_seleccionado').style.display = 'none';
    document.getElementById('nota_carrera_display').value = '';
    document.getElementById('nota_seccion_display').value = '';
    document.getElementById('nota_carrera').value = '';
    document.getElementById('nota_seccion').value = '';
    document.getElementById('nota_grado').value = '';
    document.getElementById('nota_materia').innerHTML = '<option value="">Seleccione Materia</option>';
    resetearPeriodos();
}

function resetearPeriodos() {
    for (let i = 1; i <= 4; i++) {
        const input = document.getElementById(`periodo_${i}`);
        input.value = '';
        input.disabled = i > 1;
        input.classList.toggle('periodo-bloqueado', i > 1);
    }
    document.getElementById('nota_estado_msg').textContent = 'Complete los períodos en orden';
    document.getElementById('btn_guardar_nota').disabled = false;
}

// ==========================================
// FUNCIONES DE MODALES
// ==========================================

function abrirModalNota(btn = null) {
    const form = document.getElementById('formNota');
    form.reset();
    document.getElementById('edit_calificacion_id').value = '';
    document.getElementById('tituloModalNota').textContent = 'Agregar Nueva Calificación';
    document.getElementById('btn_guardar_nota').textContent = 'Guardar Calificación';
    
    limpiarEstudiante();
    modoEdicion = false;
    
    if (btn) {
        const fila = btn.closest('tr');
        const idEstudiante = fila.dataset.id;
        if (idEstudiante) {
            const estudiante = window.estudiantesData.find(e => e.id == idEstudiante);
            if (estudiante) {
                seleccionarEstudiante(
                    estudiante.id, estudiante.nombre_completo, estudiante.nie,
                    estudiante.id_seccion, estudiante.id_carrera, estudiante.id_grado,
                    estudiante.nombre_seccion, estudiante.nombre_carrera
                );
                
                modoEdicion = true;
                document.getElementById('tituloModalNota').textContent = 'Modificar/Editar Calificación';
                document.getElementById('btn_guardar_nota').textContent = 'Actualizar Calificación';
                
                // En modo edición, cargar notas cuando se seleccione materia
                const notaMateriaSelect = document.getElementById('nota_materia');
                if (notaMateriaSelect.value) {
                    cargarNotasExistentes(idEstudiante, notaMateriaSelect.value);
                }
            }
        }
    }
    
    document.getElementById('modalNota').showModal();
}

// ==========================================
// CARGAR NOTAS EXISTENTES (BLOQUEAR/DESBLOQUEAR)
// ==========================================

async function cargarNotasExistentes(idEstudiante, idMateria) {
    if (!idEstudiante || !idMateria) return;
    try {
        const response = await fetch(`../actions/calificaciones_action.php?accion=obtener_notas_estudiante&id_estudiante=${idEstudiante}&id_materia=${idMateria}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        const notas = data.notas || {periodo_1: null, periodo_2: null, periodo_3: null, periodo_4: null};

        const msg = document.getElementById('nota_estado_msg');
        const btn = document.getElementById('btn_guardar_nota');

        if (modoEdicion) {
            // ✅ MODO EDICIÓN: Solo permitir editar notas existentes
            for (let i = 1; i <= 4; i++) {
                const input = document.getElementById(`periodo_${i}`);
                const val = notas[`periodo_${i}`];
                
                if (val !== null) {
                    // Tiene nota → mostrarla y permitir editarla
                    input.value = val;
                    input.disabled = false;
                    input.classList.remove('periodo-bloqueado');
                } else {
                    // No tiene nota → bloquear (no se pueden agregar nuevas en modo edición)
                    input.value = '';
                    input.disabled = true;
                    input.classList.add('periodo-bloqueado');
                }
            }
            
            msg.textContent = 'Edita solo las notas existentes';
            btn.disabled = false;
            btn.textContent = 'Actualizar Calificación';
            
        } else {
            // ✅ MODO AGREGAR: Bloqueo secuencial estricto
            let ultimoLleno = 0;
            for (let i = 1; i <= 4; i++) {
                const input = document.getElementById(`periodo_${i}`);
                const val = notas[`periodo_${i}`];
                
                if (val !== null) {
                    input.value = val;
                    input.disabled = true;
                    input.classList.add('periodo-bloqueado');
                    ultimoLleno = i;
                } else {
                    input.value = '';
                    input.disabled = false;
                    input.classList.remove('periodo-bloqueado');
                }
            }

            // Aplicar bloqueo secuencial
            for (let i = 1; i <= 4; i++) {
                const input = document.getElementById(`periodo_${i}`);
                if (i <= ultimoLleno) {
                    input.disabled = true; // Bloquear existentes
                } else if (i === ultimoLleno + 1) {
                    input.disabled = false; // Desbloquear siguiente
                } else {
                    input.disabled = true; // Bloquear futuros
                }
                input.classList.toggle('periodo-bloqueado', input.disabled);
            }

            if (ultimoLleno === 4) {
                msg.innerHTML = '✅ Todos los períodos están completos';
                btn.disabled = true;
                btn.textContent = 'Completo';
            } else {
                msg.textContent = 'Complete los períodos en orden';
                btn.disabled = false;
                btn.textContent = 'Guardar Calificación';
            }
        }
    } catch (error) {
        console.error('Error al cargar notas:', error);
    }
}

// ==========================================
// VERIFICAR BLOQUEO DE PERÍODOS (TIEMPO REAL)
// ==========================================

function verificarBloqueoPeriodos() {
    if (modoEdicion) return;
    
    let ultimoLleno = 0;
    for (let i = 1; i <= 4; i++) {
        const input = document.getElementById(`periodo_${i}`);
        if (input.value.trim() !== '') {
            ultimoLleno = i;
        }
    }

    for (let i = 1; i <= 4; i++) {
        const input = document.getElementById(`periodo_${i}`);
        if (i <= ultimoLleno) {
            input.disabled = false; // Permitir editar lo ya lleno en esta sesión
        } else if (i === ultimoLleno + 1) {
            input.disabled = false;
        } else {
            input.disabled = true;
            input.value = ''; // Limpiar si se salta
        }
        input.classList.toggle('periodo-bloqueado', input.disabled);
    }
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
// PDF Y DETALLES (SIN CAMBIOS RELEVANTES)
// ==========================================

function mostrarOpcionesPDF() {
    const tipo = document.getElementById('pdf_tipo').value;
    document.getElementById('opcion_individual').style.display = 'none';
    document.getElementById('opcion_multiples').style.display = 'none';
    document.getElementById('opcion_seccion').style.display = 'none';
    if (tipo === 'individual') document.getElementById('opcion_individual').style.display = 'block';
    else if (tipo === 'multiples') document.getElementById('opcion_multiples').style.display = 'block';
    else if (tipo === 'seccion') document.getElementById('opcion_seccion').style.display = 'block';
}

async function verDetalleEstudiante(btn) {
    const fila = btn.closest('tr');
    const idEstudiante = fila.dataset.id;
    const nombreEstudiante = fila.dataset.nombre;
    const seccion = fila.dataset.seccion;
    const promedio = fila.dataset.promedio;
    const estado = fila.dataset.estado;

    const contenedor = document.getElementById('detalleContenido');
    contenedor.innerHTML = `<div style="text-align: center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #2F6FED;"></i><p style="margin-top: 10px; color: #6b7280;">Cargando...</p></div>`;
    document.getElementById('modalDetalle').showModal();

    try {
        const response = await fetch(`../actions/calificaciones_action.php?accion=obtener_detalle&id_estudiante=${idEstudiante}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json();
        const materiasAgrupadas = data.materias_agrupadas || {};
        
        if (Object.keys(materiasAgrupadas).length === 0) {
            contenedor.innerHTML = `<div style="text-align: center; padding: 20px;"><i class="fa-solid fa-circle-exclamation" style="font-size: 32px; color: #f59e0b;"></i><p style="margin-top: 10px;">Sin registros.</p></div>`;
            return;
        }

        let html = `<div style="margin-bottom: 20px; padding: 15px; background: #f9fafb; border-radius: 10px;">
            <h4 style="color: #2647B8; margin-bottom: 8px;">${nombreEstudiante}</h4>
            <p style="font-size: 14px; color: #6b7280; margin: 0;"><strong>Sección:</strong> ${seccion} | <strong>Promedio:</strong> ${parseFloat(promedio).toFixed(2)} | <strong>Estado:</strong> <span class="${estado === 'Aprobado' ? 'estado-aprobado' : 'estado-reprobado'}">${estado}</span></p>
        </div><table style="width: 100%; border-collapse: collapse; font-size: 14px;"><thead><tr style="background: #2647B8; color: white;">
            <th style="padding: 10px; text-align: left;">Materia</th><th style="padding: 10px; text-align: center;">P1</th><th style="padding: 10px; text-align: center;">P2</th><th style="padding: 10px; text-align: center;">P3</th><th style="padding: 10px; text-align: center;">P4</th><th style="padding: 10px; text-align: center;">Promedio Final</th><th style="padding: 10px; text-align: center;">Estado</th>
        </tr></thead><tbody>`;

        for (const [materia, d] of Object.entries(materiasAgrupadas)) {
            html += `<tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px; font-weight: 500;">${materia}</td>
                <td style="padding: 10px; text-align: center;">${d.periodos[1] !== null ? parseFloat(d.periodos[1]).toFixed(2) : '-'}</td>
                <td style="padding: 10px; text-align: center;">${d.periodos[2] !== null ? parseFloat(d.periodos[2]).toFixed(2) : '-'}</td>
                <td style="padding: 10px; text-align: center;">${d.periodos[3] !== null ? parseFloat(d.periodos[3]).toFixed(2) : '-'}</td>
                <td style="padding: 10px; text-align: center;">${d.periodos[4] !== null ? parseFloat(d.periodos[4]).toFixed(2) : '-'}</td>
                <td style="padding: 10px; text-align: center; font-weight: 700; color: #2F6FED;">${d.promedio_final.toFixed(2)}</td>
                <td style="padding: 10px; text-align: center;"><span class="${d.estado === 'Aprobado' ? 'estado-aprobado' : 'estado-reprobado'}">${d.estado}</span></td>
            </tr>`;
        }
        html += `</tbody></table>`;
        contenedor.innerHTML = html;
    } catch (error) {
        contenedor.innerHTML = `<div style="text-align: center; padding: 20px; color: #dc2626;"><i class="fa-solid fa-triangle-exclamation" style="font-size: 32px;"></i><p>Error al cargar.</p></div>`;
    }
}

// ==========================================
// VALIDACIÓN
// ==========================================

function validarFormularioNota(form) {
    const estudiante = form.querySelector('[name="id_estudiante"]')?.value;
    const materia = form.querySelector('[name="id_materia"]')?.value;
    const seccion = form.querySelector('[name="id_seccion"]')?.value;
    const carrera = form.querySelector('[name="id_carrera"]')?.value;
    const grado = form.querySelector('[name="id_grado"]')?.value;
    
    if (!estudiante) { alert('️ Selecciona un estudiante'); return false; }
    if (!carrera || !grado || !seccion) { alert('⚠️ Datos del estudiante incompletos'); return false; }
    if (!materia) { alert('⚠️ Selecciona una materia'); return false; }
    
    let tieneNota = false;
    for (let i = 1; i <= 4; i++) {
        const input = document.getElementById(`periodo_${i}`);
        if (input && input.value.trim() !== '') {
            tieneNota = true;
            const nota = parseFloat(input.value);
            if (isNaN(nota) || nota < 0 || nota > 10) {
                alert(`️ La nota del período ${i} debe estar entre 0 y 10`);
                return false;
            }
        }
    }
    
    if (!tieneNota) { alert('⚠️ Ingresa al menos una calificación'); return false; }
    return true;
}