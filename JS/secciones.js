// ==========================================
// secciones.js - Solo interactividad UI
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // MODAL "AÑADIR SECCIÓN"
    // ==========================================
    const modalSeccion = document.getElementById('modalSeccion');
    
    if (modalSeccion) {
        // Cerrar al hacer clic fuera
        modalSeccion.addEventListener('click', (e) => {
            if (e.target === modalSeccion) {
                modalSeccion.close();
            }
        });

        // Formulario
        const formSeccion = modalSeccion.querySelector('form');
        if (formSeccion) {
            formSeccion.addEventListener('submit', (e) => {
                e.preventDefault();
                // PHP procesará los datos
                formSeccion.reset();
                modalSeccion.close();
            });
        }
    }

    // ==========================================
    // BOTONES "VER DETALLES"
    // ==========================================
    const botonesDetalles = document.querySelectorAll('.subject-card button');
    
    botonesDetalles.forEach(boton => {
        boton.addEventListener('click', function() {
            const tarjeta = this.closest('.subject-card');
            const nombreSeccion = tarjeta.querySelector('h3').textContent;
            console.log(`Ver detalles: ${nombreSeccion}`);
        });
    });

    // ==========================================
    // TABLA - SELECCIÓN DE FILAS
    // ==========================================
    const filasTabla = document.querySelectorAll('.data-table tbody tr');
    
    filasTabla.forEach(fila => {
        fila.style.cursor = 'pointer';
        
        fila.addEventListener('click', function() {
            filasTabla.forEach(f => f.style.backgroundColor = '');
            this.style.backgroundColor = '#f0f9ff';
        });
    });

});