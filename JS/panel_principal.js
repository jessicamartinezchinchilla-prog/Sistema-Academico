// JS/panel_principal.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ========================================
    // GRÁFICA 1: BARRAS (Rendimiento por materia)
    // ========================================
    const ctxBarras = document.getElementById('grafica_barras1').getContext('2d');
    new Chart(ctxBarras, {
        type: 'bar',
        data: {
            labels: nombresMaterias, 
            datasets: [{
                label: 'Promedio de Nota',
                data: promediosMaterias, 
                backgroundColor: 'rgba(37, 99, 235, 0.7)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Importante para que respete la altura del div
            layout: {
                padding: 0 // El padding ya lo maneja el div .chart-wrapper
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: true // Solo muestra tooltip si el mouse toca la barra
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.95)',
                    titleColor: '#ffffff',
                    bodyColor: '#e5e7eb',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return context[0].label; 
                        },
                        label: function(context) {
                            let valor = context.parsed.y;
                            return 'Promedio: ' + valor.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // ========================================
    // GRÁFICA 2: PASTEL (Aprobados vs Reprobados)
    // ========================================
    const ctxPastel = document.getElementById('grafica_pastel').getContext('2d');
    new Chart(ctxPastel, {
        type: 'doughnut', 
        data: {
            labels: ['Aprobados', 'Reprobados'],
            datasets: [{
                data: [totalAprobados, totalReprobados], 
                backgroundColor: [
                    '#16A34A', 
                    '#DC2626'  
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true, 
            layout: {
                padding: 0 
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 14 }, padding: 20 }
                },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.95)',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            return label + ': ' + value + ' estudiantes';
                        }
                    }
                }
            }
        }
    });

});