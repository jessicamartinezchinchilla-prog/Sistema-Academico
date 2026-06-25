<?php
// Vistas/usuarios.php
require_once '../includes/auth_check.php';

// Solo administradores pueden acceder
if (!esAdmin()) {
    header("Location: panel_principal.php?error=acceso_denegado");
    exit;
}

require_once '../config/database.php';

// Obtener todos los usuarios
$usuarios = $pdo->query("
    SELECT u.id, u.usuario, u.rol, u.id_profesor,
           p.nombres, p.apellidos
    FROM usuarios u
    LEFT JOIN profesores p ON u.id_profesor = p.id
    ORDER BY u.rol, u.usuario
")->fetchAll();

// Obtener profesores sin usuario vinculado
$profesoresDisponibles = $pdo->query("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo
    FROM profesores
    WHERE id NOT IN (SELECT id_profesor FROM usuarios WHERE id_profesor IS NOT NULL)
    ORDER BY nombres
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Gestión de Usuarios - Sistema Académico</title>
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
                <li><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a></li>
                <li><a href="usuarios.php" class="active"><i class="fa-solid fa-users-gear"></i> Usuarios</a></li>
                <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,.15); padding-top: 15px;">
                    <a href="../actions/logout.php" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <h2>Gestión de Usuarios</h2>
        <p>Administrar usuarios del sistema y sus roles</p>

        <!-- ESTADÍSTICAS -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo count($usuarios); ?></span>
                <span class="stat-label">Total usuarios</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #9333ea;">
                    <?php echo count(array_filter($usuarios, fn($u) => $u['rol'] === 'administrador')); ?>
                </span>
                <span class="stat-label">Administradores</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #2563eb;">
                    <?php echo count(array_filter($usuarios, fn($u) => $u['rol'] === 'docente')); ?>
                </span>
                <span class="stat-label">Docentes</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #16a34a;">
                    <?php echo count($profesoresDisponibles); ?>
                </span>
                <span class="stat-label">Profesores sin usuario</span>
            </article>
        </section>

        <!-- BOTÓN AGREGAR -->
        <section class="actions-bar">
            <button type="button" class="button btn-primary" onclick="abrirModalAgregar()">
                <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
            </button>
        </section>

        <!-- TABLA DE USUARIOS -->
        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Profesor Vinculado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:40px;">No hay usuarios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($u['usuario']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background: <?php 
                                        echo $u['rol'] === 'administrador' ? '#9333ea' : 
                                             ($u['rol'] === 'director' || $u['rol'] === 'subdirector' ? '#2563eb' : 
                                             ($u['rol'] === 'secretaria' ? '#16a34a' : '#f59e0b')); 
                                    ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                        <?php echo getRolNombre($u['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['id_profesor']): ?>
                                        <?php echo htmlspecialchars(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')); ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Sin vincular</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <?php if ($u['usuario'] !== 'admin'): ?>
                                        <button type="button" class="btn-action edit" onclick="editarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario']); ?>', '<?php echo $u['rol']; ?>', <?php echo $u['id_profesor'] ?? 'null'; ?>)" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn-action delete" onclick="eliminarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario']); ?>')" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-size: 12px;">Protegido</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- MODAL AGREGAR USUARIO -->
    <dialog id="modalUsuario" class="modal">
        <div class="modal-content">
            <h2>Nuevo Usuario</h2>
            <form id="formUsuario" action="../actions/usuarios_action.php" method="POST">
                <input type="hidden" name="accion" value="agregar">
                
                <div class="form-group">
                    <label>Nombre de Usuario:</label>
                    <input type="text" name="usuario" required placeholder="Ej: juan.perez">
                </div>

                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" required placeholder="Mínimo 6 caracteres" minlength="6">
                </div>

                <div class="form-group">
                    <label>Rol:</label>
                    <select name="rol" id="selectRol" required onchange="toggleProfesor()">
                        <option value="administrador">Administrador</option>
                        <option value="director">Director</option>
                        <option value="subdirector">Subdirector</option>
                        <option value="secretaria">Secretaria</option>
                        <option value="docente">Docente</option>
                    </select>
                </div>

                <div class="form-group" id="grupoProfesor" style="display: none;">
                    <label>Profesor Vinculado:</label>
                    <select name="id_profesor">
                        <option value="">-- Seleccione un profesor --</option>
                        <?php foreach ($profesoresDisponibles as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6b7280; font-size: 11px;">
                        Solo se muestran profesores sin usuario vinculado
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modalUsuario').close()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- MODAL EDITAR USUARIO -->
    <dialog id="modalEditar" class="modal">
        <div class="modal-content">
            <h2>Editar Usuario</h2>
            <form id="formEditar" action="../actions/usuarios_action.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="usuario_id" id="edit_id">
                
                <div class="form-group">
                    <label>Nombre de Usuario:</label>
                    <input type="text" name="usuario" id="edit_usuario" required>
                </div>

                <div class="form-group">
                    <label>Nueva Contraseña (dejar vacío para mantener actual):</label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6">
                </div>

                <div class="form-group">
                    <label>Rol:</label>
                    <select name="rol" id="edit_rol" required onchange="toggleProfesorEdit()">
                        <option value="administrador">Administrador</option>
                        <option value="director">Director</option>
                        <option value="subdirector">Subdirector</option>
                        <option value="secretaria">Secretaria</option>
                        <option value="docente">Docente</option>
                    </select>
                </div>

                <div class="form-group" id="grupoProfesorEdit" style="display: none;">
                    <label>Profesor Vinculado:</label>
                    <select name="id_profesor" id="edit_id_profesor">
                        <option value="">-- Seleccione un profesor --</option>
                        <?php foreach ($profesoresDisponibles as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
        function toggleProfesor() {
            const rol = document.getElementById('selectRol').value;
            const grupo = document.getElementById('grupoProfesor');
            grupo.style.display = rol === 'docente' ? 'block' : 'none';
        }

        function toggleProfesorEdit() {
            const rol = document.getElementById('edit_rol').value;
            const grupo = document.getElementById('grupoProfesorEdit');
            grupo.style.display = rol === 'docente' ? 'block' : 'none';
        }

        function abrirModalAgregar() {
            document.getElementById('formUsuario').reset();
            document.getElementById('grupoProfesor').style.display = 'none';
            document.getElementById('modalUsuario').showModal();
        }

        function editarUsuario(id, usuario, rol, idProfesor) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_usuario').value = usuario;
            document.getElementById('edit_rol').value = rol;
            document.getElementById('edit_id_profesor').value = idProfesor || '';
            
            toggleProfesorEdit();
            document.getElementById('modalEditar').showModal();
        }

        function eliminarUsuario(id, usuario) {
            if (confirm(`¿Estás seguro de eliminar al usuario "${usuario}"?\n\nEsta acción no se puede deshacer.`)) {
                window.location.href = `../actions/usuarios_action.php?accion=eliminar&id=${id}`;
            }
        }

        // Manejar envíos de formularios con AJAX
        document.querySelectorAll('#formUsuario, #formEditar').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    const result = await response.text();
                    
                    if (result.startsWith('SUCCESS:')) {
                        alert('✅ Operación realizada con éxito');
                        window.location.reload();
                    } else if (result.startsWith('ERROR:')) {
                        alert('⚠️ ' + result.replace('ERROR:', ''));
                    }
                } catch (error) {
                    alert('Error de conexión');
                }
            });
        });
    </script>
</body>
</html>