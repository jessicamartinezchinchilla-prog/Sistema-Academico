<?php
// Vistas/matricula.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener todas las matrículas con datos completos
$query = "SELECT 
            m.id,
            m.anio,
            m.estado as estado_matricula,
            m.fecha_registro,
            e.id as id_estudiante,
            e.nie,
            e.nombres,
            e.apellidos,
            e.dui,
            e.edad,
            e.telefono as est_telefono,
            e.email as est_email,
            e.direccion as est_direccion,
            e.estado as estado_estudiante,
            CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
            s.id as id_seccion,
            s.nombre as seccion,
            r.nombres as resp_nombres,
            r.apellidos as resp_apellidos,
            r.telefono as resp_telefono,
            r.dui as resp_dui,
            r.ocupacion,
            r.parentesco,
            r.email as resp_email,
            r.direccion as resp_direccion
          FROM matriculas m
          INNER JOIN estudiantes e ON m.id_estudiante = e.id
          INNER JOIN secciones s ON m.id_seccion = s.id
          LEFT JOIN responsables r ON m.id = r.id_matricula
          WHERE m.estado = 'Activo'
          ORDER BY m.fecha_registro DESC";
$matriculas = $pdo->query($query)->fetchAll();

// Estadísticas (con los mismos JOINs que la tabla para consistencia)
$totalMatriculas = count($matriculas);
$matriculasActivas = $pdo->query("
    SELECT COUNT(*) FROM matriculas m
    INNER JOIN estudiantes e ON m.id_estudiante = e.id
    INNER JOIN secciones s ON m.id_seccion = s.id
    WHERE m.estado = 'Activo'
")->fetchColumn();
$matriculasInactivas = $pdo->query("
    SELECT COUNT(*) FROM matriculas m
    INNER JOIN estudiantes e ON m.id_estudiante = e.id
    INNER JOIN secciones s ON m.id_seccion = s.id
    WHERE m.estado = 'Inactivo'
")->fetchColumn();
$matriculasAnio = $pdo->query("
    SELECT COUNT(*) FROM matriculas m
    INNER JOIN estudiantes e ON m.id_estudiante = e.id
    INNER JOIN secciones s ON m.id_seccion = s.id
    WHERE m.anio = YEAR(CURRENT_DATE)
")->fetchColumn();

// Obtener estudiantes existentes (solo los que tienen matrículas activas)
$estudiantes = $pdo->query("
    SELECT DISTINCT e.id, CONCAT(e.nombres, ' ', e.apellidos, ' (', e.nie, ')') as nombre_completo, e.nie 
    FROM estudiantes e
    INNER JOIN matriculas m ON e.id = m.id_estudiante
    WHERE e.estado = 'activo' AND m.estado = 'Activo'
    ORDER BY nombre_completo
")->fetchAll();

// Obtener secciones completas
$secciones = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../CSS/matricula.css">
    <script src="../JS/matricula.js" defer></script>
    <title>Gestión de Matrículas - Sistema Académico</title>
    <?php require_once '../includes/theme.php'; ?>
</head>
<body class="<?php echo $modo_oscuro ? 'modo-oscuro' : ''; ?>">
    <header class="header">
        <h1>Sistema Académico</h1>
                <nav>
            <ul class="list">
                <li><a href="panel_principal.php"><i class="fa-solid fa-house"></i> Panel principal</a></li>
                
                <?php if (puedeVerPanel('profesores')): ?>
                    <li><a href="profesores.php"><i class="fa-solid fa-user"></i> Profesores</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('estudiantes')): ?>
                    <li><a href="estudiantes.php"><i class="fa-solid fa-children"></i> Estudiantes</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('matricula')): ?>
                    <li><a href="matricula.php"><i class="fa-solid fa-user-graduate"></i> Matrículas</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('materias')): ?>
                    <li><a href="materias.php"><i class="fa-solid fa-book-open"></i> Materias</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('calificaciones')): ?>
                    <li><a href="calificaciones.php"><i class="fa-solid fa-award"></i> Calificaciones</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('secciones')): ?>
                    <li><a href="secciones.php"><i class="fa-solid fa-school"></i> Secciones</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('historial_academico')): ?>
                    <li><a href="historial_academico.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial académico</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('estadisticas')): ?>
                    <li><a href="estadisticas.php"><i class="fa-solid fa-chart-column"></i> Estadísticas</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('auditoria')): ?>
                    <li><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <?php endif; ?>
                
                <?php if (esAdmin()): ?>
                    <li><a href="usuarios.php"><i class="fa-solid fa-users-gear"></i> Usuarios</a></li>
                <?php endif; ?>
                
                <?php if (puedeVerPanel('configuracion')): ?>
                    <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <?php endif; ?>

                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <h2>Gestión de Matrículas</h2>
        <p>Registro, inscripción y gestión del vínculo académico de los estudiantes</p>

        <!-- ESTADÍSTICAS -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalMatriculas; ?></span>
                <span class="stat-label">Total Matrículas</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #16a34a;"><?php echo $matriculasActivas; ?></span>
                <span class="stat-label">Activas</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #dc2626;"><?php echo $matriculasInactivas; ?></span>
                <span class="stat-label">Inactivas</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #9333ea;"><?php echo $matriculasAnio; ?></span>
                <span class="stat-label">Este Año</span>
            </article>
        </section>

        <!-- PANEL DE FILTROS Y BÚSQUEDA -->
        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarMatricula" placeholder="Buscar por NIE, nombre o responsable...">
            </div>

            <div class="filtros">
                <select id="filtroSeccion">
                    <option value="">Todas las secciones</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['nombre']; ?>"><?php echo $s['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>

            <?php if (puedeMatricular()): ?>
                <button type="button" class="button btn-primary" onclick="abrirModalAgregar()">
                    <i class="fa-solid fa-plus"></i> Nueva Matrícula
                </button>
            <?php endif; ?>
        </section>

        <!-- TABLA DE MATRÍCULAS -->
        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NIE</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Sección</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaMatriculas">
                    <?php if (empty($matriculas)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px;">No hay matrículas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($matriculas as $mat): ?>
                            <tr data-id="<?php echo $mat['id']; ?>"
                                data-id-estudiante="<?php echo $mat['id_estudiante']; ?>"
                                data-nie="<?php echo htmlspecialchars($mat['nie'] ?? ''); ?>"
                                data-nombres="<?php echo htmlspecialchars($mat['nombres'] ?? ''); ?>"
                                data-apellidos="<?php echo htmlspecialchars($mat['apellidos'] ?? ''); ?>"
                                data-nombre="<?php echo htmlspecialchars($mat['nombre_completo'] ?? ''); ?>"
                                data-dui="<?php echo htmlspecialchars($mat['dui'] ?? ''); ?>"
                                data-edad="<?php echo htmlspecialchars($mat['edad'] ?? ''); ?>"
                                data-telefono="<?php echo htmlspecialchars($mat['est_telefono'] ?? ''); ?>"
                                data-email="<?php echo htmlspecialchars($mat['est_email'] ?? ''); ?>"
                                data-direccion="<?php echo htmlspecialchars($mat['est_direccion'] ?? ''); ?>"
                                data-id-seccion="<?php echo $mat['id_seccion']; ?>"
                                data-seccion="<?php echo htmlspecialchars($mat['seccion'] ?? ''); ?>"
                                data-estado="<?php echo htmlspecialchars(strtolower($mat['estado_estudiante']) ?? ''); ?>"
                                data-resp-dui="<?php echo htmlspecialchars($mat['resp_dui'] ?? ''); ?>"
                                data-resp-nombres="<?php echo htmlspecialchars($mat['resp_nombres'] ?? ''); ?>"
                                data-resp-apellidos="<?php echo htmlspecialchars($mat['resp_apellidos'] ?? ''); ?>"
                                data-resp-ocupacion="<?php echo htmlspecialchars($mat['ocupacion'] ?? ''); ?>"
                                data-resp-parentesco="<?php echo htmlspecialchars($mat['parentesco'] ?? ''); ?>"
                                data-resp-email="<?php echo htmlspecialchars($mat['resp_email'] ?? ''); ?>"
                                data-resp-telefono="<?php echo htmlspecialchars($mat['resp_telefono'] ?? ''); ?>"
                                data-resp-direccion="<?php echo htmlspecialchars($mat['resp_direccion'] ?? ''); ?>">
                                <td><?php echo $mat['nie']; ?></td>
                                <td><?php echo $mat['nombre_completo']; ?></td>
                                <td><?php echo $mat['seccion']; ?></td>
                                <td><?php echo ($mat['resp_nombres'] ?? '') . ' ' . ($mat['resp_apellidos'] ?? ''); ?></td>
                                <td><?php echo $mat['resp_telefono'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if (strtolower($mat['estado_estudiante']) === 'activo'): ?>
                                        <span class="badge active">Activo</span>
                                    <?php else: ?>
                                        <span class="badge inactive">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action see" onclick="verMatricula(this)" title="Ver detalles"><i class="fa-solid fa-eye"></i></button>
                                    <?php if (puedeMatricular()): ?>
                                        <button type="button" class="btn-action edit" onclick="abrirModalEditar(this)" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button type="button" class="btn-action delete" onclick="eliminarMatricula(<?php echo $mat['id']; ?>)" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- MODAL: AÑADIR MATRÍCULA -->
    <dialog id="modalMatricula" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Añadir Nueva Matrícula</h3>
        
        <form action="../actions/matricula_action.php" method="POST" class="modal-form" id="formMatricula">
            <input type="hidden" name="accion" value="agregar">
            
            <!-- ========== PASO 1: ESTUDIANTE + SECCIÓN ========== -->
            <div id="paso1">
                <!-- TIPO DE ESTUDIANTE -->
                <h4><i class="fa-solid fa-user-graduate"></i> Tipo de Estudiante</h4>
                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px 20px; border: 2px solid #2F6FED; border-radius: 10px; background: #f0f4ff; transition: all 0.3s;">
                        <input type="radio" name="tipo_estudiante" value="existente" checked onchange="toggleCamposEstudiante()" style="width: 18px; height: 18px;">
                        <i class="fa-solid fa-user-check" style="font-size: 20px; color: #2F6FED;"></i>
                        <span style="font-weight: 600; font-size: 15px;">Estudiante Existente</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px 20px; border: 2px solid #d1d5db; border-radius: 10px; background: white; transition: all 0.3s;">
                        <input type="radio" name="tipo_estudiante" value="nuevo" onchange="toggleCamposEstudiante()" style="width: 18px; height: 18px;">
                        <i class="fa-solid fa-user-plus" style="font-size: 20px; color: #6b7280;"></i>
                        <span style="font-weight: 600; font-size: 15px;">Estudiante Nuevo</span>
                    </label>
                </div>

                <!-- CAMPOS: ESTUDIANTE EXISTENTE -->
                <div id="campos_estudiante_existente">
                    <label>Seleccionar Estudiante:</label>
                    <select name="id_estudiante_existente" id="select_estudiante_existente">
                        <option value="">-- Seleccione un estudiante --</option>
                        <?php foreach ($estudiantes as $est): ?>
                            <option value="<?php echo $est['id']; ?>">
                                <?php echo htmlspecialchars($est['nombre_completo'] . ' (NIE: ' . $est['nie'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- ✅ Checkbox para estudiante que repite año -->
                    <div id="contenedor_checkbox_repite" style="display: none; margin-top: 12px; padding: 12px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="checkbox_repite" name="repite_anio" disabled style="width: 16px; height: 16px;">
                            <span style="font-weight: 500; color: #92400e;">El estudiante está repitiendo año</span>
                        </label>
                        <p style="font-size: 11px; color: #78350f; margin-top: 6px; margin-bottom: 0;">
                            <i class="fa-solid fa-info-circle"></i> Marque esta opción si el estudiante está repitiendo el año académico. Esto permitirá seleccionar cualquier sección disponible.
                        </p>
                        <p id="info_grado_actual" style="font-size: 12px; color: #92400e; margin-top: 8px; margin-bottom: 0; font-weight: 600;"></p>
                    </div>
                </div>

                <!-- CAMPOS: ESTUDIANTE NUEVO -->
                <div id="campos_estudiante_nuevo" style="display: none;">
                    <h4><i class="fa-solid fa-user-plus"></i> Datos del Nuevo Estudiante</h4>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label>NIE:</label>
                            <input type="text" id="mat_nie" name="nie" maxlength="10" pattern="\d{1,10}" placeholder="Ej: 123456789" title="Solo números (1-10 dígitos)">
                        </div>
                        <div class="form-col">
                            <label>Fecha de Nacimiento:</label>
                            <input type="date" id="mat_fecha_nacimiento" name="fecha_nacimiento" min="2004-01-01" max="2012-12-31">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Nombres (2 nombres):</label>
                            <input type="text" id="mat_nombres" name="nombres" pattern="[A-Za-zÁáÉéÍíÓóÚúÑñ\s]+" placeholder="Ej: Juan Carlos">
                        </div>
                        <div class="form-col">
                            <label>Apellidos (2 apellidos):</label>
                            <input type="text" id="mat_apellidos" name="apellidos" pattern="[A-Za-zÁáÉéÍíÓóÚúÑñ\s]+" placeholder="Ej: Pérez López">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>DUI:</label>
                            <input type="text" id="mat_dui" name="dui" pattern="\d{8}-\d" maxlength="10" placeholder="00000000-0" title="Formato: 00000000-0 (8 dígitos, guión, 1 dígito)" disabled style="opacity: 0.5; cursor: not-allowed; background: #f3f4f6;">
                        </div>
                        <div class="form-col">
                            <label>Edad:</label>
                            <input type="text" id="mat_edad" name="edad" readonly placeholder="Cálculo automático" style="background: #f3f4f6; cursor: not-allowed; text-align: center; font-style: italic; color: #9ca3af;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Teléfono:</label>
                            <input type="tel" id="mat_telefono" name="telefono" pattern="\d{4}-\d{4}" maxlength="9" placeholder="0000-0000" title="Formato: 0000-0000">
                        </div>
                        <div class="form-col">
                            <label>Email:</label>
                            <input type="email" id="mat_email" name="email" placeholder="estudiante@gmail.com" title="Correo electrónico">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Dirección:</label>
                            <input type="text" id="mat_direccion" name="direccion" placeholder="Dirección del estudiante">
                        </div>
                    </div>
                </div>

                <hr class="divider">

                <!-- SECCIÓN Y ESTADO (Estado readonly) -->
                <h4><i class="fa-solid fa-school"></i> Información Académica</h4>
                
                <div class="form-row">
                    <div class="form-col">
                        <label>Sección:</label>
                        <select name="id_seccion">
                            <option value="">-- Seleccione una sección --</option>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?php echo $s['id']; ?>">
                                    <?php echo htmlspecialchars($s['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Estado del Estudiante:</label>
                        <input type="text" value="Activo" readonly style="background: #f3f4f6; cursor: not-allowed; opacity: 0.7; color: #15803d; font-weight: 600;">
                        <input type="hidden" name="estado" value="activo">
                        <p style="font-size: 11px; color: #6b7280; margin-top: 5px;">
                            <i class="fa-solid fa-info-circle"></i> El estado se gestiona desde el panel de Estudiantes
                        </p>
                    </div>
                </div>

                <!-- BOTONES DEL PASO 1 -->
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalMatricula').close()">Cancelar</button>
                    <button type="button" class="btn-save" onclick="siguientePaso()">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>
            <!-- ========== FIN PASO 1 ========== -->

            <!-- ========== PASO 2: RESPONSABLE ========== -->
            <div id="paso2" style="display: none;">
                <h4><i class="fa-solid fa-address-book"></i> Contacto del Responsable</h4>
                <p style="font-size: 12px; color: #6b7280; margin-bottom: 15px;">
                    <i class="fa-solid fa-info-circle"></i> Si ingresas un DUI ya registrado, los datos se completarán automáticamente.
                </p>

                <div class="form-row">
                    <div class="form-col">
                        <label>DUI del Responsable:</label>
                        <input type="text" name="responsable_dui" pattern="\d{8}-\d" maxlength="10" placeholder="00000000-0" title="Formato: 00000000-0">
                    </div>
                    <div class="form-col">
                        <label>Ocupación:</label>
                        <input type="text" name="responsable_ocupacion" placeholder="Ej: Profesor">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres del Responsable (2 nombres):</label>
                        <input type="text" name="responsable_nombres" required pattern="[A-Za-zÁáÉéÍíÓóÚúÑñ\s]+" placeholder="Ej: María Elena">
                    </div>
                    <div class="form-col">
                        <label>Apellidos del Responsable (2 apellidos):</label>
                        <input type="text" name="responsable_apellidos" required pattern="[A-Za-zÁáÉéÍíÓóÚúÑñ\s]+" placeholder="Ej: García López">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Parentesco:</label>
                        <select name="responsable_parentesco">
                            <option value="">-- Seleccione --</option>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Tío">Tío</option>
                            <option value="Tía">Tía</option>
                            <option value="Abuelo">Abuelo</option>
                            <option value="Abuela">Abuela</option>
                            <option value="Hermano">Hermano</option>
                            <option value="Hermana">Hermana</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Email (Solo Gmail):</label>
                        <input type="email" name="responsable_email" required pattern="[a-zA-Z0-9._%+-]+@gmail\.com" placeholder="responsable@gmail.com" title="Debe ser un correo @gmail.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Número de Teléfono:</label>
                        <input type="tel" name="responsable_telefono" required pattern="\d{4}-\d{4}" maxlength="9" placeholder="0000-0000" title="Formato: 0000-0000">
                    </div>
                    <div class="form-col">
                        <label>Dirección:</label>
                        <input type="text" name="responsable_direccion" placeholder="Dirección del responsable">
                    </div>
                </div>

                <!-- BOTONES DEL PASO 2 -->
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="pasoAnterior()"><i class="fa-solid fa-arrow-left"></i> Atrás</button>
                    <button type="submit" class="btn-save">Guardar Matrícula</button>
                </div>
            </div>
            <!-- ========== FIN PASO 2 ========== -->
        </form>
    </dialog>

    <!-- MODAL: VER DETALLES -->
    <dialog id="modalVer" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Información de la Matrícula</h3>
        <div id="detalleMatricula" class="modal-form" style="padding: 0 25px 25px;"></div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalVer').close()">Cerrar</button>
        </div>
    </dialog>

    <!-- MODAL: EDITAR MATRÍCULA -->
    <dialog id="modalEditar" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Matrícula</h3>
        <form action="../actions/matricula_action.php" method="POST" class="modal-form" id="formEditar">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="matricula_id" id="edit_matricula_id">
            <input type="hidden" name="id_estudiante" id="edit_id_estudiante_hidden">
            
            <!-- PASO 1: DATOS DE LA MATRÍCULA -->
            <div class="modal-step" id="edit_paso1">
                <h4><i class="fa-solid fa-user-graduate"></i> Datos de la Matrícula</h4>
                
                <label>Estudiante:</label>
                <select id="edit_estudiante" name="id_estudiante">
                    <?php foreach ($estudiantes as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo $e['nombre_completo']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Sección:</label>
                <select id="edit_seccion" name="id_seccion" required>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
                <p id="info_filtro_seccion" style="font-size: 12px; color: #2563eb; margin-top: 6px; display: none;">
                    <i class="fa-solid fa-info-circle"></i> Solo puedes cambiar a secciones del mismo año académico
                </p>

                <label>Estado del Estudiante:</label>
                <select id="edit_estado" name="estado" required style="padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-weight: 600;">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
                <p style="font-size: 11px; color: #6b7280; margin-top: 5px;">
                    <i class="fa-solid fa-info-circle"></i> Este cambio se reflejará también en el panel de Estudiantes
                </p>

                <hr class="divider">

                <h4><i class="fa-solid fa-address-book"></i> Contacto del Estudiante</h4>
                <div class="form-row">
                    <div class="form-col">
                        <label>DUI:</label>
                        <input type="text" id="edit_est_dui" name="est_dui" maxlength="10" placeholder="00000000-0" pattern="\d{8}-\d">
                    </div>
                    <div class="form-col">
                        <label>Teléfono:</label>
                        <input type="tel" id="edit_est_telefono" name="est_telefono" maxlength="9" placeholder="0000-0000" pattern="\d{4}-\d{4}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Email (Solo Gmail):</label>
                        <input type="email" id="edit_est_email" name="est_email" placeholder="estudiante@gmail.com">
                    </div>
                    <div class="form-col">
                        <label>Dirección:</label>
                        <input type="text" id="edit_est_direccion" name="est_direccion" placeholder="Dirección del estudiante">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                    <button type="button" class="btn-save" onclick="mostrarPasoEditar(2)">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- PASO 2: DATOS DEL RESPONSABLE -->
            <div class="modal-step" id="edit_paso2" style="display: none;">
                <h4><i class="fa-solid fa-user-tie"></i> Datos del Responsable</h4>

                <label>DUI del Responsable:</label>
                <input type="text" id="edit_resp_dui" name="responsable_dui" required>

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres:</label>
                        <input type="text" id="edit_resp_nombres" name="responsable_nombres" required>
                    </div>
                    <div class="form-col">
                        <label>Apellidos:</label>
                        <input type="text" id="edit_resp_apellidos" name="responsable_apellidos" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Ocupación:</label>
                        <input type="text" id="edit_resp_ocupacion" name="responsable_ocupacion" required>
                    </div>
                    <div class="form-col">
                        <label>Parentesco:</label>
                        <select id="edit_resp_parentesco" name="responsable_parentesco" required>
                            <option value="">-- Seleccione --</option>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Tío">Tío</option>
                            <option value="Tía">Tía</option>
                            <option value="Abuelo">Abuelo</option>
                            <option value="Abuela">Abuela</option>
                            <option value="Hermano">Hermano</option>
                            <option value="Hermana">Hermana</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <hr class="divider">

                <h4><i class="fa-solid fa-address-book"></i> Contacto del Responsable</h4>

                <label>Email:</label>
                <input type="email" id="edit_resp_email" name="responsable_email" required>

                <label>Teléfono:</label>
                <input type="tel" id="edit_resp_telefono" name="responsable_telefono" required maxlength="9">

                <label>Dirección:</label>
                <input type="text" id="edit_resp_direccion" name="responsable_direccion" required>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="mostrarPasoEditar(1)"><i class="fa-solid fa-arrow-left"></i> Atrás</button>
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                    <button type="submit" class="btn-save">Actualizar</button>
                </div>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>