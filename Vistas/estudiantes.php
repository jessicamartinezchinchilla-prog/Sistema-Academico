<?php
// Vistas/estudiantes.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener estudiantes con su sección desde matriculas
$query = "SELECT 
            e.id, e.nie, e.nombres, e.apellidos, e.dui, e.edad, e.fecha_nacimiento,
            e.telefono, e.direccion, e.email, e.estado,
            s.nombre as seccion_nombre
          FROM estudiantes e
          LEFT JOIN matriculas m ON e.id = m.id_estudiante
          LEFT JOIN secciones s ON m.id_seccion = s.id
          ORDER BY e.nombres";
$estudiantes = $pdo->query($query)->fetchAll();

$totalEstudiantes = count($estudiantes);
$estudiantesActivos = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'activo'")->fetchColumn();
$estudiantesInactivos = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'inactivo'")->fetchColumn();
$totalSecciones = $pdo->query("SELECT COUNT(DISTINCT id_seccion) FROM matriculas")->fetchColumn();

// Obtener secciones para el modal (solo para visualización)
$secciones = $pdo->query("SELECT * FROM secciones ORDER BY nombre")->fetchAll();
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
                <li><a href="profesores.php"><i class="fa-solid fa-user"></i> Profesores</a></li>
                <li><a href="estudiantes.php" class="active"><i class="fa-solid fa-children"></i> Estudiantes</a></li>
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

        <!-- BARRA DE BÚSQUEDA Y FILTROS (Estilo Profesores) -->
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
                                    <button type="button" class="btn-action edit" onclick="editarEstudiante(this)" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <a href="../actions/estudiantes_action.php?accion=eliminar&id=<?php echo $est['id']; ?>" class="btn-action delete" onclick="return confirm('¿Estás seguro de eliminar a este estudiante?')" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
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
                $queryDesglose = "SELECT 
                                    s.nombre as seccion,
                                    COUNT(m.id_estudiante) as total
                                  FROM secciones s
                                  LEFT JOIN matriculas m ON s.id = m.id_seccion
                                  GROUP BY s.id
                                  ORDER BY s.nombre";
                $desgloseSecciones = $pdo->query($queryDesglose)->fetchAll();
                
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

    <!-- MODAL EDITAR ESTUDIANTE -->
    <dialog id="modalEditarEstudiante" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Estudiante</h3>
        <form class="modal-form" method="POST" action="../actions/estudiantes_action.php" onsubmit="return validarFormularioEstudiante(this)">
            <input type="hidden" name="id_estudiante" id="edit_id">
            <input type="hidden" name="accion" value="editar">
            
            <h4>Datos personales</h4>
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
                    <label>Estado:</label>
                    <select name="estado" id="edit_estado" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>

            <h4>Información Académica</h4>
            <div class="form-row">
                <div class="form-col">
                    <label>Sección Actual:</label>
                    <input type="text" id="edit_seccion_display" readonly 
                        style="background: #f3f4f6; color: #6b7280; cursor: not-allowed; opacity: 0.7;" 
                        placeholder="Sin matrícula" />
                </div>
                <div class="form-col">
                    <label style="visibility: hidden;">Espacio</label>
                    <p style="font-size: 12px; color: #6b7280; margin-top: 12px;">
                        <i class="fa-solid fa-info-circle"></i> 
                        Para cambiar la sección, ve al panel de Matrículas
                    </p>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditarEstudiante').close()">Cancelar</button>
                <button type="submit" class="btn-save">Actualizar</button>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>