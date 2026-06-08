document.addEventListener("DOMContentLoaded", () => {

    // Obtener datos desde LocalStorage
    const estudiantes = JSON.parse(localStorage.getItem("estudiantes")) || [];
    const materias = JSON.parse(localStorage.getItem("materias")) || [];
    const secciones = JSON.parse(localStorage.getItem("secciones")) || [];
    const calificaciones = JSON.parse(localStorage.getItem("calificaciones")) || [];

    // Mostrar totales
    document.getElementById("totalEstudiantes").textContent = estudiantes.length;
    document.getElementById("totalMaterias").textContent = materias.length;
    document.getElementById("totalSecciones").textContent = secciones.length;

    // Calcular promedio general
    let sumaNotas = 0;
    let cantidadNotas = 0;

    calificaciones.forEach(calificacion => {
        if (calificacion.nota !== undefined) {
            sumaNotas += Number(calificacion.nota);
            cantidadNotas++;
        }
    });

    const promedioGeneral = cantidadNotas > 0
        ? (sumaNotas / cantidadNotas).toFixed(2)
        : 0;

    document.getElementById("promedioGeneral").textContent = promedioGeneral;

    // Contenedor de tarjetas
    const listaPromedios = document.getElementById("listaPromedios");
    const mensajeVacio = document.getElementById("mensajeVacio");

    // Agrupar notas por materia
    const materiasPromedio = {};

    calificaciones.forEach(calificacion => {

        const nombreMateria = calificacion.materia || "Sin materia";

        if (!materiasPromedio[nombreMateria]) {
            materiasPromedio[nombreMateria] = {
                suma: 0,
                cantidad: 0
            };
        }

        materiasPromedio[nombreMateria].suma += Number(calificacion.nota);
        materiasPromedio[nombreMateria].cantidad++;
    });

    listaPromedios.innerHTML = "";

    const nombresMaterias = [];
    const promediosMaterias = [];

    for (const materia in materiasPromedio) {

        const promedio =
            materiasPromedio[materia].suma /
            materiasPromedio[materia].cantidad;

        nombresMaterias.push(materia);
        promediosMaterias.push(promedio.toFixed(2));

        const tarjeta = document.createElement("article");
        tarjeta.classList.add("subject-card");

        tarjeta.innerHTML = `
            <h3>${materia}</h3>
            <p>Promedio: <strong>${promedio.toFixed(2)}</strong></p>
        `;

        listaPromedios.appendChild(tarjeta);
    }

    // Mostrar mensaje si no hay datos
    mensajeVacio.style.display =
        nombresMaterias.length === 0 ? "block" : "none";

    // Crear gráfica con Chart.js
    if (document.getElementById("graficaRendimiento")) {

        const ctx = document
            .getElementById("graficaRendimiento")
            .getContext("2d");

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: nombresMaterias,
                datasets: [{
                    label: "Promedio por materia",
                    data: promediosMaterias
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });
    }

});