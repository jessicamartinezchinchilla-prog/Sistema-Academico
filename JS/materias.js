document.addEventListener('DOMContentLoaded', () => {
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
                    alert('⚠️ Error al procesar la solicitud');
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Operación realizada con éxito');
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de conexión');
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const type = urlParams.get('success');
        if (type === '1') alert('✅ Materia agregada');
        else if (type === 'editado') alert('✅ Materia actualizada');
        else if (type === 'eliminado') alert('🗑️ Materia eliminada');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function validarFormularioMateria(form) {
    const codigo = form.querySelector('[name="codigo"]').value.trim();
    const nombre = form.querySelector('[name="nombre"]').value.trim();
    const secciones = form.querySelectorAll('input[name="secciones[]"]:checked');
    // Ya no validamos profesores (es opcional)
    
    if (!codigo || !nombre) {
        alert('⚠️ Código y nombre son obligatorios');
        return false;
    }
    if (secciones.length === 0) {
        alert('⚠️ Selecciona al menos una sección');
        return false;
    }
    // Eliminada la validación de profesores
    return true;
}

function abrirModalAgregar() {
    document.getElementById('formMateria').reset();
    document.getElementById('modalMateria').showModal();
}

function verMateria(btn) {
    const d = btn.closest('.subject-card').dataset;
    document.getElementById('contenidoVerMateria').innerHTML = `
        <p><strong>Código:</strong> ${d.codigo}</p>
        <p><strong>Nombre:</strong> ${d.nombre}</p>
        <p><strong>Secciones:</strong> ${d.secciones}</p>
        <p><strong>Docentes:</strong> ${d.profesores}</p>
        <p><strong>Descripción:</strong> ${d.descripcion || 'Sin descripción'}</p>
    `;
    document.getElementById('modalVer').showModal();
}

function editarMateria(btn) {
    const d = btn.closest('.subject-card').dataset;
    document.getElementById('edit_id').value = d.id;
    document.getElementById('edit_codigo').value = d.codigo;
    document.getElementById('edit_nombre').value = d.nombre;
    document.getElementById('edit_descripcion').value = d.descripcion;

    // Marcar checkboxes
    const seccionesAsignadas = d.secciones ? d.secciones.split(',').map(s => s.trim()) : [];
    document.querySelectorAll('.edit_seccion_check').forEach(cb => {
        const texto = cb.parentElement.textContent.trim();
        cb.checked = seccionesAsignadas.some(s => texto.includes(s));
    });

    const profesoresAsignados = d.profesores ? d.profesores.split(',').map(p => p.trim()) : [];
    document.querySelectorAll('.edit_profesor_check').forEach(cb => {
        const texto = cb.parentElement.textContent.trim();
        cb.checked = profesoresAsignados.some(p => texto.includes(p));
    });

    document.getElementById('modalEditar').showModal();
}

function eliminarMateria(id) {
    if (confirm('¿Seguro que deseas eliminar esta materia?')) {
        window.location.href = `../actions/materias_action.php?accion=eliminar&id=${id}`;
    }
}