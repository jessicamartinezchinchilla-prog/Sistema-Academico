<?php
// Vistas/secciones.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// ✅ MODIFICADO: Obtener profesores desde AMBAS fuentes (asignaciones + profesor_asignacion)
$query = "SELECT 
            s.id,
            s.nombre,
            s.letra,
            s.descripcion,
            s.limite_alumnos,
            c.nombre as carrera,
            c.id as id_carrera,
            g.nombre as grado,
            g.id as id_grado,
            COUNT(DISTINCT m.id_estudiante) as total_estudiantes,
            GROUP_CONCAT(DISTINCT CONCAT(p.nombres, ' ', p.apellidos) SEPARATOR ', ') as profesores_nombres
          FROM secciones s
          INNER JOIN carreras c ON s.id_carrera = c.id
          INNER JOIN grados g ON s.id_grado = g.id
          LEFT JOIN matriculas m ON s.id = m.id_seccion AND m.estado = 'Activo'
          LEFT JOIN (
              -- Profesores desde asignaciones (materias)
              SELECT DISTINCT id_seccion, id_profesor FROM asignaciones
              UNION
              -- Profesores desde profesor_asignacion
              SELECT DISTINCT id_seccion, id_profesor FROM profesor_asignacion
          ) pa ON s.id = pa.id_seccion
          LEFT JOIN profesores p ON pa.id_profesor = p.id
          GROUP BY s.id
          ORDER BY c.nombre, g.id, s.letra";
$secciones = $pdo->query($query)->fetchAll();

// Contar profesores únicos por sección
foreach ($secciones as &$sec) {
    if (!empty($sec['profesores_nombres'])) {
        $profesores_array = array_unique(array_map('trim', explode(', ', $sec['profesores_nombres'])));
        $sec['total_profesores'] = count($profesores_array);
    } else {
        $sec['total_profesores'] = 0;
    }
}

// Estadísticas
$totalSecciones = count($secciones);

// Obtener datos para los selects
$grados = $pdo->query("SELECT * FROM grados ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/secciones.css">
    <script src="../JS/secciones.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Gestión de Secciones</title>
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
                <li><a href="secciones.php" class="active"><i class="fa-solid fa-school"></i> Secciones</a></li>
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
        <div class="header">
            <div>
                <h2>Gestión de Secciones</h2>
                <p>Vista general de todas las secciones del sistema</p>
            </div>

            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="sections-count">
                    <i class="fa-solid fa-school"></i>
                    <span id="contadorSecciones"><?php echo $totalSecciones; ?> Secciones</span>
                </div>
                
                <button class="btn-primary" onclick="abrirModalAgregar()">
                    <i class="fa-solid fa-plus"></i> Añadir sección
                </button>
            </div>
        </div>

        <!-- TARJETAS DE SECCIONES -->
        <section class="subjects-grid" id="subjectsGrid">
            <?php if (empty($secciones)): ?>
                <div class="empty-grid-message">
                    <i class="fa-solid fa-school"></i>
                    <p>No hay secciones registradas en el sistema aún.</p>
                    <p style="font-size: 14px; margin-top: 10px;">Haz clic en "Añadir sección" para crear la primera.</p>
                </div>
            <?php else: ?>
                <?php foreach ($secciones as $sec): ?>
                    <div class="subject-card"
                         data-id="<?php echo $sec['id']; ?>"
                         data-nombre="<?php echo htmlspecialchars($sec['nombre']); ?>"
                         data-carrera="<?php echo htmlspecialchars($sec['carrera']); ?>"
                         data-carrera-id="<?php echo $sec['id_carrera']; ?>"
                         data-grado="<?php echo htmlspecialchars($sec['grado']); ?>"
                         data-grado-id="<?php echo $sec['id_grado']; ?>"
                         data-letra="<?php echo htmlspecialchars($sec['letra']); ?>"
                         data-descripcion="<?php echo htmlspecialchars($sec['descripcion'] ?? ''); ?>"
                         data-limite-alumnos="<?php echo $sec['limite_alumnos'] ?? 40; ?>"
                         data-total-estudiantes="<?php echo $sec['total_estudiantes']; ?>"
                         data-total-profesores="<?php echo $sec['total_profesores']; ?>"
                         data-profesores-nombres="<?php echo htmlspecialchars($sec['profesores_nombres'] ?? ''); ?>">
                        
                        <div class="circle">
                            <?php echo $sec['letra']; ?>
                        </div>
                        
                        <h3><?php echo $sec['carrera']; ?></h3>
                        <p class="subtitle"><?php echo $sec['grado']; ?> - Sección <?php echo $sec['letra']; ?></p>
                        
                        <div class="info-row">
                            <span><i class="fa-solid fa-children"></i> Estudiantes</span>
                            <span><?php echo $sec['total_estudiantes']; ?> / <?php echo $sec['limite_alumnos'] ?? 40; ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span><i class="fa-solid fa-chalkboard-user"></i> Profesores</span>
                            <span><?php echo $sec['total_profesores']; ?></span>
                        </div>
                        
                        <?php if ($sec['profesores_nombres']): ?>
                            <div class="profesores-info">
                                <span>Docentes asignados:</span>
                                <span><?php echo $sec['profesores_nombres']; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <button class="btn-primary" onclick="verSeccion(this)">
                            <i class="fa-solid fa-eye"></i> Ver detalles
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- MODAL AÑADIR SECCIÓN -->
    <dialog id="modalSeccion" class="modal">
        <div class="modal-content">
            <h2>Añadir Nueva Sección</h2>
            <form method="dialog" id="formSeccion" action="../actions/secciones_action.php" onsubmit="return validarFormularioSeccion(this)">
                <input type="hidden" name="accion" value="agregar">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="carrera">Carrera</label>
                        <input type="text" id="carrera" name="carrera" placeholder="Ej: Desarrollo de Software" required>
                        <small class="form-hint">Si la carrera no existe, se creará automáticamente</small>
                    </div>

                    <div class="form-group">
                        <label for="grado">Grado</label>
                        <select id="grado" name="id_grado" required>
                            <option value="">Seleccione Grado</option>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="letra">Sección</label>
                        <input type="text" id="letra" name="letra" placeholder="Ej: A" maxlength="5" required style="text-transform: uppercase;">
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre de la Sección</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Se generará automáticamente" readonly style="background: #f3f4f6;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="descripcion">Descripción (opcional)</label>
                        <input type="text" id="descripcion" name="descripcion" placeholder="Descripción breve de la sección">
                    </div>

                    <div class="form-group">
                        <label for="limite_alumnos">Límite de Alumnos</label>
                        <input type="number" id="limite_alumnos" name="limite_alumnos" value="40" min="1" max="100" required>
                        <small class="form-hint">Cantidad máxima de estudiantes en la sección</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modalSeccion').close()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- MODAL VER DETALLES -->
    <dialog id="modalDetalles" class="modal modal-large">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detallesTitulo">Detalles de la Sección</h2>
                <div class="modal-actions-header">
                    <button type="button" onclick="editarSeccion()" class="btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i> Editar
                    </button>
                    <button type="button" onclick="eliminarSeccion()" class="btn-delete">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                    <button type="button" onclick="cerrarModalDetalles()" class="btn-close-modal" title="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            
            <div class="detalles-body">
                <div class="detalles-grid">
                    <div class="detalles-item">
                        <span class="detalles-label">Nombre:</span>
                        <span class="detalles-value" id="detallesNombre">-</span>
                    </div>
                    
                    <div class="detalles-item">
                        <span class="detalles-label">Carrera:</span>
                        <span class="detalles-value" id="detallesCarrera">-</span>
                    </div>
                    
                    <div class="detalles-item">
                        <span class="detalles-label">Grado:</span>
                        <span class="detalles-value" id="detallesGrado">-</span>
                    </div>
                    
                    <div class="detalles-item">
                        <span class="detalles-label">Sección:</span>
                        <span class="detalles-value badge-seccion" id="detallesLetra">-</span>
                    </div>
                    
                    <div class="detalles-item">
                        <span class="detalles-label">Estudiantes:</span>
                        <span class="detalles-value" id="detallesEstudiantes">0</span>
                    </div>
                    
                    <div class="detalles-item">
                        <span class="detalles-label">Límite de Alumnos:</span>
                        <span class="detalles-value" id="detallesLimiteAlumnos">40</span>
                    </div>
                    
                    <div class="detalles-item">
                        <span class="detalles-label">Profesores:</span>
                        <span class="detalles-value" id="detallesTotalProfesores">0</span>
                    </div>
                </div>
                
                <div class="profesores-section">
                    <h3 class="detalles-subtitle">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        Profesores Asignados
                    </h3>
                    <div class="profesores-list" id="detallesProfesoresLista">
                        <p class="no-profesores">No hay profesores asignados</p>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <!-- MODAL EDITAR SECCIÓN -->
    <dialog id="modalEditar" class="modal">
        <div class="modal-content">
            <h2>Editar Sección</h2>
            <form method="dialog" id="formEditar" action="../actions/secciones_action.php" onsubmit="return validarFormularioSeccion(this)">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="seccion_id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Carrera</label>
                        <input type="text" id="edit_carrera" name="carrera" required>
                        <small class="form-hint">Si la carrera no existe, se creará automáticamente</small>
                    </div>

                    <div class="form-group">
                        <label>Grado</label>
                        <select id="edit_grado" name="id_grado" required>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Sección</label>
                        <input type="text" id="edit_letra" name="letra" maxlength="5" required style="text-transform: uppercase;">
                    </div>

                    <div class="form-group">
                        <label>Nombre de la Sección</label>
                        <input type="text" id="edit_nombre" name="nombre" readonly style="background: #f3f4f6;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Descripción (opcional)</label>
                        <input type="text" id="edit_descripcion" name="descripcion">
                    </div>

                    <div class="form-group">
                        <label>Límite de Alumnos</label>
                        <input type="number" id="edit_limite_alumnos" name="limite_alumnos" min="1" max="100" required>
                        <small class="form-hint">Cantidad máxima de estudiantes en la sección</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modalEditar').close()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>