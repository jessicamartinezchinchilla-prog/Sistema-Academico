// JS/secciones.js

// Variable global para almacenar la sección actual en el modal de detalles
let seccionActual = null;

document.addEventListener('DOMContentLoaded', () => {
    // Intercepta formularios para usar AJAX
    document.querySelectorAll('#formSeccion, #formEditar').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validarFormularioSeccion(this)) return;

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
                    
                    if (errorType === 'duplicado') msg = '⚠️ Ya existe una sección con esa combinación de Carrera + Grado + Letra';
                    else if (errorType === 'bd') msg = '⚠️ Error en la base de datos';
                    
                    alert(msg);
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Operación realizada con éxito');
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de conexión con el servidor');
            }
        });
    });

    // Mensajes de éxito/eliminación
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const successType = urlParams.get('success');
        if (successType === '1') alert('✅ Sección agregada exitosamente');
        else if (successType === 'editado') alert('✅ Sección actualizada exitosamente');
        else if (successType === 'eliminado') alert('🗑️ Sección eliminada exitosamente');
        
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Auto-generar nombre al cambiar carrera, grado o letra
    configurarAutoNombre('modalSeccion');
    configurarAutoNombre('modalEditar');
});

function configurarAutoNombre(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const inputs = ['carrera', 'grado', 'letra'].map(campo => {
        const prefix = modalId === 'modalSeccion' ? '' : 'edit_';
        return modal.querySelector(`[name="${campo === 'carrera' ? 'id_carrera' : (campo === 'grado' ? 'id_grado' : 'letra')}"]`);
    });

    const nombreInput = modal.querySelector(`[name="nombre"]`);
    
    function actualizarNombre() {
        const carreraSelect = modal.querySelector('[name="id_carrera"]');
        const gradoSelect = modal.querySelector('[name="id_grado"]');
        const letraInput = modal.querySelector('[name="letra"]');
        
        const carreraTexto = carreraSelect.options[carreraSelect.selectedIndex]?.text || '';
        const gradoTexto = gradoSelect.options[gradoSelect.selectedIndex]?.text || '';
        const letra = letraInput.value.toUpperCase();
        
        if (carreraTexto && carreraTexto !== 'Seleccione Carrera' && 
            gradoTexto && gradoTexto !== 'Seleccione Grado' && 
            letra) {
            nombreInput.value = `${carreraTexto} - ${gradoTexto} - ${letra}`;
        }
    }

    inputs.forEach(input => {
        if (input) input.addEventListener('change', actualizarNombre);
    });

    const letraInput = modal.querySelector('[name="letra"]');
    if (letraInput) {
        letraInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            actualizarNombre();
        });
    }
}

function validarFormularioSeccion(form) {
    const nombre = form.querySelector('[name="nombre"]').value.trim();
    const letra = form.querySelector('[name="letra"]').value.trim();
    const idCarrera = form.querySelector('[name="id_carrera"]').value;
    const idGrado = form.querySelector('[name="id_grado"]').value;
    
    if (nombre.length === 0) {
        alert('⚠️ El nombre de la sección es obligatorio');
        return false;
    }
    
    if (letra.length === 0) {
        alert('⚠️ La letra de la sección es obligatoria');
        return false;
    }
    
    if (!idCarrera) {
        alert('⚠️ Debes seleccionar una carrera');
        return false;
    }
    
    if (!idGrado) {
        alert('⚠️ Debes seleccionar un grado');
        return false;
    }
    
    return true;
}

function abrirModalAgregar() {
    document.getElementById('formSeccion').reset();
    document.getElementById('modalSeccion').showModal();
}

function verSeccion(btn) {
    const card = btn.closest('.subject-card');
    const d = card.dataset;
    seccionActual = d;
    
    document.getElementById('detallesTitulo').textContent = d.nombre;
    document.getElementById('detallesNombre').textContent = d.nombre;
    document.getElementById('detallesCarrera').textContent = d.carrera;
    document.getElementById('detallesGrado').textContent = d.grado;
    document.getElementById('detallesLetra').textContent = d.letra;
    document.getElementById('detallesEstudiantes').textContent = d.totalEstudiantes;
    document.getElementById('detallesTotalProfesores').textContent = d.totalProfesores;
    
    // Mostrar profesores como tags
    const lista = document.getElementById('detallesProfesoresLista');
    const profesoresNombres = d.profesoresNombres ? d.profesoresNombres.split(',').map(p => p.trim()) : [];
    
    if (profesoresNombres.length === 0 || profesoresNombres[0] === '') {
        lista.innerHTML = '<p class="no-profesores">No hay profesores asignados</p>';
    } else {
        lista.innerHTML = profesoresNombres.map(nombre => `
            <div class="profesor-tag">
                <i class="fa-solid fa-user-tie"></i>
                ${nombre}
            </div>
        `).join('');
    }
    
    document.getElementById('modalDetalles').showModal();
}

function cerrarModalDetalles() {
    document.getElementById('modalDetalles').close();
    seccionActual = null;
}

function editarSeccion() {
    if (!seccionActual) return;
    
    const d = seccionActual;
    
    document.getElementById('edit_id').value = d.id;
    document.getElementById('edit_nombre').value = d.nombre;
    document.getElementById('edit_letra').value = d.letra;
    document.getElementById('edit_carrera').value = d.carreraId;
    document.getElementById('edit_grado').value = d.gradoId;
    document.getElementById('edit_descripcion').value = d.descripcion || '';
    
    // Marcar los profesores asignados
    const profesoresAsignados = d.profesoresNombres ? d.profesoresNombres.split(',').map(p => p.trim()) : [];
    const checkboxes = document.querySelectorAll('.edit_profesor_check');
    
    checkboxes.forEach(checkbox => {
        const profesorNombre = checkbox.parentElement.textContent.trim();
        if (profesoresAsignados.includes(profesorNombre)) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });
    
    document.getElementById('modalDetalles').close();
    document.getElementById('modalEditar').showModal();
}

function eliminarSeccion() {
    if (!seccionActual) return;
    
    if (confirm(`¿Estás seguro de eliminar la sección "${seccionActual.nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = `../actions/secciones_action.php?accion=eliminar&id=${seccionActual.id}`;
    }
}