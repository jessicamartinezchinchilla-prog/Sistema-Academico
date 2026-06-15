// JS/calificaciones.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX)
    // ==========================================
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            // Si es el formulario de PDF, dejar que se envíe normalmente
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
                        case 'campos_incompletos':
                            msg = '⚠️ Todos los campos son obligatorios';
                            break;
                        case 'sin_nota':
                            msg = '⚠️ Debes ingresar una calificación';
                            break;
                        case 'nota_invalida':
                            msg = '⚠️ La nota debe estar entre 0 y 10';
                            break;
                        case 'periodo_invalido':
                            msg = '⚠️ El período debe ser entre 1 y 4';
                            break;
                        case 'duplicado':
                            msg = '⚠️ Ya existe una calificación para este estudiante en esta materia, sección y período';
                            break;
                        case 'sin_id':
                            msg = '⚠️ No se pudo identificar la calificación';
                            break;
                        case 'bd':
                            msg = '⚠️ Error en la base de datos';
                            break;
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
            if (!fila.dataset.id) return; // Saltar filas vacías

            const nie = fila.dataset.nie || '';
            const nombre = fila.dataset.nombre || '';
            const seccionFila = fila.dataset.seccion || '';
            const estadoFila = fila.dataset.estado || '';

            const coincideBusqueda = nie.toLowerCase().includes(texto) || 
                                     nombre.toLowerCase().includes(texto);
            const coincideMateria = !materia || true; // La materia se filtra en el backend
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
    // 3. MENSAJES DE URL (después de redirección)
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('🗑️ Calificación eliminada exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// ==========================================
// FUNCIONES DE MODALES
// ==========================================

function abrirModalNota(btn = null) {
    const form = document.getElementById('formNota');
    form.reset();
    document.getElementById('edit_calificacion_id').value = '';
    document.getElementById('tituloModalNota').textContent = 'Agregar Nueva Calificación';
    
    // Si se llama desde el botón de editar en una fila, preseleccionar el estudiante
    if (btn) {
        const fila = btn.closest('tr');
        const idEstudiante = fila.dataset.id;
        if (idEstudiante) {
            document.getElementById('nota_estudiante').value = idEstudiante;
        }
    }
    
    document.getElementById('modalNota').showModal();
}

function abrirModalPDF() {
    document.getElementById('modalPDF').showModal();
}

// ==========================================
// MOSTRAR/OCULTAR OPCIONES DEL PDF
// ==========================================

function mostrarOpcionesPDF() {
    const tipo = document.getElementById('pdf_tipo').value;
    
    // Ocultar todas las opciones
    document.getElementById('opcion_individual').style.display = 'none';
    document.getElementById('opcion_multiples').style.display = 'none';
    document.getElementById('opcion_seccion').style.display = 'none';
    
    // Mostrar la opción seleccionada
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

        // Agrupar calificaciones por materia
        const materiasAgrupadas = {};
        calificaciones.forEach(cal => {
            if (!materiasAgrupadas[cal.materia]) {
                materiasAgrupadas[cal.materia] = { 1: null, 2: null, 3: null, 4: null };
            }
            materiasAgrupadas[cal.materia][cal.periodo] = cal.nota;
        });

        // Construir HTML
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

        let totalPromedios = 0;
        let cantidadMaterias = 0;

        for (const [materia, periodos] of Object.entries(materiasAgrupadas)) {
            const notas = Object.values(periodos).filter(n => n !== null);
            const promedioMateria = notas.length > 0 
                ? (notas.reduce((a, b) => a + parseFloat(b), 0) / notas.length) 
                : 0;
            const estadoMateria = promedioMateria >= 6 ? 'Aprobado' : 'Reprobado';
            
            totalPromedios += promedioMateria;
            cantidadMaterias++;

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

        html += `
                </tbody>
            </table>
        `;

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
    const periodo = form.querySelector('[name="periodo"]')?.value;
    const notaInput = form.querySelector('[name="nota"]');
    
    if (!estudiante) {
        alert('⚠️ Debes seleccionar un estudiante');
        return false;
    }
    if (!materia) {
        alert('⚠️ Debes seleccionar una materia');
        return false;
    }
    if (!seccion) {
        alert('⚠️ Debes seleccionar una sección');
        return false;
    }
    if (!periodo) {
        alert('⚠️ Debes seleccionar un período');
        return false;
    }
    
    if (notaInput) {
        const nota = parseFloat(notaInput.value);
        if (isNaN(nota) || nota < 0 || nota > 10) {
            alert('⚠️ La calificación debe estar entre 0 y 10');
            return false;
        }
    }
    
    return true;
}