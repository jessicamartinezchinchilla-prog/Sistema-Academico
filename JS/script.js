// JS/script.js

// 1. Función para mostrar/ocultar contraseña (El "ojito")
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash'); // Icono de ojo tachado
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye'); // Icono de ojo normal
    }
}

// 2. Función para mostrar notificaciones (Aprovechando tu CSS)
function mostrarNotificacion(mensaje, tipo = 'error') {
    const notif = document.getElementById('notificacion');
    const msgText = document.getElementById('notificacion-mensaje');
    
    // Limpiar clases previas
    notif.classList.remove('notificacion-success', 'notificacion-error', 'mostrar');
    
    // Agregar clase según el tipo
    if (tipo === 'success') {
        notif.classList.add('notificacion-success');
    } else {
        notif.classList.add('notificacion-error');
    }
    
    msgText.textContent = mensaje;
    
    // Mostrar la notificación
    setTimeout(() => notif.classList.add('mostrar'), 10);
    
    // Ocultarla después de 3.5 segundos
    setTimeout(() => {
        notif.classList.remove('mostrar');
    }, 3500);
}

// 3. Revisar si venimos de un error de PHP
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        mostrarNotificacion('Usuario o contraseña incorrectos.', 'error');
    }
});