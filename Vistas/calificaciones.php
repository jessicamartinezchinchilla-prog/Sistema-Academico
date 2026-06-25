<?php
// Vistas/calificaciones.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// ==========================================
// FILTROS PARA DOCENTES
// ==========================================
$seccionesDocenteIds = [];
$materiasDocenteIds = [];
$estudiantesDocenteIds = [];

if (esDocente()) {
    $seccionesDocenteIds = getSeccionesDocente($pdo);
    $materiasDocenteIds = getMateriasDocente($pdo);
    
    // Obtener IDs de estudiantes de las secciones del docente
    if (!empty($seccionesDocenteIds)) {
        $idsSeguros = array_map('intval', $seccionesDocenteIds);
        $idsStr = implode(',', $idsSeguros);
        $estudiantesDocenteIds = $pdo->query("
            SELECT DISTINCT e.id 
            FROM estudiantes e
            INNER JOIN matriculas m ON e.id = m.id_estudiante AND m.estado = 'Activo'
            WHERE m.id_seccion IN ($idsStr)
        ")->fetchAll(PDO::FETCH_COLUMN);
    }
}

// ✅ CORRECCIÓN: Calcular promedios con lógica Opción B (suma / 4)
// Primero obtenemos todas las calificaciones individuales
$query = "SELECT 
            e.id as id_estudiante,
            e.nie,
            CONCAT(e.nombres, ' ', e.apellidos) as nombre_estudiante,
            s.nombre as nombre_seccion,
            c.id_materia,
            c.nota
          FROM estudiantes e
          INNER JOIN matriculas m ON e.id = m.id_estudiante
          INNER JOIN calificaciones c ON e.id = c.id_estudiante
          INNER JOIN secciones s ON m.id_seccion = s.id
          WHERE e.estado = 'activo' AND m.estado = 'Activo'";

// ✅ FILTRO PARA DOCENTES
if (esDocente() && !empty($estudiantesDocenteIds)) {
    $idsSeguros = array_map('intval', $estudiantesDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    $query .= " AND e.id IN ($idsStr)";
} else if (esDocente()) {
    $query .= " AND 1=0";
}

$query .= " ORDER BY e.nombres";

$calificaciones_raw = $pdo->query($query)->fetchAll();

// ✅ Calcular promedios por estudiante usando lógica Opción B
$promediosEstudiantes = [];

foreach ($calificaciones_raw as $cal) {
    $idEst = $cal['id_estudiante'];
    $idMateria = $cal['id_materia'];
    
    if (!isset($promediosEstudiantes[$idEst])) {
        $promediosEstudiantes[$idEst] = [
            'nie' => $cal['nie'],
            'nombre' => $cal['nombre_estudiante'],
            'seccion' => $cal['nombre_seccion'],
            'materias' => []
        ];
    }
    
    if (!isset($promediosEstudiantes[$idEst]['materias'][$idMateria])) {
        $promediosEstudiantes[$idEst]['materias'][$idMateria] = [];
    }
    $promediosEstudiantes[$idEst]['materias'][$idMateria][] = $cal['nota'];
}

// ✅ Calcular promedio por materia (suma / 4) y promedio general
foreach ($promediosEstudiantes as $idEst => &$est) {
    $sumaTotal = 0;
    $cantidadMaterias = count($est['materias']);
    
    foreach ($est['materias'] as $idMateria => $notas) {
        $promedioMateria = array_sum($notas) / 4;
        $sumaTotal += $promedioMateria;
    }
    
    $est['promedio_general'] = $cantidadMaterias > 0 ? round($sumaTotal / $cantidadMaterias, 2) : 0;
    $est['estado'] = $est['promedio_general'] >= 6 ? 'Aprobado' : 'Reprobado';
}

// Estadísticas
$totalRegistros = count($promediosEstudiantes);
$totalAprobados = count(array_filter($promediosEstudiantes, fn($e) => $e['estado'] === 'Aprobado'));
$totalReprobados = count(array_filter($promediosEstudiantes, fn($e) => $e['estado'] === 'Reprobado'));
$promedioGeneral = $totalRegistros > 0 
    ? round(array_sum(array_column($promediosEstudiantes, 'promedio_general')) / $totalRegistros, 2) 
    : 0;

// ✅ Datos para los filtros (FILTRADOS si es docente)
$sqlMaterias = "SELECT id, nombre FROM materias";
if (esDocente() && !empty($materiasDocenteIds)) {
    $idsSeguros = array_map('intval', $materiasDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    $sqlMaterias .= " WHERE id IN ($idsStr)";
} else if (esDocente()) {
    $sqlMaterias .= " WHERE 1=0";
}
$sqlMaterias .= " ORDER BY nombre";
$materias = $pdo->query($sqlMaterias)->fetchAll();

$sqlSecciones = "SELECT id, nombre FROM secciones";
if (esDocente() && !empty($seccionesDocenteIds)) {
    $idsSeguros = array_map('intval', $seccionesDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    $sqlSecciones .= " WHERE id IN ($idsStr)";
} else if (esDocente()) {
    $sqlSecciones .= " WHERE 1=0";
}
$sqlSecciones .= " ORDER BY nombre";
$secciones = $pdo->query($sqlSecciones)->fetchAll();

// ✅ Obtener estudiantes con sus matrículas activas (FILTRADOS si es docente)
$sqlEstudiantes = "
    SELECT 
        e.id, 
        e.nie, 
        CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
        m.id as id_matricula,
        m.id_seccion,
        s.nombre as nombre_seccion,
        s.id_carrera,
        s.id_grado,
        c.nombre as nombre_carrera
    FROM estudiantes e
    INNER JOIN matriculas m ON e.id = m.id_estudiante
    INNER JOIN secciones s ON m.id_seccion = s.id
    INNER JOIN carreras c ON s.id_carrera = c.id
    WHERE e.estado = 'activo' AND m.estado = 'Activo'";

if (esDocente() && !empty($estudiantesDocenteIds)) {
    $idsSeguros = array_map('intval', $estudiantesDocenteIds);
    $idsStr = implode(',', $idsSeguros);
    $sqlEstudiantes .= " AND e.id IN ($idsStr)";
} else if (esDocente()) {
    $sqlEstudiantes .= " AND 1=0";
}

$sqlEstudiantes .= " ORDER BY e.nombres";
$estudiantes = $pdo->query($sqlEstudiantes)->fetchAll();

$estudiantesJSON = json_encode($estudiantes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/calificaciones.css">
    <script src="../JS/calificaciones.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Gestión de Calificaciones - Sistema Académico</title>
    <script>
        window.estudiantesData = <?php echo $estudiantesJSON; ?>;
    </script>
    <style>
        .autocomplete-container {
            position: relative;
        }
        
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }
        
        .autocomplete-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        
        .autocomplete-item:hover {
            background: #f0f4ff;
        }
        
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        
        .autocomplete-item .nie {
            font-size: 12px;
            color: #6b7280;
        }
        
        .autocomplete-item .nombre {
            font-weight: 500;
            color: #374151;
        }
        
        .periodo-bloqueado {
            background: #f3f4f6 !important;
            cursor: not-allowed !important;
            opacity: 0.5;
        }
        
        .periodo-bloqueado:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }
    </style>
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
                    <li><a href="calificaciones.php" class="active"><i class="fa-solid fa-award"></i> Calificaciones</a></li>
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
        <h2>Gestión de Calificaciones</h2>
        <p>Registro, consulta y control del rendimiento académico de los estudiantes</p>

        <section class="actions-bar">
            <button type="button" class="button btn-secondary" onclick="abrirModalPDF()">
                <i class="fa-solid fa-file-pdf"></i> Generar PDF
            </button>
            <?php if (puedeModificarCalificaciones()): ?>
            <button type="button" class="button btn-primary" onclick="abrirModalNota()">
                <i class="fa-solid fa-plus"></i> Agregar nota
            </button>
            <?php endif; ?>
        </section>

        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number" id="totalAprobados"><?php echo $totalAprobados; ?></span>
                <span class="stat-label">Aprobados</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" id="totalReprobados"><?php echo $totalReprobados; ?></span>
                <span class="stat-label">Reprobados</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" id="promedioGeneral"><?php echo $promedioGeneral; ?></span>
                <span class="stat-label">Promedio general</span>
            </article>
        </section>

        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarCalificacion" placeholder="Buscar por NIE, nombre o apellido...">
            </div>

            <div class="filtros">
                <select id="filtroMateria">
                    <option value="">Todas las materias</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?php echo $m['nombre']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroSeccion">
                    <option value="">Todas las secciones</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['nombre']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="Aprobado">Aprobado</option>
                    <option value="Reprobado">Reprobado</option>
                </select>
            </div>
        </section>

        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NIE</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Sección</th>
                        <th scope="col">Promedio</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaCalificaciones">
                    <?php if (empty($promediosEstudiantes)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px;">No hay calificaciones registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($promediosEstudiantes as $idEst => $est): ?>
                            <tr data-id="<?php echo $idEst; ?>"
                                data-nie="<?php echo htmlspecialchars($est['nie']); ?>"
                                data-nombre="<?php echo htmlspecialchars($est['nombre']); ?>"
                                data-seccion="<?php echo htmlspecialchars($est['seccion']); ?>"
                                data-promedio="<?php echo $est['promedio_general']; ?>"
                                data-estado="<?php echo $est['estado']; ?>">
                                <td><?php echo $est['nie']; ?></td>
                                <td><?php echo $est['nombre']; ?></td>
                                <td><?php echo $est['seccion']; ?></td>
                                <td class="promedio"><?php echo number_format($est['promedio_general'], 2); ?></td>
                                <td>
                                    <?php if ($est['estado'] === 'Aprobado'): ?>
                                        <span class="estado-aprobado">Aprobado</span>
                                    <?php else: ?>
                                        <span class="estado-reprobado">Reprobado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action see" onclick="verDetalleEstudiante(this)" title="Ver detalle por materia">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <?php if (puedeModificarCalificaciones()): ?>
                                    <button type="button" class="btn-action edit" onclick="abrirModalNota(this)" title="Modificar/Editar calificación">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- MODAL: AGREGAR/EDITAR NOTA -->
    <dialog id="modalNota" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3 id="tituloModalNota">Agregar Nueva Calificación</h3>
        <form action="../actions/calificaciones_action.php" method="POST" class="modal-form" id="formNota" onsubmit="return validarFormularioNota(this)">
            <input type="hidden" name="accion" value="agregar" id="accion_form">
            <input type="hidden" name="calificacion_id" id="edit_calificacion_id">
            
            <label>Estudiante:</label>
            <div class="autocomplete-container">
                <input type="text" id="buscador_estudiante" placeholder="Escribe el nombre o NIE del estudiante..." autocomplete="off">
                <input type="hidden" id="nota_estudiante" name="id_estudiante">
                <div class="autocomplete-results" id="autocomplete_results"></div>
            </div>
            <div id="estudiante_seleccionado" style="display: none; padding: 10px; background: #f0f4ff; border-radius: 8px; margin-top: 10px;">
                <span id="estudiante_nombre"></span>
                <button type="button" onclick="limpiarEstudiante()" style="float: right; background: none; border: none; color: #dc2626; cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <label>Carrera:</label>
            <input type="text" id="nota_carrera_display" readonly placeholder="Se selecciona automáticamente" 
                   style="background: #f3f4f6; cursor: not-allowed; opacity: 0.7;">
            <input type="hidden" id="nota_carrera" name="id_carrera">
            <input type="hidden" id="nota_grado" name="id_grado">

            <label>Sección:</label>
            <input type="text" id="nota_seccion_display" readonly placeholder="Se selecciona automáticamente" 
                   style="background: #f3f4f6; cursor: not-allowed; opacity: 0.7;">
            <input type="hidden" id="nota_seccion" name="id_seccion">

            <label>Materia:</label>
            <select id="nota_materia" name="id_materia" required>
                <option value="">Seleccione Materia</option>
            </select>
            <small style="color: #6b7280; font-size: 12px;">
                <i class="fa-solid fa-info-circle"></i> Solo se muestran las materias de la carrera y grado del estudiante
            </small>

            <label>Períodos:</label>
            <div id="contenedor_periodos" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                <div>
                    <label style="font-size: 13px; color: #6b7280;">Período 1:</label>
                    <input type="number" id="periodo_1" name="periodo_1" min="0" max="10" step="0.01" placeholder="Nota P1" onchange="verificarBloqueoPeriodos()">
                </div>
                <div>
                    <label style="font-size: 13px; color: #6b7280;">Período 2:</label>
                    <input type="number" id="periodo_2" name="periodo_2" min="0" max="10" step="0.01" placeholder="Nota P2" disabled class="periodo-bloqueado" onchange="verificarBloqueoPeriodos()">
                </div>
                <div>
                    <label style="font-size: 13px; color: #6b7280;">Período 3:</label>
                    <input type="number" id="periodo_3" name="periodo_3" min="0" max="10" step="0.01" placeholder="Nota P3" disabled class="periodo-bloqueado" onchange="verificarBloqueoPeriodos()">
                </div>
                <div>
                    <label style="font-size: 13px; color: #6b7280;">Período 4:</label>
                    <input type="number" id="periodo_4" name="periodo_4" min="0" max="10" step="0.01" placeholder="Nota P4" disabled class="periodo-bloqueado" onchange="verificarBloqueoPeriodos()">
                </div>
            </div>
            <small id="nota_estado_msg" style="color: #6b7280; font-size: 12px; margin-top: 5px; display: block;">Complete los períodos en orden</small>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalNota').close()">Cancelar</button>
                <button type="submit" class="btn-save" id="btn_guardar_nota">Guardar Calificación</button>
            </div>
        </form>
    </dialog>

    <!-- MODAL: VER DETALLE POR MATERIA -->
    <dialog id="modalDetalle" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Detalle de Calificaciones</h3>
        <div id="detalleContenido" class="modal-form" style="padding: 0 25px 25px;"></div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalDetalle').close()">Cerrar</button>
        </div>
    </dialog>

    <!-- MODAL: GENERAR PDF -->
    <dialog id="modalPDF" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Generar Reporte PDF</h3>
        <form action="../actions/generar_pdf.php" method="POST" class="modal-form">
            <label>Tipo de Reporte:</label>
            <select id="pdf_tipo" name="tipo_reporte" required onchange="mostrarOpcionesPDF()">
                <option value="">Seleccione tipo de reporte</option>
                <option value="individual">Estudiante Individual</option>
                <option value="multiples">Múltiples Estudiantes</option>
                <option value="seccion">Sección Completa</option>
                <option value="general">Reporte General</option>
            </select>

            <div id="opcion_individual" style="display:none;">
                <label>Estudiante:</label>
                <select id="pdf_estudiante_individual" name="estudiante_id">
                    <option value="">Seleccione Estudiante</option>
                    <?php foreach ($estudiantes as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="opcion_multiples" style="display:none;">
                <label>Seleccionar Estudiantes:</label>
                <div id="lista_estudiantes_checkboxes" 
                     style="max-height: 200px; overflow-y: auto; border: 1px solid #d1d5db; 
                            border-radius: 8px; padding: 10px; background: #f9fafb;">
                    <?php foreach ($estudiantes as $e): ?>
                        <label style="display: block; padding: 8px; cursor: pointer;">
                            <input type="checkbox" name="estudiantes[]" value="<?php echo $e['id']; ?>">
                            <?php echo htmlspecialchars($e['nombre_completo'] . ' (NIE: ' . $e['nie'] . ')'); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small style="color: #6b7280; font-size: 12px;">Seleccione uno o varios estudiantes</small>
            </div>

            <div id="opcion_seccion" style="display:none;">
                <label>Sección:</label>
                <select id="pdf_seccion" name="seccion_id">
                    <option value="">Seleccione Sección</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="divider">

            <label>Filtrar por Materia (opcional):</label>
            <select id="pdf_materia" name="materia_id">
                <option value="">Todas las materias</option>
                <?php foreach ($materias as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Período (opcional):</label>
            <select id="pdf_periodo" name="periodo">
                <option value="">Todos los períodos</option>
                <option value="1">Primer Período</option>
                <option value="2">Segundo Período</option>
                <option value="3">Tercer Período</option>
                <option value="4">Cuarto Período</option>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalPDF').close()">Cancelar</button>
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-file-pdf"></i> Generar PDF
                </button>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>