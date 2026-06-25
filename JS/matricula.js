// JS/matricula.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 0. CAPITALIZACIÓN AUTOMÁTICA DE NOMBRES
    // ==========================================
    configurarCapitalizacion('mat_nombres');
    configurarCapitalizacion('mat_apellidos');
    configurarCapitalizacionNombre('responsable_nombres');
    configurarCapitalizacionNombre('responsable_apellidos');
    configurarCapitalizacionNombre('edit_resp_nombres');
    configurarCapitalizacionNombre('edit_resp_apellidos');

    // ==========================================
    // 1. INTERCEPTAR ENVÍO DE FORMULARIOS (AJAX)
    // ==========================================
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (this.action.includes('generar_pdf.php')) return;
            
            // ✅ PREVENIR DOBLE ENVÍO
            const submitBtn = this.querySelector('button[type="submit"]');
            const textoOriginal = submitBtn ? submitBtn.innerHTML : 'Guardar';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
            }

            const formData = new FormData(this);
            const esFormMatricula = this.id === 'formMatricula' || this.id === 'formEditar';
            
            if (esFormMatricula) {
                if (!validarFormularioMatricula(this, false)) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = textoOriginal;
                    }
                    return;
                }
            }
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.text();
                
                if (result.startsWith('ERROR:')) {
                    const errorType = result.replace('ERROR:', '').trim();
                    let msg = 'Error al procesar la solicitud';
                    
                    switch(errorType) {
                        case 'campos_incompletos': msg = 'Todos los campos obligatorios deben estar llenos'; break;
                        case 'gmail': msg = 'El correo del responsable debe ser @gmail.com'; break;
                        case 'nie_duplicado': msg = 'Ya existe un estudiante con ese NIE'; break;
                        case 'dui_estudiante_duplicado': msg = 'Ya existe un estudiante con ese DUI'; break;
                        case 'dui_responsable_existe_estudiante': msg = 'El DUI del responsable ya está registrado como estudiante'; break;
                        case 'dui_responsable_existe_profesor': msg = 'El DUI del responsable ya está registrado como profesor'; break;
                        case 'dui_estudiante_existe_profesor': msg = 'El DUI del estudiante ya está registrado como profesor'; break;
                        case 'dui_estudiante_existe_responsable': msg = 'El DUI del estudiante ya está registrado como responsable'; break;
                        case 'dui_profesor_existe_estudiante': msg = 'El DUI del profesor ya está registrado como estudiante'; break;
                        case 'dui_profesor_existe_responsable': msg = 'El DUI del profesor ya está registrado como responsable'; break;
                        case 'dui_duplicado': msg = 'Este DUI ya está registrado en el sistema'; break;
                        case 'telefono_estudiante_duplicado': msg = 'Ya existe un estudiante con ese número de teléfono'; break;
                        case 'telefono_existe_responsable': msg = 'El teléfono del estudiante ya está registrado en un responsable'; break;
                        case 'telefono_responsable_duplicado': msg = 'Ya existe un responsable con ese número de teléfono'; break;
                        case 'telefono_existe_estudiante': msg = 'El teléfono del responsable ya está registrado en un estudiante'; break;
                        case 'email_estudiante_duplicado': msg = 'Ya existe un estudiante con ese correo electrónico'; break;
                        case 'email_existe_responsable': msg = 'El correo del estudiante ya está registrado en un responsable'; break;
                        case 'email_responsable_duplicado': msg = 'Ya existe un responsable con ese correo electrónico'; break;
                        case 'email_existe_estudiante': msg = 'El correo del responsable ya está registrado en un estudiante'; break;
                        case 'telefono_invalido': msg = 'El teléfono del estudiante debe tener el formato 0000-0000'; break;
                        case 'email_estudiante_invalido': msg = 'El correo del estudiante debe ser @gmail.com'; break;
                        case 'sin_estudiante': msg = 'Debes seleccionar un estudiante existente'; break;
                        case 'seccion_invalida': msg = 'La sección seleccionada no es válida'; break;
                        case 'matricula_duplicada': msg = 'El estudiante ya está matriculado en esta sección'; break;
                        case 'grado_no_superior': msg = 'La sección seleccionada no es válida. Si el estudiante está repitiendo año, marque la opción correspondiente.'; break;
                        case 'grado_diferente_editar': msg = 'Solo puedes cambiar a secciones del mismo año académico.'; break;
                        
                        // ✅ NUEVO CASO: Sección llena
                        default:
                            if (errorType.startsWith('ya_matriculado:')) {
                                const seccionActual = errorType.split(':')[1] || 'otra sección';
                                msg = `El estudiante ya tiene una matrícula activa en: ${seccionActual}. Si desea cambiar de sección, use la opción de editar matrícula.`;
                            } else if (errorType.startsWith('seccion_llena:')) {
                                const limite = errorType.split(':')[1] || 'el límite';
                                msg = `La sección ya alcanzó el límite de ${limite} alumnos. No se pueden matricular más estudiantes.`;
                            } else {
                                msg = errorType;
                            }
                            break;
                    }
                    
                    alert('⚠️ ' + msg);
                    
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = textoOriginal;
                    }
                } else if (result.startsWith('INFO:')) {
                    const infoType = result.replace('INFO:', '').trim();
                    let msg = 'Información';
                    
                    if (infoType === 'sin_cambios') {
                        msg = 'No has realizado ningún cambio en la matrícula';
                    }
                    
                    alert('ℹ️ ' + msg);
                    
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = textoOriginal;
                    }
                } else if (result.startsWith('SUCCESS:')) {
                    alert('✅ Matrícula guardada exitosamente');
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión con el servidor');
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = textoOriginal;
                }
            }
        });
    });

    // ==========================================
    // 2. FILTROS DE BÚSQUEDA
    // ==========================================
    const inputBuscar = document.getElementById('buscarMatricula');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('#listaMatriculas tr');

    function filtrarMatriculas() {
        const texto = inputBuscar ? inputBuscar.value.toLowerCase() : '';
        const seccion = filtroSeccion ? filtroSeccion.value.toLowerCase() : '';
        const estado = filtroEstado ? filtroEstado.value.toLowerCase() : '';

        filas.forEach(fila => {
            if (!fila.dataset.id) return;

            const nie = fila.dataset.nie || '';
            const nombre = fila.dataset.nombre || '';
            const responsable = (fila.dataset.respNombres || '') + ' ' + (fila.dataset.respApellidos || '');
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

    if (inputBuscar) inputBuscar.addEventListener('input', filtrarMatriculas);
    if (filtroSeccion) filtroSeccion.addEventListener('change', filtrarMatriculas);
    if (filtroEstado) filtroEstado.addEventListener('change', filtrarMatriculas);

    // ==========================================
    // 3. MENSAJES DE URL
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') === 'eliminado') {
        alert('️ Matrícula eliminada exitosamente');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // ==========================================
    // 4. CALCULAR EDAD AUTOMÁTICAMENTE
    // ==========================================
    const inputFechaNac = document.getElementById('mat_fecha_nacimiento');
    const inputEdad = document.getElementById('mat_edad');
    const inputDui = document.getElementById('mat_dui');

    function actualizarEdadDesdeFecha() {
        if (!inputFechaNac || !inputFechaNac.value) {
            if (inputEdad) {
                inputEdad.value = '';
                inputEdad.placeholder = 'Cálculo automático';
                inputEdad.removeAttribute('data-edad-calculada');
            }
            if (inputDui) {
                inputDui.setAttribute('disabled', 'disabled');
                inputDui.removeAttribute('required');
                inputDui.value = '';
                inputDui.style.opacity = '0.5';
                inputDui.style.cursor = 'not-allowed';
                inputDui.style.backgroundColor = '#f3f4f6';
                inputDui.placeholder = 'No requerido (menor de 18)';
            }
            return;
        }

        const fechaNac = new Date(inputFechaNac.value);
        const hoy = new Date();
        let edad = hoy.getFullYear() - fechaNac.getFullYear();
        const mes = hoy.getMonth() - fechaNac.getMonth();
        
        if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
            edad--;
        }

        if (edad < 14 || edad > 22) {
            alert('⚠️ La edad debe estar entre 14 y 22 años');
            inputFechaNac.value = '';
            if (inputEdad) {
                inputEdad.value = '';
                inputEdad.removeAttribute('data-edad-calculada');
            }
            return;
        }

        if (inputEdad) {
            inputEdad.value = edad;
            inputEdad.setAttribute('data-edad-calculada', edad);
        }

        if (inputDui) {
            if (edad >= 18) {
                inputDui.removeAttribute('disabled');
                inputDui.setAttribute('required', 'required');
                inputDui.style.opacity = '1';
                inputDui.style.cursor = 'text';
                inputDui.style.backgroundColor = '';
                inputDui.placeholder = '00000000-0';
            } else {
                inputDui.setAttribute('disabled', 'disabled');
                inputDui.removeAttribute('required');
                inputDui.value = '';
                inputDui.style.opacity = '0.5';
                inputDui.style.cursor = 'not-allowed';
                inputDui.style.backgroundColor = '#f3f4f6';
                inputDui.placeholder = 'No requerido (menor de 18)';
            }
        }
    }

    if (inputFechaNac) {
        inputFechaNac.addEventListener('change', actualizarEdadDesdeFecha);
    }

    // ==========================================
    // 5. MOSTRAR/OCULTAR CAMPOS SEGÚN TIPO
    // ==========================================
    const radiosTipo = document.querySelectorAll('[name="tipo_estudiante"]');
    const camposExistente = document.getElementById('campos_estudiante_existente');
    const camposNuevo = document.getElementById('campos_estudiante_nuevo');

    function toggleCamposEstudiante() {
        const tipoSeleccionado = document.querySelector('[name="tipo_estudiante"]:checked');
        if (!tipoSeleccionado) return;

        if (tipoSeleccionado.value === 'existente') {
            if (camposExistente) camposExistente.style.display = 'block';
            if (camposNuevo) camposNuevo.style.display = 'none';
        } else if (tipoSeleccionado.value === 'nuevo') {
            if (camposExistente) camposExistente.style.display = 'none';
            if (camposNuevo) camposNuevo.style.display = 'block';
        }
    }

    window.toggleCamposEstudiante = toggleCamposEstudiante;

    radiosTipo.forEach(radio => {
        radio.addEventListener('change', toggleCamposEstudiante);
    });

    toggleCamposEstudiante();

    // ==========================================
    // 6. FORMATEAR DUI AUTOMÁTICAMENTE
    // ==========================================
    const duiInput = document.getElementById('mat_dui');
    if (duiInput) {
        duiInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.substring(0, 9);
            if (valor.length > 8) {
                valor = valor.substring(0, 8) + '-' + valor.substring(8, 9);
            }
            e.target.value = valor;
        });
    }

    // ==========================================
    // 7. FORMATEAR TELÉFONO AUTOMÁTICAMENTE
    // ==========================================
    const telInput = document.getElementById('mat_telefono');
    if (telInput) {
        telInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.substring(0, 8);
            if (valor.length > 4) {
                valor = valor.substring(0, 4) + '-' + valor.substring(4, 8);
            }
            e.target.value = valor;
        });
    }

    const respTelInput = document.querySelector('[name="responsable_telefono"]');
    if (respTelInput) {
        respTelInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.substring(0, 8);
            if (valor.length > 4) {
                valor = valor.substring(0, 4) + '-' + valor.substring(4, 8);
            }
            e.target.value = valor;
        });
    }

    const respDuiInput = document.querySelector('[name="responsable_dui"]');
    if (respDuiInput) {
        respDuiInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.substring(0, 9);
            if (valor.length > 8) {
                valor = valor.substring(0, 8) + '-' + valor.substring(8, 9);
            }
            e.target.value = valor;
        });
    }

    // ==========================================
    // 8. BUSCAR RESPONSABLE POR DUI
    // ==========================================
    const buscarResponsableDuiInput = document.querySelector('[name="responsable_dui"]');
    if (buscarResponsableDuiInput) {
        let debounceTimer;
        
        buscarResponsableDuiInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            const dui = e.target.value.trim();
            
            if (dui.length === 10 && /^\d{8}-\d$/.test(dui)) {
                debounceTimer = setTimeout(async () => {
                    try {
                        const response = await fetch(`../actions/buscar_responsable.php?dui=${encodeURIComponent(dui)}`);
                        const data = await response.json();
                        
                        if (data.encontrado) {
                            document.querySelector('[name="responsable_nombres"]').value = data.datos.nombres || '';
                            document.querySelector('[name="responsable_apellidos"]').value = data.datos.apellidos || '';
                            document.querySelector('[name="responsable_ocupacion"]').value = data.datos.ocupacion || '';
                            document.querySelector('[name="responsable_parentesco"]').value = data.datos.parentesco || '';
                            document.querySelector('[name="responsable_email"]').value = data.datos.email || '';
                            document.querySelector('[name="responsable_telefono"]').value = data.datos.telefono || '';
                            document.querySelector('[name="responsable_direccion"]').value = data.datos.direccion || '';
                            
                            e.target.style.borderColor = '#16a34a';
                            e.target.style.boxShadow = '0 0 5px rgba(22, 163, 74, 0.5)';
                            
                            setTimeout(() => {
                                alert('✅ Responsable encontrado. Campos auto-completados.');
                                e.target.style.borderColor = '';
                                e.target.style.boxShadow = '';
                            }, 300);
                        } else {
                            e.target.style.borderColor = '#9ca3af';
                        }
                    } catch (error) {
                        console.error('Error buscando responsable:', error);
                    }
                }, 500);
            }
        });
    }

    // ==========================================
    // 9. CAMBIO DE ESTUDIANTE EXISTENTE (CON FILTRO DE GRADO)
    // ==========================================
    const selectEstudianteExistente = document.getElementById('select_estudiante_existente');
    const checkboxRepite = document.getElementById('checkbox_repite');
    const contenedorCheckboxRepite = document.getElementById('contenedor_checkbox_repite');
    const infoGradoActual = document.getElementById('info_grado_actual');
    const selectSeccionAgregar = document.querySelector('#paso1 select[name="id_seccion"]');

    let ultimoGradoEstudiante = null;

    if (selectEstudianteExistente) {
        selectEstudianteExistente.addEventListener('change', async function() {
            const idEstudiante = this.value;
            
            if (contenedorCheckboxRepite) contenedorCheckboxRepite.style.display = 'none';
            if (checkboxRepite) {
                checkboxRepite.checked = false;
                checkboxRepite.disabled = true;
            }
            if (infoGradoActual) infoGradoActual.textContent = '';
            ultimoGradoEstudiante = null;
            
            if (!idEstudiante) return;
            
            try {
                const responseGrado = await fetch(`../actions/obtener_ultimo_grado.php?id_estudiante=${idEstudiante}`);
                const dataGrado = await responseGrado.json();
                
                if (dataGrado.encontrado) {
                    ultimoGradoEstudiante = dataGrado.id_grado;
                    
                    if (contenedorCheckboxRepite) contenedorCheckboxRepite.style.display = 'block';
                    if (checkboxRepite) checkboxRepite.disabled = false;
                    
                    if (infoGradoActual) {
                        infoGradoActual.textContent = ` Grado actual del estudiante: ${dataGrado.nombre_grado}`;
                    }
                    
                    await actualizarSeccionesAgregar(false);
                } else {
                    await actualizarSeccionesAgregar(true);
                }
                
                const responseResp = await fetch(`../actions/buscar_responsable_por_estudiante.php?id_estudiante=${idEstudiante}`);
                const dataResp = await responseResp.json();
                
                if (dataResp.encontrado) {
                    document.querySelector('[name="responsable_nombres"]').value = dataResp.datos.nombres || '';
                    document.querySelector('[name="responsable_apellidos"]').value = dataResp.datos.apellidos || '';
                    document.querySelector('[name="responsable_ocupacion"]').value = dataResp.datos.ocupacion || '';
                    document.querySelector('[name="responsable_parentesco"]').value = dataResp.datos.parentesco || '';
                    document.querySelector('[name="responsable_email"]').value = dataResp.datos.email || '';
                    document.querySelector('[name="responsable_telefono"]').value = dataResp.datos.telefono || '';
                    document.querySelector('[name="responsable_dui"]').value = dataResp.datos.dui || '';
                    document.querySelector('[name="responsable_direccion"]').value = dataResp.datos.direccion || '';
                }
            } catch (error) {
                console.error('Error buscando datos:', error);
            }
        });
    }

    async function actualizarSeccionesAgregar(mostrarTodas) {
        if (!selectSeccionAgregar) return;
        
        const seleccionActual = selectSeccionAgregar.value;
        selectSeccionAgregar.innerHTML = '<option value="">-- Seleccione una sección --</option>';
        
        try {
            let url;
            
            if (ultimoGradoEstudiante !== null) {
                if (mostrarTodas) {
                    url = `../actions/obtener_secciones_filtradas.php?id_grado=${ultimoGradoEstudiante}&mostrar_todas=0`;
                } else {
                    const gradoSuperior = ultimoGradoEstudiante + 1;
                    url = `../actions/obtener_secciones_filtradas.php?id_grado=${gradoSuperior}&mostrar_todas=0`;
                }
            } else {
                url = '../actions/obtener_secciones_filtradas.php?mostrar_todas=1';
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.secciones && data.secciones.length > 0) {
                data.secciones.forEach(seccion => {
                    const option = document.createElement('option');
                    option.value = seccion.id;
                    option.textContent = seccion.nombre;
                    selectSeccionAgregar.appendChild(option);
                });
                
                if (seleccionActual) {
                    const optionExiste = selectSeccionAgregar.querySelector(`option[value="${seleccionActual}"]`);
                    if (optionExiste) {
                        selectSeccionAgregar.value = seleccionActual;
                    }
                }
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = mostrarTodas ? 'No hay secciones del mismo año disponibles' : 'No hay secciones de año superior disponibles';
                option.disabled = true;
                selectSeccionAgregar.appendChild(option);
            }
        } catch (error) {
            console.error('Error cargando secciones:', error);
        }
    }

    if (checkboxRepite) {
        checkboxRepite.addEventListener('change', async function() {
            const mostrarTodas = this.checked;
            await actualizarSeccionesAgregar(mostrarTodas);
        });
    }

    // ==========================================
    // 10. NAVEGACIÓN ENTRE PASOS
    // ==========================================
    function siguientePaso() {
        const form = document.getElementById('formMatricula');
        if (!validarFormularioMatricula(form, true)) return;
        
        document.getElementById('paso1').style.display = 'none';
        document.getElementById('paso2').style.display = 'block';
        document.getElementById('modalMatricula').scrollTop = 0;
    }
    
    function pasoAnterior() {
        document.getElementById('paso2').style.display = 'none';
        document.getElementById('paso1').style.display = 'block';
        document.getElementById('modalMatricula').scrollTop = 0;
    }
    
    function mostrarPasoEditar(paso) {
        if (paso === 1) {
            document.getElementById('edit_paso1').style.display = 'block';
            document.getElementById('edit_paso2').style.display = 'none';
        } else if (paso === 2) {
            document.getElementById('edit_paso1').style.display = 'none';
            document.getElementById('edit_paso2').style.display = 'block';
        }
        document.getElementById('modalEditar').scrollTop = 0;
    }
    
    window.siguientePaso = siguientePaso;
    window.pasoAnterior = pasoAnterior;
    window.mostrarPasoEditar = mostrarPasoEditar;

    // ==========================================
    // 11. FORMATEAR CAMPOS DE CONTACTO EN EDICIÓN
    // ==========================================
    const editDuiInput = document.getElementById('edit_est_dui');
    if (editDuiInput) {
        editDuiInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.substring(0, 9);
            if (valor.length > 8) {
                valor = valor.substring(0, 8) + '-' + valor.substring(8, 9);
            }
            e.target.value = valor;
        });
    }

    const editTelInput = document.getElementById('edit_est_telefono');
    if (editTelInput) {
        editTelInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.substring(0, 8);
            if (valor.length > 4) {
                valor = valor.substring(0, 4) + '-' + valor.substring(4, 8);
            }
            e.target.value = valor;
        });
    }
});

// ==========================================
// FUNCIONES DE CAPITALIZACIÓN (GLOBALES)
// ==========================================

function configurarCapitalizacion(idInput) {
    const input = document.getElementById(idInput);
    if (!input) return;

    input.addEventListener('input', (e) => {
        const valor = e.target.value;
        const cursorPos = e.target.selectionStart;
        
        const palabras = valor.split(' ');
        const excepciones = ['de', 'la', 'las', 'los', 'y', 'del', 'van', 'von'];
        
        const formateado = palabras.map((p, index) => {
            if (p.length === 0) return p;
            if (index === 0) return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
            if (excepciones.includes(p.toLowerCase())) return p.toLowerCase();
            return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
        }).join(' ');
        
        if (valor !== formateado) {
            e.target.value = formateado;
            e.target.setSelectionRange(cursorPos, cursorPos);
        }
    });
}

function configurarCapitalizacionNombre(idInput) {
    const input = document.querySelector(`[name="${idInput}"]`);
    if (!input) return;

    input.addEventListener('input', (e) => {
        const valor = e.target.value;
        const cursorPos = e.target.selectionStart;
        
        const palabras = valor.split(' ');
        const excepciones = ['de', 'la', 'las', 'los', 'y', 'del', 'van', 'von'];
        
        const formateado = palabras.map((p, index) => {
            if (p.length === 0) return p;
            if (index === 0) return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
            if (excepciones.includes(p.toLowerCase())) return p.toLowerCase();
            return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
        }).join(' ');
        
        if (valor !== formateado) {
            e.target.value = formateado;
            e.target.setSelectionRange(cursorPos, cursorPos);
        }
    });
}

// ==========================================
// FUNCIONES DE MODALES (Globales)
// ==========================================

function abrirModalAgregar() {
    const form = document.getElementById('formMatricula');
    if (form) form.reset();
    
    const contenedorCheckboxRepite = document.getElementById('contenedor_checkbox_repite');
    const checkboxRepite = document.getElementById('checkbox_repite');
    const infoGradoActual = document.getElementById('info_grado_actual');
    
    if (contenedorCheckboxRepite) contenedorCheckboxRepite.style.display = 'none';
    if (checkboxRepite) {
        checkboxRepite.checked = false;
        checkboxRepite.disabled = true;
    }
    if (infoGradoActual) infoGradoActual.textContent = '';
    ultimoGradoEstudiante = null;
    
    const inputEdad = document.getElementById('mat_edad');
    const inputDui = document.getElementById('mat_dui');
    const inputFechaNac = document.getElementById('mat_fecha_nacimiento');
    
    if (inputEdad) {
        inputEdad.value = '';
        inputEdad.placeholder = 'Cálculo automático';
        inputEdad.removeAttribute('data-edad-calculada');
    }
    if (inputDui) {
        inputDui.setAttribute('disabled', 'disabled');
        inputDui.removeAttribute('required');
        inputDui.value = '';
        inputDui.style.opacity = '0.5';
        inputDui.style.cursor = 'not-allowed';
        inputDui.style.backgroundColor = '#f3f4f6';
        inputDui.placeholder = 'No requerido (menor de 18)';
    }
    if (inputFechaNac) inputFechaNac.value = '';
    
    const camposExistente = document.getElementById('campos_estudiante_existente');
    const camposNuevo = document.getElementById('campos_estudiante_nuevo');
    if (camposExistente) camposExistente.style.display = 'block';
    if (camposNuevo) camposNuevo.style.display = 'none';
    
    const paso1 = document.getElementById('paso1');
    const paso2 = document.getElementById('paso2');
    if (paso1) paso1.style.display = 'block';
    if (paso2) paso2.style.display = 'none';
    
    document.getElementById('modalMatricula').showModal();
}

function abrirModalEditar(btn) {
    const fila = btn.closest('tr');
    if (!fila) return;

    const d = fila.dataset;
    
    document.getElementById('edit_matricula_id').value = d.id;
    document.getElementById('edit_estudiante').value = d.idEstudiante;
    document.getElementById('edit_id_estudiante_hidden').value = d.idEstudiante;
    document.getElementById('edit_seccion').value = d.idSeccion;
    document.getElementById('edit_estado').value = d.estado || 'activo';
    
    document.getElementById('edit_est_dui').value = d.dui || '';
    document.getElementById('edit_est_telefono').value = d.telefono || '';
    document.getElementById('edit_est_email').value = d.email || '';
    document.getElementById('edit_est_direccion').value = d.direccion || '';
    
    document.getElementById('edit_resp_dui').value = d.respDui || '';
    document.getElementById('edit_resp_nombres').value = d.respNombres || '';
    document.getElementById('edit_resp_apellidos').value = d.respApellidos || '';
    document.getElementById('edit_resp_ocupacion').value = d.respOcupacion || '';
    document.getElementById('edit_resp_parentesco').value = d.respParentesco || '';
    document.getElementById('edit_resp_email').value = d.respEmail || '';
    document.getElementById('edit_resp_telefono').value = d.respTelefono || '';
    document.getElementById('edit_resp_direccion').value = d.respDireccion || '';
    
    document.getElementById('edit_paso1').style.display = 'block';
    document.getElementById('edit_paso2').style.display = 'none';
    
    filtrarSeccionesEditar(d.idSeccion);
    
    document.getElementById('modalEditar').showModal();
}

async function filtrarSeccionesEditar(idSeccionActual) {
    const selectSeccionEditar = document.getElementById('edit_seccion');
    const infoFiltro = document.getElementById('info_filtro_seccion');
    
    if (!selectSeccionEditar) return;
    
    if (infoFiltro) infoFiltro.style.display = 'block';
    
    try {
        const responseGrado = await fetch(`../actions/obtener_grado_seccion.php?id_seccion=${idSeccionActual}`);
        const dataGrado = await responseGrado.json();
        
        if (!dataGrado.encontrado) return;
        
        const idGradoActual = dataGrado.id_grado;
        
        const responseSecciones = await fetch(`../actions/obtener_secciones_filtradas.php?id_grado=${idGradoActual}&mostrar_todas=0`);
        const dataSecciones = await responseSecciones.json();
        
        selectSeccionEditar.innerHTML = '';
        
        if (dataSecciones.secciones && dataSecciones.secciones.length > 0) {
            dataSecciones.secciones.forEach(seccion => {
                const option = document.createElement('option');
                option.value = seccion.id;
                option.textContent = seccion.nombre;
                if (seccion.id == idSeccionActual) {
                    option.selected = true;
                }
                selectSeccionEditar.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error filtrando secciones:', error);
    }
}

function eliminarMatricula(id) {
    if (confirm('¿Estás seguro de eliminar esta matrícula? Se eliminará también al estudiante asociado.')) {
        window.location.href = '../actions/matricula_action.php?accion=eliminar&id=' + id;
    }
}

function verMatricula(btn) {
    const fila = btn.closest('tr');
    if (!fila) return;

    const d = fila.dataset;
    
    const contenido = `
        <div style="padding: 10px;">
            <h4 style="color: #2647B8; margin-bottom: 15px;">Datos del Estudiante</h4>
            <p><strong>NIE:</strong> ${d.nie}</p>
            <p><strong>Nombre:</strong> ${d.nombres} ${d.apellidos}</p>
            <p><strong>Edad:</strong> ${d.edad || 'No registrada'} años</p>
            <p><strong>DUI:</strong> ${d.dui || 'No registrado'}</p>
            <p><strong>Teléfono:</strong> ${d.telefono || 'No registrado'}</p>
            <p><strong>Email:</strong> ${d.email || 'No registrado'}</p>
            <p><strong>Dirección:</strong> ${d.direccion || 'No registrada'}</p>
            <p><strong>Sección:</strong> ${d.seccion}</p>
            <p><strong>Estado:</strong> 
                <span class="${d.estado === 'activo' ? 'estado-aprobado' : 'estado-reprobado'}">
                    ${d.estado === 'activo' ? 'Activo' : 'Inactivo'}
                </span>
            </p>
            
            <hr style="margin: 20px 0;">
            
            <h4 style="color: #2647B8; margin-bottom: 15px;">Datos del Responsable</h4>
            <p><strong>Nombre:</strong> ${d.respNombres || 'No registrado'} ${d.respApellidos || ''}</p>
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

// ==========================================
// VALIDACIÓN DEL FORMULARIO
// ==========================================

function validarFormularioMatricula(form, soloPaso1 = false) {
    if (form.id === 'formEditar') {
        const estudianteEdit = form.querySelector('[name="id_estudiante"]');
        if (estudianteEdit && !estudianteEdit.value) {
            alert('️ Debes seleccionar un estudiante');
            return false;
        }
    }
    const tipoEstudiante = form.querySelector('[name="tipo_estudiante"]:checked')?.value;
    
    if (tipoEstudiante === 'existente') {
        const estudiante = form.querySelector('[name="id_estudiante_existente"]')?.value;
        if (!estudiante) {
            alert('⚠️ Debes seleccionar un estudiante existente');
            return false;
        }
    } else if (tipoEstudiante === 'nuevo') {
        const nieInput = form.querySelector('[name="nie"]');
        if (nieInput) {
            const nie = nieInput.value.trim();
            if (nie.length === 0 || nie.length > 10 || !/^\d+$/.test(nie)) {
                alert('⚠️ El NIE debe contener entre 1 y 10 dígitos numéricos.');
                return false;
            }
        }

        const nombresInput = form.querySelector('[name="nombres"]');
        if (nombresInput) {
            const nombres = nombresInput.value.trim().split(/\s+/);
            if (nombres.length < 2) {
                alert('⚠️ Debe ingresar al menos dos nombres.');
                return false;
            }
        }

        const apellidosInput = form.querySelector('[name="apellidos"]');
        if (apellidosInput) {
            const apellidos = apellidosInput.value.trim().split(/\s+/);
            if (apellidos.length < 2) {
                alert('⚠️ Debe ingresar al menos dos apellidos.');
                return false;
            }
        }

        const edadInput = form.querySelector('[name="edad"]');
        if (edadInput) {
            const edadTexto = edadInput.value.trim();
            const edad = parseInt(edadTexto);
            
            if (isNaN(edad) || edad < 14 || edad > 22) {
                alert('️ La edad debe estar entre 14 y 22 años.');
                return false;
            }
            
            if (edad >= 18) {
                const duiInput = form.querySelector('[name="dui"]');
                if (duiInput) {
                    const dui = duiInput.value.trim();
                    if (!dui || dui.length < 10) {
                        alert('⚠️ Al tener 18 años o más, el DUI es obligatorio (formato: 00000000-0)');
                        return false;
                    }
                }
            }
        }
    }

    if (soloPaso1) {
        const seccion = form.querySelector('[name="id_seccion"]')?.value;
        if (!seccion) {
            alert('⚠️ Debes seleccionar una sección');
            return false;
        }
        return true;
    }

    const esModalEditar = form.id === 'formEditar';
    
    if (!esModalEditar) {
        const respNombresInput = form.querySelector('[name="responsable_nombres"]');
        if (respNombresInput && respNombresInput.value.trim() === '') {
            alert('⚠️ Los nombres del responsable son obligatorios.');
            return false;
        }
        
        const respApellidosInput = form.querySelector('[name="responsable_apellidos"]');
        if (respApellidosInput && respApellidosInput.value.trim() === '') {
            alert('️ Los apellidos del responsable son obligatorios.');
            return false;
        }
        
        const respEmailInput = form.querySelector('[name="responsable_email"]');
        if (respEmailInput && respEmailInput.value.trim() === '') {
            alert('️ El email del responsable es obligatorio.');
            return false;
        }
        
        const respTelefonoInput = form.querySelector('[name="responsable_telefono"]');
        if (respTelefonoInput && respTelefonoInput.value.trim() === '') {
            alert('⚠️ El teléfono del responsable es obligatorio.');
            return false;
        }
    }

    if (esModalEditar) {
        const estEmailInput = form.querySelector('[name="est_email"]');
        if (estEmailInput && estEmailInput.value.trim()) {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
            if (!emailRegex.test(estEmailInput.value.trim())) {
                alert('⚠️ El correo del estudiante debe ser @gmail.com');
                return false;
            }
        }
        
        const estTelefonoInput = form.querySelector('[name="est_telefono"]');
        if (estTelefonoInput && estTelefonoInput.value.trim()) {
            const telefono = estTelefonoInput.value.trim().replace(/-/g, '');
            if (telefono.length !== 8 || !/^\d+$/.test(telefono)) {
                alert('⚠️ El teléfono del estudiante debe tener 8 dígitos (formato: 0000-0000)');
                return false;
            }
        }
    }

    const emailInputs = form.querySelectorAll('[name="responsable_email"]');
    const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    
    for (let input of emailInputs) {
        const email = input.value.trim();
        if (email && !emailRegex.test(email)) {
            alert('️ El correo del responsable debe ser @gmail.com');
            return false;
        }
    }

    const respNombresInput = form.querySelector('[name="responsable_nombres"]');
    if (respNombresInput && respNombresInput.value.trim() !== '') {
        const respNombres = respNombresInput.value.trim().split(/\s+/);
        if (respNombres.length < 2) {
            alert('⚠️ Los nombres del responsable deben incluir al menos dos nombres.');
            return false;
        }
    }

    const respApellidosInput = form.querySelector('[name="responsable_apellidos"]');
    if (respApellidosInput && respApellidosInput.value.trim() !== '') {
        const respApellidos = respApellidosInput.value.trim().split(/\s+/);
        if (respApellidos.length < 2) {
            alert('⚠️ Los apellidos del responsable deben incluir al menos dos apellidos.');
            return false;
        }
    }

    const respTelefonoInput = form.querySelector('[name="responsable_telefono"]');
    if (respTelefonoInput && respTelefonoInput.value.trim() !== '') {
        const telefono = respTelefonoInput.value.trim().replace(/-/g, '');
        if (telefono.length !== 8 || !/^\d+$/.test(telefono)) {
            alert('⚠️ El teléfono debe tener 8 dígitos (formato: 0000-0000)');
            return false;
        }
    }

    const respDuiInput = form.querySelector('[name="responsable_dui"]');
    if (respDuiInput && respDuiInput.value.trim() !== '') {
        const dui = respDuiInput.value.trim();
        if (!/^\d{8}-\d$/.test(dui)) {
            alert('️ El DUI del responsable debe tener el formato: 00000000-0');
            return false;
        }
    }

    return true;
}