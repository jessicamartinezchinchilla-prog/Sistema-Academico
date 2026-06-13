<?php
// Vistas/secciones.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener todas las secciones con información completa
$query = "SELECT 
            s.id,
            s.nombre,
            s.letra,
            s.descripcion,
            c.nombre as carrera,
            c.id as id_carrera,
            g.nombre as grado,
            g.id as id_grado,
            COUNT(DISTINCT m.id_estudiante) as total_estudiantes,
            COUNT(DISTINCT pa.id_profesor) as total_profesores,
            GROUP_CONCAT(DISTINCT CONCAT(p.nombres, ' ', p.apellidos) SEPARATOR ', ') as profesores_nombres
          FROM secciones s
          INNER JOIN carreras c ON s.id_carrera = c.id
          INNER JOIN grados g ON s.id_grado = g.id
          LEFT JOIN matriculas m ON s.id = m.id_seccion
          LEFT JOIN profesor_asignacion pa ON s.id = pa.id_seccion
          LEFT JOIN profesores p ON pa.id_profesor = p.id
          GROUP BY s.id
          ORDER BY c.nombre, g.id, s.letra";
$secciones = $pdo->query($query)->fetchAll();

// Estadísticas
$totalSecciones = count($secciones);

// Obtener datos para los selects
$carreras = $pdo->query("SELECT * FROM carreras ORDER BY nombre")->fetchAll();
$grados = $pdo->query("SELECT * FROM grados ORDER BY id")->fetchAll();
$profesores = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM profesores ORDER BY nombres")->fetchAll();

// Obtener asignaciones existentes para el modal de editar
$asignacionesProfesores = $pdo->query("SELECT id_seccion, GROUP_CONCAT(id_profesor) as profesores_ids FROM profesor_asignacion GROUP BY id_seccion")->fetchAll(PDO::FETCH_KEY_PAIR);
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
                            <span><?php echo $sec['total_estudiantes']; ?></span>
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
                        <label for="nombre">Nombre de la Sección</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Ej: Desarrollo de Software - 1° Año - A" required>
                    </div>

                    <div class="form-group">
                        <label for="letra">Letra de Sección</label>
                        <input type="text" id="letra" name="letra" placeholder="Ej: A" maxlength="5" required style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="carrera">Carrera</label>
                        <select id="carrera" name="id_carrera" required>
                            <option value="">Seleccione Carrera</option>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
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

                <div class="form-group">
                    <label for="descripcion">Descripción (opcional)</label>
                    <input type="text" id="descripcion" name="descripcion" placeholder="Descripción breve de la sección">
                </div>

                <div class="form-group">
                    <label>Profesores Asignados (selecciona uno o más):</label>
                    <div class="checkbox-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px; max-height: 200px; overflow-y: auto; padding: 10px; background: #f8fafc; border-radius: 8px;">
                        <?php if (empty($profesores)): ?>
                            <p style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 20px;">No hay profesores registrados</p>
                        <?php else: ?>
                            <?php foreach ($profesores as $p): ?>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                                    <input type="checkbox" name="profesores[]" value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['nombre_completo']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                        <label>Nombre de la Sección</label>
                        <input type="text" id="edit_nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Letra de Sección</label>
                        <input type="text" id="edit_letra" name="letra" maxlength="5" required style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Carrera</label>
                        <select id="edit_carrera" name="id_carrera" required>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
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

                <div class="form-group">
                    <label>Descripción (opcional)</label>
                    <input type="text" id="edit_descripcion" name="descripcion">
                </div>

                <div class="form-group">
                    <label>Profesores Asignados (selecciona uno o más):</label>
                    <div class="checkbox-group" id="edit_profesores_container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px; max-height: 200px; overflow-y: auto; padding: 10px; background: #f8fafc; border-radius: 8px;">
                        <?php foreach ($profesores as $p): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="profesores[]" value="<?php echo $p['id']; ?>" class="edit_profesor_check">
                                <?php echo htmlspecialchars($p['nombre_completo']); ?>
                            </label>
                        <?php endforeach; ?>
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

    <script>
        // Inyectamos las asignaciones de profesores para el modal de edición
        window.asignacionesProfesores = <?php echo json_encode($asignacionesProfesores); ?>;
    </script>
</body>
</html>