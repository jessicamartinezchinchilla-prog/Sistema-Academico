// JS/configuracion.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. MOSTRAR MENSAJES DE ÉXITO/ERROR
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (success) {
        let mensaje = '✅ Configuración guardada exitosamente';
        
        if (success === 'password_cambiada') {
            mensaje = '✅ Contraseña cambiada exitosamente';
        }
        
        mostrarNotificacion(mensaje, 'success');
        
        // Limpiar la URL para que no se muestre de nuevo al recargar
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    if (error) {
        const mensajesError = {
            'campos_vacios': '⚠️ Todos los campos son obligatorios',
            'email_invalido': '⚠️ El correo electrónico no es válido',
            'telefono_invalido': '⚠️ El teléfono debe tener exactamente 8 dígitos (Ej: 2222-3333)',
            'nota_minima_invalida': '⚠️ La nota mínima debe estar entre 0 y 10',
            'escala_maxima_invalida': '⚠️ La escala máxima debe ser mayor a 0 y menor o igual a 100',
            'nota_mayor_escala': '⚠️ La nota mínima no puede ser mayor o igual a la escala máxima',
            'tiempo_sesion_invalido': '⚠️ El tiempo de sesión debe ser mínimo 5 minutos',
            'intentos_invalidos': '⚠️ Los intentos máximos deben ser mínimo 3',
            'password_corta': '⚠️ La contraseña debe tener al menos 6 caracteres',
            'puerto_invalido': '⚠️ El puerto debe estar entre 1 y 65535',
            'password_no_coincide': '⚠️ Las contraseñas no coinciden',
            'password_incorrecta': '⚠️ La contraseña actual es incorrecta',
            'bd': '⚠️ Error en la base de datos',
            'invalido': '⚠️ Solicitud inválida'
        };
        
        const mensaje = mensajesError[error] || '⚠️ Ocurrió un error inesperado';
        mostrarNotificacion(mensaje, 'error');
        
        // Limpiar la URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // ==========================================
    // 2. APLICAR COLOR PRIMARIO EN VIVO
    // ==========================================
    const colorInput = document.querySelector('input[name="visual_color_primario"]');
    
    if (colorInput) {
        colorInput.addEventListener('input', function() {
            document.documentElement.style.setProperty('--color-primario', this.value);
        });
    }

    // ==========================================
    // 3. VALIDACIÓN EN TIEMPO REAL
    // ==========================================
    
    // Validar correos
    const camposEmail = document.querySelectorAll('input[type="email"], input[name*="email"]');
    camposEmail.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !this.value.includes('@')) {
                this.style.borderColor = '#DC2626';
                this.title = 'Correo no válido';
            } else {
                this.style.borderColor = '#D8DCE5';
                this.title = '';
            }
        });
    });

    // ==========================================
    // VALIDAR TELÉFONO (8 dígitos con guión automático)
    // ==========================================
    const inputTelefono = document.querySelector('input[name="institucion_telefono"]');

    if (inputTelefono) {
        inputTelefono.addEventListener('input', function(e) {
            // 1. Eliminar todo lo que no sea número
            let valor = this.value.replace(/[^0-9]/g, '');
            
            // 2. Limitar a 8 dígitos
            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }
            
            // 3. Agregar guión automáticamente después del 4to dígito
            if (valor.length > 4) {
                valor = valor.substring(0, 4) + '-' + valor.substring(4);
            }
            
            // 4. Actualizar el valor del input
            this.value = valor;
            
            // 5. Validar visualmente (borde amarillo si está incompleto, verde si está completo)
            const digitos = valor.replace(/-/g, '').length;
            if (digitos === 8) {
                this.style.borderColor = '#16A34A'; // Verde = válido
                this.title = 'Teléfono válido';
            } else if (digitos > 0) {
                this.style.borderColor = '#F59E0B'; // Amarillo = incompleto
                this.title = `Faltan ${8 - digitos} dígitos`;
            } else {
                this.style.borderColor = '#D8DCE5'; // Gris = vacío
                this.title = '';
            }
        });
        
        // Al perder el foco, validar definitivamente
        inputTelefono.addEventListener('blur', function() {
            const digitos = this.value.replace(/-/g, '').length;
            if (this.value && digitos !== 8) {
                this.style.borderColor = '#DC2626'; // Rojo = inválido
                this.title = 'El teléfono debe tener 8 dígitos';
            }
        });
    }

    // Validar puerto SMTP
    const inputPuerto = document.querySelector('input[name="email_puerto"]');
    if (inputPuerto) {
        inputPuerto.addEventListener('input', function() {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
            
            const puerto = parseInt(this.value);
            if (this.value && (puerto < 1 || puerto > 65535)) {
                this.style.borderColor = '#DC2626';
            } else {
                this.style.borderColor = '#D8DCE5';
            }
        });
    }

    // ==========================================
    // 4. VALIDACIÓN DE CONTRASEÑAS EN MODAL
    // ==========================================
    const formCambiarPassword = document.getElementById('formCambiarPassword');
    
    if (formCambiarPassword) {
        formCambiarPassword.addEventListener('submit', function(e) {
            const nueva = document.getElementById('nueva_password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            
            if (nueva !== confirmar) {
                e.preventDefault();
                mostrarNotificacion('⚠️ Las contraseñas no coinciden', 'error');
                return false;
            }
            
            if (nueva.length < 8) {
                e.preventDefault();
                mostrarNotificacion('️ La contraseña debe tener al menos 8 caracteres', 'error');
                return false;
            }
        });
    }

    // ==========================================
    // 5. CONFIRMACIÓN ANTES DE CERRAR SESIÓN
    // ==========================================
    const btnCerrarSesion = document.querySelector('.btn-danger');
    
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro que deseas cerrar sesión?')) {
                e.preventDefault();
            }
        });
    }
});

// ==========================================
// FUNCIÓN: Mostrar notificación
// ==========================================

function mostrarNotificacion(mensaje, tipo = 'success') {
    // Crear el contenedor de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.innerHTML = `
        <span>${mensaje}</span>
        <button onclick="this.parentElement.remove()" class="notificacion-close">&times;</button>
    `;
    
    // Estilos dinámicos
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        font-size: 14px;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
        max-width: 400px;
        ${tipo === 'success' ? 'background: #16A34A;' : 'background: #DC2626;'}
    `;
    
    document.body.appendChild(notificacion);
    
    // Auto-eliminar después de 4 segundos
    setTimeout(() => {
        if (notificacion.parentElement) {
            notificacion.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notificacion.remove(), 300);
        }
    }, 4000);
}

// ==========================================
// ANIMACIONES CSS DINÁMICAS
// ==========================================

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notificacion-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    
    .notificacion-close:hover {
        opacity: 0.7;
    }
`;
document.head.appendChild(style);