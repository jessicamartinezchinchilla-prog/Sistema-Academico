// JS/historial_academico.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. FILTROS DE BÚSQUEDA
    // ==========================================
    const inputBuscar = document.getElementById('buscarHistorial');
    const filtroSeccion = document.getElementById('filtroSeccionHist');
    const filtroAnio = document.getElementById('filtroAnioHist');
    const filtroEstado = document.getElementById('filtroEstadoHist');
    const filtroEstadoEstudiante = document.getElementById('filtroEstadoEstudianteHist'); // ✅ NUEVO
    const filas = document.querySelectorAll('#listaHistorial tr');

    // ... función filtrarHistorial (la de arriba) ...

    if (inputBuscar) inputBuscar.addEventListener('input', filtrarHistorial);
    if (filtroSeccion) filtroSeccion.addEventListener('change', filtrarHistorial);
    if (filtroAnio) filtroAnio.addEventListener('change', filtrarHistorial);
    if (filtroEstado) filtroEstado.addEventListener('change', filtrarHistorial);
    if (filtroEstadoEstudiante) filtroEstadoEstudiante.addEventListener('change', filtrarHistorial); // ✅ NUEVO
    
function filtrarHistorial() {
    const texto = inputBuscar ? inputBuscar.value.toLowerCase() : '';
    const seccion = filtroSeccion ? filtroSeccion.value.toLowerCase() : '';
    const anio = filtroAnio ? filtroAnio.value : '';
    const estadoAcademico = filtroEstado ? filtroEstado.value.toLowerCase() : '';
    
    // ✅ NUEVO: Filtro de estado del estudiante
    const filtroEstadoEstudiante = document.getElementById('filtroEstadoEstudianteHist');
    const estadoEstudiante = filtroEstadoEstudiante ? filtroEstadoEstudiante.value.toLowerCase() : '';

    filas.forEach(fila => {
        if (!fila.dataset.id) return;

        const nie = fila.dataset.nie || '';
        const nombre = fila.dataset.nombre || '';
        const seccionFila = fila.dataset.seccion || '';
        const anioFila = fila.dataset.anio || '';
        const estadoAcademicoFila = fila.dataset.estadoAcademico || '';
        const estadoEstudianteFila = fila.dataset.estadoEstudiante || '';

        const coincideBusqueda = nie.toLowerCase().includes(texto) || nombre.toLowerCase().includes(texto);
        const coincideSeccion = !seccion || seccionFila.toLowerCase() === seccion;
        const coincideAnio = !anio || anioFila === anio;
        const coincideEstadoAcademico = !estadoAcademico || estadoAcademicoFila.toLowerCase() === estadoAcademico;
        const coincideEstadoEstudiante = !estadoEstudiante || estadoEstudianteFila === estadoEstudiante;

        if (coincideBusqueda && coincideSeccion && coincideAnio && coincideEstadoAcademico && coincideEstadoEstudiante) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}
    // ==========================================
    // 2. FILTRO DE TIPO DE EVENTO EN TIMELINE
    // ==========================================
    const filtroTipoEvento = document.getElementById('filtroTipoEvento');
    if (filtroTipoEvento) {
        filtroTipoEvento.addEventListener('change', function() {
            const tipo = this.value.toLowerCase();
            const items = document.querySelectorAll('.timeline-item');
            
            items.forEach(item => {
                if (!tipo) {
                    item.style.display = '';
                } else {
                    const itemTipo = item.dataset.tipo || '';
                    if (itemTipo.includes(tipo)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        });
    }
});

// ==========================================
// VER DETALLES DEL HISTORIAL (TIMELINE)
// ==========================================

async function verDetallesHistorial(btn) {
    const fila = btn.closest('tr');
    const idEstudiante = fila.dataset.id;
    const nombreEstudiante = fila.dataset.nombre;
    const seccion = fila.dataset.seccion;
    const anio = fila.dataset.anio;
    const promedio = fila.dataset.promedio;
    const estado = fila.dataset.estado;

    const contenedor = document.getElementById('detalleContenidoHistorial');
    contenedor.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #2F6FED;"></i>
            <p style="margin-top: 10px; color: #6b7280;">Cargando historial...</p>
        </div>
    `;
    document.getElementById('modalVerDetalles').showModal();

    // Resetear filtro
    const filtroTipo = document.getElementById('filtroTipoEvento');
    if (filtroTipo) filtroTipo.value = '';

    try {
        const response = await fetch(`../actions/historial_action.php?accion=obtener_historial&id_estudiante=${idEstudiante}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const data = await response.json();
        
        if (data.error) {
            contenedor.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #dc2626;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 32px;"></i>
                    <p style="margin-top: 10px;">${data.error}</p>
                </div>
            `;
            return;
        }

        const eventos = data.eventos || [];
        
        // Encabezado del estudiante
        let html = `
            <div style="margin-bottom: 25px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                <h4 style="margin: 0 0 10px 0; font-size: 20px;">${nombreEstudiante}</h4>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px; opacity: 0.95;">
                    <span><i class="fa-solid fa-id-card"></i> NIE: ${fila.dataset.nie}</span>
                    <span><i class="fa-solid fa-school"></i> ${seccion}</span>
                    <span><i class="fa-solid fa-calendar"></i> Año: ${anio}</span>
                    <span><i class="fa-solid fa-chart-line"></i> Promedio: ${parseFloat(promedio).toFixed(2)}</span>
                    <span><i class="fa-solid fa-circle-check"></i> ${estado}</span>
                </div>
            </div>
        `;

        if (eventos.length === 0) {
            html += `
                <div style="text-align: center; padding: 40px; background: #f9fafb; border-radius: 12px;">
                    <i class="fa-solid fa-inbox" style="font-size: 48px; color: #9ca3af;"></i>
                    <p style="margin-top: 15px; color: #6b7280; font-size: 16px;">No hay eventos registrados para este estudiante</p>
                </div>
            `;
        } else {
            html += `<div class="timeline-container" style="position: relative; padding-left: 30px;">`;
            html += `<div style="position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e5e7eb;"></div>`;
            
            eventos.forEach((evento, index) => {
                const icono = obtenerIconoEvento(evento.tipo_evento);
                const color = obtenerColorEvento(evento.tipo_evento);
                const fechaFormateada = formatearFecha(evento.fecha_registro);
                
                html += `
                    <div class="timeline-item" data-tipo="${evento.tipo_evento}" style="position: relative; margin-bottom: 20px; padding: 15px 20px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid ${color};">
                        <div style="position: absolute; left: -26px; top: 20px; width: 14px; height: 14px; border-radius: 50%; background: ${color}; border: 3px solid white; box-shadow: 0 0 0 2px ${color};"></div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="${icono}" style="color: ${color}; font-size: 16px;"></i>
                                    <strong style="color: ${color}; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">${formatearTipoEvento(evento.tipo_evento)}</strong>
                                </div>
                                <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.5;">${evento.descripcion}</p>
                                ${evento.datos_adicionales ? `<div style="margin-top: 8px; padding: 8px 12px; background: #f3f4f6; border-radius: 6px; font-size: 12px; color: #6b7280; font-family: monospace;">${formatearDatosJSON(evento.datos_adicionales)}</div>` : ''}
                            </div>
                            <div style="text-align: right; min-width: 120px;">
                                <span style="font-size: 12px; color: #9ca3af; display: block;">${fechaFormateada.fecha}</span>
                                <span style="font-size: 11px; color: #d1d5db;">${fechaFormateada.hora}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `</div>`;
        }

        contenedor.innerHTML = html;
        
    } catch (error) {
        console.error('Error al cargar historial:', error);
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #dc2626;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px;"></i>
                <p style="margin-top: 10px;">Error al cargar el historial</p>
            </div>
        `;
    }
}

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

function obtenerIconoEvento(tipo) {
    const iconos = {
        'matricula_creada': 'fa-solid fa-user-plus',
        'matricula_modificada': 'fa-solid fa-user-pen',
        'matricula_eliminada': 'fa-solid fa-user-xmark',
        'nota_agregada': 'fa-solid fa-plus-circle',
        'nota_modificada': 'fa-solid fa-pen-to-square',
        'nota_eliminada': 'fa-solid fa-trash',
        'estado_cambiado': 'fa-solid fa-toggle-on',
        'seccion_cambiada': 'fa-solid fa-right-left'
    };
    return iconos[tipo] || 'fa-solid fa-circle-info';
}

function obtenerColorEvento(tipo) {
    const colores = {
        'matricula_creada': '#16a34a',
        'matricula_modificada': '#2563eb',
        'matricula_eliminada': '#dc2626',
        'nota_agregada': '#16a34a',
        'nota_modificada': '#2563eb',
        'nota_eliminada': '#dc2626',
        'estado_cambiado': '#9333ea',
        'seccion_cambiada': '#ea580c'
    };
    return colores[tipo] || '#6b7280';
}

function formatearTipoEvento(tipo) {
    const nombres = {
        'matricula_creada': 'Matrícula creada',
        'matricula_modificada': 'Matrícula modificada',
        'matricula_eliminada': 'Matrícula eliminada',
        'nota_agregada': 'Nota agregada',
        'nota_modificada': 'Nota modificada',
        'nota_eliminada': 'Nota eliminada',
        'estado_cambiado': 'Estado cambiado',
        'seccion_cambiada': 'Sección cambiada'
    };
    return nombres[tipo] || tipo;
}

function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
    const hora = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    return { fecha: fechaFormateada, hora: hora };
}

function formatearDatosJSON(datos) {
    if (!datos) return '';
    try {
        const obj = typeof datos === 'string' ? JSON.parse(datos) : datos;
        return Object.entries(obj)
            .map(([key, value]) => `<strong>${key}:</strong> ${value}`)
            .join(' | ');
    } catch (e) {
        return datos;
    }
}