document.addEventListener("DOMContentLoaded", () => {

    /* ==================================
       ESTADÍSTICAS VISUALES
    ================================== */

    const totalEstudiantes =
        document.getElementById("totalEstudiantesConf");

    const totalProfesores =
        document.getElementById("totalProfesoresConf");

    const totalSecciones =
        document.getElementById("totalSeccionesConf");

    const totalMaterias =
        document.getElementById("totalMateriasConf");

    // Valores temporales mientras PHP no los carga
    totalEstudiantes.textContent ||= "0";
    totalProfesores.textContent ||= "0";
    totalSecciones.textContent ||= "0";
    totalMaterias.textContent ||= "0";


    /* ==================================
       VALIDAR CONFIGURACIÓN GENERAL
    ================================== */

    const formConfiguracion =
        document.querySelector(
            'form[action="actualizar_configuracion.php"]'
        );

    if(formConfiguracion){

        formConfiguracion.addEventListener("submit", function(e){

            const nombreSistema =
                document.getElementById("conf_nombre_sistema");

            const anio =
                document.getElementById("conf_anio_lectivo");

            if(nombreSistema.value.trim() === ""){

                e.preventDefault();

                alert(
                    "Ingrese el nombre del sistema."
                );

                nombreSistema.focus();
                return;
            }

            if(anio.value === ""){

                e.preventDefault();

                alert(
                    "Seleccione un año lectivo."
                );

                anio.focus();
            }

        });

    }


    /* ==================================
       VALIDAR ESCALA DE NOTAS
    ================================== */

    const formEscala =
        document.querySelector(
            'form[action="actualizar_escala.php"]'
        );

    if(formEscala){

        formEscala.addEventListener("submit", function(e){

            const notaMinima =
                parseFloat(
                    document.getElementById(
                        "conf_nota_minima"
                    ).value
                );

            const escalaMaxima =
                parseFloat(
                    document.getElementById(
                        "conf_escala_maxima"
                    ).value
                );

            if(notaMinima < 0 || notaMinima > escalaMaxima){

                e.preventDefault();

                alert(
                    "La nota mínima no puede ser mayor que la escala máxima."
                );

                return;
            }

        });

    }


    /* ==================================
       EDITAR PERÍODOS
    ================================== */

    const botonesPeriodo =
        document.querySelectorAll(
            ".btn-action.edit"
        );

    botonesPeriodo.forEach(boton => {

        boton.addEventListener("click", () => {

            const periodo =
                boton.closest(".period-item")
                .querySelector("span")
                .textContent;

            alert(
                "Editar " + periodo +
                "\n\nEsta función será conectada con PHP."
            );

        });

    });


    /* ==================================
       CONFIRMAR CIERRE DE SESIÓN
    ================================== */

    const cerrarSesion =
        document.querySelector(".btn-danger");

    if(cerrarSesion){

        cerrarSesion.addEventListener("click", function(e){

            const confirmar = confirm(
                "¿Desea cerrar sesión?"
            );

            if(!confirmar){

                e.preventDefault();
            }

        });

    }


    /* ==================================
       VALIDAR NUEVA CONTRASEÑA
    ================================== */

    const formCambiarPassword =
        document.getElementById(
            "formCambiarPassword"
        );

    if(formCambiarPassword){

        formCambiarPassword.addEventListener(
            "submit",
            function(e){

                const nueva =
                    document.getElementById(
                        "nueva_password"
                    );

                const confirmar =
                    document.getElementById(
                        "confirmar_password"
                    );

                if(
                    nueva.value.length < 8
                ){

                    e.preventDefault();

                    alert(
                        "La contraseña debe tener al menos 8 caracteres."
                    );

                    nueva.focus();

                    return;
                }

                if(
                    nueva.value !==
                    confirmar.value
                ){

                    e.preventDefault();

                    alert(
                        "Las contraseñas no coinciden."
                    );

                    confirmar.focus();

                    return;
                }

            }
        );

    }


    /* ==================================
       VALIDAR CORREO RECUPERACIÓN
    ================================== */

    const emailRecuperar =
        document.getElementById(
            "email_recuperar"
        );

    if(emailRecuperar){

        emailRecuperar.addEventListener(
            "blur",
            function(){

                const patron =
                    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if(
                    this.value !== "" &&
                    !patron.test(this.value)
                ){

                    alert(
                        "Ingrese un correo válido."
                    );
                }

            }
        );

    }


    /* ==================================
       CERRAR MODALES CON ESC
    ================================== */

    document.addEventListener(
        "keydown",
        function(e){

            if(e.key === "Escape"){

                document
                    .querySelectorAll("dialog")
                    .forEach(modal => {

                        if(modal.open){

                            modal.close();
                        }

                    });

            }

        }
    );

});


/* ==================================
   FLUJO RECUPERAR CONTRASEÑA
================================== */

function abrirRecuperarPassword(event){

    event.preventDefault();

    document
        .getElementById(
            "modalVerificarPassword"
        )
        .close();

    document
        .getElementById(
            "modalRecuperarPassword"
        )
        .showModal();
}


function enviarCodigo(){

    const email =
        document.getElementById(
            "email_recuperar"
        );

    if(email.value.trim() === ""){

        alert(
            "Ingrese un correo electrónico."
        );

        email.focus();

        return;
    }

    document.getElementById(
        "pasoEnviarCodigo"
    ).style.display = "none";

    document.getElementById(
        "pasoVerificarCodigo"
    ).style.display = "block";
}


function volverAEnviarCodigo(){

    document.getElementById(
        "pasoVerificarCodigo"
    ).style.display = "none";

    document.getElementById(
        "pasoEnviarCodigo"
    ).style.display = "block";
}


function verificarCodigo(){

    const codigo =
        document.getElementById(
            "codigo_verificacion"
        );

    if(codigo.value.length !== 6){

        alert(
            "Ingrese un código válido de 6 dígitos."
        );

        codigo.focus();

        return;
    }

    document
        .getElementById(
            "modalRecuperarPassword"
        )
        .close();

    document
        .getElementById(
            "modalCambiarPassword"
        )
        .showModal();
}


function verificarPassword(){

    const password =
        document.getElementById(
            "password_actual"
        );

    if(password.value.trim() === ""){

        alert(
            "Ingrese su contraseña actual."
        );

        password.focus();

        return;
    }

    document
        .getElementById(
            "modalVerificarPassword"
        )
        .close();

    document
        .getElementById(
            "modalCambiarPassword"
        )
        .showModal();
}