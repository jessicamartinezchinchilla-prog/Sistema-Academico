<?php
// Vistas/matricula.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener datos para los selects
$carreras = $pdo->query("SELECT * FROM carreras ORDER BY nombre")->fetchAll();
$grados = $pdo->query("SELECT * FROM grados ORDER BY id")->fetchAll();
$secciones = $pdo->query("SELECT * FROM secciones ORDER BY nombre")->fetchAll();

// Obtener todas las matrículas con datos completos
$query = "SELECT 
            m.id,
            m.anio,
            m.estado,
            m.fecha_registro,
            e.nie,
            CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
            c.nombre as carrera,
            g.nombre as grado,
            s.nombre as seccion,
            r.nombres as resp_nombres,
            r.apellidos as resp_apellidos,
            r.telefono as resp_telefono,
            r.dui as resp_dui,
            r.ocupacion,
            r.parentesco,
            r.email as resp_email,
            r.direccion as resp_direccion,
            e.dui as est_dui,
            e.edad,
            e.fecha_nacimiento,
            e.telefono as est_telefono,
            e.direccion as est_direccion,
            e.email as est_email
          FROM matriculas m
          INNER JOIN estudiantes e ON m.id_estudiante = e.id
          INNER JOIN carreras c ON m.id_carrera = c.id
          INNER JOIN grados g ON m.id_grado = g.id
          INNER JOIN secciones s ON m.id_seccion = s.id
          LEFT JOIN responsables r ON m.id = r.id_matricula
          ORDER BY m.fecha_registro DESC";
$matriculas = $pdo->query($query)->fetchAll();

// Estadísticas
$totalMatriculas = count($matriculas);
$matriculasActivas = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE estado = 'Activo'")->fetchColumn();
$matriculasInactivas = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE estado = 'Inactivo'")->fetchColumn();
$matriculasAnio = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE anio = YEAR(CURRENT_DATE)")->fetchColumn();
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
                <select id="filtroGrado">
                    <option value="">Todos los grados</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?php echo $g['nombre']; ?>"><?php echo $g['nombre']; ?></option>
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
                        <th scope="col">Grado</th>
                        <th scope="col">Carrera</th>
                        <th scope="col">Sección</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="listaMatriculas">
                    <?php if (empty($matriculas)): ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px;">No hay matrículas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($matriculas as $mat): ?>
                            <tr data-id="<?php echo $mat['id']; ?>"
                                data-nie="<?php echo htmlspecialchars($mat['nie'] ?? ''); ?>"
                                data-nombre="<?php echo htmlspecialchars($mat['nombre_completo'] ?? ''); ?>"
                                data-grado="<?php echo htmlspecialchars($mat['grado'] ?? ''); ?>"
                                data-carrera="<?php echo htmlspecialchars($mat['carrera'] ?? ''); ?>"
                                data-seccion="<?php echo htmlspecialchars($mat['seccion'] ?? ''); ?>"
                                data-responsable="<?php echo htmlspecialchars(($mat['resp_nombres'] ?? '') . ' ' . ($mat['resp_apellidos'] ?? '')); ?>"
                                data-telefono="<?php echo htmlspecialchars($mat['resp_telefono'] ?? ''); ?>"
                                data-estado="<?php echo htmlspecialchars($mat['estado'] ?? ''); ?>"
                                data-est-dui="<?php echo htmlspecialchars($mat['est_dui'] ?? ''); ?>"
                                data-est-edad="<?php echo htmlspecialchars($mat['edad'] ?? ''); ?>"
                                data-est-fecha-nac="<?php echo htmlspecialchars($mat['fecha_nacimiento'] ?? ''); ?>"
                                data-est-telefono="<?php echo htmlspecialchars($mat['est_telefono'] ?? ''); ?>"
                                data-est-direccion="<?php echo htmlspecialchars($mat['est_direccion'] ?? ''); ?>"
                                data-est-email="<?php echo htmlspecialchars($mat['est_email'] ?? ''); ?>"
                                data-resp-dui="<?php echo htmlspecialchars($mat['resp_dui'] ?? ''); ?>"
                                data-resp-nombres="<?php echo htmlspecialchars($mat['resp_nombres'] ?? ''); ?>"
                                data-resp-apellidos="<?php echo htmlspecialchars($mat['resp_apellidos'] ?? ''); ?>"
                                data-resp-ocupacion="<?php echo htmlspecialchars($mat['ocupacion'] ?? ''); ?>"
                                data-resp-parentesco="<?php echo htmlspecialchars($mat['parentesco'] ?? ''); ?>"
                                data-resp-email="<?php echo htmlspecialchars($mat['resp_email'] ?? ''); ?>"
                                data-resp-telefono="<?php echo htmlspecialchars($mat['resp_telefono'] ?? ''); ?>"
                                data-resp-direccion="<?php echo htmlspecialchars($mat['resp_direccion'] ?? ''); ?>"
                                data-carrera-id="<?php echo $pdo->query("SELECT id FROM carreras WHERE nombre = '" . $mat['carrera'] . "'")->fetchColumn(); ?>"
                                data-grado-id="<?php echo $pdo->query("SELECT id FROM grados WHERE nombre = '" . $mat['grado'] . "'")->fetchColumn(); ?>"
                                data-seccion-id="<?php echo $pdo->query("SELECT id FROM secciones WHERE nombre = '" . $mat['seccion'] . "'")->fetchColumn(); ?>">
                                <td><?php echo $mat['nie']; ?></td>
                                <td><?php echo $mat['nombre_completo']; ?></td>
                                <td><?php echo $mat['grado']; ?></td>
                                <td><?php echo $mat['carrera']; ?></td>
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
            
            <!-- PASO 1: DATOS DEL ESTUDIANTE -->
            <div class="modal-step" id="paso1">
                <h4><i class="fa-solid fa-user"></i> Datos personales del estudiante</h4>
                
                <label>NIE del Estudiante (máx. 10 dígitos):</label>
                <input type="text" id="mat_nie" name="nie" class="input-nie" required maxlength="10" placeholder="Ej: 1234567890" title="Solo números, máximo 10 dígitos">

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres (2 nombres):</label>
                        <input type="text" id="mat_nombres" name="nombres" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               placeholder="Ej: Juan Carlos" 
                               title="Solo se permiten letras">
                    </div>
                    <div class="form-col">
                        <label>Apellidos (2 apellidos):</label>
                        <input type="text" id="mat_apellidos" name="apellidos" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               placeholder="Ej: Pérez López" 
                               title="Solo se permiten letras">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label id="label_mat_dui">DUI (no aplica - menor de edad):</label>
                        <input type="text" id="mat_dui" name="dui" class="input-dui" disabled
                               placeholder="00000000-0" 
                               pattern="\d{8}-\d{1}" 
                               title="Formato: 12345678-9">
                    </div>
                    <div class="form-col">
                        <label>Edad (calculada automáticamente):</label>
                        <input type="number" id="mat_edad" name="edad" readonly title="La edad se calcula automáticamente desde la fecha de nacimiento">
                    </div>
                </div>

                <label>Fecha de Nacimiento:</label>
                <input type="date" id="mat_fecha_nacimiento" name="fecha_nacimiento" required max="2012-12-31">

                <hr class="divider">

                <h4><i class="fa-solid fa-graduation-cap"></i> Datos académicos</h4>

                <label>Carrera:</label>
                <select id="mat_carrera" name="carrera_id" required>
                    <option value="">Seleccione Carrera</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="form-row">
                    <div class="form-col">
                        <label>Grado:</label>
                        <select id="mat_grado" name="grado_id" required>
                            <option value="">Seleccione Grado</option>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Sección:</label>
                        <select id="mat_seccion" name="seccion_id" required>
                            <option value="">Seleccione Sección</option>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Estado de la Matrícula:</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" value="Activo" readonly 
                           style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; 
                                  background-color: #f3f4f6; color: #16a34a; font-weight: 600; 
                                  cursor: not-allowed; opacity: 0.7;">
                    <input type="hidden" name="estado" value="Activo">
                </div>
                <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                    <i class="fa-solid fa-info-circle"></i> 
                    El estado se gestiona desde el panel de Estudiantes.
                </p>

                <hr class="divider">

                <h4><i class="fa-solid fa-address-book"></i> Datos de contacto</h4>

                <label>Número de Teléfono:</label>
                <input type="tel" id="mat_telefono" name="telefono" class="input-tel" required maxlength="9" 
                       placeholder="0000-0000" 
                       pattern="\d{4}-\d{4}" 
                       title="Formato: 1234-5678">

                <label>Dirección:</label>
                <input type="text" id="mat_direccion" name="direccion" required placeholder="Ej: Col. Las Flores, Casa #123">

                <label>Correo (Solo Gmail):</label>
                <input type="email" id="mat_email" name="email" required 
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
                       placeholder="estudiante@gmail.com" 
                       title="Debe ser un correo @gmail.com">

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalMatricula').close()">Cancelar</button>
                    <button type="button" class="btn-primary" onclick="mostrarPaso(2)">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- PASO 2: DATOS DEL RESPONSABLE -->
            <div class="modal-step" id="paso2" style="display: none;">
                <h4><i class="fa-solid fa-user-tie"></i> Datos personales del responsable</h4>

                <label>DUI del Responsable:</label>
                <input type="text" id="resp_dui" name="responsable_dui" class="input-dui" required 
                       placeholder="00000000-0" 
                       pattern="\d{8}-\d{1}" 
                       title="Formato: 12345678-9">

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres (2 nombres):</label>
                        <input type="text" id="resp_nombres" name="responsable_nombres" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras">
                    </div>
                    <div class="form-col">
                        <label>Apellidos (2 apellidos):</label>
                        <input type="text" id="resp_apellidos" name="responsable_apellidos" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras">
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

                <h4><i class="fa-solid fa-address-book"></i> Datos de contacto del responsable</h4>

                <label>Email (Solo Gmail):</label>
                <input type="email" id="resp_email" name="responsable_email" required 
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
                       placeholder="responsable@gmail.com" 
                       title="Debe ser un correo @gmail.com">

                <label>Número de Teléfono:</label>
                <input type="tel" id="resp_telefono" name="responsable_telefono" class="input-tel" required maxlength="9" 
                       placeholder="0000-0000" 
                       pattern="\d{4}-\d{4}" 
                       title="Formato: 1234-5678">

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
            
            <!-- PASO 1: DATOS DEL ESTUDIANTE -->
            <div class="modal-step" id="edit_paso1">
                <h4><i class="fa-solid fa-user"></i> Datos personales del estudiante</h4>
                
                <label>NIE:</label>
                <input type="text" id="edit_nie" name="nie" class="input-nie" required maxlength="10" readonly>

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres:</label>
                        <input type="text" id="edit_nombres" name="nombres" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras">
                    </div>
                    <div class="form-col">
                        <label>Apellidos:</label>
                        <input type="text" id="edit_apellidos" name="apellidos" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label id="label_edit_dui">DUI (no aplica - menor de edad):</label>
                        <input type="text" id="edit_dui" name="dui" class="input-dui" disabled
                               pattern="\d{8}-\d{1}" 
                               title="Formato: 12345678-9">
                    </div>
                    <div class="form-col">
                        <label>Edad (calculada automáticamente):</label>
                        <input type="number" id="edit_edad" name="edad" readonly title="La edad se calcula automáticamente desde la fecha de nacimiento">
                    </div>
                </div>

                <label>Fecha de Nacimiento:</label>
                <input type="date" id="edit_fecha_nacimiento" name="fecha_nacimiento" required max="2012-12-31">

                <hr class="divider">

                <h4><i class="fa-solid fa-graduation-cap"></i> Datos académicos</h4>

                <label>Carrera:</label>
                <select id="edit_carrera" name="carrera_id" required>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="form-row">
                    <div class="form-col">
                        <label>Grado:</label>
                        <select id="edit_grado" name="grado_id" required>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Sección:</label>
                        <select id="edit_seccion" name="seccion_id" required>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Estado:</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" value="Activo" readonly 
                           style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; 
                                  background-color: #f3f4f6; color: #16a34a; font-weight: 600; 
                                  cursor: not-allowed; opacity: 0.7;">
                    <input type="hidden" name="estado" value="Activo">
                </div>
                <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                    <i class="fa-solid fa-info-circle"></i> 
                    El estado se gestiona desde el panel de Estudiantes.
                </p>

                <hr class="divider">

                <h4><i class="fa-solid fa-address-book"></i> Datos de contacto</h4>

                <label>Teléfono:</label>
                <input type="tel" id="edit_telefono" name="telefono" class="input-tel" required maxlength="9" 
                       pattern="\d{4}-\d{4}" 
                       title="Formato: 1234-5678">

                <label>Dirección:</label>
                <input type="text" id="edit_direccion" name="direccion" required>

                <label>Correo:</label>
                <input type="email" id="edit_email" name="email" required 
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
                       title="Debe ser un correo @gmail.com">

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                    <button type="button" class="btn-primary" onclick="mostrarPasoEditar(2)">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- PASO 2: DATOS DEL RESPONSABLE -->
            <div class="modal-step" id="edit_paso2" style="display: none;">
                <h4><i class="fa-solid fa-user-tie"></i> Datos del responsable</h4>

                <label>DUI del Responsable:</label>
                <input type="text" id="edit_resp_dui" name="responsable_dui" class="input-dui" required 
                       pattern="\d{8}-\d{1}" 
                       title="Formato: 12345678-9">

                <div class="form-row">
                    <div class="form-col">
                        <label>Nombres:</label>
                        <input type="text" id="edit_resp_nombres" name="responsable_nombres" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras">
                    </div>
                    <div class="form-col">
                        <label>Apellidos:</label>
                        <input type="text" id="edit_resp_apellidos" name="responsable_apellidos" class="input-nombre" required 
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras">
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

                <h4><i class="fa-solid fa-address-book"></i> Contacto del responsable</h4>

                <label>Email:</label>
                <input type="email" id="edit_resp_email" name="responsable_email" required 
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
                       title="Debe ser un correo @gmail.com">

                <label>Teléfono:</label>
                <input type="tel" id="edit_resp_telefono" name="responsable_telefono" class="input-tel" required maxlength="9" 
                       pattern="\d{4}-\d{4}" 
                       title="Formato: 1234-5678">

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