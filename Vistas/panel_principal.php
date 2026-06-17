<?php
// Vistas/panel_principal.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// 1. Consultas para las tarjetas (Conteos)
$totalProfesores = $pdo->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$totalMaterias = $pdo->query("SELECT COUNT(*) FROM materias")->fetchColumn();
$totalEstudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
$estudiantesActivos = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'activo'")->fetchColumn();
$estudiantesInactivos = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'inactivo'")->fetchColumn();
$totalSecciones = $pdo->query("SELECT COUNT(*) FROM secciones")->fetchColumn();

// 2. Consulta para Gráfica 1: Promedio por materia (SOLO estudiantes activos)
$stmtChart1 = $pdo->query("
    SELECT m.nombre, COALESCE(ROUND(AVG(c.nota), 2), 0) as promedio 
    FROM materias m 
    LEFT JOIN calificaciones c ON m.id = c.id_materia 
    INNER JOIN estudiantes e ON c.id_estudiante = e.id AND e.estado = 'activo'
    GROUP BY m.id
");
$chart1Data = $stmtChart1->fetchAll();

// 3. Consulta para Gráfica 2: Estudiantes Aprobados vs Reprobados (SOLO activos)
$stmtChart2 = $pdo->query("
    SELECT 
        SUM(CASE WHEN promedio >= 6.0 THEN 1 ELSE 0 END) as aprobados,
        SUM(CASE WHEN promedio < 6.0 THEN 1 ELSE 0 END) as reprobados
    FROM (
        SELECT c.id_estudiante, AVG(c.nota) as promedio 
        FROM calificaciones c
        INNER JOIN estudiantes e ON c.id_estudiante = e.id AND e.estado = 'activo'
        GROUP BY c.id_estudiante
    ) as promedios_estudiantes
");
$chart2Data = $stmtChart2->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../CSS/panel_principal.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Dashboard - Sistema Académico</title>
    
    <script>
        const nombresMaterias = <?php echo json_encode(array_column($chart1Data, 'nombre')); ?>;
        const promediosMaterias = <?php echo json_encode(array_column($chart1Data, 'promedio')); ?>;
        const totalAprobados = <?php echo $chart2Data['aprobados'] ?? 0; ?>;
        const totalReprobados = <?php echo $chart2Data['reprobados'] ?? 0; ?>;
    </script>
    <script src="../JS/panel_principal.js" defer></script>
</head>

<body>
    <header class="header">
        <h1>Sistema Académico</h1>
        <nav>
            <ul class="list">
                <li><a href="panel_principal.php" class="active"><i class="fa-solid fa-house"></i> Panel principal</a></li>
                <li><a href="profesores.php"><i class="fa-solid fa-user"></i> Profesores</a></li>
                <li><a href="estudiantes.php"><i class="fa-solid fa-children"></i> Estudiantes</a></li>
                <li><a href="matricula.php"><i class="fa-solid fa-user-graduate"></i> Matrículas</a></li>
                <li><a href="materias.php"><i class="fa-solid fa-book-open"></i> Materias</a></li>
                <li><a href="calificaciones.php"><i class="fa-solid fa-award"></i> Calificaciones</a></li>
                <li><a href="secciones.php"><i class="fa-solid fa-school"></i> Secciones</a></li>
                <li><a href="historial_academico.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial académico</a></li>
                <li><a href="estadisticas.php"><i class="fa-solid fa-chart-column"></i> Estadísticas</a></li>
                <li><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h2>Dashboard</h2>
            <p>Vista general del sistema</p>
        </div>

        <section class="grid">
            <article class="card">
                <div class="card-icon"><i class="fa-solid fa-person-chalkboard"></i></div>
                <div class="card-info">
                    <span>Total Profesores</span>
                    <h3><?php echo $totalProfesores; ?></h3>
                </div>
            </article>

            <article class="card">
                <div class="card-icon"><i class="fa-solid fa-book"></i></div>
                <div class="card-info">
                    <span>Total Materias</span>
                    <h3><?php echo $totalMaterias; ?></h3>
                </div>
            </article>

            <article class="card">
                <div class="card-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="card-info">
                    <span>Total Estudiantes</span>
                    <h3><?php echo $totalEstudiantes; ?></h3>
                </div>
            </article>

            <article class="card">
                <div class="card-icon"><i class="fa-solid fa-user-check"></i></div>
                <div class="card-info">
                    <span>Estudiantes Activos</span>
                    <h3><?php echo $estudiantesActivos; ?></h3>
                </div>
            </article>

            <article class="card">
                <div class="card-icon"><i class="fa-solid fa-ban"></i></div>
                <div class="card-info">
                    <span>Estudiantes Inactivos</span>
                    <h3><?php echo $estudiantesInactivos; ?></h3>
                </div>
            </article>

            <article class="card">
                <div class="card-icon"><i class="fa-solid fa-school-flag"></i></div>
                <div class="card-info">
                    <span>Total de Secciones</span>
                    <h3><?php echo $totalSecciones; ?></h3>
                </div>
            </article>
        </section>

        <section class="panel">
            <h3>Rendimiento académico por materia:</h3>
            <div class="chart-wrapper chart-barras">
                <canvas id="grafica_barras1"></canvas>
            </div>

            <h3>Porcentaje de aprobación general:</h3>
            <div class="chart-wrapper chart-pastel">
                <canvas id="grafica_pastel"></canvas>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>