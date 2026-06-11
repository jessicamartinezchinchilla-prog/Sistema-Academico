// ==========================================
// GESTIÓN DE SECCIONES - INTERACTIVIDAD
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    // ===== REFERENCIAS AL DOM =====
    const modalSeccion = document.getElementById('modalSeccion');
    const modalDetalles = document.getElementById('modalDetalles');
    const formSeccion = document.getElementById('formSeccion');
    const subjectsGrid = document.getElementById('subjectsGrid');
    const contadorSecciones = document.getElementById('contadorSecciones');
    
    // ===== DATOS (Vacíos al inicio - se llenarán desde PHP/BD) =====
    let secciones = [];
    let seccionActualIndex = null;
    
    // Datos que vendrán de la BD (por ahora vacíos)
    let especialidades = [];
    let años = [];
    let seccionesDisponibles = [];
    let profesoresDisponibles = [];

    // ===== INICIALIZACIÓN =====
    renderizarSecciones();
    actualizarContador();
    cargarDatosVacios();

    // ===== EVENT LISTENERS =====
    
    // Cerrar modal agregar - Botón Cancelar
    const btnCancel = modalSeccion.querySelector('.btn-secondary');
    btnCancel.addEventListener('click', () => {
        modalSeccion.close();
        formSeccion.reset();
    });

    // Cerrar modal agregar - Click fuera
    modalSeccion.addEventListener('click', (e) => {
        if (e.target === modalSeccion) {
            modalSeccion.close();
            formSeccion.reset();
        }
    });

    // Submit del formulario agregar/editar
    formSeccion.addEventListener('submit', (e) => {
        e.preventDefault();
        if (seccionActualIndex !== null) {
            actualizarSeccion();
        } else {
            agregarSeccion();
        }
    });

    // ===== FUNCIONES =====

    // 1. Cargar datos vacíos (simulando sin BD)
    function cargarDatosVacios() {
        // Especialidades - vacío
        const selectEspecialidad = document.getElementById('especialidad');
        selectEspecialidad.innerHTML = '<option value="">No hay especialidades disponibles</option>';
        
        // Años - vacío
        const selectAño = document.getElementById('año');
        selectAño.innerHTML = '<option value="">No hay años disponibles</option>';
        
        // Secciones - vacío
        const selectSeccion = document.getElementById('seccion');
        selectSeccion.innerHTML = '<option value="">No hay secciones disponibles</option>';
        
        // Profesores - vacío
        const selectProfesores = document.getElementById('profesores');
        selectProfesores.innerHTML = '<option value="">No hay profesores disponibles</option>';
        
        // Mostrar advertencia
        document.getElementById('profesoresWarning').style.display = 'block';
        
        console.log('⚠️ Sistema cargado sin conexión a base de datos');
        console.log('💡 Los campos se llenarán cuando se conecte con PHP/BD');
    }

    // 2. Renderizar secciones
    function renderizarSecciones() {
        subjectsGrid.innerHTML = '';

        if (secciones.length === 0) {
            subjectsGrid.innerHTML = `
                <div class="empty-grid-message">
                    <i class="fa-solid fa-school"></i>
                    No hay secciones registradas aún.<br>
                    <small>Haz clic en "Añadir sección" para comenzar.</small>
                </div>
            `;
        } else {
            secciones.forEach((seccion, index) => {
                const card = crearTarjetaSeccion(seccion, index);
                subjectsGrid.appendChild(card);
            });
        }
    }

    // 3. Crear tarjeta de sección
    function crearTarjetaSeccion(seccion, index) {
        const article = document.createElement('article');
        article.className = 'subject-card';
        
        const profesoresCount = seccion.profesores ? seccion.profesores.length : 0;
        
        article.innerHTML = `
            <div class="circle">${seccion.estudiantes}</div>
            <h3>${seccion.nombre}</h3>
            <p class="subtitle">${seccion.especialidad}</p>
            
            <div class="info-row">
                <span>Estudiantes</span>
                <span>${seccion.estudiantes}</span>
            </div>
            
            <div class="info-row">
                <span>Año</span>
                <span>${seccion.año}</span>
            </div>
            
            <div class="info-row">
                <span>Sección</span>
                <span class="badge-seccion">${seccion.seccion}</span>
            </div>
            
            <div class="profesores-info">
                <span>Profesores asignados:</span>
                <span>${profesoresCount} docentes</span>
            </div>
            
            <button class="btn-primary btn-ver-detalles" data-index="${index}">Ver Detalles</button>
        `;
        
        return article;
    }

    // 4. Agregar sección
    function agregarSeccion() {
        const datos = obtenerDatosFormulario();
        
        if (!datos) return;

        secciones.push(datos);
        
        renderizarSecciones();
        actualizarContador();
        modalSeccion.close();
        formSeccion.reset();
        
        console.log('Sección agregada:', datos);
    }

    // 5. Actualizar sección
    function actualizarSeccion() {
        const datos = obtenerDatosFormulario();
        
        if (!datos) return;

        secciones[seccionActualIndex] = datos;
        
        renderizarSecciones();
        modalSeccion.close();
        formSeccion.reset();
        seccionActualIndex = null;
        
        console.log('Sección actualizada:', datos);
    }

    // 6. Obtener datos del formulario
    function obtenerDatosFormulario() {
        const nombre = document.getElementById('nombre').value.trim();
        const especialidad = document.getElementById('especialidad').value;
        const año = document.getElementById('año').value;
        const seccion = document.getElementById('seccion').value;
        const estudiantes = parseInt(document.getElementById('estudiantes').value) || 0;
        
        const profesoresSelect = document.getElementById('profesores');
        const profesores = Array.from(profesoresSelect.selectedOptions)
            .map(option => option.value)
            .filter(value => value !== '');

        if (!nombre || !especialidad || !año || !seccion) {
            alert('⚠️ No se puede guardar: Los campos están vacíos porque no hay conexión con la base de datos.\n\nCuando se conecte con PHP, estos campos se llenarán automáticamente.');
            return null;
        }

        return {
            nombre: nombre,
            especialidad: especialidad,
            año: año,
            seccion: seccion,
            estudiantes: estudiantes,
            profesores: profesores
        };
    }

    // 7. Actualizar contador
    function actualizarContador() {
        contadorSecciones.textContent = `${secciones.length} Secciones`;
    }

    // 8. Ver detalles
    subjectsGrid.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-ver-detalles')) {
            const index = parseInt(e.target.dataset.index);
            seccionActualIndex = index;
            mostrarDetalles(index);
        }
    });

    // 9. Mostrar detalles
    function mostrarDetalles(index) {
        const seccion = secciones[index];
        
        if (!seccion) return;

        document.getElementById('detallesNombre').textContent = seccion.nombre;
        document.getElementById('detallesEspecialidad').textContent = seccion.especialidad;
        document.getElementById('detallesAño').textContent = seccion.año;
        document.getElementById('detallesSeccion').textContent = seccion.seccion;
        document.getElementById('detallesEstudiantes').textContent = seccion.estudiantes;

        const profesoresLista = document.getElementById('detallesProfesoresLista');
        
        if (seccion.profesores && seccion.profesores.length > 0) {
            profesoresLista.innerHTML = seccion.profesores.map(prof => `
                <div class="profesor-tag">
                    <i class="fa-solid fa-user-tie"></i>
                    ${prof}
                </div>
            `).join('');
        } else {
            profesoresLista.innerHTML = '<p class="no-profesores">No hay profesores asignados</p>';
        }

        modalDetalles.showModal();
    }

    // 10. Editar sección
    window.editarSeccion = function() {
        if (seccionActualIndex === null) return;
        
        const seccion = secciones[seccionActualIndex];
        
        modalDetalles.close();
        
        document.getElementById('nombre').value = seccion.nombre;
        document.getElementById('especialidad').value = seccion.especialidad;
        document.getElementById('año').value = seccion.año;
        document.getElementById('seccion').value = seccion.seccion;
        document.getElementById('estudiantes').value = seccion.estudiantes;
        
        const profesoresSelect = document.getElementById('profesores');
        Array.from(profesoresSelect.options).forEach(option => {
            option.selected = seccion.profesores.includes(option.value);
        });
        
        modalSeccion.querySelector('h2').textContent = 'Editar Sección';
        modalSeccion.showModal();
    };

    // 11. Eliminar sección
    window.eliminarSeccion = function() {
        if (seccionActualIndex === null) return;
        
        const seccion = secciones[seccionActualIndex];
        const confirmacion = confirm(`¿Está seguro de eliminar la sección "${seccion.nombre}"?`);
        
        if (confirmacion) {
            secciones.splice(seccionActualIndex, 1);
            seccionActualIndex = null;
            
            renderizarSecciones();
            actualizarContador();
            modalDetalles.close();
            
            console.log('Sección eliminada');
        }
    };

    // 12. Cerrar modal detalles
    window.cerrarModalDetalles = function() {
        document.getElementById('modalDetalles').close();
    };

    // Cerrar modal detalles - Click fuera
    modalDetalles.addEventListener('click', (e) => {
        if (e.target === modalDetalles) {
            modalDetalles.close();
        }
    });

    // Resetear título del modal al cerrar
    modalSeccion.addEventListener('close', () => {
        modalSeccion.querySelector('h2').textContent = 'Añadir Nueva Sección';
        seccionActualIndex = null;
    });
});