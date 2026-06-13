// JS/materias.js

document.addEventListener('DOMContentLoaded', () => {
    // Intercepta formularios para usar AJAX
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validarFormularioMateria(this)) return;

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
                    
                    if (errorType === 'codigo_duplicado') msg = '⚠️ El código de materia ya está registrado';
                    else if (errorType === 'sin_carreras') msg = '⚠️ Debes seleccionar al menos una carrera';
                    else if (errorType === 'sin_docentes') msg = '⚠️ Debes seleccionar al menos un docente';
                    else if (errorType === 'sin_secciones') msg = '️ Debes seleccionar al menos una sección';
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
        if (successType === '1') alert('✅ Materia agregada exitosamente');
        else if (successType === 'editado') alert('✅ Materia actualizada exitosamente');
        else if (successType === 'eliminado') alert('️ Materia eliminada exitosamente');
        
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function validarFormularioMateria(form) {
    const codigo = form.querySelector('[name="codigo"]').value.trim();
    const nombre = form.querySelector('[name="nombre"]').value.trim();
    const carreras = form.querySelectorAll('input[name="carreras[]"]:checked');
    const docentes = form.querySelectorAll('input[name="docentes[]"]:checked');
    const secciones = form.querySelectorAll('input[name="secciones[]"]:checked');
    
    if (codigo.length === 0) {
        alert('⚠️ El código de materia es obligatorio');
        return false;
    }
    
    if (nombre.length === 0) {
        alert('⚠️ El nombre de la materia es obligatorio');
        return false;
    }
    
    if (carreras.length === 0) {
        alert('⚠️ Debes seleccionar al menos una carrera');
        return false;
    }
    
    if (docentes.length === 0) {
        alert('⚠️ Debes seleccionar al menos un docente');
        return false;
    }
    
    if (secciones.length === 0) {
        alert('⚠️ Debes seleccionar al menos una sección');
        return false;
    }
    
    return true;
}

function abrirModalAgregar() {
    document.querySelector('#modalMateria form').reset();
    document.getElementById('modalMateria').showModal();
}

function verMateria(btn) {
    const card = btn.closest('.subject-card');
    const d = card.dataset;
    
    const contenido = `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Información General</h4>
            <p><strong>Código:</strong> ${d.codigo}</p>
            <p><strong>Nombre:</strong> ${d.nombre}</p>
            <p><strong>Carreras:</strong> ${d.carreras || 'Sin carreras asignadas'}</p>
            ${d.descripcion ? `<p><strong>Descripción:</strong> ${d.descripcion}</p>` : ''}
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Estadísticas</h4>
            <p><strong>Total de Docentes:</strong> ${d.totalDocentes}</p>
        </div>
        
        <div>
            <h4 style="color: #2647B8; margin-bottom: 10px;">Docentes Asignados</h4>
            <p>${d.docentes || 'Sin docentes asignados'}</p>
        </div>
    `;
    
    document.getElementById('contenidoVerMateria').innerHTML = contenido;
    document.getElementById('modalVerMateria').showModal();
}

function editarMateria(btn) {
    const card = btn.closest('.subject-card');
    const d = card.dataset;
    const materiaId = d.id;

    document.getElementById('edit_materia_id').value = materiaId;
    document.getElementById('edit_codigo').value = d.codigo;
    document.getElementById('edit_nombre').value = d.nombre;
    document.getElementById('edit_descripcion').value = d.descripcion;
    
    // Marcar las carreras asignadas
    const carrerasAsignadas = d.carreras ? d.carreras.split(',').map(c => c.trim()) : [];
    const checkboxesCarreras = document.querySelectorAll('.edit_carrera_check');
    
    checkboxesCarreras.forEach(checkbox => {
        const carreraNombre = checkbox.parentElement.textContent.trim();
        if (carrerasAsignadas.includes(carreraNombre)) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });

    // Marcar los docentes asignados
    const docentesAsignados = d.docentes ? d.docentes.split(',').map(doc => doc.trim()) : [];
    const checkboxesDocentes = document.querySelectorAll('.edit_docente_check');
    
    checkboxesDocentes.forEach(checkbox => {
        const docenteNombre = checkbox.parentElement.textContent.trim();
        if (docentesAsignados.includes(docenteNombre)) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });

    // Marcar las secciones asignadas (usando las asignaciones inyectadas desde PHP)
    const asignaciones = window.asignacionesMaterias[materiaId];
    const seccionesIds = asignaciones ? asignaciones.split(',').map(s => s.trim()) : [];
    const checkboxesSecciones = document.querySelectorAll('.edit_seccion_check');
    
    checkboxesSecciones.forEach(checkbox => {
        if (seccionesIds.includes(checkbox.value)) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });

    document.getElementById('modalEditar').showModal();
}