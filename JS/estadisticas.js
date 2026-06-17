// JS/estadisticas.js

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // GRÁFICA DE RENDIMIENTO POR MATERIA
    // ==========================================
    const ctx = document.getElementById('graficaRendimiento');
    
    if (ctx && typeof nombresMaterias !== 'undefined' && nombresMaterias.length > 0) {
        
        // Generar colores dinámicos
        const colores = promediosMaterias.map(promedio => {
            if (promedio >= 8) return '#4CAF50';      // Verde (excelente)
            if (promedio >= 6) return '#2196F3';      // Azul (aprobado)
            if (promedio >= 5) return '#FF9800';      // Naranja (reprobado por poco)
            return '#f44336';                          // Rojo (reprobado)
        });
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: nombresMaterias,
                datasets: [{
                    label: 'Promedio',
                    data: promediosMaterias,
                    backgroundColor: colores,
                    borderColor: colores.map(c => c),
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                const materia = materiasData[context.dataIndex];
                                return [
                                    `Promedio: ${context.parsed.y.toFixed(2)}`,
                                    `Estudiantes: ${materia.total_estudiantes}`,
                                    `Aprobados: ${materia.aprobados}`,
                                    `Reprobados: ${materia.reprobados}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 1,
                            font: { size: 12 },
                            color: '#666'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: 11 },
                            color: '#333',
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
});