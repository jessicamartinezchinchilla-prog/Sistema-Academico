document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. REFERENCIAS A ELEMENTOS DEL DOM
    // ==========================================
    const tbody = document.querySelector('.data-table tbody');
    const buscador = document.getElementById('buscador-estudiantes');
    const statsNumbers = document.querySelectorAll('.stat-number');
    const mensajeVacio = document.querySelector('.empty-state');
    
    // Dropdowns de filtros
    const dropdownSecciones = document.querySelectorAll('.filter-dropdown')[0];
    const dropdownEstados = document.querySelectorAll('.filter-dropdown')[1];
    const summarySecciones = dropdownSecciones.querySelector('summary');
    const summaryEstados = dropdownEstados.querySelector('summary');
    
    // Estado actual de filtros
    let filtroSeccionActual = '';
    let filtroEstadoActual = 'todos';

    // ==========================================
    // 2. FUNCIÓN PRINCIPAL DE FILTRADO
    // ==========================================
    const aplicarFiltros = () => {
        const textoBusqueda = buscador.value.toLowerCase().trim();
        const filas = tbody.querySelectorAll('tr');
        
        let totalVisibles = 0;
        let activosVisibles = 0;
        let inactivosVisibles = 0;
        const seccionesVisibles = new Set();

        filas.forEach(fila => {
            const celdas = fila.querySelectorAll('td');
            
            // Ignorar filas vacías o sin datos
            if (celdas.length === 0) return;
            
            const nie = celdas[0]?.textContent.toLowerCase().trim() || '';
            const nombres = celdas[1]?.textContent.toLowerCase().trim() || '';
            const apellidos = celdas[2]?.textContent.toLowerCase().trim() || '';
            const seccion = celdas[3]?.textContent.toLowerCase().trim() || '';
            const estadoBadge = celdas[4]?.querySelector('.badge');
            const estado = estadoBadge ? estadoBadge.textContent.toLowerCase().trim() : '';
            
            // Ignorar filas completamente vacías
            if (!nie && !nombres && !apellidos) {
                fila.style.display = 'none';
                return;
            }

            // Lógica de coincidencias
            const coincideBusqueda = nie.includes(textoBusqueda) || 
                                    nombres.includes(textoBusqueda) || 
                                    apellidos.includes(textoBusqueda);
            
            const coincideSeccion = !filtroSeccionActual || 
                                   seccion === filtroSeccionActual.toLowerCase();
            
            const coincideEstado = filtroEstadoActual === 'todos' || 
                                  estado === filtroEstadoActual;

            // Mostrar/Ocultar fila y contar estadísticas
            if (coincideBusqueda && coincideSeccion && coincideEstado) {
                fila.style.display = '';
                totalVisibles++;
                
                if (estado === 'activo') activosVisibles++;
                if (estado === 'inactivo') inactivosVisibles++;
                if (seccion) seccionesVisibles.add(seccion);
            } else {
                fila.style.display = 'none';
            }
        });

        // Actualizar estadísticas
        statsNumbers[0].textContent = totalVisibles;
        statsNumbers[1].textContent = activosVisibles;
        statsNumbers[2].textContent = inactivosVisibles;
        statsNumbers[3].textContent = seccionesVisibles.size;

        // Mostrar/ocultar mensaje de estado vacío
        if (mensajeVacio) {
            mensajeVacio.style.display = totalVisibles === 0 ? 'block' : 'none';
        }
    };

    // ==========================================
    // 3. MANEJO DEL BUSCADOR
    // ==========================================
    buscador.addEventListener('input', aplicarFiltros);

    // ==========================================
    // 4. MANEJO DE DROPDOWNS <details>
    // ==========================================
    
    // Dropdown de Secciones
    dropdownSecciones.addEventListener('click', (e) => {
        const opcion = e.target.closest('a, button, [data-seccion]');
        
        if (opcion && !e.target.closest('summary')) {
            e.preventDefault();
            
            // Obtener valor de la sección
            filtroSeccionActual = opcion.dataset.seccion || opcion.textContent.trim();
            
            // Actualizar texto del summary
            const icono = summarySecciones.querySelector('i');
            summarySecciones.innerHTML = `${opcion.textContent.trim()} <i class="fa-solid fa-chevron-down"></i>`;
            
            // Cerrar dropdown
            dropdownSecciones.removeAttribute('open');
            
            // Aplicar filtros
            aplicarFiltros();
        }
    });

    // Dropdown de Estados (radio buttons)
    const radioEstados = dropdownEstados.querySelectorAll('input[type="radio"]');
    radioEstados.forEach(radio => {
        radio.addEventListener('change', (e) => {
            filtroEstadoActual = e.target.value;
            
            // Actualizar texto del summary
            const textoEstado = e.target.value === 'todos' ? 'Todos los estados' : 
                               e.target.value.charAt(0).toUpperCase() + e.target.value.slice(1);
            const icono = summaryEstados.querySelector('i');
            summaryEstados.innerHTML = `${textoEstado} <i class="fa-solid fa-chevron-down"></i>`;
            
            // Cerrar dropdown
            dropdownEstados.removeAttribute('open');
            
            // Aplicar filtros
            aplicarFiltros();
        });
    });

    // ==========================================
    // 5. MANEJO DE BOTONES DE ACCIÓN
    // ==========================================
    tbody.addEventListener('click', (e) => {
        const boton = e.target.closest('.btn-action');
        if (!boton) return;

        const fila = boton.closest('tr');
        const celdas = fila.querySelectorAll('td');
        const nie = celdas[0]?.textContent.trim();
        const nombreCompleto = `${celdas[1]?.textContent.trim()} ${celdas[2]?.textContent.trim()}`;

        // Botón VER
        if (boton.classList.contains('see')) {
            // Aquí puedes redirigir a una página de detalles o abrir un modal
            // Por ahora, solo un placeholder
            console.log('Ver detalles de:', nie, nombreCompleto);
            // window.location.href = `ver_estudiante.html?nie=${nie}`;
        }

        // Botón EDITAR
        if (boton.classList.contains('edit')) {
            // Aquí puedes redirigir a una página de edición o abrir un modal
            console.log('Editar estudiante:', nie);
            // window.location.href = `editar_estudiante.html?nie=${nie}`;
        }

        // Botón ELIMINAR
        if (boton.classList.contains('delete')) {
            const confirmacion = confirm(`¿Estás seguro de que deseas eliminar al estudiante ${nombreCompleto} (NIE: ${nie})?\n\nEsta acción no se puede deshacer.`);
            
            if (confirmacion) {
                // Aquí iría la llamada a PHP para eliminar
                console.log('Eliminar estudiante:', nie);
                // Ejemplo: fetch(`eliminar_estudiante.php?nie=${nie}`, {method: 'DELETE'})
                //     .then(response => response.json())
                //     .then(data => {
                //         if (data.success) {
                //             fila.remove();
                //             aplicarFiltros();
                //         }
                //     });
                
                // Placeholder: eliminar fila visualmente (solo para demostración)
                // fila.remove();
                // aplicarFiltros();
            }
        }
    });

    // ==========================================
    // 6. INICIALIZACIÓN
    // ==========================================
    aplicarFiltros(); // Calcular estadísticas iniciales
});