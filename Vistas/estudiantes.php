<?php
// Vistas/estudiantes.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/permisos.php';

// ==========================================
// CONSULTA DE ESTUDIANTES (CON FILTRO DOCENTE)
// ==========================================

// ✅ Obtener secciones del docente primero
$seccionesDocenteIds = [];
if (esDocente()) {
    $seccionesDocenteIds = getSeccionesDocente($pdo);
}

// Construir consulta base con DISTINCT para evitar duplicados
$sqlEstudiantes = "SELECT DISTINCT
            e.id, e.nie, e.nombres, e.apellidos, e.dui, e.edad, e.fecha_nacimiento,
            e.telefono, e.direccion, e.email, e.estado,
            s.nombre as seccion_nombre, s.id as id_seccion
          FROM estudiantes e
          INNER JOIN matriculas m ON e.id = m.id_estudiante AND m.estado = 'Activo'
          INNER JOIN secciones s ON m.id_seccion = s.id";

// ✅ FILTRO PARA DOCENTES: Solo estudiantes de sus secciones
if (esDocente() && !empty($seccionesDocenteIds)) {
    $idsSeguros = array_map('intval', $seccionesDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    $sqlEstudiantes .= " WHERE s.id IN ($idsStr)";
    // DEBUG: Descomenta esto si quieres ver la consulta
    // echo "<!-- SQL: $sqlEstudiantes -->";
} else if (esDocente()) {
    // Docente sin secciones asignadas
    $sqlEstudiantes .= " WHERE 1=0";
}

$sqlEstudiantes .= " ORDER BY e.nombres";

// DEBUG: Ver consulta ejecutada
// echo "<pre>SQL: $sqlEstudiantes</pre>"; exit;

$estudiantes = $pdo->query($sqlEstudiantes)->fetchAll();

// DEBUG: Ver resultados
// echo "<pre>Resultados: "; print_r($estudiantes); echo "</pre>"; exit;

$totalEstudiantes = count($estudiantes);

// ✅ Estadísticas filtradas para docentes
if (esDocente() && !empty($seccionesDocenteIds)) {
    $idsSeguros = array_map('intval', $seccionesDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    
    $estudiantesActivos = $pdo->query("
        SELECT COUNT(DISTINCT e.id) FROM estudiantes e
        INNER JOIN matriculas m ON e.id = m.id_estudiante AND m.estado = 'Activo'
        WHERE m.id_seccion IN ($idsStr) AND e.estado = 'activo'
    ")->fetchColumn();
    
    $estudiantesInactivos = $pdo->query("
        SELECT COUNT(DISTINCT e.id) FROM estudiantes e
        INNER JOIN matriculas m ON e.id = m.id_estudiante AND m.estado = 'Activo'
        WHERE m.id_seccion IN ($idsStr) AND e.estado = 'inactivo'
    ")->fetchColumn();
    
    $totalSecciones = count($seccionesDocenteIds);
} else if (esDocente()) {
    $estudiantesActivos = 0;
    $estudiantesInactivos = 0;
    $totalSecciones = 0;
} else {
    $estudiantesActivos = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'activo'")->fetchColumn();
    $estudiantesInactivos = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'inactivo'")->fetchColumn();
    $totalSecciones = $pdo->query("SELECT COUNT(DISTINCT id_seccion) FROM matriculas")->fetchColumn();
}

// Obtener secciones para el filtro (filtradas si es docente)
$sqlSecciones = "SELECT * FROM secciones";
if (esDocente() && !empty($seccionesDocenteIds)) {
    $idsSeguros = array_map('intval', $seccionesDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    $sqlSecciones .= " WHERE id IN ($idsStr)";
} else if (esDocente()) {
    $sqlSecciones .= " WHERE 1=0";
}
$sqlSecciones .= " ORDER BY nombre";
$secciones = $pdo->query($sqlSecciones)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../CSS/estudiantes.css">
    <script src="../JS/estudiantes.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Gestión de Estudiantes - Sistema Académico</title>
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
                    <li><a href="estudiantes.php" class="active"><i class="fa-solid fa-children"></i> Estudiantes</a></li>
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
        <h2>Directorio de Estudiantes</h2>
        <p>Vista general y consulta de estudiantes matriculados en el sistema</p>

        <!-- ESTADÍSTICAS -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalEstudiantes; ?></span>
                <span class="stat-label">Total estudiantes</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $estudiantesActivos; ?></span>
                <span class="stat-label">Activos</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $estudiantesInactivos; ?></span>
                <span class="stat-label">Inactivos</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalSecciones; ?></span>
                <span class="stat-label">Secciones</span>
            </article>
        </section>

        <!-- BARRA DE BÚSQUEDA Y FILTROS -->
        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscador-estudiantes" placeholder="Buscar por NIE, nombre o apellido..." />
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
        </section>

        <!-- TABLA DE DATOS -->
        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NIE</th>
                        <th scope="col">Nombres</th>
                        <th scope="col">Apellidos</th>
                        <th scope="col">Sección</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaEstudiantes">
                    <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px;">No hay estudiantes registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $est): ?>
                            <tr data-id="<?php echo $est['id']; ?>"
                                data-nie="<?php echo htmlspecialchars($est['nie'] ?? ''); ?>"
                                data-nombres="<?php echo htmlspecialchars($est['nombres'] ?? ''); ?>"
                                data-apellidos="<?php echo htmlspecialchars($est['apellidos'] ?? ''); ?>"
                                data-seccion="<?php echo htmlspecialchars($est['seccion_nombre'] ?? 'Sin matrícula'); ?>"
                                data-estado="<?php echo htmlspecialchars($est['estado'] ?? 'activo'); ?>"
                                data-dui="<?php echo htmlspecialchars($est['dui'] ?? ''); ?>"
                                data-edad="<?php echo htmlspecialchars($est['edad'] ?? ''); ?>"
                                data-fecha-nac="<?php echo htmlspecialchars($est['fecha_nacimiento'] ?? ''); ?>"
                                data-telefono="<?php echo htmlspecialchars($est['telefono'] ?? ''); ?>"
                                data-direccion="<?php echo htmlspecialchars($est['direccion'] ?? ''); ?>"
                                data-email="<?php echo htmlspecialchars($est['email'] ?? ''); ?>">
                                <td><?php echo $est['nie']; ?></td>
                                <td><?php echo $est['nombres']; ?></td>
                                <td><?php echo $est['apellidos']; ?></td>
                                <td><?php echo $est['seccion_nombre'] ?? 'Sin matrícula'; ?></td>
                                <td>
                                    <?php if ($est['estado'] === 'activo'): ?>
                                        <span class="badge active">Activo</span>
                                    <?php else: ?>
                                        <span class="badge inactive">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action see" onclick="verEstudiante(this)" title="Ver detalles"><i class="fa-solid fa-eye"></i></button>
                                    <?php if (!esDocente()): ?>
                                        <button type="button" class="btn-action edit" onclick="editarEstudiante(this)" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <a href="../actions/estudiantes_action.php?accion=eliminar&id=<?php echo $est['id']; ?>" class="btn-action delete" onclick="return confirm('¿Estás seguro de eliminar a este estudiante?')" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- ESTUDIANTES POR SECCIÓN -->
        <section class="students-by-section">
            <h2>Estudiantes por Sección</h2>
            <div class="section-grid">
                <?php 
                // ✅ Consulta filtrada para docentes
                $sqlDesglose = "SELECT 
                                    s.nombre as seccion,
                                    COUNT(m.id_estudiante) as total
                                  FROM secciones s
                                  LEFT JOIN matriculas m ON s.id = m.id_seccion AND m.estado = 'Activo'";
                
                if (esDocente()) {
                    $seccionesIds = getSeccionesDocente($pdo);
                    if (!empty($seccionesIds)) {
                        $idsSeguros = array_map('intval', $seccionesIds);
                        $idsStr = implode(',', $idsSeguros);
                        $sqlDesglose .= " WHERE s.id IN ($idsStr)";
                    } else {
                        $sqlDesglose .= " WHERE 1=0";
                    }
                }
                
                $sqlDesglose .= " GROUP BY s.id ORDER BY s.nombre";
                $desgloseSecciones = $pdo->query($sqlDesglose)->fetchAll();
                
                if (empty($desgloseSecciones)): ?>
                    <p class="empty-state">Aún no hay secciones creadas para mostrar el desglose.</p>
                <?php else: ?>
                    <?php foreach ($desgloseSecciones as $sec): ?>
                        <div class="stat-item" style="text-align: center;">
                            <span class="stat-number" style="color: #9C27B0;"><?php echo $sec['total']; ?></span>
                            <span class="stat-label" style="font-size: 16px; font-weight: 600;"><?php echo $sec['seccion']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- MODAL VER ESTUDIANTE -->
    <dialog id="modalVerEstudiante" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Detalles del Estudiante</h3>
        <div class="modal-form" id="contenidoVerEstudiante"></div>
    </dialog>

    <?php if (!esDocente()): ?>
    <!-- MODAL EDITAR ESTUDIANTE (SOLO NO DOCENTES) -->
    <dialog id="modalEditarEstudiante" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Estudiante</h3>
        <form class="modal-form" method="POST" action="../actions/estudiantes_action.php">
            <input type="hidden" name="id_estudiante" id="edit_id">
            <input type="hidden" name="accion" value="editar">
            
            <h4><i class="fa-solid fa-user"></i> Datos personales</h4>
            <div class="form-row">
                <div class="form-col">
                    <label>NIE (máx. 10 dígitos):</label>
                    <input type="text" name="nie" id="edit_nie" class="input-nie" required maxlength="10" placeholder="0000000000" />
                </div>
                <div class="form-col">
                    <label>Nombres (2 nombres):</label>
                    <input type="text" name="nombres" id="edit_nombres" class="input-nombre" required placeholder="Ej: Juan Carlos" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label>Apellidos (2 apellidos):</label>
                    <input type="text" name="apellidos" id="edit_apellidos" class="input-nombre" required placeholder="Ej: Pérez López" />
                </div>
                <div class="form-col">
                    <label>DUI:</label>
                    <input type="text" name="dui" id="edit_dui" maxlength="10" placeholder="00000000-0" pattern="\d{8}-\d" title="Formato: 00000000-0" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label>Estado Actual:</label>
                    <input type="text" id="edit_estado_display" readonly 
                        style="background: #f3f4f6; color: #6b7280; cursor: not-allowed; opacity: 0.7; font-weight: 600;" 
                        placeholder="Activo" />
                    <input type="hidden" name="estado" id="edit_estado" value="activo">
                </div>
                <div class="form-col">
                    <label>Sección Actual:</label>
                    <input type="text" id="edit_seccion_display" readonly 
                        style="background: #f3f4f6; color: #6b7280; cursor: not-allowed; opacity: 0.7;" 
                        placeholder="Sin matrícula" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-col" style="grid-column: 1 / -1;">
                    <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                        <i class="fa-solid fa-info-circle"></i> 
                        Para cambiar el estado o la sección, ve al panel de <strong>Matrículas</strong>
                    </p>
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-address-book"></i> Contacto</h4>
            <div class="form-row">
                <div class="form-col">
                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" id="edit_telefono" maxlength="9" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="Formato: 0000-0000" />
                </div>
                <div class="form-col">
                    <label>Email (Solo Gmail):</label>
                    <input type="email" name="email" id="edit_email" placeholder="estudiante@gmail.com" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-col" style="grid-column: 1 / -1;">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" id="edit_direccion" placeholder="Dirección del estudiante" />
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditarEstudiante').close()">Cancelar</button>
                <button type="submit" class="btn-save">Actualizar</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>