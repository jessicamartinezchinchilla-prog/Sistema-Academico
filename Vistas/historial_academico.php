<?php
// Vistas/historial_academico.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// ✅ CORRECCIÓN: Calcular promedios con lógica Opción B (suma / 4)
// Primero obtenemos las calificaciones individuales
$queryCalificaciones = "
    SELECT 
        e.id as id_estudiante,
        e.nie,
        CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
        e.estado as estado_estudiante,
        s.nombre as nombre_seccion,
        c.nombre as nombre_carrera,
        YEAR(m.fecha_registro) as anio_matricula,
        cal.id_materia,
        cal.nota
    FROM estudiantes e
    INNER JOIN matriculas m ON e.id = m.id_estudiante
    INNER JOIN secciones s ON m.id_seccion = s.id
    INNER JOIN carreras c ON s.id_carrera = c.id
    LEFT JOIN calificaciones cal ON e.id = cal.id_estudiante AND cal.anio = YEAR(m.fecha_registro)
    ORDER BY e.nombres, anio_matricula DESC
";

$calificacionesRaw = $pdo->query($queryCalificaciones)->fetchAll();

// ✅ Agrupar y calcular promedios con lógica Opción B (suma / 4)
$resumenMap = [];

foreach ($calificacionesRaw as $cal) {
    $idEst = $cal['id_estudiante'];
    $anio = $cal['anio_matricula'];
    $key = $idEst . '_' . $anio;
    
    if (!isset($resumenMap[$key])) {
        $resumenMap[$key] = [
            'id_estudiante' => $idEst,
            'nie' => $cal['nie'],
            'nombre_completo' => $cal['nombre_completo'],
            'estado_estudiante' => $cal['estado_estudiante'],
            'nombre_seccion' => $cal['nombre_seccion'],
            'nombre_carrera' => $cal['nombre_carrera'],
            'anio_matricula' => $anio,
            'materias' => []
        ];
    }
    
    // Agrupar notas por materia
    if ($cal['id_materia'] !== null) {
        $idMateria = $cal['id_materia'];
        if (!isset($resumenMap[$key]['materias'][$idMateria])) {
            $resumenMap[$key]['materias'][$idMateria] = [];
        }
        $resumenMap[$key]['materias'][$idMateria][] = floatval($cal['nota']);
    }
}

// ✅ Calcular promedios finales (suma de notas / 4 por materia, luego promedio de materias)
$resumen = [];
foreach ($resumenMap as $key => &$data) {
    $sumaPromediosMaterias = 0;
    $cantidadMaterias = count($data['materias']);
    
    foreach ($data['materias'] as $idMateria => $notas) {
        // Promedio de materia = suma de notas / 4
        $promedioMateria = array_sum($notas) / 4;
        $sumaPromediosMaterias += $promedioMateria;
    }
    
    // Promedio anual = promedio de los promedios por materia
    $data['promedio_anual'] = $cantidadMaterias > 0 
        ? round($sumaPromediosMaterias / $cantidadMaterias, 2) 
        : null;
    
    // Estado académico
    if ($data['promedio_anual'] === null) {
        $data['estado_academico'] = 'Sin notas';
    } elseif ($data['promedio_anual'] >= 6) {
        $data['estado_academico'] = 'Aprobado';
    } else {
        $data['estado_academico'] = 'Reprobado';
    }
    
    $resumen[] = $data;
}

// Estadísticas
$totalRegistros = count($resumen);
$totalAprobados = count(array_filter($resumen, fn($r) => $r['estado_academico'] === 'Aprobado'));
$totalReprobados = count(array_filter($resumen, fn($r) => $r['estado_academico'] === 'Reprobado'));
$promediosValidos = array_filter(array_column($resumen, 'promedio_anual'), fn($p) => $p !== null);
$promedioGeneral = count($promediosValidos) > 0 
    ? round(array_sum($promediosValidos) / count($promediosValidos), 2) 
    : 0;

// Datos para filtros
$secciones = $pdo->query("SELECT DISTINCT s.nombre FROM secciones s ORDER BY s.nombre")->fetchAll();
$anios = $pdo->query("SELECT DISTINCT YEAR(fecha_registro) as anio FROM matriculas ORDER BY anio DESC")->fetchAll();

$resumenJSON = json_encode($resumen);
$seccionesJSON = json_encode(array_column($secciones, 'nombre'));
$aniosJSON = json_encode(array_column($anios, 'anio'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/historial_academico.css">
    <script src="../JS/historial_academico.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Historial Académico - Sistema Académico</title>
    <script>
        window.historialData = <?php echo $resumenJSON; ?>;
        window.seccionesData = <?php echo $seccionesJSON; ?>;
        window.aniosData = <?php echo $aniosJSON; ?>;
    </script>
</head>
<body>
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
                <li><a href="historial_academico.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Historial académico</a></li>
                <li><a href="estadisticas.php"><i class="fa-solid fa-chart-column"></i> Estadísticas</a></li>
                <li><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <h2>Historial Académico</h2>
        <p>Registro completo del desempeño estudiantil a lo largo del tiempo</p>

        <section class="actions-bar">
            <button type="button" class="button btn-secondary" onclick="document.getElementById('modalPDF').showModal()">
                <i class="fa-solid fa-file-pdf"></i> Exportar en PDF
            </button>
        </section>

        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number" id="totalRegistros"><?php echo $totalRegistros; ?></span>
                <span class="stat-label">Total registros</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" id="totalAprobadosHist"><?php echo $totalAprobados; ?></span>
                <span class="stat-label">Aprobados</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" id="totalReprobadosHist"><?php echo $totalReprobados; ?></span>
                <span class="stat-label">Reprobados</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" id="promedioGeneralHist"><?php echo $promedioGeneral; ?></span>
                <span class="stat-label">Promedio general</span>
            </article>
        </section>

        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarHistorial" placeholder="Buscar por NIE o nombre...">
            </div>

            <div class="filtros">
                <select id="filtroSeccionHist">
                    <option value="">Todas las secciones</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['nombre']); ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroAnioHist">
                    <option value="">Todos los años</option>
                    <?php foreach ($anios as $a): ?>
                        <option value="<?php echo $a['anio']; ?>"><?php echo $a['anio']; ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroEstadoEstudianteHist">
                    <option value="">Todos los estudiantes</option>
                    <option value="activo">Solo activos</option>
                    <option value="inactivo">Solo inactivos</option>
                </select>

                <select id="filtroEstadoHist">
                    <option value="">Todos los estados académicos</option>
                    <option value="Aprobado">Aprobado</option>
                    <option value="Reprobado">Reprobado</option>
                    <option value="Sin notas">Sin notas</option>
                </select>
            </div>
        </section>

        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NIE</th>
                        <th scope="col">Nombre completo</th>
                        <th scope="col">Sección</th>
                        <th scope="col">Año</th>
                        <th scope="col">Promedio</th>
                        <th scope="col">Estado Académico</th>
                        <th scope="col">Estado Estudiante</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaHistorial">
                    <?php if (empty($resumen)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:40px;">No hay registros en el historial académico aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($resumen as $r): ?>
                            <tr data-id="<?php echo $r['id_estudiante']; ?>"
                                data-nie="<?php echo htmlspecialchars($r['nie']); ?>"
                                data-nombre="<?php echo htmlspecialchars($r['nombre_completo']); ?>"
                                data-seccion="<?php echo htmlspecialchars($r['nombre_seccion']); ?>"
                                data-anio="<?php echo $r['anio_matricula']; ?>"
                                data-promedio="<?php echo $r['promedio_anual'] ?? 0; ?>"
                                data-estado="<?php echo $r['estado_academico']; ?>"
                                data-estado-estudiante="<?php echo strtolower($r['estado_estudiante']); ?>">
                                <td><?php echo $r['nie']; ?></td>
                                <td><?php echo $r['nombre_completo']; ?></td>
                                <td><?php echo $r['nombre_seccion']; ?></td>
                                <td><?php echo $r['anio_matricula']; ?></td>
                                <td class="promedio"><?php echo $r['promedio_anual'] !== null ? number_format($r['promedio_anual'], 2) : '-'; ?></td>
                                <td>
                                    <?php if ($r['estado_academico'] === 'Aprobado'): ?>
                                        <span class="badge aprobado">Aprobado</span>
                                    <?php elseif ($r['estado_academico'] === 'Reprobado'): ?>
                                        <span class="badge reprobado">Reprobado</span>
                                    <?php else: ?>
                                        <span class="badge sin-notas">Sin notas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (strtolower($r['estado_estudiante']) === 'activo'): ?>
                                        <span class="badge activo">Activo</span>
                                    <?php else: ?>
                                        <span class="badge inactivo">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action see" onclick="verDetallesHistorial(this)" title="Ver timeline completo">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <p class="empty-state" id="mensajeVacio" style="display: <?php echo empty($resumen) ? 'block' : 'none'; ?>;">
            No hay registros en el historial académico aún.
        </p>
    </main>

    <!-- MODAL: TIMELINE DE DETALLES -->
    <dialog id="modalVerDetalles" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Historial Completo del Estudiante</h3>
        
        <div style="padding: 15px 25px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <label style="font-weight: 600; color: #374151; font-size: 13px;">Filtrar por tipo:</label>
                <select id="filtroTipoEvento" style="padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                    <option value="">Todos los eventos</option>
                    <option value="matricula">Matrículas</option>
                    <option value="nota">Calificaciones</option>
                    <option value="estado">Cambios de estado</option>
                    <option value="seccion">Cambios de sección</option>
                </select>
            </div>
        </div>
        
        <div class="modal-body" id="detalleContenidoHistorial" style="padding: 25px;">
            <div style="text-align: center; padding: 20px;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #2F6FED;"></i>
                <p style="margin-top: 10px; color: #6b7280;">Cargando historial...</p>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalVerDetalles').close()">Cerrar</button>
        </div>
    </dialog>

    <!-- MODAL: EXPORTAR PDF -->
    <dialog id="modalPDF" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Exportar Historial en PDF</h3>
        <form action="../actions/generar_pdf.php" method="POST" class="modal-form">
            <input type="hidden" name="tipo_reporte" value="historial">
            
            <label for="pdf_estudiante_hist">Estudiante (opcional):</label>
            <select id="pdf_estudiante_hist" name="estudiante_id">
                <option value="">Todos los estudiantes</option>
                <?php 
                $estudiantesPDF = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos, ' (', nie, ')') as nombre FROM estudiantes ORDER BY nombres")->fetchAll();
                foreach ($estudiantesPDF as $e): ?>
                    <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="pdf_anio_hist">Año (opcional):</label>
            <select id="pdf_anio_hist" name="anio">
                <option value="">Todos los años</option>
                <?php foreach ($anios as $a): ?>
                    <option value="<?php echo $a['anio']; ?>"><?php echo $a['anio']; ?></option>
                <?php endforeach; ?>
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