<?php
// Vistas/estadisticas.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// ==========================================
// 1. CONTADORES GENERALES
// ==========================================
$totalEstudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'activo'")->fetchColumn();
$totalMaterias = $pdo->query("SELECT COUNT(*) FROM materias")->fetchColumn();
$totalSecciones = $pdo->query("SELECT COUNT(*) FROM secciones")->fetchColumn();

// ==========================================
// 2. PROMEDIO GENERAL (Lógica Opción B: suma / 4)
// ==========================================
$queryPromedios = "
    SELECT c.id_estudiante, c.id_materia, c.nota
    FROM calificaciones c
    INNER JOIN estudiantes e ON c.id_estudiante = e.id AND e.estado = 'activo'
";
$calificacionesRaw = $pdo->query($queryPromedios)->fetchAll();

// Agrupar por estudiante y materia
$estudiantesMaterias = [];
foreach ($calificacionesRaw as $cal) {
    $idEst = $cal['id_estudiante'];
    $idMat = $cal['id_materia'];
    if (!isset($estudiantesMaterias[$idEst])) {
        $estudiantesMaterias[$idEst] = [];
    }
    if (!isset($estudiantesMaterias[$idEst][$idMat])) {
        $estudiantesMaterias[$idEst][$idMat] = [];
    }
    $estudiantesMaterias[$idEst][$idMat][] = floatval($cal['nota']);
}

// Calcular promedio general (promedio de promedios por materia / 4)
$promediosEstudiantes = [];
foreach ($estudiantesMaterias as $idEst => $materias) {
    $sumaPromedios = 0;
    foreach ($materias as $notas) {
        $sumaPromedios += array_sum($notas) / 4;
    }
    $promediosEstudiantes[] = count($materias) > 0 ? $sumaPromedios / count($materias) : 0;
}

$promedioGeneral = count($promediosEstudiantes) > 0 
    ? round(array_sum($promediosEstudiantes) / count($promediosEstudiantes), 2) 
    : 0;

// ==========================================
// 3. RENDIMIENTO POR MATERIA (para gráfica y tarjetas)
// ==========================================
$queryMaterias = "
    SELECT 
        m.id,
        m.nombre,
        c.nota,
        c.id_estudiante
    FROM materias m
    LEFT JOIN calificaciones c ON m.id = c.id_materia
    INNER JOIN estudiantes e ON c.id_estudiante = e.id AND e.estado = 'activo'
    ORDER BY m.nombre
";
$datosMateriasRaw = $pdo->query($queryMaterias)->fetchAll();

// Agrupar por materia
$materiasData = [];
foreach ($datosMateriasRaw as $row) {
    $idMat = $row['id'];
    if (!isset($materiasData[$idMat])) {
        $materiasData[$idMat] = [
            'nombre' => $row['nombre'],
            'notas' => [],
            'estudiantes' => []
        ];
    }
    $materiasData[$idMat]['notas'][] = floatval($row['nota']);
    $materiasData[$idMat]['estudiantes'][$row['id_estudiante']] = true;
}

// Calcular promedios y aprobados/reprobados por materia
$materiasProcesadas = [];
foreach ($materiasData as $idMat => $data) {
    // Promedio por materia = suma de notas / 4 (Opción B)
    $promedioMateria = count($data['notas']) > 0 
        ? round(array_sum($data['notas']) / 4, 2) 
        : 0;
    
    // Para contar aprobados/reprobados, necesitamos promedios por estudiante en esta materia
    $estudiantesEnMateria = [];
    foreach ($datosMateriasRaw as $row) {
        if ($row['id'] == $idMat) {
            $idEst = $row['id_estudiante'];
            if (!isset($estudiantesEnMateria[$idEst])) {
                $estudiantesEnMateria[$idEst] = [];
            }
            $estudiantesEnMateria[$idEst][] = floatval($row['nota']);
        }
    }
    
    $aprobados = 0;
    $reprobados = 0;
    foreach ($estudiantesEnMateria as $notasEst) {
        $promedioEst = array_sum($notasEst) / 4;
        if ($promedioEst >= 6) {
            $aprobados++;
        } else {
            $reprobados++;
        }
    }
    
    $materiasProcesadas[] = [
        'id' => $idMat,
        'nombre' => $data['nombre'],
        'promedio' => $promedioMateria,
        'total_estudiantes' => count($estudiantesEnMateria),
        'aprobados' => $aprobados,
        'reprobados' => $reprobados
    ];
}

// Ordenar por promedio descendente
usort($materiasProcesadas, fn($a, $b) => $b['promedio'] <=> $a['promedio']);

// Pasar datos al JS
$nombresMaterias = json_encode(array_column($materiasProcesadas, 'nombre'));
$promediosMaterias = json_encode(array_column($materiasProcesadas, 'promedio'));
$materiasJSON = json_encode($materiasProcesadas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/estadisticas.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Estadísticas - Sistema Académico</title>
    <script>
        const nombresMaterias = <?php echo $nombresMaterias; ?>;
        const promediosMaterias = <?php echo $promediosMaterias; ?>;
        const materiasData = <?php echo $materiasJSON; ?>;
    </script>
    <script src="../JS/estadisticas.js" defer></script>
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
                <li><a href="historial_academico.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial académico</a></li>
                <li><a href="estadisticas.php" class="active"><i class="fa-solid fa-chart-column"></i> Estadísticas</a></li>
                <li><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <h2>Estadísticas del Sistema</h2>
        <p>Resumen visual del rendimiento académico y métricas generales</p>

        <!-- ESTADÍSTICAS GENERALES -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalEstudiantes; ?></span>
                <span class="stat-label">Total estudiantes</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalMaterias; ?></span>
                <span class="stat-label">Total materias</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalSecciones; ?></span>
                <span class="stat-label">Total secciones</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $promedioGeneral; ?></span>
                <span class="stat-label">Promedio general</span>
            </article>
        </section>

        <!-- GRÁFICA DE RENDIMIENTO -->
        <section class="chart-container">
            <h3>Rendimiento por materia</h3>
            <div class="chart-wrapper">
                <canvas id="graficaRendimiento"></canvas>
            </div>
        </section>

        <!-- PROMEDIOS POR MATERIA -->
        <h3 style="margin-bottom: 20px;">Detalle por materia</h3>
        <section class="subjects-grid" id="listaPromedios">
            <?php if (empty($materiasProcesadas)): ?>
                <p class="empty-state">No hay datos estadísticos disponibles aún.</p>
            <?php else: ?>
                <?php foreach ($materiasProcesadas as $m): ?>
                    <article class="subject-card">
                        <div class="subject-info">
                            <div class="subject-icon">
                                <?php echo strtoupper(substr($m['nombre'], 0, 2)); ?>
                            </div>
                            <div class="subject-name"><?php echo htmlspecialchars($m['nombre']); ?></div>
                        </div>
                        <div class="subject-stats">
                            <div class="stat-box">
                                <span class="stat-box-label">Promedio</span>
                                <span class="stat-box-value"><?php echo number_format($m['promedio'], 2); ?></span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-box-label">Estudiantes</span>
                                <span class="stat-box-value"><?php echo $m['total_estudiantes']; ?></span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-box-label">Aprobados</span>
                                <span class="stat-box-value approved"><?php echo $m['aprobados']; ?></span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-box-label">Reprobados</span>
                                <span class="stat-box-value" style="color: #f44336;"><?php echo $m['reprobados']; ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>