<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener profesores
$query = "SELECT 
            p.id, p.nombres, p.apellidos, p.dui, p.nip, p.telefono, p.email
          FROM profesores p
          ORDER BY p.nombres";
$profesores = $pdo->query($query)->fetchAll();

// Estadísticas
$totalProfesores = count($profesores);

// Obtener materias para los checkboxes
$materias = $pdo->query("SELECT id, nombre FROM materias ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/profesores.css">
    <script src="../JS/profesores.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Gestión de Profesores - Sistema Académico</title>
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
        <h2>Gestión de Profesores</h2>
        <p>Administración del cuerpo docente del sistema</p>

        <section class="actions-bar">
            <button type="button" class="button btn-primary" onclick="abrirModalAgregar()">
                <i class="fa-solid fa-plus"></i> Añadir Profesor
            </button>
        </section>

        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalProfesores; ?></span>
                <span class="stat-label">Total Profesores</span>
            </article>
        </section>

        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarProfesor" placeholder="Buscar por nombre, apellido o materia...">
            </div>
        </section>

        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Nombres</th>
                        <th scope="col">Apellidos</th>
                        <th scope="col">Materias</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Email</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaProfesores">
                    <?php if (empty($profesores)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px;">No hay profesores registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($profesores as $prof): ?>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT m.nombre 
                                FROM materias m
                                INNER JOIN profesor_materia pm ON m.id = pm.id_materia
                                WHERE pm.id_profesor = ?
                            ");
                            $stmt->execute([$prof['id']]);
                            $materias_prof = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $materias_text = implode(', ', $materias_prof);
                            
                            $stmt2 = $pdo->prepare("SELECT id_materia FROM profesor_materia WHERE id_profesor = ?");
                            $stmt2->execute([$prof['id']]);
                            $materias_ids = implode(',', $stmt2->fetchAll(PDO::FETCH_COLUMN));
                            ?>
                            <tr data-id="<?php echo $prof['id']; ?>"
                                data-nombres="<?php echo htmlspecialchars($prof['nombres'] ?? ''); ?>"
                                data-apellidos="<?php echo htmlspecialchars($prof['apellidos'] ?? ''); ?>"
                                data-materias="<?php echo htmlspecialchars($materias_text); ?>"
                                data-materias-ids="<?php echo $materias_ids; ?>"
                                data-telefono="<?php echo htmlspecialchars($prof['telefono'] ?? ''); ?>"
                                data-email="<?php echo htmlspecialchars($prof['email'] ?? ''); ?>"
                                data-dui="<?php echo htmlspecialchars($prof['dui'] ?? ''); ?>"
                                data-nip="<?php echo htmlspecialchars($prof['nip'] ?? ''); ?>">
                                <td><?php echo $prof['nombres']; ?></td>
                                <td><?php echo $prof['apellidos']; ?></td>
                                <td><?php echo $materias_text ?: 'Sin asignar'; ?></td>
                                <td><?php echo $prof['telefono'] ?? 'N/A'; ?></td>
                                <td><?php echo $prof['email'] ?? 'N/A'; ?></td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action see" onclick="verProfesor(this)" title="Ver detalles"><i class="fa-solid fa-eye"></i></button>
                                    <button type="button" class="btn-action edit" onclick="editarProfesor(this)" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <a href="../actions/profesores_action.php?accion=eliminar&id=<?php echo $prof['id']; ?>" class="btn-action delete" onclick="return confirm('¿Estás seguro de eliminar a este profesor?')" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- MODAL: AÑADIR PROFESOR -->
    <dialog id="modalProfesor" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Añadir Nuevo Profesor</h3>
        
        <form action="../actions/profesores_action.php" method="POST" class="modal-form" id="formProfesor">
            <input type="hidden" name="accion" value="agregar">
            
            <h4><i class="fa-solid fa-user"></i> Datos Personales</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Nombres (2 nombres):</label>
                    <input type="text" id="prof_nombres" name="nombres" required placeholder="Ej: Juan Carlos">
                </div>
                <div class="form-col">
                    <label>Apellidos (2 apellidos):</label>
                    <input type="text" id="prof_apellidos" name="apellidos" required placeholder="Ej: Pérez López">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>DUI:</label>
                    <input type="text" id="prof_dui" name="dui" required maxlength="10" placeholder="00000000-0" pattern="\d{8}-\d" title="Formato: 00000000-0">
                </div>
                <div class="form-col">
                    <label>NIP:</label>
                    <input type="text" id="prof_nip" name="nip" required maxlength="10" placeholder="Número de Identificación Profesional">
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-address-book"></i> Contacto</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Teléfono:</label>
                    <input type="tel" id="prof_telefono" name="telefono" required maxlength="9" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="Formato: 0000-0000">
                </div>
                <div class="form-col">
                    <label>Email (Solo Gmail):</label>
                    <input type="email" id="prof_email" name="email" required placeholder="profesor@gmail.com">
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-book"></i> Materias que Imparte</h4>
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 10px;">
                <i class="fa-solid fa-info-circle"></i> Seleccione una o varias materias
            </p>
            
            <div id="prof_materias_container" class="checkbox-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                <?php if (empty($materias)): ?>
                    <p style="color: #9ca3af; text-align: center; grid-column: 1 / -1;">No hay materias registradas</p>
                <?php else: ?>
                    <?php foreach ($materias as $m): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <input type="checkbox" name="id_materias[]" value="<?php echo $m['id']; ?>">
                            <span><?php echo htmlspecialchars($m['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalProfesor').close()">Cancelar</button>
                <button type="submit" class="btn-save">Guardar Profesor</button>
            </div>
        </form>
    </dialog>

    <!-- MODAL: VER DETALLES -->
    <dialog id="modalVer" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Detalles del Profesor</h3>
        <div id="detalleProfesor" class="modal-form"></div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalVer').close()">Cerrar</button>
        </div>
    </dialog>

    <!-- MODAL: EDITAR PROFESOR -->
    <dialog id="modalEditar" class="modal modal-large">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Profesor</h3>
        <form action="../actions/profesores_action.php" method="POST" class="modal-form" id="formEditar">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="profesor_id" id="edit_profesor_id">
            
            <h4><i class="fa-solid fa-user"></i> Datos Personales</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Nombres:</label>
                    <input type="text" id="edit_nombres" name="nombres" required>
                </div>
                <div class="form-col">
                    <label>Apellidos:</label>
                    <input type="text" id="edit_apellidos" name="apellidos" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>DUI:</label>
                    <input type="text" id="edit_dui" name="dui" required maxlength="10" pattern="\d{8}-\d" title="Formato: 00000000-0">
                </div>
                <div class="form-col">
                    <label>NIP:</label>
                    <input type="text" id="edit_nip" name="nip" required maxlength="10">
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-address-book"></i> Contacto</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Teléfono:</label>
                    <input type="tel" id="edit_telefono" name="telefono" required maxlength="9" pattern="\d{4}-\d{4}" title="Formato: 0000-0000">
                </div>
                <div class="form-col">
                    <label>Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-book"></i> Materias que Imparte</h4>
            <div id="edit_materias_container" class="checkbox-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                <?php if (empty($materias)): ?>
                    <p style="color: #9ca3af; text-align: center; grid-column: 1 / -1;">No hay materias registradas</p>
                <?php else: ?>
                    <?php foreach ($materias as $m): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <input type="checkbox" name="id_materias[]" value="<?php echo $m['id']; ?>" class="edit_materia_checkbox">
                            <span><?php echo htmlspecialchars($m['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                <button type="submit" class="btn-save">Actualizar</button>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>