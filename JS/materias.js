// materias.js - VERSIÓN QUE FUNCIONA CON TU HTML ACTUAL

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ materias.js cargado');
    
    // Prevenir envío del formulario de cerrar modal
    const formCerrar = document.querySelector('#modalMateria .modal-header form');
    if (formCerrar) {
        formCerrar.addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById('modalMateria').close();
        });
    }
    
    // Configurar formulario de nueva materia
    const formNueva = document.querySelector('#modalMateria form.modal-form');
    if (formNueva) {
        console.log('✅ Formulario encontrado');
        
        formNueva.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🚫 Previniendo envío del formulario');
            
            await guardarNuevaMateria(this);
        });
    }
    
    cargarMaterias();
    cargarDocentes();
});

async function guardarNuevaMateria(form) {
    console.log('💾 Guardando materia...');
    
    const formData = new FormData(form);
    const btnGuardar = form.querySelector('.btn-save');
    
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';
    }
    
    try {
        const response = await fetch('../PHP/procesar_materia.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('📋 Respuesta:', data);
        
        if (data.success) {
            mostrarNotificacion('success', 'Materia guardada correctamente');
            document.getElementById('modalMateria').close();
            form.reset();
            cargarMaterias();
        } else {
            mostrarNotificacion('error', data.message || 'Error al guardar');
        }
    } catch (error) {
        console.error('💥 Error:', error);
        mostrarNotificacion('error', 'Error de conexión');
    } finally {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar Materia';
        }
    }
}

// ========================================
// CARGAR MATERIAS
// ========================================

async function cargarMaterias() {
    try {
        const response = await fetch('../PHP/obtener_materias.php');
        const data = await response.json();
        
        if (data.success) {
            renderizarMaterias(data.materias);
            actualizarEstadisticas(data.materias);
        }
    } catch (error) {
        console.error('Error al cargar materias:', error);
        mostrarEstadoVacio();
    }
}

function renderizarMaterias(materias) {
    const listaMaterias = document.getElementById('listaMaterias');
    const mensajeVacio = document.getElementById('mensajeVacio');
    
    if (!listaMaterias) return;
    
    listaMaterias.innerHTML = '';
    
    if (!materias || materias.length === 0) {
        mostrarEstadoVacio();
        return;
    }
    
    if (mensajeVacio) {
        mensajeVacio.style.display = 'none';
    }
    
    materias.forEach(materia => {
        const tarjeta = crearTarjetaMateria(materia);
        listaMaterias.appendChild(tarjeta);
    });
}

function crearTarjetaMateria(materia) {
    const article = document.createElement('article');
    article.setAttribute('data-materia-id', materia.id);
    
    article.innerHTML = `
        <h3>${materia.nombre}</h3>
        <p><strong>Código:</strong> ${materia.codigo}</p>
        <p><strong>Docente:</strong> ${materia.docente_nombre || 'Sin asignar'}</p>
        <p class="descripcion">${materia.descripcion || 'Sin descripción'}</p>
    `;
    
    return article;
}

// ========================================
// CARGAR DOCENTES
// ========================================

async function cargarDocentes() {
    try {
        const response = await fetch('../PHP/obtener_docentes.php');
        const data = await response.json();
        
        if (data.success) {
            llenarSelectDocentes(data.docentes);
        }
    } catch (error) {
        console.error('Error al cargar docentes:', error);
    }
}

function llenarSelectDocentes(docentes) {
    const selectNuevo = document.getElementById('mat_docente');
    const selectEditar = document.getElementById('edit_docente');
    
    if (selectNuevo) {
        selectNuevo.innerHTML = '<option value="">Seleccione Docente</option>';
        docentes.forEach(docente => {
            const option = document.createElement('option');
            option.value = docente.id;
            option.textContent = docente.nombre_completo;
            selectNuevo.appendChild(option);
        });
    }
    
    if (selectEditar) {
        selectEditar.innerHTML = '<option value="">Seleccione Docente</option>';
        docentes.forEach(docente => {
            const option = document.createElement('option');
            option.value = docente.id;
            option.textContent = docente.nombre_completo;
            selectEditar.appendChild(option);
        });
    }
}

// ========================================
// ESTADÍSTICAS
// ========================================

function actualizarEstadisticas(materias) {
    const totalMateriasEl = document.getElementById('totalMaterias');
    const totalDocentesEl = document.getElementById('totalDocentes');
    
    if (totalMateriasEl) {
        totalMateriasEl.textContent = materias.length;
    }
    
    if (totalDocentesEl) {
        const docentesUnicos = new Set(materias.map(m => m.docente_id)).size;
        totalDocentesEl.textContent = docentesUnicos;
    }
}

// ========================================
// MOSTRAR ESTADO VACÍO
// ========================================

function mostrarEstadoVacio() {
    const mensajeVacio = document.getElementById('mensajeVacio');
    if (mensajeVacio) {
        mensajeVacio.style.display = 'block';
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