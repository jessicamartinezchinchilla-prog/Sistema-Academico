<?php
// Vistas/calificaciones.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener todas las calificaciones con información completa
$query = "SELECT 
            c.id,
            c.id_estudiante,
            c.id_materia,
            c.id_seccion,
            c.periodo,
            c.anio,
            c.nota,
            e.nie,
            CONCAT(e.nombres, ' ', e.apellidos) as nombre_estudiante,
            m.nombre as nombre_materia,
            s.nombre as nombre_seccion
          FROM calificaciones c
          INNER JOIN estudiantes e ON c.id_estudiante = e.id
          INNER JOIN materias m ON c.id_materia = m.id
          INNER JOIN secciones s ON c.id_seccion = s.id
          ORDER BY e.nombres, m.nombre, c.periodo";
$calificaciones = $pdo->query($query)->fetchAll();

// Calcular promedios por estudiante
$promediosEstudiantes = [];
foreach ($calificaciones as $cal) {
    $idEst = $cal['id_estudiante'];
    if (!isset($promediosEstudiantes[$idEst])) {
        $promediosEstudiantes[$idEst] = [
            'nie' => $cal['nie'],
            'nombre' => $cal['nombre_estudiante'],
            'seccion' => $cal['nombre_seccion'],
            'notas' => [],
            'total' => 0,
            'cantidad' => 0
        ];
    }
    $promediosEstudiantes[$idEst]['notas'][$cal['id_materia']][] = $cal['nota'];
    $promediosEstudiantes[$idEst]['total'] += $cal['nota'];
    $promediosEstudiantes[$idEst]['cantidad']++;
}

// Calcular promedio general de cada estudiante
foreach ($promediosEstudiantes as &$est) {
    $est['promedio'] = $est['cantidad'] > 0 ? round($est['total'] / $est['cantidad'], 2) : 0;
    $est['estado'] = $est['promedio'] >= 6 ? 'Aprobado' : 'Reprobado';
}

// Estadísticas
$totalAprobados = count(array_filter($promediosEstudiantes, fn($e) => $e['estado'] === 'Aprobado'));
$totalReprobados = count(array_filter($promediosEstudiantes, fn($e) => $e['estado'] === 'Reprobado'));
$promedioGeneral = count($promediosEstudiantes) > 0 
    ? round(array_sum(array_column($promediosEstudiantes, 'promedio')) / count($promediosEstudiantes), 2) 
    : 0;

// Datos para los filtros
$materias = $pdo->query("SELECT id, nombre FROM materias ORDER BY nombre")->fetchAll();
$secciones = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre")->fetchAll();
$estudiantes = $pdo->query("SELECT id, nie, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM estudiantes WHERE estado = 'activo' ORDER BY nombres")->fetchAll();
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
                <li><a href="calificaciones.php" class="active"><i class="fa-solid fa-award"></i> Calificaciones</a></li>
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

    <main class="main-content">
        <h2>Gestión de Calificaciones</h2>
        <p>Registro, consulta y control del rendimiento académico de los estudiantes</p>

        <!-- BARRA DE ACCIONES -->
        <section class="actions-bar">
            <button type="button" class="button btn-secondary" onclick="abrirModalPDF()">
                <i class="fa-solid fa-file-pdf"></i> Generar PDF
            </button>
            
            <button type="button" class="button btn-primary" onclick="abrirModalNota()">
                <i class="fa-solid fa-plus"></i> Agregar nota
            </button>
        </section>

        <!-- ESTADÍSTICAS RÁPIDAS -->
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

        <!-- PANEL DE FILTROS Y BÚSQUEDA -->
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

        <!-- TABLA DE CALIFICACIONES -->
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
                                data-promedio="<?php echo $est['promedio']; ?>"
                                data-estado="<?php echo $est['estado']; ?>">
                                <td><?php echo $est['nie']; ?></td>
                                <td><?php echo $est['nombre']; ?></td>
                                <td><?php echo $est['seccion']; ?></td>
                                <td class="promedio"><?php echo number_format($est['promedio'], 2); ?></td>
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
                                    <button type="button" class="btn-action edit" onclick="abrirModalNota(this)" title="Agregar/Editar nota">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
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
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="calificacion_id" id="edit_calificacion_id">
            
            <label>Estudiante:</label>
            <select id="nota_estudiante" name="id_estudiante" required>
                <option value="">Seleccione Estudiante</option>
                <?php foreach ($estudiantes as $e): ?>
                    <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo'] . ' (' . $e['nie'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Materia:</label>
            <select id="nota_materia" name="id_materia" required>
                <option value="">Seleccione Materia</option>
                <?php foreach ($materias as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Sección:</label>
            <select id="nota_seccion" name="id_seccion" required>
                <option value="">Seleccione Sección</option>
                <?php foreach ($secciones as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Período:</label>
            <select id="nota_periodo" name="periodo" required>
                <option value="">Seleccione Período</option>
                <option value="1">Primer Período</option>
                <option value="2">Segundo Período</option>
                <option value="3">Tercer Período</option>
                <option value="4">Cuarto Período</option>
            </select>

            <label>Calificación (0 - 10):</label>
            <input type="number" id="nota_valor" name="nota" min="0" max="10" step="0.01" required placeholder="Ej: 8.50">
            <small style="color: #6b7280; font-size: 12px;">Nota mínima aprobatoria: 6.00</small>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalNota').close()">Cancelar</button>
                <button type="submit" class="btn-save">Guardar Calificación</button>
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