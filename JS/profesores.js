// ==========================================
// GESTIÓN DE PROFESORES - INTERACTIVIDAD
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    // ===== REFERENCIAS AL DOM =====
    const modal = document.getElementById('modalProfesor');
    const btnAgregar = document.querySelector('.btn-primary');
    const btnCerrar = document.querySelector('.btn-close');
    const btnCancel = document.querySelector('.btn-cancel');
    const formProfesor = document.querySelector('.modal-form');
    const listaProfesores = document.getElementById('listaProfesores');
    const buscarInput = document.getElementById('buscarProfesor');
    const filtroMateria = document.getElementById('filtroMateria');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const mensajeVacio = document.getElementById('mensajeVacio');
    const totalProfesoresEl = document.getElementById('totalProfesores');
    const totalMateriasEl = document.getElementById('totalMateriasCubiertas');

    // ===== DATOS VACÍOS (Se llenarán desde PHP/BD) =====
    let profesores = []; // ← SIN DATOS INICIALES

    // ===== INICIALIZACIÓN =====
    inicializarSidebar();
    renderizarTabla(); // ← Mostrará mensaje de "vacío"
    actualizarEstadisticas(); // ← Mostrará 0

    // ===== EVENT LISTENERS =====
    
    // Abrir modal
    btnAgregar.addEventListener('click', () => {
        modal.showModal();
    });

    // Cerrar modal - Botón X
    btnCerrar.addEventListener('click', () => {
        modal.close();
        formProfesor.reset();
    });

    // Cerrar modal - Botón Cancelar
    btnCancel.addEventListener('click', () => {
        modal.close();
        formProfesor.reset();
    });

    // Cerrar modal - Click fuera
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.close();
            formProfesor.reset();
        }
    });

    // Submit del formulario
    formProfesor.addEventListener('submit', (e) => {
        e.preventDefault();
        agregarProfesor();
    });

    // Búsqueda en tiempo real
    buscarInput.addEventListener('input', () => {
        renderizarTabla();
    });

    // Filtros
    filtroMateria.addEventListener('change', () => {
        renderizarTabla();
    });

    filtroSeccion.addEventListener('change', () => {
        renderizarTabla();
    });

    // ===== FUNCIONES =====

    // 1. Sidebar activo
    function inicializarSidebar() {
        const currentPage = window.location.pathname.split('/').pop();
        const links = document.querySelectorAll('.nav a');
        
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // 2. Renderizar tabla (sin filtros por ahora)
    function renderizarTabla() {
        const busqueda = buscarInput.value.toLowerCase();

        // Filtrar profesores (si hay búsqueda)
        const profesoresFiltrados = profesores.filter(prof => {
            return prof.nombres.toLowerCase().includes(busqueda) ||
                   prof.apellidos.toLowerCase().includes(busqueda) ||
                   prof.nip.includes(busqueda) ||
                   prof.correo.toLowerCase().includes(busqueda);
        });

        // Limpiar tabla
        listaProfesores.innerHTML = '';

        // Mostrar/ocultar mensaje vacío
        if (profesoresFiltrados.length === 0) {
            mensajeVacio.style.display = 'block';
        } else {
            mensajeVacio.style.display = 'none';
            
            // Renderizar filas
            profesoresFiltrados.forEach(prof => {
                const fila = crearFilaTabla(prof);
                listaProfesores.appendChild(fila);
            });
        }
    }

    // 3. Crear fila de tabla
    function crearFilaTabla(prof) {
        const tr = document.createElement('tr');

        // Nombre completo
        const tdNombre = document.createElement('td');
        tdNombre.innerHTML = `
            <strong>${prof.nombres} ${prof.apellidos}</strong><br>
            <small style="color: #64748b;">${prof.nip}</small>
        `;

        // Correo
        const tdCorreo = document.createElement('td');
        tdCorreo.textContent = prof.correo;

        // Materia
        const tdMateria = document.createElement('td');
        tdMateria.innerHTML = `<span class="materia-badge">${prof.materia}</span>`;

        // Secciones
        const tdSecciones = document.createElement('td');
        const seccionesContainer = document.createElement('div');
        seccionesContainer.className = 'seccion-container';
        
        const seccionesMostrar = prof.secciones.slice(0, 3);
        const seccionesRestantes = prof.secciones.length - 3;

        seccionesMostrar.forEach(sec => {
            const badge = document.createElement('span');
            badge.className = 'seccion-badge';
            badge.textContent = sec;
            seccionesContainer.appendChild(badge);
        });

        if (seccionesRestantes > 0) {
            const masBadge = document.createElement('span');
            masBadge.className = 'mas-secciones';
            masBadge.textContent = `+${seccionesRestantes} más`;
            seccionesContainer.appendChild(masBadge);
        }

        tdSecciones.appendChild(seccionesContainer);

        // Acciones
        const tdAcciones = document.createElement('td');
        tdAcciones.className = 'actions-cell';
        tdAcciones.innerHTML = `
            <button class="btn-action see" title="Ver detalles" onclick="verProfesor(${prof.id})">
                <i class="fa-solid fa-eye"></i>
            </button>
            <button class="btn-action edit" title="Editar" onclick="editarProfesor(${prof.id})">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button class="btn-action delete" title="Eliminar" onclick="eliminarProfesor(${prof.id})">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        tr.appendChild(tdNombre);
        tr.appendChild(tdCorreo);
        tr.appendChild(tdMateria);
        tr.appendChild(tdSecciones);
        tr.appendChild(tdAcciones);

        return tr;
    }

    // 4. Actualizar estadísticas
    function actualizarEstadisticas() {
        totalProfesoresEl.textContent = profesores.length;
        totalProfesoresEl.style.color = '#2563eb';
        
        const materiasUnicas = new Set(profesores.map(p => p.materia));
        totalMateriasEl.textContent = materiasUnicas.length;
        totalMateriasEl.style.color = '#9333ea';
    }

    // 5. Agregar profesor (SIMULACIÓN - Sin BD)
    function agregarProfesor() {
        // Validación básica
        const inputs = formProfesor.querySelectorAll('input[required], select[required]');
        let valido = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                valido = false;
                input.style.borderColor = '#ef4444';
            } else {
                input.style.borderColor = '#e2e8f0';
            }
        });

        if (!valido) {
            alert('Por favor, complete todos los campos requeridos');
            return;
        }

        // NOTA: Esto es solo una simulación visual
        // Cuando conectes con PHP, esto se enviará al servidor
        alert('⚠️ NOTA: Aún no hay conexión con la base de datos.\n\nCuando integres PHP, este formulario enviará los datos al servidor.');
        
        modal.close();
        formProfesor.reset();
        
        // Los datos NO se agregan al array porque no hay BD
        // renderizarTabla(); // No se llama porque no hay datos nuevos
    }

    // 6. Ver profesor (detalle)
    window.verProfesor = function(id) {
        alert('ℹ️ Información: Esta funcionalidad requerirá conexión con la base de datos.');
    };

    // 7. Editar profesor
    window.editarProfesor = function(id) {
        alert('✏️ Edición: Esta funcionalidad requerirá conexión con la base de datos.');
    };

    // 8. Eliminar profesor
    window.eliminarProfesor = function(id) {
        const confirmacion = confirm('⚠️ Eliminación: Esta funcionalidad requerirá conexión con la base de datos.\n\n¿Desea continuar?');
        
        if (confirmacion) {
            alert('Acción cancelada - Sin conexión a BD');
        }
    };

    // 9. Validación de email en tiempo real
    const emailInput = formProfesor.querySelector('input[type="email"]');
    if (emailInput) {
        emailInput.addEventListener('blur', () => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value && !emailRegex.test(emailInput.value)) {
                emailInput.style.borderColor = '#ef4444';
            } else if (emailInput.value) {
                emailInput.style.borderColor = '#2563eb';
            }
        });
    }

    // 10. Validación de teléfono
    const telefonoInput = formProfesor.querySelector('input[type="tel"]');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', (e) => {
            // Solo permitir números y guión
            e.target.value = e.target.value.replace(/[^0-9-]/g, '');
        });
    }

    // ===== MENSAJE INICIAL EN CONSOLA =====
    console.log('📌 Sistema de Profesores cargado');
    console.log('⚠️ Estado: Sin conexión a base de datos');
    console.log('💡 Cuando integres PHP, reemplaza el array "profesores" con datos del servidor');
});