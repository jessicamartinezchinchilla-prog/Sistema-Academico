document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. REFERENCIAS A ELEMENTOS DEL DOM
    // ==========================================
    const listaHistorial = document.getElementById('listaHistorial');
    const mensajeVacio = document.getElementById('mensajeVacio');
    
    // Filtros
    const buscarHistorial = document.getElementById('buscarHistorial');
    const filtroSeccion = document.getElementById('filtroSeccionHist');
    const filtroAnio = document.getElementById('filtroAnioHist');
    const filtroPeriodo = document.getElementById('filtroPeriodoHist');
    const filtroEstado = document.getElementById('filtroEstadoHist');
    
    // Estadísticas
    const statTotal = document.getElementById('totalRegistros');
    const statAprobados = document.getElementById('totalAprobadosHist');
    const statReprobados = document.getElementById('totalReprobadosHist');
    const statPromedio = document.getElementById('promedioGeneralHist');

    // Modales
    const modalPDF = document.getElementById('modalPDF');
    const modalVerDetalles = document.getElementById('modalVerDetalles');
    const pdfTipoReporte = document.getElementById('pdf_tipo_hist');
    // Nota: Hay un typo en tu HTML ('detalestudiantesleHistorial'). Lo uso tal cual está.
    const detallesHistorial = document.getElementById('detalestudiantesleHistorial'); 

    // ==========================================
    // 2. FUNCIÓN PRINCIPAL DE FILTRADO
    // ==========================================
    const aplicarFiltros = () => {
        const textoBusqueda = buscarHistorial.value.toLowerCase().trim();
        const seccion = filtroSeccion.value.toLowerCase();
        const anio = filtroAnio.value;
        const periodo = filtroPeriodo.value;
        const estado = filtroEstado.value.toLowerCase();

        const filas = listaHistorial.querySelectorAll('tr');
        let visibles = 0;
        let aprobados = 0;
        let reprobados = 0;
        let sumaPromedios = 0;

        // Mapeo para tolerar variaciones en el texto del período (1, Primer, 1er, etc.)
        const periodoMap = {
            "1": ["1", "primer"],
            "2": ["2", "segundo"],
            "3": ["3", "tercer"],
            "4": ["4", "cuarto"]
        };

        filas.forEach(fila => {
            if (!fila.querySelector('td')) return; // Ignorar si no es una fila de datos

            // Lectura de celdas (basado en el orden de tu HTML)
            const celdas = fila.querySelectorAll('td');
            const nie = celdas[0]?.textContent.toLowerCase().trim() || '';
            const nombre = celdas[1]?.textContent.toLowerCase().trim() || '';
            const filaSeccion = celdas[2]?.textContent.toLowerCase().trim() || '';
            const filaAnio = celdas[3]?.textContent.trim() || '';
            const filaPeriodo = celdas[4]?.textContent.toLowerCase().trim() || '';
            const filaPromedio = parseFloat(celdas[5]?.textContent) || 0;
            const filaEstado = celdas[6]?.textContent.toLowerCase().trim() || '';

            // Ignorar filas completamente vacías (ej. plantillas de PHP antes de cargar)
            if (!nie && !nombre) {
                fila.style.display = 'none';
                return; 
            }

            // Lógica de coincidencias
            const coincideBusqueda = nie.includes(textoBusqueda) || nombre.includes(textoBusqueda);
            const coincideSeccion = !seccion || filaSeccion.includes(seccion);
            const coincideAnio = !anio || filaAnio === anio;
            
            let coincidePeriodo = true;
            if (periodo) {
                const opciones = periodoMap[periodo] || [periodo];
                coincidePeriodo = opciones.some(p => filaPeriodo.includes(p));
            }
            
            const coincideEstado = !estado || filaEstado === estado;

            // Mostrar/Ocultar y calcular estadísticas
            if (coincideBusqueda && coincideSeccion && coincideAnio && coincidePeriodo && coincideEstado) {
                fila.style.display = '';
                visibles++;
                sumaPromedios += filaPromedio;
                if (filaEstado.includes('aprobado')) aprobados++;
                if (filaEstado.includes('reprobado')) reprobados++;
            } else {
                fila.style.display = 'none';
            }
        });

        // Actualizar UI de estadísticas
        statTotal.textContent = visibles;
        statAprobados.textContent = aprobados;
        statReprobados.textContent = reprobados;
        statPromedio.textContent = visibles > 0 ? (sumaPromedios / visibles).toFixed(2) : '0';

        // Control del mensaje de "No hay registros"
        mensajeVacio.style.display = (visibles === 0 && filas.length > 0) ? 'block' : 'none';
    };

    // Asignar eventos a todos los filtros
    [buscarHistorial, filtroSeccion, filtroAnio, filtroPeriodo, filtroEstado].forEach(el => {
        el.addEventListener('input', aplicarFiltros);
        el.addEventListener('change', aplicarFiltros);
    });

    // ==========================================
    // 3. LÓGICA DEL MODAL DE EXPORTAR PDF
    // ==========================================
    const camposPDF = {
        estudiante: document.getElementById('pdf_estudiante_hist'),
        seccion: document.getElementById('pdf_seccion_hist'),
        anio: document.getElementById('pdf_anio_hist'),
        periodo: document.getElementById('pdf_periodo_hist')
    };

    const toggleCamposPDF = () => {
        const tipo = pdfTipoReporte.value;
        
        // Ocultar todos los campos y sus labels por defecto
        Object.values(camposPDF).forEach(campo => {
            const label = campo.previousElementSibling;
            campo.style.display = 'none';
            if (label && label.tagName === 'LABEL') label.style.display = 'none';
        });

        // Mostrar solo el campo correspondiente al tipo de reporte
        if (tipo && tipo !== 'general' && camposPDF[tipo]) {
            camposPDF[tipo].style.display = 'block';
            const label = camposPDF[tipo].previousElementSibling;
            if (label && label.tagName === 'LABEL') label.style.display = 'block';
        }
    };

    pdfTipoReporte.addEventListener('change', toggleCamposPDF);

    // ==========================================
    // 4. CARGA DINÁMICA EN MODAL "VER DETALLES"
    // ==========================================
    listaHistorial.addEventListener('click', (e) => {
        // Buscamos si el clic fue en el botón "Ver" o en su ícono interno
        const botonVer = e.target.closest('.btn-action.see');
        
        if (botonVer) {
            e.preventDefault(); // Evitar cualquier comportamiento por defecto
            
            const fila = botonVer.closest('tr');
            const celdas = fila.querySelectorAll('td');
            
            // Extraer datos de la fila
            const datos = {
                nie: celdas[0].textContent,
                nombre: celdas[1].textContent,
                seccion: celdas[2].textContent,
                anio: celdas[3].textContent,
                periodo: celdas[4].textContent,
                promedio: celdas[5].textContent,
                estado: celdas[6].textContent
            };

            // Inyectar HTML dinámico en el cuerpo del modal
            detallesHistorial.innerHTML = `
                <div class="detalle-grid">
                    <p><strong>NIE:</strong> ${datos.nie}</p>
                    <p><strong>Nombre completo:</strong> ${datos.nombre}</p>
                    <p><strong>Sección:</strong> ${datos.seccion}</p>
                    <p><strong>Año lectivo:</strong> ${datos.anio}</p>
                    <p><strong>Período:</strong> ${datos.periodo}</p>
                    <p><strong>Promedio Final:</strong> ${datos.promedio}</p>
                    <p><strong>Estado:</strong> <span class="badge ${datos.estado.toLowerCase() === 'aprobado' ? 'success' : 'danger'}">${datos.estado}</span></p>
                </div>
            `;
            
            // Abrir modal programáticamente
            modalVerDetalles.showModal();
        }
    });

    // ==========================================
    // 5. MEJORAS DE UX EN MODALES (Cerrar al hacer clic fuera)
    // ==========================================
    [modalPDF, modalVerDetalles].forEach(modal => {
        modal.addEventListener('click', (e) => {
            // Verificar si el clic fue en el "backdrop" (el área oscura fuera del modal)
            const dialogDim = modal.getBoundingClientRect();
            const isInDialog = (dialogDim.top <= e.clientY && e.clientY <= dialogDim.top + dialogDim.height &&
                                dialogDim.left <= e.clientX && e.clientX <= dialogDim.left + dialogDim.width);
            if (!isInDialog) {
                modal.close();
            }
        });
    });

    // ==========================================
    // 6. INICIALIZACIÓN
    // ==========================================
    aplicarFiltros();   // Calcula estadísticas y oculta filas vacías al cargar
    toggleCamposPDF();  // Oculta campos del PDF por defecto
});