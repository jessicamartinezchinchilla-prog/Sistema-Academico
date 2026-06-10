// script.js
// ========================================
// LOGIN - INTERACTIVIDAD
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ script.js cargado');
    
    // Configurar formulario de login
    configurarFormularioLogin();
});

// ========================================
// CONFIGURAR FORMULARIO LOGIN
// ========================================

function configurarFormularioLogin() {
    const form = document.querySelector('.body form');
    
    // Si no hay form, buscar el botón directamente
    const btnLogin = document.querySelector('.body .button');
    
    if (btnLogin) {
        btnLogin.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(' Intentando iniciar sesión...');
            
            const usuario = document.getElementById('usuario');
            const contraseña = document.getElementById('contraseña');
            
            if (usuario && contraseña) {
                if (usuario.value.trim() === '') {
                    mostrarNotificacion('error', 'Ingrese su usuario');
                    usuario.focus();
                    return;
                }
                
                if (contraseña.value.trim() === '') {
                    mostrarNotificacion('error', 'Ingrese su contraseña');
                    contraseña.focus();
                    return;
                }
                
                console.log('✅ Validación correcta, redirigiendo...');
                window.location.href = '../HTML/panel_principal.html';
            }
        });
    }
}

// ========================================
// TOGGLE CONTRASEÑA (MOSTRAR/OCULTAR)
// ========================================

function togglePassword() {
    console.log('👁️ Toggle contraseña');
    
    const passwordInput = document.getElementById('contraseña');
    const eyeIcon = document.getElementById('eye-icon');
    
    if (!passwordInput || !eyeIcon) {
        console.error('❌ No se encontró el input o el icono');
        return;
    }
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
        console.log('👁️ Contraseña visible');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
        console.log(' Contraseña oculta');
    }
}

// ========================================
// NOTIFICACIONES
// ========================================

function mostrarNotificacion(tipo, mensaje) {
    // Eliminar notificación anterior si existe
    const notificacionAnterior = document.querySelector('.notificacion');
    if (notificacionAnterior) {
        notificacionAnterior.remove();
    }
    
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
// ENTER PARA ENVIAR FORMULARIO
// ========================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const usuario = document.getElementById('usuario');
        const contraseña = document.getElementById('contraseña');
        
        if (usuario && contraseña && document.activeElement === contraseña) {
            const btnLogin = document.querySelector('.body .button');
            if (btnLogin) {
                btnLogin.click();
            }
        }
    }
});