document.addEventListener("DOMContentLoaded", () => {

    const usuario = document.getElementById("usuario");
    const contraseña = document.getElementById("contraseña");
    const boton = document.querySelector(".button");

    boton.addEventListener("click", function (e) {

        e.preventDefault();

        const user = usuario.value.trim();
        const pass = contraseña.value.trim();

        if(user === "" || pass === ""){
            alert("Complete todos los campos");
            return;
        }

        // ADMINISTRADOR
        if(user === "admin" && pass === "1234"){

            localStorage.setItem("rol","admin");

            window.location.href = "panel_principal.html";
        }

        // PROFESOR
        else if(user === "profesor" && pass === "1234"){

            localStorage.setItem("rol","profesor");

            window.location.href = "panel_principal.html";
        }

        else{
            alert("Usuario o contraseña incorrectos");
        }

    });

});