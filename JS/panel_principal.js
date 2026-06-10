document.addEventListener("DOMContentLoaded", () => {

    /* ==========================================
       GRÁFICA DE BARRAS (VACÍA)
    ========================================== */

    const canvasBarras = document.getElementById("grafica_barras1");

    if (canvasBarras) {

        const ctxBarras = canvasBarras.getContext("2d");

        new Chart(ctxBarras, {
            type: "bar",
            data: {
                labels: [],
                datasets: [{
                    label: "",
                    data: [],
                    backgroundColor: "#2643B5",
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

    }

    /* ==========================================
       GRÁFICA DE PASTEL (VACÍA)
    ========================================== */

    const canvasPastel = document.getElementById("grafica_pastel");

    if (canvasPastel) {

        const ctxPastel = canvasPastel.getContext("2d");

        new Chart(ctxPastel, {
            type: "pie",
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        "#2643B5",
                        "#E5E7EB"
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });

    }

});