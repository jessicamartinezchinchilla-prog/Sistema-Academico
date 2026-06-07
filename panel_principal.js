document.addEventListener("DOMContentLoaded", () => {

    // ==============================
    // 📊 DATOS (puedes cambiarlos)
    // ==============================
    const datos = {
        profesores: 12,
        materias: 8,
        estudiantes: 120,
        activos: 95,
        inactivos: 25,
        secciones: 6
    };

    // ==============================
    // 🔢 LLENAR TARJETAS
    // ==============================
    const numeros = document.querySelectorAll(".stat-number");

    numeros[0].textContent = datos.profesores;
    numeros[1].textContent = datos.materias;
    numeros[2].textContent = datos.estudiantes;
    numeros[3].textContent = datos.activos;
    numeros[4].textContent = datos.inactivos;
    numeros[5].textContent = datos.secciones;

    // ==============================
    // 📈 GRÁFICA DE BARRAS
    // ==============================
    const ctxBarras = document.getElementById("grafica_barras1");

    new Chart(ctxBarras, {
        type: "bar",
        data: {
            labels: ["Matemática", "Lenguaje", "Ciencias", "Historia", "Inglés"],
            datasets: [{
                label: "Promedio",
                data: [8, 7, 9, 6, 8],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });

    // ==============================
    // 🥧 GRÁFICA DE PASTEL
    // ==============================
    const ctxPastel = document.getElementById("grafica_pastel");

    new Chart(ctxPastel, {
        type: "pie",
        data: {
            labels: ["Aprobados", "Reprobados"],
            datasets: [{
                data: [80, 20],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });

});