<?php
// Vistas/matricula.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener todas las matrículas con datos completos
$query = "SELECT 
            m.id,
            m.anio,
            m.estado,
            m.fecha_registro,
            e.nie,
            CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
            s.nombre as seccion,
            r.nombres as resp_nombres,
            r.apellidos as resp_apellidos,
            r.telefono as resp_telefono,
            r.dui as resp_dui,
            r.ocupacion,
            r.parentesco,
            r.email as resp_email,
            r.direccion as resp_direccion
          FROM matriculas m
          INNER JOIN estudiantes e ON m.id_estudiante = e.id
          INNER JOIN secciones s ON m.id_seccion = s.id
          LEFT JOIN responsables r ON m.id = r.id_matricula
          ORDER BY m.fecha_registro DESC";
$matriculas = $pdo->query($query)->fetchAll();

// Estadísticas
$totalMatriculas = count($matriculas);
$matriculasActivas = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE estado = 'Activo'")->fetchColumn();
$matriculasInactivas = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE estado = 'Inactivo'")->fetchColumn();
$matriculasAnio = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE anio = YEAR(CURRENT_DATE)")->fetchColumn();

// Obtener estudiantes existentes
$estudiantes = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos, ' (', nie, ')') as nombre_completo FROM estudiantes WHERE estado = 'activo' ORDER BY nombres")->fetchAll();

// Obtener secciones completas
$secciones = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../CSS/matricula.css">
    <script src="../JS/matricula.js" defer></script>
    <title>Gestión de Matrículas - Sistema Académico</title>
</head>
<body>
    <header class="header">
        <h1>Sistema Académico</h1>
        <nav>
            <ul class="list">
                <li><a href="panel_principal.php"><i class="fa-solid fa-house"></i> Panel principal</a></li>
                <li><a href="profesores.php"><i class="fa-solid fa-user"></i> Profesores</a></li>
                <li><a href="estudiantes.php"><i class="fa-solid fa-children"></i> Estudiantes</a></li>
                <li><a href="matricula.php" class="active"><i class="fa-solid fa-user-graduate"></i> Matrículas</a></li>
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
        <h2>Gestión de Matrículas</h2>
        <p>Registro, inscripción y gestión del vínculo académico de los estudiantes</p>

        <!-- ESTADÍSTICAS -->
        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalMatriculas; ?></span>
                <span class="stat-label">Total Matrículas</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #16a34a;"><?php echo $matriculasActivas; ?></span>
                <span class="stat-label">Activas</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #dc2626;"><?php echo $matriculasInactivas; ?></span>
                <span class="stat-label">Inactivas</span>
            </article>
            <article class="stat-item">
                <span class="stat-number" style="color: #9333ea;"><?php echo $matriculasAnio; ?></span>
                <span class="stat-label">Este Año</span>
            </article>
        </section>

        <!-- PANEL DE FILTROS Y BÚSQUEDA -->
        <section class="filters-bar">
            <div class="busqueda">
                <input type="search" id="buscarMatricula" placeholder="Buscar por NIE, nombre o responsable...">
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
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <button type="button" class="button btn-primary" onclick="abrirModalNuevaMatricula()">
                <i class="fa-solid fa-plus"></i> Nueva Matrícula
            </button>
        </section>

        <!-- TABLA DE MATRÍCULAS -->
        <section class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NIE</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Sección</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaMatriculas">
                    <?php if (empty($matriculas)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px;">No hay matrículas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($matriculas as $mat): ?>
                            <tr data-id="<?php echo $mat['id']; ?>"
                                data-nie="<?php echo htmlspecialchars($mat['nie'] ?? ''); ?>"
                                data-nombre="<?php echo htmlspecialchars($mat['nombre_completo'] ?? ''); ?>"
                                data-seccion="<?php echo htmlspecialchars($mat['seccion'] ?? ''); ?>"
                                data-responsable="<?php echo htmlspecialchars(($mat['resp_nombres'] ?? '') . ' ' . ($mat['resp_apellidos'] ?? '')); ?>"
                                data-telefono="<?php echo htmlspecialchars($mat['resp_telefono'] ?? ''); ?>"
                                data-estado="<?php echo htmlspecialchars($mat['estado'] ?? ''); ?>"
                                data-resp-dui="<?php echo htmlspecialchars($mat['resp_dui'] ?? ''); ?>"
                                data-resp-nombres="<?php echo htmlspecialchars($mat['resp_nombres'] ?? ''); ?>"
                                data-resp-apellidos="<?php echo htmlspecialchars($mat['resp_apellidos'] ?? ''); ?>"
                                data-resp-ocupacion="<?php echo htmlspecialchars($mat['ocupacion'] ?? ''); ?>"
                                data-resp-parentesco="<?php echo htmlspecialchars($mat['parentesco'] ?? ''); ?>"
                                data-resp-email="<?php echo htmlspecialchars($mat['resp_email'] ?? ''); ?>"
                                data-resp-telefono="<?php echo htmlspecialchars($mat['resp_telefono'] ?? ''); ?>"
                                data-resp-direccion="<?php echo htmlspecialchars($mat['resp_direccion'] ?? ''); ?>">
                                <td><?php echo $mat['nie']; ?></td>
                                <td><?php echo $mat['nombre_completo']; ?></td>
                                <td><?php echo $mat['seccion']; ?></td>
                                <td><?php echo ($mat['resp_nombres'] ?? '') . ' ' . ($mat['resp_apellidos'] ?? ''); ?></td>
                                <td><?php echo $mat['resp_telefono'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if ($mat['estado'] === 'Activo'): ?>
                                        <span class="badge active">Activo</span>
                                    <?php else: ?>
                                        <span class="badge inactive">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action see" onclick="verMatricula(this)" title="Ver detalles"><i class="fa-solid fa-eye"></i></button>
                                    <button type="button" class="btn-action edit" onclick="editarMatricula(this)" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <a href="../actions/matricula_action.php?accion=eliminar&id=<?php echo $mat['id']; ?>" class="btn-action delete" onclick="return confirm('¿Estás seguro de eliminar esta matrícula?')" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- MODAL: NUEVA MATRÍCULA -->
    <dialog id="modalMatricula" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Registrar Nueva Matrícula</h3>
        
        <form action="../actions/matricula_action.php" method="POST" class="modal-form" id="formMatricula" onsubmit="return validarFormularioMatricula(this, false)">
            <input type="hidden" name="accion" value="agregar">
            
            <!-- PASO 1: TIPO DE ESTUDIANTE -->
            <div class="modal-step" id="paso1">
                <h4><i class="fa-solid fa-user-graduate"></i> Tipo de Estudiante</h4>
                
                <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <label style="flex: 1; padding: 15px; border: 2px solid #d1d5db; border-radius: 10px; cursor: pointer; text-align: center; transition: all 0.3s;" id="labelExistente">
                        <input type="radio" name="tipo_estudiante" value="existente" checked onchange="toggleTipoEstudiante()" style="margin-right: 8px;">
                        <i class="fa-solid fa-user-check" style="font-size: 24px; color: #2563eb; display: block; margin-bottom: 8px;"></i>
                        <strong>Estudiante Existente</strong>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">Ya está registrado en el sistema</p>
                    </label>
                    <label style="flex: 1; padding: 15px; border: 2px solid #d1d5db; border-radius: 10px; cursor: pointer; text-align: center; transition: all 0.3s;" id="labelNuevo">
                        <input type="radio" name="tipo_estudiante" value="nuevo" onchange="toggleTipoEstudiante()" style="margin-right: 8px;">
                        <i class="fa-solid fa-user-plus" style="font-size: 24px; color: #2563eb; display: block; margin-bottom: 8px;"></i>
                        <strong>Estudiante Nuevo</strong>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">Primera vez en el sistema</p>
                    </label>
                </div>

                <!-- OPCIÓN A: Estudiante Existente -->
                <div id="bloqueExistente">
                    <label>Seleccionar Estudiante:</label>
                    <select id="mat_estudiante_existente" name="id_estudiante_existente">
                        <option value="">-- Seleccione un estudiante --</option>
                        <?php foreach ($estudiantes as $e): ?>
                            <option value="<?php echo $e['id']; ?>"><?php echo $e['nombre_completo']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- OPCIÓN B: Estudiante Nuevo -->
                <div id="bloqueNuevo" style="display: none;">
                    <h4><i class="fa-solid fa-user"></i> Datos Personales del Estudiante</h4>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label>NIE (máx. 10 dígitos):</label>
                            <input type="text" id="mat_nie" name="nie" class="input-nie" maxlength="10" placeholder="0000000000">
                        </div>
                        <div class="form-col">
                            <label>Edad:</label>
                            <input type="number" id="mat_edad" name="edad" min="14" max="22" readonly style="background: #f3f4f6; cursor: not-allowed; opacity: 0.7;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Nombres (2 nombres):</label>
                            <input type="text" id="mat_nombres" name="nombres" class="input-nombre" placeholder="Ej: Juan Carlos">
                        </div>
                        <div class="form-col">
                            <label>Apellidos (2 apellidos):</label>
                            <input type="text" id="mat_apellidos" name="apellidos" class="input-nombre" placeholder="Ej: Pérez López">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>DUI (si tiene 18+):</label>
                            <input type="text" id="mat_dui" name="dui" class="input-dui" disabled placeholder="00000000-0" style="background: #f3f4f6; cursor: not-allowed; opacity: 0.5;">
                        </div>
                        <div class="form-col">
                            <label>Fecha de Nacimiento:</label>
                            <input type="date" id="mat_fecha_nacimiento" name="fecha_nacimiento" max="2012-12-31">
                        </div>
                    </div>

                    <h4><i class="fa-solid fa-address-book"></i> Contacto del Estudiante</h4>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Teléfono:</label>
                            <input type="tel" id="mat_telefono" name="telefono" class="input-tel" maxlength="9" placeholder="0000-0000">
                        </div>
                        <div class="form-col">
                            <label>Email (Solo Gmail):</label>
                            <input type="email" id="mat_email" name="email" placeholder="estudiante@gmail.com">
                        </div>
                    </div>

                    <label>Dirección:</label>
                    <input type="text" id="mat_direccion" name="direccion" placeholder="Ej: Col. Las Flores, Casa #123">
                </div>

                <hr class="divider">

                <h4><i class="fa-solid fa-school"></i> Datos Académicos</h4>

                <label>Sección:</label>
                <select id="mat_seccion" name="id_seccion" required>
                    <option value="">Seleccione Sección</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Estado de la Matrícula:</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" value="Activo" readonly 
                           style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; 
                                  background-color: #f3f4f6; color: #16a34a; font-weight: 600; 
                                  cursor: not-allowed; opacity: 0.7;">
                    <input type="hidden" name="estado" value="Activo">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalMatricula').close()">Cancelar</button>
                    <button type="button" class="btn-primary" onclick="mostrarPaso(2)">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- PASO 2: DATOS DEL RESPONSABLE -->
            <div class="modal-step" id="paso2" style="display: none;">
                <h4><i class="fa-solid fa-user-tie"></i> Datos del Responsable</h4>

                <label>DUI del Responsable:</label>
                <input type="text" id="resp_dui" name="responsable_dui" class="input-dui" required placeholder="00000000-0">

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres (2 nombres):</label>
                        <input type="text" id="resp_nombres" name="responsable_nombres" class="input-nombre" required>
                    </div>
                    <div class="form-col">
                        <label>Apellidos (2 apellidos):</label>
                        <input type="text" id="resp_apellidos" name="responsable_apellidos" class="input-nombre" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Ocupación:</label>
                        <input type="text" id="resp_ocupacion" name="responsable_ocupacion" required placeholder="Ej: Comerciante">
                    </div>
                    <div class="form-col">
                        <label>Parentesco:</label>
                        <select id="resp_parentesco" name="responsable_parentesco" required>
                            <option value="">Seleccione Parentesco</option>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Tío">Tío</option>
                            <option value="Tía">Tía</option>
                            <option value="Abuelo">Abuelo</option>
                            <option value="Abuela">Abuela</option>
                            <option value="Hermano">Hermano</option>
                            <option value="Hermana">Hermana</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <hr class="divider">

                <h4><i class="fa-solid fa-address-book"></i> Contacto del Responsable</h4>

                <label>Email (Solo Gmail):</label>
                <input type="email" id="resp_email" name="responsable_email" required placeholder="responsable@gmail.com">

                <label>Número de Teléfono:</label>
                <input type="tel" id="resp_telefono" name="responsable_telefono" class="input-tel" required maxlength="9" placeholder="0000-0000">

                <label>Dirección:</label>
                <input type="text" id="resp_direccion" name="responsable_direccion" required placeholder="Ej: Col. Las Flores, Casa #123">

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="mostrarPaso(1)"><i class="fa-solid fa-arrow-left"></i> Atrás</button>
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalMatricula').close()">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Matrícula</button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- MODAL: VER DETALLES -->
    <dialog id="modalVer" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Información de la Matrícula</h3>
        <div id="detalleMatricula" class="modal-form"></div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalVer').close()">Cerrar</button>
        </div>
    </dialog>

    <!-- MODAL: EDITAR MATRÍCULA -->
    <dialog id="modalEditar" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Matrícula</h3>
        <form action="../actions/matricula_action.php" method="POST" class="modal-form" id="formEditar" onsubmit="return validarFormularioMatricula(this, false)">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="matricula_id" id="edit_matricula_id">
            
            <!-- PASO 1: DATOS DE LA MATRÍCULA -->
            <div class="modal-step" id="edit_paso1">
                <h4><i class="fa-solid fa-user-graduate"></i> Datos de la Matrícula</h4>
                
                <label>Estudiante:</label>
                <select id="edit_estudiante" name="id_estudiante" required>
                    <?php foreach ($estudiantes as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo $e['nombre_completo']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Sección:</label>
                <select id="edit_seccion" name="id_seccion" required>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Estado:</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" value="Activo" readonly 
                           style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; 
                                  background-color: #f3f4f6; color: #16a34a; font-weight: 600; 
                                  cursor: not-allowed; opacity: 0.7;">
                    <input type="hidden" name="estado" value="Activo">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                    <button type="button" class="btn-primary" onclick="mostrarPasoEditar(2)">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- PASO 2: DATOS DEL RESPONSABLE -->
            <div class="modal-step" id="edit_paso2" style="display: none;">
                <h4><i class="fa-solid fa-user-tie"></i> Datos del Responsable</h4>

                <label>DUI del Responsable:</label>
                <input type="text" id="edit_resp_dui" name="responsable_dui" class="input-dui" required>

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres:</label>
                        <input type="text" id="edit_resp_nombres" name="responsable_nombres" class="input-nombre" required>
                    </div>
                    <div class="form-col">
                        <label>Apellidos:</label>
                        <input type="text" id="edit_resp_apellidos" name="responsable_apellidos" class="input-nombre" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Ocupación:</label>
                        <input type="text" id="edit_resp_ocupacion" name="responsable_ocupacion" required>
                    </div>
                    <div class="form-col">
                        <label>Parentesco:</label>
                        <select id="edit_resp_parentesco" name="responsable_parentesco" required>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Tío">Tío</option>
                            <option value="Tía">Tía</option>
                            <option value="Abuelo">Abuelo</option>
                            <option value="Abuela">Abuela</option>
                            <option value="Hermano">Hermano</option>
                            <option value="Hermana">Hermana</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <hr class="divider">

                <h4><i class="fa-solid fa-address-book"></i> Contacto del Responsable</h4>

                <label>Email:</label>
                <input type="email" id="edit_resp_email" name="responsable_email" required>

                <label>Teléfono:</label>
                <input type="tel" id="edit_resp_telefono" name="responsable_telefono" class="input-tel" required maxlength="9">

                <label>Dirección:</label>
                <input type="text" id="edit_resp_direccion" name="responsable_direccion" required>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="mostrarPasoEditar(1)"><i class="fa-solid fa-arrow-left"></i> Atrás</button>
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                    <button type="submit" class="btn-save">Actualizar</button>
                </div>
            </div>
        </form>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>