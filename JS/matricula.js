// matricula.js
// ========================================
// GESTIÓN DE MATRÍCULAS - INTERACTIVIDAD
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ matricula.js cargado');
    
    // Configurar formularios
    configurarFormularioNuevaMatricula();
    configurarFormularioEditarMatricula();
    
    // Cargar datos al iniciar
    cargarMatriculas();
    cargarDatosIniciales();
    
    // Configurar búsqueda y filtros
    configurarBusqueda();
    configurarFiltros();
});

// ========================================
// CONFIGURAR FORMULARIO NUEVA MATRÍCULA
// ========================================

function configurarFormularioNuevaMatricula() {
    const form = document.getElementById('formMatricula');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🚫 Previniendo envío del formulario');
            
            if (!validarFormularioMatricula(this)) {
                return;
            }
            
            await guardarNuevaMatricula(this);
        });
    }
}

// ========================================
// GUARDAR NUEVA MATRÍCULA
// ========================================

async function guardarNuevaMatricula(form) {
    const formData = new FormData(form);
    
    const btnGuardar = form.querySelector('.btn-save');
    const textoOriginal = btnGuardar ? btnGuardar.textContent : '';
    
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';
    }
    
    try {
        const response = await fetch('../PHP/procesar_matricula.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('📋 Respuesta:', data);
        
        if (data.success) {
            mostrarNotificacion('success', data.message || 'Matrícula guardada correctamente');
            document.getElementById('modalMatricula').close();
            form.reset();
            mostrarPaso(1);
            cargarMatriculas();
        } else {
            mostrarNotificacion('error', data.message || 'Error al guardar la matrícula');
        }
    } catch (error) {
        console.error('💥 Error:', error);
        mostrarNotificacion('error', 'Error de conexión. Intente de nuevo.');
    } finally {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = textoOriginal;
        }
    }
}

// ========================================
// CONFIGURAR FORMULARIO EDITAR MATRÍCULA
// ========================================

function configurarFormularioEditarMatricula() {
    const form = document.getElementById('formEditar');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🚫 Previniendo envío del formulario de edición');
            
            if (!validarFormularioEditarMatricula(this)) {
                return;
            }
            
            await actualizarMatricula(this);
        });
    }
}

// ========================================
// ACTUALIZAR MATRÍCULA
// ========================================

async function actualizarMatricula(form) {
    const formData = new FormData(form);
    
    const btnGuardar = form.querySelector('.btn-save');
    const textoOriginal = btnGuardar ? btnGuardar.textContent : '';
    
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Actualizando...';
    }
    
    try {
        const response = await fetch('../PHP/actualizar_matricula.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('📋 Respuesta:', data);
        
        if (data.success) {
            mostrarNotificacion('success', data.message || 'Matrícula actualizada correctamente');
            document.getElementById('modalEditar').close();
            cargarMatriculas();
        } else {
            mostrarNotificacion('error', data.message || 'Error al actualizar la matrícula');
        }
    } catch (error) {
        console.error('💥 Error:', error);
        mostrarNotificacion('error', 'Error de conexión. Intente de nuevo.');
    } finally {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = textoOriginal;
        }
    }
}

// ========================================
// CARGAR MATRÍCULAS
// ========================================

async function cargarMatriculas() {
    try {
        const response = await fetch('../PHP/obtener_matriculas.php');
        const data = await response.json();
        
        if (data.success) {
            renderizarMatriculas(data.matriculas);
            actualizarEstadisticas(data.matriculas);
        } else {
            console.error('Error al cargar matrículas:', data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        mostrarEstadoVacio();
    }
}

// ========================================
// RENDERIZAR MATRÍCULAS EN LA TABLA
// ========================================

function renderizarMatriculas(matriculas) {
    const tbody = document.getElementById('listaEstudiantes');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!matriculas || matriculas.length === 0) {
        mostrarEstadoVacio();
        return;
    }
    
    matriculas.forEach(matricula => {
        const fila = crearFilaMatricula(matricula);
        tbody.appendChild(fila);
    });
}

function crearFilaMatricula(matricula) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-matricula-id', matricula.id);
    
    const estadoClass = matricula.estado === 'Activo' ? 'active' : 'inactive';
    
    tr.innerHTML = `
        <td>${matricula.nie}</td>
        <td>${matricula.nombres} ${matricula.apellidos}</td>
        <td>${matricula.anio_nombre || 'N/A'}</td>
        <td>${matricula.seccion_nombre || 'N/A'}</td>
        <td>${matricula.responsable_nombres} ${matricula.responsable_apellidos}</td>
        <td>${matricula.responsable_telefono}</td>
        <td><span class="badge ${estadoClass}">${matricula.estado}</span></td>
        <td class="actions-cell">
            <button type="button" class="btn-action see" title="Ver detalles" onclick="verDetalleMatricula(${matricula.id})">
                <i class="fa-solid fa-eye"></i>
                <span class="sr-only">Ver detalles</span>
            </button>
            <button type="button" class="btn-action edit" title="Editar" onclick="editarMatricula(${matricula.id})">
                <i class="fa-solid fa-pen-to-square"></i>
                <span class="sr-only">Editar</span>
            </button>
            <button type="button" class="btn-action delete" title="Eliminar" onclick="confirmarEliminarMatricula(${matricula.id}, '${matricula.nombres} ${matricula.apellidos}')">
                <i class="fa-solid fa-trash"></i>
                <span class="sr-only">Eliminar</span>
            </button>
        </td>
    `;
    
    return tr;
}

// ========================================
// VER DETALLE DE MATRÍCULA
// ========================================

async function verDetalleMatricula(matriculaId) {
    try {
        const response = await fetch(`../PHP/obtener_matricula_detalle.php?id=${matriculaId}`);
        const data = await response.json();
        
        if (data.success) {
            const detalle = document.getElementById('detalleEstudiante');
            const mat = data.matricula;
            
            detalle.innerHTML = `
                <div style="display: grid; gap: 15px;">
                    <div style="background: #f9fafb; padding: 15px; border-radius: 10px;">
                        <h4 style="margin-bottom: 10px; color: #2563eb;">Datos del Estudiante</h4>
                        <p><strong>NIE:</strong> ${mat.nie}</p>
                        <p><strong>Nombre:</strong> ${mat.nombres} ${mat.apellidos}</p>
                        <p><strong>Edad:</strong> ${mat.edad} años</p>
                        <p><strong>Fecha de Nacimiento:</strong> ${mat.fecha_nacimiento}</p>
                        <p><strong>Email:</strong> ${mat.email}</p>
                        <p><strong>Teléfono:</strong> ${mat.telefono}</p>
                        <p><strong>Dirección:</strong> ${mat.direccion}</p>
                    </div>
                    
                    <div style="background: #f9fafb; padding: 15px; border-radius: 10px;">
                        <h4 style="margin-bottom: 10px; color: #2563eb;">Datos Académicos</h4>
                        <p><strong>Carrera:</strong> ${mat.carrera_nombre || 'N/A'}</p>
                        <p><strong>Año:</strong> ${mat.anio_nombre || 'N/A'}</p>
                        <p><strong>Sección:</strong> ${mat.seccion_nombre || 'N/A'}</p>
                        <p><strong>Estado:</strong> <span class="badge ${mat.estado === 'Activo' ? 'active' : 'inactive'}">${mat.estado}</span></p>
                    </div>
                    
                    <div style="background: #f9fafb; padding: 15px; border-radius: 10px;">
                        <h4 style="margin-bottom: 10px; color: #2563eb;">Datos del Responsable</h4>
                        <p><strong>Nombre:</strong> ${mat.responsable_nombres} ${mat.responsable_apellidos}</p>
                        <p><strong>DUI:</strong> ${mat.responsable_dui}</p>
                        <p><strong>Ocupación:</strong> ${mat.responsable_ocupacion}</p>
                        <p><strong>Parentesco:</strong> ${mat.responsable_parentesco}</p>
                        <p><strong>Email:</strong> ${mat.responsable_email}</p>
                        <p><strong>Teléfono:</strong> ${mat.responsable_telefono}</p>
                        <p><strong>Dirección:</strong> ${mat.responsable_direccion}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('modalVer').showModal();
        } else {
            mostrarNotificacion('error', data.message || 'Error al cargar los detalles');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error de conexión');
    }
}

// ========================================
// EDITAR MATRÍCULA
// ========================================

async function editarMatricula(matriculaId) {
    try {
        const response = await fetch(`../PHP/obtener_matricula_detalle.php?id=${matriculaId}`);
        const data = await response.json();
        
        if (data.success) {
            const mat = data.matricula;
            
            // Llenar formulario paso 1
            document.getElementById('edit_matricula_id').value = mat.id;
            document.getElementById('edit_nie').value = mat.nie;
            document.getElementById('edit_nombres').value = mat.nombres;
            document.getElementById('edit_apellidos').value = mat.apellidos;
            document.getElementById('edit_dui').value = mat.dui || '';
            document.getElementById('edit_edad').value = mat.edad;
            document.getElementById('edit_fecha_nacimiento').value = mat.fecha_nacimiento;
            document.getElementById('edit_carrera').value = mat.carrera_id || '';
            document.getElementById('edit_anio').value = mat.anio_id || '';
            document.getElementById('edit_seccion').value = mat.seccion_id || '';
            document.getElementById('edit_estado').value = mat.estado;
            document.getElementById('edit_telefono').value = mat.telefono;
            document.getElementById('edit_direccion').value = mat.direccion;
            document.getElementById('edit_email').value = mat.email;
            
            // Llenar formulario paso 2
            document.getElementById('edit_resp_dui').value = mat.responsable_dui;
            document.getElementById('edit_resp_nombres').value = mat.responsable_nombres;
            document.getElementById('edit_resp_apellidos').value = mat.responsable_apellidos;
            document.getElementById('edit_resp_ocupacion').value = mat.responsable_ocupacion;
            document.getElementById('edit_resp_parentesco').value = mat.responsable_parentesco;
            document.getElementById('edit_resp_email').value = mat.responsable_email;
            document.getElementById('edit_resp_telefono').value = mat.responsable_telefono;
            document.getElementById('edit_resp_direccion').value = mat.responsable_direccion;
            
            // Abrir modal en paso 1
            document.getElementById('modalEditar').showModal();
            mostrarPasoEditar(1);
        } else {
            mostrarNotificacion('error', data.message || 'Error al cargar los datos');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error de conexión');
    }
}

// ========================================
// ELIMINAR MATRÍCULA
// ========================================

async function confirmarEliminarMatricula(matriculaId, nombreCompleto) {
    const confirmacion = confirm(`¿Está seguro de eliminar la matrícula de "${nombreCompleto}"?\n\nEsta acción no se puede deshacer.`);
    
    if (!confirmacion) return;
    
    try {
        const response = await fetch(`../PHP/eliminar_matricula.php?id=${matriculaId}`);
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('success', data.message || 'Matrícula eliminada correctamente');
            cargarMatriculas();
        } else {
            mostrarNotificacion('error', data.message || 'Error al eliminar la matrícula');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error de conexión');
    }
}

// ========================================
// CARGAR DATOS INICIALES (Carreras, Años, Secciones)
// ========================================

async function cargarDatosIniciales() {
    try {
        // Cargar carreras
        const responseCarreras = await fetch('../PHP/obtener_carreras.php');
        const dataCarreras = await responseCarreras.json();
        
        if (dataCarreras.success) {
            llenarSelectCarreras(dataCarreras.carreras);
        }
        
        // Cargar secciones
        const responseSecciones = await fetch('../PHP/obtener_secciones.php');
        const dataSecciones = await responseSecciones.json();
        
        if (dataSecciones.success) {
            llenarSelectSecciones(dataSecciones.secciones);
        }
    } catch (error) {
        console.error('Error al cargar datos iniciales:', error);
    }
}

function llenarSelectCarreras(carreras) {
    const selectNuevo = document.getElementById('mat_carrera');
    const selectEditar = document.getElementById('edit_carrera');
    
    if (selectNuevo) {
        selectNuevo.innerHTML = '<option value="">Seleccione Carrera</option>';
        carreras.forEach(carrera => {
            const option = document.createElement('option');
            option.value = carrera.id;
            option.textContent = carrera.nombre;
            selectNuevo.appendChild(option);
        });
    }
    
    if (selectEditar) {
        selectEditar.innerHTML = '<option value="">Seleccione Carrera</option>';
        carreras.forEach(carrera => {
            const option = document.createElement('option');
            option.value = carrera.id;
            option.textContent = carrera.nombre;
            selectEditar.appendChild(option);
        });
    }
}

function llenarSelectSecciones(secciones) {
    const selectNuevo = document.getElementById('mat_seccion');
    const selectEditar = document.getElementById('edit_seccion');
    
    if (selectNuevo) {
        selectNuevo.innerHTML = '<option value="">Seleccione Sección</option>';
        secciones.forEach(seccion => {
            const option = document.createElement('option');
            option.value = seccion.id;
            option.textContent = seccion.nombre;
            selectNuevo.appendChild(option);
        });
    }
    
    if (selectEditar) {
        selectEditar.innerHTML = '<option value="">Seleccione Sección</option>';
        secciones.forEach(seccion => {
            const option = document.createElement('option');
            option.value = seccion.id;
            option.textContent = seccion.nombre;
            selectEditar.appendChild(option);
        });
    }
}

// ========================================
// BÚSQUEDA
// ========================================

function configurarBusqueda() {
    const inputBusqueda = document.getElementById('buscarEstudiante');
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            filtrarMatriculas(termino, null, null);
        });
    }
}

// ========================================
// FILTROS
// ========================================

function configurarFiltros() {
    const filtroGrado = document.getElementById('filtroGrado');
    const filtroEstado = document.getElementById('filtroEstado');
    
    if (filtroGrado) {
        filtroGrado.addEventListener('change', function() {
            const termino = document.getElementById('buscarEstudiante').value.toLowerCase();
            filtrarMatriculas(termino, this.value, null);
        });
    }
    
    if (filtroEstado) {
        filtroEstado.addEventListener('change', function() {
            const termino = document.getElementById('buscarEstudiante').value.toLowerCase();
            const grado = document.getElementById('filtroGrado').value;
            filtrarMatriculas(termino, grado, this.value);
        });
    }
}

async function filtrarMatriculas(termino, grado, estado) {
    try {
        let url = '../PHP/obtener_matriculas.php?';
        const params = [];
        
        if (termino) params.push(`busqueda=${encodeURIComponent(termino)}`);
        if (grado) params.push(`grado=${grado}`);
        if (estado) params.push(`estado=${encodeURIComponent(estado)}`);
        
        url += params.join('&');
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            renderizarMatriculas(data.matriculas);
        }
    } catch (error) {
        console.error('Error al filtrar:', error);
    }
}

// ========================================
// VALIDACIONES
// ========================================

function validarFormularioMatricula(form) {
    const camposObligatorios = form.querySelectorAll('[required]');
    
    for (let campo of camposObligatorios) {
        if (!campo.value.trim()) {
            mostrarNotificacion('error', 'Todos los campos obligatorios deben estar completos');
            campo.focus();
            return false;
        }
    }
    
    // Validar email
    const email = form.querySelector('[name="email"]');
    if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        mostrarNotificacion('error', 'El correo electrónico no es válido');
        email.focus();
        return false;
    }
    
    return true;
}

function validarFormularioEditarMatricula(form) {
    return validarFormularioMatricula(form);
}

// ========================================
// ESTADÍSTICAS
// ========================================

function actualizarEstadisticas(matriculas) {
    const totalEl = document.getElementById('totalMatriculas');
    const activasEl = document.getElementById('matriculasActivas');
    const inactivasEl = document.getElementById('matriculasInactivas');
    const anioEl = document.getElementById('matriculasAnio');
    
    if (totalEl) totalEl.textContent = matriculas.length;
    
    if (activasEl) {
        const activas = matriculas.filter(m => m.estado === 'Activo').length;
        activasEl.textContent = activas;
    }
    
    if (inactivasEl) {
        const inactivas = matriculas.filter(m => m.estado === 'Inactivo').length;
        inactivasEl.textContent = inactivas;
    }
    
    if (anioEl) {
        const anioActual = new Date().getFullYear();
        const esteAnio = matriculas.filter(m => {
            const fecha = new Date(m.fecha_creacion);
            return fecha.getFullYear() === anioActual;
        }).length;
        anioEl.textContent = esteAnio;
    }
}

// ========================================
// MOSTRAR ESTADO VACÍO
// ========================================

function mostrarEstadoVacio() {
    const tbody = document.getElementById('listaEstudiantes');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                    No hay matrículas registradas en el sistema aún.
                </td>
            </tr>
        `;
    }
}

// ========================================
// NOTIFICACIONES
// ========================================

function mostrarNotificacion(tipo, mensaje) {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    
    const icono = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    notificacion.innerHTML = `<i class="fa-solid ${icono}"></i> ${mensaje}`;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.classList.add('mostrar');
    }, 10);
    
    setTimeout(() => {
        notificacion.classList.remove('mostrar');
        setTimeout(() => notificacion.remove(), 300);
    }, 3000);
}

// ========================================
// PASOS DEL FORMULARIO
// ========================================

function mostrarPaso(paso) {
    document.getElementById('paso1').style.display = paso === 1 ? 'block' : 'none';
    document.getElementById('paso2').style.display = paso === 2 ? 'block' : 'none';
}

function mostrarPasoEditar(paso) {
    document.getElementById('edit_paso1').style.display = paso === 1 ? 'block' : 'none';
    document.getElementById('edit_paso2').style.display = paso === 2 ? 'block' : 'none';
}

// ========================================
// CERRAR MODALES
// ========================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('dialog[open]').forEach(modal => {
            modal.close();
        });
    }
});

document.querySelectorAll('dialog').forEach(dialog => {
    dialog.addEventListener('click', function(e) {
        if (e.target === this) {
            this.close();
        }
    });
});