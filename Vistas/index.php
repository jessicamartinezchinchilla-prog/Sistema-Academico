<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inicio de sesión</title>
    <link rel="stylesheet" href="../CSS/index.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Agregamos 'defer' para asegurar que el JS cargue después del HTML -->
    <script src="../JS/script.js" defer></script> 
  </head>
  <body>
    <!-- Contenedor para las notificaciones (usa tu CSS) -->
    <div id="notificacion" class="notificacion">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span id="notificacion-mensaje"></span>
    </div>

    <header class="header">
      <img src="https://images.vexels.com/media/users/3/203184/isolated/preview/8c5175a48400ee381a0a755032bc7cbb-trazo-de-icono-de-gorro-de-graduacion.png" alt="Gorro de graduacion" />  
      <h1>Sistema Académico</h1>
      <h3>Gestión de notas escolares</h3>
    </header>

    <!-- CAMBIO 1: Envolvemos todo en <form>. Apunta al archivo PHP que procesará los datos -->
    <form id="formLogin" class="body" method="POST" action="../actions/login_action.php">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required />
      
      <label for="password">Contraseña</label>
      
      <!-- CAMBIO 2: Usamos las clases .input-password y .btn-toggle-password de tu CSS -->
      <div class="input-password">
        <!-- CAMBIO 3: Cambiamos name="contraseña" a name="password" por buenas prácticas en PHP -->
        <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required />
        <button type="button" class="btn-toggle-password" onclick="togglePassword()">
          <i class="fa-solid fa-eye" id="eye-icon"></i>
        </button>
      </div>
      
      <!-- CAMBIO 4: Quitamos el <a> y el botón ahora es type="submit" para enviar el formulario -->
      <button type="submit" class="button">Iniciar Sesión</button>
    </form>

    <footer>
      <h3>Sistema Académico</h3>
    </footer>
  </body>
</html>