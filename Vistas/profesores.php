<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener profesores con su especialidad
$query = "SELECT 
            p.id, p.nombres, p.apellidos, p.dui, p.telefono, p.email,
            c.nombre as especialidad
          FROM profesores p
          LEFT JOIN carreras c ON p.id_carrera = c.id
          ORDER BY p.nombres";
$profesores = $pdo->query($query)->fetchAll();

// Estadísticas
$totalProfesores = count($profesores);

// Obtener carreras para el select
$carreras = $pdo->query("SELECT id, nombre FROM carreras ORDER BY nombre")->fetchAll();
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
</head>
<body>
    <header class="header">
        <h1>Sistema Académico</h1>
        <nav>
            <ul class="list">
                <li><a href="panel_principal.php"><i class="fa-solid fa-house"></i> Panel principal</a></li>
                <li><a href="profesores.php" class="active"><i class="fa-solid fa-user"></i> Profesores</a></li>
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

    <main class="main-content">
        <h2>Gestión de Profesores</h2>
        <p>Administración del cuerpo docente del sistema</p>

        <!-- BARRA DE ACCIONES (BOTÓN ARRIBA A LA DERECHA) -->
        <section class="actions-bar">
            <button type="button" class="button btn-primary" onclick="abrirModalAgregar()">
                <i class="fa-solid fa-plus"></i> Añadir Profesor
            </button>
        </section>

        <!-- ESTADÍSTICAS -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalProfesores; ?></span>
                <span class="stat-label">Total Profesores</span>
            </article>
        </section>

        <!-- BARRA DE FILTROS Y BÚSQUEDA -->
        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarProfesor" placeholder="Buscar por nombre, apellido o especialidad...">
            </div>

            <div class="filtros">
                <select id="filtroEspecialidad">
                    <option value="">Todas las especialidades</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['nombre']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- TABLA DE PROFESORES -->
        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Nombres</th>
                        <th scope="col">Apellidos</th>
                        <th scope="col">Especialidad</th>
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
                            <tr data-id="<?php echo $prof['id']; ?>"
                                data-nombres="<?php echo htmlspecialchars($prof['nombres'] ?? ''); ?>"
                                data-apellidos="<?php echo htmlspecialchars($prof['apellidos'] ?? ''); ?>"
                                data-especialidad="<?php echo htmlspecialchars($prof['especialidad'] ?? 'Sin especialidad'); ?>"
                                data-telefono="<?php echo htmlspecialchars($prof['telefono'] ?? ''); ?>"
                                data-email="<?php echo htmlspecialchars($prof['email'] ?? ''); ?>"
                                data-dui="<?php echo htmlspecialchars($prof['dui'] ?? ''); ?>">
                                <td><?php echo $prof['nombres']; ?></td>
                                <td><?php echo $prof['apellidos']; ?></td>
                                <td><?php echo $prof['especialidad'] ?? 'Sin especialidad'; ?></td>
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
    <dialog id="modalProfesor" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Añadir Nuevo Profesor</h3>
        
        <form action="../actions/profesores_action.php" method="POST" class="modal-form" id="formProfesor" onsubmit="return validarFormularioProfesor(this)">
            <input type="hidden" name="accion" value="agregar">
            
            <h4><i class="fa-solid fa-user"></i> Datos Personales</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Nombres (2 nombres):</label>
                    <input type="text" id="prof_nombres" name="nombres" class="input-nombre" required placeholder="Ej: Juan Carlos">
                </div>
                <div class="form-col">
                    <label>Apellidos (2 apellidos):</label>
                    <input type="text" id="prof_apellidos" name="apellidos" class="input-nombre" required placeholder="Ej: Pérez López">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>DUI:</label>
                    <input type="text" id="prof_dui" name="dui" class="input-dui" placeholder="00000000-0">
                </div>
                <div class="form-col">
                    <label>Especialidad:</label>
                    <select id="prof_especialidad" name="id_carrera" required>
                        <option value="">Seleccione Especialidad</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-address-book"></i> Contacto</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Teléfono:</label>
                    <input type="tel" id="prof_telefono" name="telefono" class="input-tel" required maxlength="9" placeholder="0000-0000">
                </div>
                <div class="form-col">
                    <label>Email (Solo Gmail):</label>
                    <input type="email" id="prof_email" name="email" required placeholder="profesor@gmail.com">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalProfesor').close()">Cancelar</button>
                <button type="submit" class="btn-save">Guardar Profesor</button>
            </div>
        </form>
    </dialog>

    <!-- MODAL: VER DETALLES -->
    <dialog id="modalVer" class="modal">
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
    <dialog id="modalEditar" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Profesor</h3>
        <form action="../actions/profesores_action.php" method="POST" class="modal-form" id="formEditar" onsubmit="return validarFormularioProfesor(this)">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="profesor_id" id="edit_profesor_id">
            
            <h4><i class="fa-solid fa-user"></i> Datos Personales</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Nombres:</label>
                    <input type="text" id="edit_nombres" name="nombres" class="input-nombre" required>
                </div>
                <div class="form-col">
                    <label>Apellidos:</label>
                    <input type="text" id="edit_apellidos" name="apellidos" class="input-nombre" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>DUI:</label>
                    <input type="text" id="edit_dui" name="dui" class="input-dui">
                </div>
                <div class="form-col">
                    <label>Especialidad:</label>
                    <select id="edit_especialidad" name="id_carrera" required>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="divider">

            <h4><i class="fa-solid fa-address-book"></i> Contacto</h4>

            <div class="form-row">
                <div class="form-col">
                    <label>Teléfono:</label>
                    <input type="tel" id="edit_telefono" name="telefono" class="input-tel" required maxlength="9">
                </div>
                <div class="form-col">
                    <label>Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
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