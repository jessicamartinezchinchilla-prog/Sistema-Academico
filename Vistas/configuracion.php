<?php
// Vistas/configuracion.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener todas las configuraciones
$configQuery = $pdo->query("SELECT clave, valor, descripcion, categoria FROM configuraciones ORDER BY categoria, clave");
$config = [];
while ($row = $configQuery->fetch()) {
    $config[$row['clave']] = $row['valor'];
}

// Estadísticas del sistema
$totalEstudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'activo'")->fetchColumn();
$totalProfesores = $pdo->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$totalSecciones = $pdo->query("SELECT COUNT(*) FROM secciones")->fetchColumn();
$totalMaterias = $pdo->query("SELECT COUNT(*) FROM materias")->fetchColumn();

// Datos del usuario actual
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();
$userNombre = $userData['usuario'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../CSS/configuracion.css">
    <title>Configuración - Sistema Académico</title>
    <style>
        :root {
            --color-primario: <?php echo $config['visual_color_primario'] ?? '#2F6FED'; ?>;
        }
    </style>
</head>
<body class="<?php echo ($config['visual_modo_oscuro'] ?? '0') === '1' ? 'modo-oscuro' : ''; ?>">
    <header class="header">
        <h1>Sistema Académico</h1>
        <nav>
            <ul class="list">
                <li><a href="panel_principal.php"><i class="fa-solid fa-house"></i> Panel principal</a></li>
                <li><a href="profesores.php"><i class="fa-solid fa-user"></i> Profesores</a></li>
                <li><a href="estudiantes.php"><i class="fa-solid fa-children"></i> Estudiantes</a></li>
                <li><a href="matricula.php"><i class="fa-solid fa-user-graduate"></i> Matrículas</a></li>
                <li><a href="materias.php"><i class="fa-solid fa-book-open"></i> Materias</a></li>
                <li><a href="calificaciones.php"><i class="fa-solid fa-award"></i> Calificaciones</a></li>
                <li><a href="secciones.php"><i class="fa-solid fa-school"></i> Secciones</a></li>
                <li><a href="historial_academico.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial académico</a></li>
                <li><a href="estadisticas.php"><i class="fa-solid fa-chart-column"></i> Estadísticas</a></li>
                <li><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <li><a href="configuracion.php" class="active"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <h2>Configuración del Sistema</h2>
        <p>Administración general y parámetros del sistema académico</p>

        <!-- DATOS DE LA INSTITUCIÓN -->
        <section class="config-section">
            <h3><i class="fa-solid fa-building"></i> Datos de la Institución</h3>
            <form action="../actions/configuracion_action.php" method="POST" class="config-form">
                <input type="hidden" name="categoria" value="institucion">
                
                <label>Nombre de la Institución:</label>
                <input type="text" name="institucion_nombre" 
                       value="<?php echo ($config['institucion_nombre'] ?? '') !== 'No configurado' ? htmlspecialchars($config['institucion_nombre'] ?? '') : ''; ?>" 
                       placeholder="Ej: Instituto Nacional de Ciencias">

                <label>Dirección:</label>
                <input type="text" name="institucion_direccion" 
                       value="<?php echo ($config['institucion_direccion'] ?? '') !== 'Dirección no configurada' ? htmlspecialchars($config['institucion_direccion'] ?? '') : ''; ?>" 
                       placeholder="Ej: Calle Principal #123, Ciudad">

                <label>Teléfono:</label>
                <input type="text" name="institucion_telefono" 
                       value="<?php echo ($config['institucion_telefono'] ?? '') !== 'No configurado' ? htmlspecialchars($config['institucion_telefono'] ?? '') : ''; ?>" 
                       placeholder="Ej: 2222-3333">

                <label>Correo Electrónico:</label>
                <input type="email" name="institucion_email" 
                       value="<?php echo ($config['institucion_email'] ?? '') !== 'No configurado' ? htmlspecialchars($config['institucion_email'] ?? '') : ''; ?>" 
                       placeholder="Ej: contacto@institucion.edu.sv">

                <div class="modal-actions">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </section>

        <!-- PARÁMETROS DEL SISTEMA -->
        <section class="config-section">
            <h3><i class="fa-solid fa-sliders"></i> Parámetros del Sistema</h3>
            <form action="../actions/configuracion_action.php" method="POST" class="config-form">
                <input type="hidden" name="categoria" value="sistema">
                
                <label>Año Lectivo:</label>
                <select name="sistema_anio_lectivo" required>
                    <?php for ($i = 2026; $i >= 2020; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($config['sistema_anio_lectivo'] ?? '') == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>

                <label>Nota Mínima Aprobatoria:</label>
                <input type="number" name="sistema_nota_minima" value="<?php echo htmlspecialchars($config['sistema_nota_minima'] ?? '6.0'); ?>" min="0" max="10" step="0.1" required>

                <label>Escala Máxima:</label>
                <input type="number" name="sistema_escala_maxima" value="<?php echo htmlspecialchars($config['sistema_escala_maxima'] ?? '10.0'); ?>" min="0" max="100" step="0.1" required>

                <label>Cantidad de Períodos:</label>
                <input type="number" name="sistema_cantidad_periodos" value="<?php echo htmlspecialchars($config['sistema_cantidad_periodos'] ?? '4'); ?>" min="1" max="10" required>

                <label>Formato de Fecha:</label>
                <select name="sistema_formato_fecha" required>
                    <option value="d/m/Y" <?php echo ($config['sistema_formato_fecha'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/AAAA</option>
                    <option value="m/d/Y" <?php echo ($config['sistema_formato_fecha'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/AAAA</option>
                    <option value="Y-m-d" <?php echo ($config['sistema_formato_fecha'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>AAAA-MM-DD</option>
                </select>

                <div class="modal-actions">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </section>

        <!-- SEGURIDAD -->
        <section class="config-section">
            <h3><i class="fa-solid fa-shield-halved"></i> Seguridad</h3>
            <form action="../actions/configuracion_action.php" method="POST" class="config-form">
                <input type="hidden" name="categoria" value="seguridad">
                
                <label>Tiempo de Expiración de Sesión (minutos):</label>
                <input type="number" name="seguridad_tiempo_sesion" value="<?php echo htmlspecialchars($config['seguridad_tiempo_sesion'] ?? '30'); ?>" min="5" max="480" required>

                <label>Intentos Máximos de Login:</label>
                <input type="number" name="seguridad_intentos_maximos" value="<?php echo htmlspecialchars($config['seguridad_intentos_maximos'] ?? '5'); ?>" min="3" max="20" required>

                <label>Longitud Mínima de Contraseña:</label>
                <input type="number" name="seguridad_longitud_password" value="<?php echo htmlspecialchars($config['seguridad_longitud_password'] ?? '8'); ?>" min="6" max="32" required>

                <label class="checkbox-label">
                    <input type="checkbox" name="seguridad_requiere_mayusculas" value="1" <?php echo ($config['seguridad_requiere_mayusculas'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    Requerir mayúsculas en contraseñas
                </label>

                <label class="checkbox-label">
                    <input type="checkbox" name="seguridad_requiere_numeros" value="1" <?php echo ($config['seguridad_requiere_numeros'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    Requerir números en contraseñas
                </label>

                <div class="modal-actions">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </section>

        <!-- PERSONALIZACIÓN VISUAL -->
        <section class="config-section">
            <h3><i class="fa-solid fa-palette"></i> Personalización Visual</h3>
            <form action="../actions/configuracion_action.php" method="POST" class="config-form">
                <input type="hidden" name="categoria" value="visual">
                
                <label>Color Primario:</label>
                <input type="color" name="visual_color_primario" value="<?php echo htmlspecialchars($config['visual_color_primario'] ?? '#2F6FED'); ?>" required>

                <label class="checkbox-label">
                    <input type="checkbox" name="visual_modo_oscuro" value="1" <?php echo ($config['visual_modo_oscuro'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    Activar modo oscuro
                </label>

                <div class="modal-actions">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </section>

        <!-- CONFIGURACIÓN DE CORREOS -->
        <section class="config-section">
            <h3><i class="fa-solid fa-envelope"></i> Configuración de Correos</h3>
            <form action="../actions/configuracion_action.php" method="POST" class="config-form">
                <input type="hidden" name="categoria" value="email">
                
                <label>Servidor SMTP:</label>
                <input type="text" name="email_servidor" 
                       value="<?php echo ($config['email_servidor'] ?? '') !== 'No configurado' ? htmlspecialchars($config['email_servidor'] ?? '') : ''; ?>" 
                       placeholder="Ej: smtp.gmail.com">

                <label>Puerto:</label>
                <input type="number" name="email_puerto" 
                       value="<?php echo ($config['email_puerto'] ?? '') !== '587' ? htmlspecialchars($config['email_puerto'] ?? '') : '587'; ?>" 
                       placeholder="587" required>

                <label>Usuario SMTP:</label>
                <input type="text" name="email_usuario" 
                       value="<?php echo ($config['email_usuario'] ?? '') !== 'No configurado' ? htmlspecialchars($config['email_usuario'] ?? '') : ''; ?>" 
                       placeholder="Ej: tu_correo@gmail.com">

                <label>Contraseña SMTP:</label>
                <input type="password" name="email_password" 
                       value="<?php echo ($config['email_password'] ?? '') !== 'No configurado' ? htmlspecialchars($config['email_password'] ?? '') : ''; ?>" 
                       placeholder="Contraseña de aplicación">

                <label>Correo Remitente:</label>
                <input type="email" name="email_remitente" 
                       value="<?php echo ($config['email_remitente'] ?? '') !== 'No configurado' ? htmlspecialchars($config['email_remitente'] ?? '') : ''; ?>" 
                       placeholder="Ej: noreply@institucion.com">

                <div class="modal-actions">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </section>

        <!-- RESPALDOS DE BASE DE DATOS -->
        <section class="config-section">
            <h3><i class="fa-solid fa-database"></i> Respaldos de Base de Datos</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                Genera respaldos completos de tu base de datos para seguridad
            </p>
            
            <div class="backup-actions">
                <a href="../actions/backup_database.php" class="btn-primary">
                    <i class="fa-solid fa-download"></i> Generar Respaldo SQL
                </a>
            </div>

            <div class="backup-info">
                <i class="fa-solid fa-circle-info"></i>
                <span>Los respaldos incluyen todas las tablas y datos del sistema</span>
            </div>
        </section>

        <!-- ESTADÍSTICAS DEL SISTEMA -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalEstudiantes; ?></span>
                <span class="stat-label">Estudiantes Activos</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalProfesores; ?></span>
                <span class="stat-label">Profesores</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalSecciones; ?></span>
                <span class="stat-label">Secciones</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalMaterias; ?></span>
                <span class="stat-label">Materias</span>
            </article>
        </section>

        <!-- DATOS DEL USUARIO -->
        <section class="config-section">
            <h3><i class="fa-solid fa-user-circle"></i> Mi Cuenta</h3>
            <div class="user-info-display">
                <div class="info-item">
                    <label><i class="fa-solid fa-user"></i> Usuario:</label>
                    <span><?php echo htmlspecialchars($userNombre); ?></span>
                </div>
            </div>

            <div class="modal-actions" style="margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalCambiarPassword').showModal()">
                    <i class="fa-solid fa-key"></i> Cambiar Contraseña
                </button>
            </div>
        </section>

        <!-- ZONA PELIGROSA -->
        <section class="config-section danger-zone">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Cerrar Sesión</h3>
            <p>Cierre de sesión seguro del sistema</p>
            <a href="../actions/logout.php" class="btn-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
            </a>
        </section>
    </main>

    <!-- MODAL: CAMBIAR CONTRASEÑA -->
    <dialog id="modalCambiarPassword" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3><i class="fa-solid fa-key"></i> Cambiar Contraseña</h3>
        <form action="../actions/cambiar_password.php" method="POST" class="modal-form" id="formCambiarPassword">
            <label>Contraseña Actual:</label>
            <input type="password" name="password_actual" required>

            <label>Nueva Contraseña:</label>
            <input type="password" name="nueva_password" id="nueva_password" required minlength="<?php echo $config['seguridad_longitud_password'] ?? 8; ?>">

            <label>Confirmar Nueva Contraseña:</label>
            <input type="password" name="confirmar_password" id="confirmar_password" required minlength="<?php echo $config['seguridad_longitud_password'] ?? 8; ?>">

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalCambiarPassword').close()">Cancelar</button>
                <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>

    <script src="../JS/configuracion.js"></script>
</body>
</html>