<?php
// Vistas/auditoria.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// ==========================================
// 1. OBTENER TODOS LOS REGISTROS DE AUDITORÍA
// ==========================================
$query = "SELECT * FROM auditoria ORDER BY fecha_registro DESC";
$registros = $pdo->query($query)->fetchAll();

// ==========================================
// 2. ESTADÍSTICAS RÁPIDAS
// ==========================================
$totalRegistros = count($registros);
$totalIniciosSesion = count(array_filter($registros, fn($r) => $r['accion'] === 'inicio_sesion'));
$totalAgregados = count(array_filter($registros, fn($r) => $r['accion'] === 'creacion'));
$totalModificaciones = count(array_filter($registros, fn($r) => $r['accion'] === 'modificacion'));
$totalEliminaciones = count(array_filter($registros, fn($r) => $r['accion'] === 'eliminacion'));

// ==========================================
// 3. OBTENER USUARIOS ÚNICOS PARA EL FILTRO
// ==========================================
$usuariosUnicos = $pdo->query("SELECT DISTINCT usuario FROM auditoria ORDER BY usuario")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/auditoria.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Auditoría - Sistema Académico</title>
    <?php require_once '../includes/theme.php'; ?>
</head>
<body class="<?php echo $modo_oscuro ? 'modo-oscuro' : ''; ?>">
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
                <li><a href="auditoria.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <h2>Auditoría del Sistema</h2>
        <p>Registro de todas las acciones realizadas por los usuarios en el sistema</p>

        <section class="actions-bar">
            <button type="button" class="button btn-secondary" onclick="document.getElementById('modalExportar').showModal()">
                <i class="fa-solid fa-file-export"></i> Exportar Registro
            </button>
        </section>

        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalRegistros; ?></span>
                <span class="stat-label">Total registros</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalIniciosSesion; ?></span>
                <span class="stat-label">Inicios de sesión</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalAgregados; ?></span>
                <span class="stat-label">Creaciones</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalModificaciones; ?></span>
                <span class="stat-label">Modificaciones</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalEliminaciones; ?></span>
                <span class="stat-label">Eliminaciones</span>
            </article>
        </section>

        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarAuditoria" placeholder="Buscar por usuario, acción o detalles...">
            </div>

            <div class="filtros">
                <select id="filtroUsuario">
                    <option value="">Todos los usuarios</option>
                    <?php foreach ($usuariosUnicos as $usuario): ?>
                        <option value="<?php echo htmlspecialchars($usuario); ?>"><?php echo htmlspecialchars($usuario); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroAccion">
                    <option value="">Todas las acciones</option>
                    <option value="inicio_sesion">Inicio de sesión</option>
                    <option value="creacion">Creación</option>
                    <option value="modificacion">Modificación</option>
                    <option value="eliminacion">Eliminación</option>
                    <option value="exportacion">Exportación</option>
                </select>

                <select id="filtroModulo">
                    <option value="">Todos los módulos</option>
                    <option value="estudiantes">Estudiantes</option>
                    <option value="profesores">Profesores</option>
                    <option value="materias">Materias</option>
                    <option value="calificaciones">Calificaciones</option>
                    <option value="matriculas">Matrículas</option>
                    <option value="secciones">Secciones</option>
                    <option value="sistema">Sistema</option>
                </select>
            </div>
        </section>

        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Acción</th>
                        <th scope="col">Detalles</th>
                        <th scope="col">Módulo</th>
                    </tr>
                </thead>
                <tbody id="listaAuditoria">
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:40px;">No hay registros de auditoría aún.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <tr data-usuario="<?php echo htmlspecialchars(strtolower($r['usuario'])); ?>"
                                data-accion="<?php echo $r['accion']; ?>"
                                data-modulo="<?php echo $r['modulo']; ?>"
                                data-descripcion="<?php echo htmlspecialchars(strtolower($r['descripcion'])); ?>">
                                <td>
                                    <?php 
                                    $fecha = new DateTime($r['fecha_registro']);
                                    echo $fecha->format('d/m/Y H:i:s');
                                    ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($r['usuario']); ?></strong></td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-info';
                                    $accionTexto = $r['accion'];
                                    
                                    switch($r['accion']) {
                                        case 'inicio_sesion':
                                            $badgeClass = 'badge-success';
                                            $accionTexto = 'Inicio de sesión';
                                            break;
                                        case 'cierre_sesion':
                                            $badgeClass = 'badge-info';
                                            $accionTexto = 'Cierre de sesión';
                                            break;
                                        case 'creacion':
                                            $badgeClass = 'badge-success';
                                            $accionTexto = 'Creación';
                                            break;
                                        case 'modificacion':
                                            $badgeClass = 'badge-warning';
                                            $accionTexto = 'Modificación';
                                            break;
                                        case 'eliminacion':
                                            $badgeClass = 'badge-danger';
                                            $accionTexto = 'Eliminación';
                                            break;
                                        case 'exportacion':
                                            $badgeClass = 'badge-info';
                                            $accionTexto = 'Exportación';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $accionTexto; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($r['descripcion']); ?></td>
                                <td><span class="badge badge-info"><?php echo ucfirst($r['modulo']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <p class="empty-state" id="mensajeVacio" style="display: <?php echo empty($registros) ? 'block' : 'none'; ?>;">
            No hay registros de auditoría en el sistema aún.
        </p>
    </main>

    <!-- MODAL: EXPORTAR REGISTRO -->
    <dialog id="modalExportar" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Exportar Registro de Auditoría</h3>
        <form action="../actions/exportar_auditoria.php" method="POST" class="modal-form">
            <label>Formato de Exportación:</label>
            <select name="formato" required>
                <option value="">Seleccione formato</option>
                <option value="csv">CSV (Excel)</option>
                <option value="pdf">PDF</option>
            </select>

            <label>Fecha de inicio:</label>
            <input type="date" name="fecha_inicio">

            <label>Fecha de fin:</label>
            <input type="date" name="fecha_fin">

            <label>Usuario (opcional):</label>
            <select name="usuario">
                <option value="">Todos los usuarios</option>
                <?php foreach ($usuariosUnicos as $usuario): ?>
                    <option value="<?php echo htmlspecialchars($usuario); ?>"><?php echo htmlspecialchars($usuario); ?></option>
                <?php endforeach; ?>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalExportar').close()">Cancelar</button>
                <button type="submit" class="btn-save">Exportar</button>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>

    <script src="../JS/auditoria.js"></script>
</body>
</html>