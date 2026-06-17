// JS/panel_principal.js

document.addEventListener('DOMContentLoaded', () => {
    
    // Función para obtener colores de interfaz según el tema
    function getThemeColors() {
        const isDark = document.body.classList.contains('modo-oscuro');
        return {
            text: isDark ? '#e0e0e0' : '#666',
            grid: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)'
        };
    }

    // ==========================================
    // GRÁFICA 1: Rendimiento por materia (Barras)
    // ==========================================
    const ctx1 = document.getElementById('grafica_barras1');
    if (ctx1 && typeof nombresMaterias !== 'undefined' && nombresMaterias.length > 0) {
        
        const theme = getThemeColors();
        
        // ✅ Lógica original: color por barra según promedio
        const barColors = promediosMaterias.map(promedio => {
            if (promedio >= 8) return '#4CAF50';
            if (promedio >= 6) return '#2196F3';
            if (promedio >= 5) return '#FF9800';
            return '#f44336';
        });

        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: nombresMaterias,
                datasets: [{
                    label: 'Promedio',
                    data: promediosMaterias,
                    backgroundColor: barColors,
                    borderColor: barColors,
                    borderWidth: 1,
                    borderRadius: 6
                    // ✅ Se eliminó barThickness fijo para recuperar el compactado automático
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 6
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 1,
                            color: theme.text,
                            font: { size: 11 }
                        },
                        grid: { color: theme.grid }
                    },
                    x: {
                        ticks: {
                            color: theme.text,
                            font: { size: 11 }
                        },
                        grid: { display: false }
                    }
                },
                animation: { duration: 1200, easing: 'easeOutQuart' }
            }
        });
    }

    // ==========================================
    // GRÁFICA 2: Aprobados vs Reprobados (Dona)
    // ==========================================
    const ctx2 = document.getElementById('grafica_pastel');
    if (ctx2 && typeof totalAprobados !== 'undefined') {
        
        const theme = getThemeColors();

        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Reprobados'],
                datasets: [{
                    data: [totalAprobados, totalReprobados],
                    backgroundColor: ['#4CAF50', '#f44336'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: theme.text,
                            font: { size: 12, weight: '500' },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const total = totalAprobados + totalReprobados;
                                const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${context.parsed} (${pct}%)`;
                            }
                        }
                    }
                },
                animation: { animateScale: true, animateRotate: true, duration: 1200 }
            }
        });
    }
});