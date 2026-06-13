// JS/matricula.js

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. FORMATEO AUTOMÁTICO (Modal Nueva Matrícula) ---
    configurarFormateo('mat_dui', 'dui');
    configurarFormateo('mat_telefono', 'tel');
    configurarFormateo('mat_nie', 'nie');
    configurarNombres('mat_nombres');
    configurarNombres('mat_apellidos');
    
    configurarFormateo('resp_dui', 'dui');
    configurarFormateo('resp_telefono', 'tel');
    configurarNombres('resp_nombres');
    configurarNombres('resp_apellidos');

    // --- 2. FORMATEO AUTOMÁTICO (Modal Editar) ---
    configurarFormateo('edit_dui', 'dui');
    configurarFormateo('edit_telefono', 'tel');
    configurarFormateo('edit_nie', 'nie');
    configurarNombres('edit_nombres');
    configurarNombres('edit_apellidos');
    
    configurarFormateo('edit_resp_dui', 'dui');
    configurarFormateo('edit_resp_telefono', 'tel');
    configurarNombres('edit_resp_nombres');
    configurarNombres('edit_resp_apellidos');

    // --- 3. CÁLCULO AUTOMÁTICO DE EDAD ---
    actualizarEdadDesdeFecha('mat_fecha_nacimiento', 'mat_edad');
    actualizarEdadDesdeFecha('edit_fecha_nacimiento', 'edit_edad');

    // --- 4. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX) ---
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar TODO el formulario (ambos pasos)
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
                    
                    if (errorType === 'gmail') msg = '⚠️ Los correos deben ser obligatoriamente @gmail.com';
                    else if (errorType === 'nie_duplicado') msg = '️ El NIE ingresado ya está registrado';
                    else if (errorType === 'dui_duplicado') msg = '⚠️ El DUI ingresado ya está registrado';
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

    // --- 5. FILTROS DE BÚSQUEDA ---
    const inputBuscar = document.getElementById('buscarMatricula');
    const filtroGrado = document.getElementById('filtroGrado');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('#listaMatriculas tr');

    function filtrarMatriculas() {
        const texto = inputBuscar.value.toLowerCase();
        const grado = filtroGrado.value.toLowerCase();
        const estado = filtroEstado.value.toLowerCase();

        filas.forEach(fila => {
            const nie = fila.dataset.nie || '';
            const nombre = fila.dataset.nombre || '';
            const responsable = fila.dataset.responsable || '';
            const gradoFila = fila.dataset.grado || '';
            const estadoFila = fila.dataset.estado || '';

            const coincideBusqueda = nie.toLowerCase().includes(texto) || 
                                     nombre.toLowerCase().includes(texto) || 
                                     responsable.toLowerCase().includes(texto);
            const coincideGrado = !grado || gradoFila.toLowerCase() === grado;
            const coincideEstado = !estado || estadoFila.toLowerCase() === estado;

            if (coincideBusqueda && coincideGrado && coincideEstado) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    inputBuscar.addEventListener('input', filtrarMatriculas);
    filtroGrado.addEventListener('change', filtrarMatriculas);
    filtroEstado.addEventListener('change', filtrarMatriculas);

    // --- 6. MENSAJES DE ÉXITO/ELIMINACIÓN ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('🗑️ Matrícula eliminada exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// --- FUNCIONES DE CÁLCULO DE EDAD ---

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
            
            // Determinar el input de DUI correspondiente
            let duiInput, duiLabel;
            if (inputId === 'mat_fecha_nacimiento') {
                duiInput = document.getElementById('mat_dui');
                duiLabel = document.getElementById('label_mat_dui');
            } else {
                duiInput = document.getElementById('edit_dui');
                duiLabel = document.getElementById('label_edit_dui');
            }
            
            // Si tiene 18 o más, el DUI es obligatorio y está habilitado
            if (edad >= 18) {
                if (duiInput) {
                    duiInput.removeAttribute('disabled');
                    duiInput.setAttribute('required', 'required');
                    duiInput.style.opacity = '1';
                    duiInput.style.cursor = 'text';
                    duiInput.style.backgroundColor = '';
                }
                if (duiLabel) {
                    duiLabel.innerHTML = 'DUI <span style="color: #dc2626;">*</span> (obligatorio):';
                }
            } else {
                // Si es menor de 18, se deshabilita y se limpia
                if (duiInput) {
                    duiInput.setAttribute('disabled', 'disabled');
                    duiInput.removeAttribute('required');
                    duiInput.value = '';
                    duiInput.style.opacity = '0.5';
                    duiInput.style.cursor = 'not-allowed';
                    duiInput.style.backgroundColor = '#f3f4f6';
                }
                if (duiLabel) {
                    duiLabel.innerHTML = 'DUI (no aplica - menor de edad):';
                }
            }
            
            // Validar rango de edad
            if (edad < 14) {
                alert('⚠️ La edad mínima para matrícula es 14 años.');
                e.target.value = '';
                inputEdad.value = '';
                if (duiInput) {
                    duiInput.setAttribute('disabled', 'disabled');
                    duiInput.removeAttribute('required');
                    duiInput.value = '';
                    duiInput.style.opacity = '0.5';
                    duiInput.style.cursor = 'not-allowed';
                    duiInput.style.backgroundColor = '#f3f4f6';
                }
                if (duiLabel) {
                    duiLabel.innerHTML = 'DUI (no aplica - menor de edad):';
                }
            } else if (edad > 22) {
                alert('⚠️ La edad máxima para matrícula es 22 años. No somos modalidad flexible.');
                e.target.value = '';
                inputEdad.value = '';
                if (duiInput) {
                    duiInput.setAttribute('disabled', 'disabled');
                    duiInput.removeAttribute('required');
                    duiInput.value = '';
                    duiInput.style.opacity = '0.5';
                    duiInput.style.cursor = 'not-allowed';
                    duiInput.style.backgroundColor = '#f3f4f6';
                }
                if (duiLabel) {
                    duiLabel.innerHTML = 'DUI (no aplica - menor de edad):';
                }
            }
        }
    });
}

// --- FUNCIONES DE FORMATEO ---

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
    
    input.addEventListener('paste', (e) => {
        setTimeout(() => {
            let val = input.value;
            if (tipo === 'dui' || tipo === 'tel' || tipo === 'nie') {
                val = val.replace(/\D/g, '');
                input.value = val;
            }
        }, 0);
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
    // Validar NIE (máximo 10 dígitos, mínimo 1)
    const nieInput = form.querySelector('[name="nie"]');
    if (nieInput) {
        const nie = nieInput.value.trim();
        if (nie.length === 0 || nie.length > 10 || !/^\d+$/.test(nie)) {
            alert('⚠️ El NIE debe contener entre 1 y 10 dígitos numéricos.');
            return false;
        }
    }

    // Validar nombres (mínimo 2)
    const nombresInput = form.querySelector('[name="nombres"]');
    if (nombresInput) {
        const nombres = nombresInput.value.trim().split(/\s+/);
        if (nombres.length < 2) {
            alert('⚠️ Debe ingresar al menos dos nombres.');
            return false;
        }
    }

    // Validar apellidos (mínimo 2)
    const apellidosInput = form.querySelector('[name="apellidos"]');
    if (apellidosInput) {
        const apellidos = apellidosInput.value.trim().split(/\s+/);
        if (apellidos.length < 2) {
            alert('⚠️ Debe ingresar al menos dos apellidos.');
            return false;
        }
    }

    // Validar edad (calculada automáticamente desde fecha de nacimiento)
    const edadInput = form.querySelector('[name="edad"]');
    if (edadInput) {
        const edad = parseInt(edadInput.value);
        
        if (isNaN(edad) || edad < 14 || edad > 22) {
            alert('⚠️ La edad debe estar entre 14 y 22 años. No somos modalidad flexible.');
            return false;
        }
    }

    // Validar fecha de nacimiento (considerando meses y días)
    const fechaNacInput = form.querySelector('[name="fecha_nacimiento"]');
    if (fechaNacInput && fechaNacInput.value) {
        const edadCalculada = calcularEdad(fechaNacInput.value);
        
        if (edadCalculada < 14) {
            alert('️ El estudiante debe tener al menos 14 años.');
            return false;
        }
        if (edadCalculada > 22) {
            alert('⚠️ El estudiante no puede tener más de 22 años. No somos modalidad flexible.');
            return false;
        }
    }

    // Validar DUI (obligatorio si tiene 18+ años)
    const duiInput = form.querySelector('[name="dui"]');
    if (edadInput && duiInput) {
        const edad = parseInt(edadInput.value);
        const dui = duiInput.value.trim();
        
        if (edad >= 18 && dui === '') {
            alert('⚠️ El DUI es obligatorio para personas mayores de 18 años.');
            duiInput.focus();
            return false;
        }
        
        // Validar formato del DUI si está lleno
        if (dui !== '' && !/^\d{8}-\d{1}$/.test(dui)) {
            alert('⚠️ El DUI debe tener el formato: 12345678-9');
            return false;
        }
    }

    // Si solo es validación del Paso 1, no validar los campos del Paso 2 (responsable)
    if (soloPaso1) {
        return true;
    }

    // Validar correos Gmail
    const emailInputs = form.querySelectorAll('[name="email"], [name="responsable_email"]');
    const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    
    for (let input of emailInputs) {
        const email = input.value.trim();
        if (email && !emailRegex.test(email)) {
            alert('⚠️ Los correos deben ser obligatoriamente @gmail.com');
            return false;
        }
    }

    // Validar nombres del responsable (mínimo 2)
    const respNombresInput = form.querySelector('[name="responsable_nombres"]');
    if (respNombresInput) {
        const respNombres = respNombresInput.value.trim().split(/\s+/);
        if (respNombres.length < 2) {
            alert('⚠️ Los nombres del responsable deben incluir al menos dos nombres.');
            return false;
        }
    }

    // Validar apellidos del responsable (mínimo 2)
    const respApellidosInput = form.querySelector('[name="responsable_apellidos"]');
    if (respApellidosInput) {
        const respApellidos = respApellidosInput.value.trim().split(/\s+/);
        if (respApellidos.length < 2) {
            alert('⚠️ Los apellidos del responsable deben incluir al menos dos apellidos.');
            return false;
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
    if (paso === 2) {
        const form = document.getElementById('formEditar');
        if (!validarFormularioMatricula(form, true)) return;
    }
    
    document.getElementById('edit_paso1').style.display = paso === 1 ? 'block' : 'none';
    document.getElementById('edit_paso2').style.display = paso === 2 ? 'block' : 'none';
}

// --- FUNCIONES DE MODALES ---

function abrirModalNuevaMatricula() {
    document.getElementById('formMatricula').reset();
    
    // Deshabilitar DUI al inicio (aún no hay edad)
    const duiInput = document.getElementById('mat_dui');
    const duiLabel = document.getElementById('label_mat_dui');
    if (duiInput) {
        duiInput.setAttribute('disabled', 'disabled');
        duiInput.removeAttribute('required');
        duiInput.value = '';
        duiInput.style.opacity = '0.5';
        duiInput.style.cursor = 'not-allowed';
        duiInput.style.backgroundColor = '#f3f4f6';
    }
    if (duiLabel) {
        duiLabel.innerHTML = 'DUI (no aplica - menor de edad):';
    }
    
    mostrarPaso(1);
    document.getElementById('modalMatricula').showModal();
}

function verMatricula(btn) {
    const d = btn.closest('tr').dataset;
    
    const contenido = `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Datos del Estudiante</h4>
            <p><strong>NIE:</strong> ${d.nie}</p>
            <p><strong>Nombre:</strong> ${d.nombre}</p>
            <p><strong>DUI:</strong> ${d.estDui || 'No registrado'}</p>
            <p><strong>Edad:</strong> ${d.estEdad} años</p>
            <p><strong>Fecha de Nacimiento:</strong> ${d.estFechaNac}</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Datos Académicos</h4>
            <p><strong>Grado:</strong> ${d.grado}</p>
            <p><strong>Carrera:</strong> ${d.carrera}</p>
            <p><strong>Sección:</strong> ${d.seccion}</p>
            <p><strong>Estado:</strong> <span class="badge ${d.estado === 'Activo' ? 'active' : 'inactive'}">${d.estado}</span></p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="color: #2647B8; margin-bottom: 10px;">Contacto del Estudiante</h4>
            <p><strong>Teléfono:</strong> ${d.estTelefono || 'No registrado'}</p>
            <p><strong>Dirección:</strong> ${d.estDireccion || 'No registrada'}</p>
            <p><strong>Email:</strong> ${d.estEmail || 'No registrado'}</p>
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
    document.getElementById('edit_nie').value = d.nie;
    document.getElementById('edit_nombres').value = d.nombre.split(' ').slice(0, 2).join(' ');
    document.getElementById('edit_apellidos').value = d.nombre.split(' ').slice(2).join(' ');
    document.getElementById('edit_dui').value = d.estDui || '';
    document.getElementById('edit_edad').value = d.estEdad || '';
    document.getElementById('edit_fecha_nacimiento').value = d.estFechaNac || '';
    document.getElementById('edit_telefono').value = d.estTelefono || '';
    document.getElementById('edit_direccion').value = d.estDireccion || '';
    document.getElementById('edit_email').value = d.estEmail || '';
    
    document.getElementById('edit_carrera').value = d.carreraId || '';
    document.getElementById('edit_grado').value = d.gradoId || '';
    document.getElementById('edit_seccion').value = d.seccionId || '';
    document.getElementById('edit_estado').value = d.estado || 'Activo';
    
    document.getElementById('edit_resp_dui').value = d.respDui || '';
    document.getElementById('edit_resp_nombres').value = d.respNombres || '';
    document.getElementById('edit_resp_apellidos').value = d.respApellidos || '';
    document.getElementById('edit_resp_ocupacion').value = d.respOcupacion || '';
    document.getElementById('edit_resp_parentesco').value = d.respParentesco || '';
    document.getElementById('edit_resp_email').value = d.respEmail || '';
    document.getElementById('edit_resp_telefono').value = d.respTelefono || '';
    document.getElementById('edit_resp_direccion').value = d.respDireccion || '';

    // Configurar estado del DUI según la edad
    const edadEdit = parseInt(d.estEdad);
    const editDuiInput = document.getElementById('edit_dui');
    const editDuiLabel = document.getElementById('label_edit_dui');
    
    if (editDuiInput && editDuiLabel) {
        if (!isNaN(edadEdit) && edadEdit >= 18) {
            editDuiInput.removeAttribute('disabled');
            editDuiInput.setAttribute('required', 'required');
            editDuiInput.style.opacity = '1';
            editDuiInput.style.cursor = 'text';
            editDuiInput.style.backgroundColor = '';
            editDuiLabel.innerHTML = 'DUI <span style="color: #dc2626;">*</span> (obligatorio):';
        } else {
            editDuiInput.setAttribute('disabled', 'disabled');
            editDuiInput.removeAttribute('required');
            editDuiInput.value = '';
            editDuiInput.style.opacity = '0.5';
            editDuiInput.style.cursor = 'not-allowed';
            editDuiInput.style.backgroundColor = '#f3f4f6';
            editDuiLabel.innerHTML = 'DUI (no aplica - menor de edad):';
        }
    }

    mostrarPasoEditar(1);
    document.getElementById('modalEditar').showModal();
}