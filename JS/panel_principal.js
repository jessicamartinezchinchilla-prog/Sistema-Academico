// ==============================
// 📊 ESTADO (como en React 😏)
// ==============================
const state = {
    stats: {
        profesores: 12,
        materias: 8,
        estudiantes: 120,
        activos: 95,
        inactivos: 25,
        secciones: 6
    },

    rendimiento: [8, 7, 9, 6, 8],
    aprobacion: [80, 20]
};

// ==============================
// 🔢 RENDER TARJETAS
// ==============================
function renderStats() {
    const cards = document.querySelectorAll(".card-info h3");

    if (!cards.length) return;

    cards[0].textContent = state.stats.profesores;
    cards[1].textContent = state.stats.materias;
    cards[2].textContent = state.stats.estudiantes;
    cards[3].textContent = state.stats.activos;
    cards[4].textContent = state.stats.inactivos;
    cards[5].textContent = state.stats.secciones;
}

// ==============================
// 📈 GRÁFICA DE BARRAS
// ==============================
function renderBarChart() {
    const ctx = document.getElementById("grafica_barras1");

    if (!ctx) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["Matemática", "Lenguaje", "Ciencias", "Historia", "Inglés"],
            datasets: [{
                label: "Promedio",
                data: state.rendimiento,
                backgroundColor: "#2563eb",
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
}

// ==============================
// 🥧 GRÁFICA DE PASTEL
// ==============================
function renderPieChart() {
    const ctx = document.getElementById("grafica_pastel");

    if (!ctx) return;

    new Chart(ctx, {
        type: "doughnut", // más moderno que pie 😏
        data: {
            labels: ["Aprobados", "Reprobados"],
            datasets: [{
                data: state.aprobacion,
                backgroundColor: ["#22c55e", "#ef4444"],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom"
                }
            }
        }
    });
}

// ==============================
// 🚀 INIT (como useEffect 😎)
// ==============================
function initDashboard() {
    renderStats();
    renderBarChart();
    renderPieChart();
}

// ==============================
// 🔥 START
// ==============================
document.addEventListener("DOMContentLoaded", initDashboard);