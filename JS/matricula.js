// JS/matricula.js

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. FORMATEO AUTOMÁTICO ---
    configurarFormateo('mat_dui', 'dui');
    configurarFormateo('mat_telefono', 'tel');
    configurarFormateo('mat_nie', 'nie');
    configurarNombres('mat_nombres');
    configurarNombres('mat_apellidos');
    
    configurarFormateo('resp_dui', 'dui');
    configurarFormateo('resp_telefono', 'tel');
    configurarNombres('resp_nombres');
    configurarNombres('resp_apellidos');
    
    configurarFormateo('edit_resp_dui', 'dui');
    configurarFormateo('edit_resp_telefono', 'tel');
    configurarNombres('edit_resp_nombres');
    configurarNombres('edit_resp_apellidos');

    // --- 2. CÁLCULO AUTOMÁTICO DE EDAD ---
    actualizarEdadDesdeFecha('mat_fecha_nacimiento', 'mat_edad');

    // --- 3. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX) ---
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validarFormularioMatricula(this, false)) return;

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
                    
                    if (errorType === 'gmail') msg = '⚠️ El correo debe ser @gmail.com';
                    else if (errorType === 'nie_duplicado') msg = '⚠️ El NIE ya está registrado';
                    else if (errorType === 'dui_duplicado') msg = '⚠️ El DUI del responsable ya está registrado';
                    else if (errorType === 'sin_estudiante') msg = '⚠️ Debes seleccionar un estudiante';
                    else if (errorType === 'bd') msg = '⚠️ Error en la base de datos';
                    
                    alert(msg);
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Matrícula guardada exitosamente');
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de conexión con el servidor');
            }
        });
    });

    // --- 4. FILTROS DE BÚSQUEDA ---
    const inputBuscar = document.getElementById('buscarMatricula');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('#listaMatriculas tr');

    function filtrarMatriculas() {
        const texto = inputBuscar.value.toLowerCase();
        const seccion = filtroSeccion.value.toLowerCase();
        const estado = filtroEstado.value.toLowerCase();

        filas.forEach(fila => {
            const nie = fila.dataset.nie || '';
            const nombre = fila.dataset.nombre || '';
            const responsable = fila.dataset.responsable || '';
            const seccionFila = fila.dataset.seccion || '';
            const estadoFila = fila.dataset.estado || '';

            const coincideBusqueda = nie.toLowerCase().includes(texto) || 
                                     nombre.toLowerCase().includes(texto) || 
                                     responsable.toLowerCase().includes(texto);
            const coincideSeccion = !seccion || seccionFila.toLowerCase() === seccion;
            const coincideEstado = !estado || estadoFila.toLowerCase() === estado;

            if (coincideBusqueda && coincideSeccion && coincideEstado) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    inputBuscar.addEventListener('input', filtrarMatriculas);
    filtroSeccion.addEventListener('change', filtrarMatriculas);
    filtroEstado.addEventListener('change', filtrarMatriculas);

    // --- 5. MENSAJES DE ÉXITO/ELIMINACIÓN ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('️ Matrícula eliminada exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// --- TOGGLE TIPO DE ESTUDIANTE ---

function toggleTipoEstudiante() {
    const tipo = document.querySelector('input[name="tipo_estudiante"]:checked').value;
    const bloqueExistente = document.getElementById('bloqueExistente');
    const bloqueNuevo = document.getElementById('bloqueNuevo');
    const labelExistente = document.getElementById('labelExistente');
    const labelNuevo = document.getElementById('labelNuevo');
    
    if (tipo === 'existente') {
        bloqueExistente.style.display = 'block';
        bloqueNuevo.style.display = 'none';
        labelExistente.style.borderColor = '#2563eb';
        labelExistente.style.background = '#eff6ff';
        labelNuevo.style.borderColor = '#d1d5db';
        labelNuevo.style.background = 'white';
    } else {
        bloqueExistente.style.display = 'none';
        bloqueNuevo.style.display = 'block';
        labelNuevo.style.borderColor = '#2563eb';
        labelNuevo.style.background = '#eff6ff';
        labelExistente.style.borderColor = '#d1d5db';
        labelExistente.style.background = 'white';
    }
}

// --- FUNCIONES DE FORMATEO ---

function calcularEdad(fechaNacimiento) {
    if (!fechaNacimiento) return null;
    
    const hoy = new Date();
    const fechaNac = new Date(fechaNacimiento + 'T00:00:00');
    
    let edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mesDiff = hoy.getMonth() - fechaNac.getMonth();
    
    if (mesDiff < 0 || (mesDiff === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
    }
    
    return edad;
}

function actualizarEdadDesdeFecha(inputId, edadId) {
    const inputFecha = document.getElementById(inputId);
    const inputEdad = document.getElementById(edadId);
    
    if (!inputFecha || !inputEdad) return;
    
    inputFecha.addEventListener('change', (e) => {
        const fechaNac = e.target.value;
        const edad = calcularEdad(fechaNac);
        
        if (edad !== null) {
            inputEdad.value = edad;
            
            const duiInput = document.getElementById('mat_dui');
            const duiLabel = document.getElementById('label_mat_dui');
            
            if (edad >= 18) {
                if (duiInput) {
                    duiInput.removeAttribute('disabled');
                    duiInput.setAttribute('required', 'required');
                    duiInput.style.opacity = '1';
                    duiInput.style.cursor = 'text';
                    duiInput.style.backgroundColor = '';
                }
            } else {
                if (duiInput) {
                    duiInput.setAttribute('disabled', 'disabled');
                    duiInput.removeAttribute('required');
                    duiInput.value = '';
                    duiInput.style.opacity = '0.5';
                    duiInput.style.cursor = 'not-allowed';
                    duiInput.style.backgroundColor = '#f3f4f6';
                }
            }
            
            if (edad < 14) {
                alert('⚠️ La edad mínima para matrícula es 14 años.');
                e.target.value = '';
                inputEdad.value = '';
            } else if (edad > 22) {
                alert('⚠️ La edad máxima para matrícula es 22 años.');
                e.target.value = '';
                inputEdad.value = '';
            }
        }
    });
}

function configurarFormateo(idInput, tipo) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        let val = e.target.value;
        
        if (tipo === 'dui') {
            val = val.replace(/\D/g, '');
            if (val.length > 8) {
                val = val.slice(0, 8) + '-' + val.slice(8, 9);
            }
        } else if (tipo === 'tel') {
            val = val.replace(/\D/g, '');
            if (val.length > 4) {
                val = val.slice(0, 4) + '-' + val.slice(4, 8);
            }
        } else if (tipo === 'nie') {
            val = val.replace(/\D/g, '').slice(0, 10);
        }
        
        e.target.value = val;
    });
}

function configurarNombres(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    });

    input.addEventListener('blur', (e) => {
        const palabras = e.target.value.trim().split(/\s+/);
        const excepciones = ['de', 'la', 'las', 'los', 'y', 'del', 'van', 'von'];
        
        const formateado = palabras.map((p, index) => {
            if (p.length === 0) return p;
            if (index === 0) {
                return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
            }
            if (excepciones.includes(p.toLowerCase())) {
                return p.toLowerCase();
            }
            return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
        }).join(' ');
        
        e.target.value = formateado;
    });
}

// --- VALIDACIÓN DEL FORMULARIO ---

function validarFormularioMatricula(form, soloPaso1 = false) {
    const tipoEstudiante = form.querySelector('[name="tipo_estudiante"]')?.value;
    
    // Validar según tipo de estudiante
    if (tipoEstudiante === 'existente') {
        const estudiante = form.querySelector('[name="id_estudiante_existente"]')?.value;
        if (!estudiante) {
            alert('⚠️ Debes seleccionar un estudiante existente');
            return false;
        }
    } else if (tipoEstudiante === 'nuevo') {
        // Validar NIE
        const nieInput = form.querySelector('[name="nie"]');
        if (nieInput) {
            const nie = nieInput.value.trim();
            if (nie.length === 0 || nie.length > 10 || !/^\d+$/.test(nie)) {
                alert('⚠️ El NIE debe contener entre 1 y 10 dígitos numéricos.');
                return false;
            }
        }

        // Validar nombres
        const nombresInput = form.querySelector('[name="nombres"]');
        if (nombresInput) {
            const nombres = nombresInput.value.trim().split(/\s+/);
            if (nombres.length < 2) {
                alert('️ Debe ingresar al menos dos nombres.');
                return false;
            }
        }

        // Validar apellidos
        const apellidosInput = form.querySelector('[name="apellidos"]');
        if (apellidosInput) {
            const apellidos = apellidosInput.value.trim().split(/\s+/);
            if (apellidos.length < 2) {
                alert('⚠️ Debe ingresar al menos dos apellidos.');
                return false;
            }
        }

        // Validar edad
        const edadInput = form.querySelector('[name="edad"]');
        if (edadInput) {
            const edad = parseInt(edadInput.value);
            if (isNaN(edad) || edad < 14 || edad > 22) {
                alert('⚠️ La edad debe estar entre 14 y 22 años.');
                return false;
            }
        }
    }

    if (!soloPaso1) {
        // Validar sección
        const seccion = form.querySelector('[name="id_seccion"]')?.value;
        if (!seccion) {
            alert('⚠️ Debes seleccionar una sección');
            return false;
        }

        // Validar correos Gmail
        const emailInputs = form.querySelectorAll('[name="responsable_email"]');
        const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
        
        for (let input of emailInputs) {
            const email = input.value.trim();
            if (email && !emailRegex.test(email)) {
                alert('⚠️ El correo debe ser @gmail.com');
                return false;
            }
        }

        // Validar nombres del responsable
        const respNombresInput = form.querySelector('[name="responsable_nombres"]');
        if (respNombresInput) {
            const respNombres = respNombresInput.value.trim().split(/\s+/);
            if (respNombres.length < 2) {
                alert('⚠️ Los nombres del responsable deben incluir al menos dos nombres.');
                return false;
            }
        }

        // Validar apellidos del responsable
        const respApellidosInput = form.querySelector('[name="responsable_apellidos"]');
        if (respApellidosInput) {
            const respApellidos = respApellidosInput.value.trim().split(/\s+/);
            if (respApellidos.length < 2) {
                alert('⚠️ Los apellidos del responsable deben incluir al menos dos apellidos.');
                return false;
            }
        }
    }

    return true;
}

// --- FUNCIONES DE PASOS DEL FORMULARIO ---

function mostrarPaso(paso) {
    if (paso === 2) {
        const form = document.getElementById('formMatricula');
        if (!validarFormularioMatricula(form, true)) return;
    }
    
    document.getElementById('paso1').style.display = paso === 1 ? 'block' : 'none';
    document.getElementById('paso2').style.display = paso === 2 ? 'block' : 'none';
}

function mostrarPasoEditar(paso) {
    document.getElementById('edit_paso1').style.display = paso === 1 ? 'block' : 'none';
    document.getElementById('edit_paso2').style.display = paso === 2 ? 'block' : 'none';
}

// --- FUNCIONES DE MODALES ---

function abrirModalNuevaMatricula() {
    document.getElementById('formMatricula').reset();
    toggleTipoEstudiante();
    mostrarPaso(1);
    document.getElementById('modalMatricula').showModal();
}

function verMatricula(btn) {
    const d = btn.closest('tr').dataset;
    
    const contenido = `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Datos de la Matrícula</h4>
            <p><strong>NIE:</strong> ${d.nie}</p>
            <p><strong>Nombre:</strong> ${d.nombre}</p>
            <p><strong>Sección:</strong> ${d.seccion}</p>
            <p><strong>Estado:</strong> <span class="badge ${d.estado === 'Activo' ? 'active' : 'inactive'}">${d.estado}</span></p>
        </div>
        
        <div>
            <h4 style="color: #2647B8; margin-bottom: 10px;">Datos del Responsable</h4>
            <p><strong>Nombre:</strong> ${d.respNombres} ${d.respApellidos}</p>
            <p><strong>DUI:</strong> ${d.respDui || 'No registrado'}</p>
            <p><strong>Ocupación:</strong> ${d.respOcupacion || 'No registrada'}</p>
            <p><strong>Parentesco:</strong> ${d.respParentesco || 'No registrado'}</p>
            <p><strong>Email:</strong> ${d.respEmail || 'No registrado'}</p>
            <p><strong>Teléfono:</strong> ${d.respTelefono || 'No registrado'}</p>
            <p><strong>Dirección:</strong> ${d.respDireccion || 'No registrada'}</p>
        </div>
    `;
    
    document.getElementById('detalleMatricula').innerHTML = contenido;
    document.getElementById('modalVer').showModal();
}

function editarMatricula(btn) {
    const d = btn.closest('tr').dataset;

    document.getElementById('edit_matricula_id').value = d.id;
    document.getElementById('edit_resp_dui').value = d.respDui || '';
    document.getElementById('edit_resp_nombres').value = d.respNombres || '';
    document.getElementById('edit_resp_apellidos').value = d.respApellidos || '';
    document.getElementById('edit_resp_ocupacion').value = d.respOcupacion || '';
    document.getElementById('edit_resp_parentesco').value = d.respParentesco || '';
    document.getElementById('edit_resp_email').value = d.respEmail || '';
    document.getElementById('edit_resp_telefono').value = d.respTelefono || '';
    document.getElementById('edit_resp_direccion').value = d.respDireccion || '';

    mostrarPasoEditar(1);
    document.getElementById('modalEditar').showModal();
}