// ============================================
// HISTORIAL ACADÉMICO - INTERACTIVIDAD
// ============================================

// Datos del historial (PHP inyectará estos datos dinámicamente)
let datosHistorial = [];

// Elementos del DOM
const listaHistorial = document.getElementById('listaHistorial');
const mensajeVacio = document.getElementById('mensajeVacio');
const buscador = document.getElementById('buscarHistorial');
const filtroSeccion = document.getElementById('filtroSeccionHist');
const filtroAnio = document.getElementById('filtroAnioHist');
const filtroPeriodo = document.getElementById('filtroPeriodoHist');
const filtroEstado = document.getElementById('filtroEstadoHist');

// Elementos de estadísticas
const totalRegistros = document.getElementById('totalRegistros');
const totalAprobados = document.getElementById('totalAprobadosHist');
const totalReprobados = document.getElementById('totalReprobadosHist');
const promedioGeneral = document.getElementById('promedioGeneralHist');

// Modal de detalles
const modalVerDetalles = document.getElementById('modalVerDetalles');
const detalleHistorial = document.getElementById('detalleHistorial');

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Cargar datos (PHP los inyectará aquí)
    cargarDatosHistorial();
    
    // Event listeners para filtros y búsqueda
    buscador.addEventListener('input', aplicarFiltros);
    filtroSeccion.addEventListener('change', aplicarFiltros);
    filtroAnio.addEventListener('change', aplicarFiltros);
    filtroPeriodo.addEventListener('change', aplicarFiltros);
    filtroEstado.addEventListener('change', aplicarFiltros);
});

// ============================================
// CARGAR DATOS (PHP inyectará los datos reales)
// ============================================
function cargarDatosHistorial() {
    // Aquí PHP inyectará los datos. Por ahora usamos datos de ejemplo
    datosHistorial = [
        {
            nie: '12345678',
            nombre: 'Juan Pérez García',
            seccion: '1° A',
            anio: '2026',
            periodo: '1',
            promedio: 8.5,
            estado: 'Aprobado',
            materias: [
                { nombre: 'Matemáticas', nota: 9.0 },
                { nombre: 'Lenguaje', nota: 8.0 },
                { nombre: 'Ciencias', nota: 8.5 }
            ],
            observaciones: 'Excelente desempeño académico'
        },
        {
            nie: '87654321',
            nombre: 'María López Hernández',
            seccion: '2° B',
            anio: '2026',
            periodo: '1',
            promedio: 6.2,
            estado: 'Reprobado',
            materias: [
                { nombre: 'Matemáticas', nota: 5.5 },
                { nombre: 'Lenguaje', nota: 7.0 },
                { nombre: 'Ciencias', nota: 6.0 }
            ],
            observaciones: 'Necesita refuerzo en matemáticas'
        }
        // PHP agregará más registros aquí
    ];
    
    renderizarTabla(datosHistorial);
    actualizarEstadisticas(datosHistorial);
    verificarEstadoVacio();
}

// ============================================
// RENDERIZAR TABLA
// ============================================
function renderizarTabla(datos) {
    listaHistorial.innerHTML = '';
    
    if (datos.length === 0) {
        mensajeVacio.style.display = 'block';
        listaHistorial.style.display = 'none';
        return;
    }
    
    mensajeVacio.style.display = 'none';
    listaHistorial.style.display = 'table-row-group';
    
    datos.forEach((registro, index) => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${registro.nie}</td>
            <td>${registro.nombre}</td>
            <td><span class="section-badge">${registro.seccion}</span></td>
            <td>${registro.anio}</td>
            <td>${obtenerNombrePeriodo(registro.periodo)}</td>
            <td><span class="average">${registro.promedio.toFixed(1)}</span></td>
            <td><span class="badge ${registro.estado.toLowerCase()}">${registro.estado}</span></td>
            <td class="actions-cell">
                <button type="button" class="btn-action see" title="Ver detalles" onclick="verDetalles(${index})">
                    <i class="fa-solid fa-eye"></i>
                    <span class="sr-only">Ver detalles</span>
                </button>
            </td>
        `;
        listaHistorial.appendChild(fila);
    });
}

// ============================================
// APLICAR FILTROS Y BÚSQUEDA
// ============================================
function aplicarFiltros() {
    const textoBusqueda = buscador.value.toLowerCase().trim();
    const seccion = filtroSeccion.value;
    const anio = filtroAnio.value;
    const periodo = filtroPeriodo.value;
    const estado = filtroEstado.value;
    
    const datosFiltrados = datosHistorial.filter(registro => {
        // Filtro de búsqueda (NIE o nombre)
        const coincideBusqueda = textoBusqueda === '' || 
            registro.nie.toLowerCase().includes(textoBusqueda) ||
            registro.nombre.toLowerCase().includes(textoBusqueda);
        
        // Filtro de sección
        const coincideSeccion = seccion === '' || registro.seccion === seccion;
        
        // Filtro de año
        const coincideAnio = anio === '' || registro.anio === anio;
        
        // Filtro de período
        const coincidePeriodo = periodo === '' || registro.periodo === periodo;
        
        // Filtro de estado
        const coincideEstado = estado === '' || registro.estado === estado;
        
        return coincideBusqueda && coincideSeccion && coincideAnio && 
               coincidePeriodo && coincideEstado;
    });
    
    renderizarTabla(datosFiltrados);
    actualizarEstadisticas(datosFiltrados);
}

// ============================================
// ACTUALIZAR ESTADÍSTICAS
// ============================================
function actualizarEstadisticas(datos) {
    const total = datos.length;
    const aprobados = datos.filter(r => r.estado === 'Aprobado').length;
    const reprobados = datos.filter(r => r.estado === 'Reprobado').length;
    const promedio = total > 0 
        ? (datos.reduce((sum, r) => sum + r.promedio, 0) / total).toFixed(1)
        : 0;
    
    // Animación de números
    animarNumero(totalRegistros, total);
    animarNumero(totalAprobados, aprobados);
    animarNumero(totalReprobados, reprobados);
    promedioGeneral.textContent = promedio;
}

// ============================================
// ANIMACIÓN DE NÚMEROS
// ============================================
function animarNumero(elemento, valorFinal) {
    const duracion = 500;
    const inicio = 0;
    const incremento = valorFinal / (duracion / 16);
    let actual = inicio;
    
    const intervalo = setInterval(() => {
        actual += incremento;
        if (actual >= valorFinal) {
            elemento.textContent = valorFinal;
            clearInterval(intervalo);
        } else {
            elemento.textContent = Math.floor(actual);
        }
    }, 16);
}

// ============================================
// VER DETALLES (MODAL)
// ============================================
function verDetalles(index) {
    const registro = datosHistorial[index];
    
    if (!registro) {
        alert('No se encontró el registro');
        return;
    }
    
    // Construir HTML de detalles
    let materiasHTML = '';
    if (registro.materias && registro.materias.length > 0) {
        materiasHTML = `
            <div class="grades-section">
                <h5>Materias Cursadas</h5>
                <div class="grades-list">
                    ${registro.materias.map(m => `
                        <div class="grade-item">
                            <span class="grade-name">${m.nombre}</span>
                            <span class="grade-score">${m.nota.toFixed(1)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    let observacionesHTML = '';
    if (registro.observaciones) {
        observacionesHTML = `
            <div class="observations">
                <div class="observations-label">Observaciones:</div>
                <div class="observations-text">${registro.observaciones}</div>
            </div>
        `;
    }
    
    detalleHistorial.innerHTML = `
        <div class="student-card">
            <h4>${registro.nombre}</h4>
            <div class="student-info">
                <div class="info-item">
                    <span class="info-label">NIE:</span>
                    <span class="info-value">${registro.nie}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sección:</span>
                    <span class="info-value">${registro.seccion}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Año:</span>
                    <span class="info-value">${registro.anio}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Período:</span>
                    <span class="info-value">${obtenerNombrePeriodo(registro.periodo)}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Promedio:</span>
                    <span class="info-value average">${registro.promedio.toFixed(1)}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-value">
                        <span class="badge ${registro.estado.toLowerCase()}">${registro.estado}</span>
                    </span>
                </div>
            </div>
            ${materiasHTML}
            ${observacionesHTML}
        </div>
    `;
    
    modalVerDetalles.showModal();
}

// ============================================
// VERIFICAR ESTADO VACÍO
// ============================================
function verificarEstadoVacio() {
    if (datosHistorial.length === 0) {
        mensajeVacio.style.display = 'block';
        listaHistorial.style.display = 'none';
    } else {
        mensajeVacio.style.display = 'none';
        listaHistorial.style.display = 'table-row-group';
    }
}

// ============================================
// UTILIDADES
// ============================================
function obtenerNombrePeriodo(periodo) {
    const nombres = {
        '1': 'Primer período',
        '2': 'Segundo período',
        '3': 'Tercer período',
        '4': 'Cuarto período'
    };
    return nombres[periodo] || periodo;
}

// ============================================
// INTEGRACIÓN CON PHP (ejemplo)
// ============================================
// Cuando PHP inyecte los datos, reemplaza la función cargarDatosHistorial() con:
/*
function cargarDatosHistorial() {
    // PHP inyectará algo como:
    // datosHistorial = <?php echo json_encode($historial); ?>;
    
    // O mediante fetch:
    fetch('../API/obtener_historial.php')
        .then(response => response.json())
        .then(data => {
            datosHistorial = data;
            renderizarTabla(datosHistorial);
            actualizarEstadisticas(datosHistorial);
            verificarEstadoVacio();
        })
        .catch(error => console.error('Error al cargar historial:', error));
}
*/