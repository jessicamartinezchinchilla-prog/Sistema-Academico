document.addEventListener("DOMContentLoaded", () => {
    const lista = document.getElementById("listaProfesores");
    const buscador = document.getElementById("buscarProfesor");
    const totalProfesores = document.getElementById("totalProfesores");

    function actualizarContador() {
        const filas = lista.querySelectorAll("tr");
        totalProfesores.textContent = filas.length;
    }

    actualizarContador();

    buscador.addEventListener("input", () => {
        const texto = buscador.value.toLowerCase();

        const filas = lista.querySelectorAll("tr");

        filas.forEach(fila => {
            const contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(texto) ? "" : "none";
        });
    });
});