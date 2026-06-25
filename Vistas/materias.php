<?php
// Vistas/materias.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener materias (Filtradas si es docente)
$materias = [];
$sqlMaterias = "SELECT id, codigo, nombre, descripcion FROM materias";

if (esDocente()) {
    $materiasIds = getMateriasDocente($pdo);
    if (!empty($materiasIds)) {
        $idsSeguros = array_map('intval', $materiasIds);
        $idsStr = implode(',', $idsSeguros);
        $sqlMaterias .= " WHERE id IN ($idsStr)";
    } else {
        $sqlMaterias .= " WHERE 1=0";
    }
}

$sqlMaterias .= " ORDER BY nombre";
$query = $pdo->query($sqlMaterias);
while ($mat = $query->fetch()) {
    $id_materia = $mat['id'];
    
    // Obtener secciones desde asignaciones
    $stmt = $pdo->prepare("
        SELECT GROUP_CONCAT(DISTINCT s.nombre SEPARATOR ', ') as secciones
        FROM asignaciones a
        INNER JOIN secciones s ON a.id_seccion = s.id
        WHERE a.id_materia = ?
    ");
    $stmt->execute([$id_materia]);
    $secciones_data = $stmt->fetch();
    $mat['secciones_nombres'] = $secciones_data['secciones'] ?? null;
    
    // Obtener profesores desde asignaciones
    $stmt = $pdo->prepare("
        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.nombres, ' ', p.apellidos) SEPARATOR ', ') as profesores
        FROM asignaciones a
        INNER JOIN profesores p ON a.id_profesor = p.id
        WHERE a.id_materia = ?
    ");
    $stmt->execute([$id_materia]);
    $prof_asignaciones = $stmt->fetch();
    
    // Obtener profesores desde profesor_materia
    $stmt = $pdo->prepare("
        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.nombres, ' ', p.apellidos) SEPARATOR ', ') as profesores
        FROM profesor_materia pm
        INNER JOIN profesores p ON pm.id_profesor = p.id
        WHERE pm.id_materia = ?
    ");
    $stmt->execute([$id_materia]);
    $prof_materias = $stmt->fetch();
    
    // Combinar profesores de ambas fuentes
    $profesores = [];
    if (!empty($prof_asignaciones['profesores'])) {
        $profesores = array_merge($profesores, explode(', ', $prof_asignaciones['profesores']));
    }
    if (!empty($prof_materias['profesores'])) {
        $profesores = array_merge($profesores, explode(', ', $prof_materias['prof_materias']));
    }
    
    $profesores = array_unique($profesores);
    $mat['profesores_nombres'] = !empty($profesores) ? implode(', ', $profesores) : null;
    
    $materias[] = $mat;
}

// ✅ Datos para los selects (FILTRADOS si es docente)
$sqlSecciones = "SELECT id, nombre FROM secciones";
if (esDocente()) {
    $seccionesIds = getSeccionesDocente($pdo);
    if (!empty($seccionesIds)) {
        $idsSeguros = array_map('intval', $seccionesIds);
        $idsStr = implode(',', $idsSeguros);
        $sqlSecciones .= " WHERE id IN ($idsStr)";
    } else {
        $sqlSecciones .= " WHERE 1=0";
    }
}
$sqlSecciones .= " ORDER BY nombre";
$secciones = $pdo->query($sqlSecciones)->fetchAll();

// ✅ Profesores disponibles (filtrados si es docente)
$sqlProfesores = "SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM profesores";
if (esDocente()) {
    $idProf = getIdProfesorUsuario();
    if ($idProf) {
        $sqlProfesores .= " WHERE id = " . intval($idProf);
    } else {
        $sqlProfesores .= " WHERE 1=0";
    }
}
$sqlProfesores .= " ORDER BY nombres";
$profesoresLista = $pdo->query($sqlProfesores)->fetchAll();

$totalProfesores = $pdo->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../CSS/materias.css" />
    <script src="../JS/materias.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <title>Gestión de Materias - Sistema Académico</title>
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
                    <li><a href="materias.php" class="active"><i class="fa-solid fa-book-open"></i> Materias</a></li>
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
        <h2>Gestión de Materias</h2>
        <p>Administración del plan de estudios y asignación docente</p>

        <section class="actions-bar">
            <button type="button" class="button btn-secondary" onclick="document.getElementById('modalPromedios').showModal()">
                <i class="fa-solid fa-chart-line"></i> Ver promedios
            </button>
            <?php if (!esDocente()): ?>
                <button type="button" class="button btn-primary" onclick="abrirModalAgregar()">
                    <i class="fa-solid fa-plus"></i> Añadir materia
                </button>
            <?php endif; ?>
        </section>

        <section class="stats-summary">
            <article class="stat-item">
                <span class="stat-number"><?php echo count($materias); ?></span>
                <span class="stat-label">Total materias</span>
            </article>
            <article class="stat-item">
                <span class="stat-number"><?php echo $totalProfesores; ?></span>
                <span class="stat-label">Docentes disponibles</span>
            </article>
        </section>

        <section class="subjects-grid" id="listaMaterias">
            <?php if (empty($materias)): ?>
                <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 16px; border: 2px dashed #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    <i class="fa-solid fa-book-open" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px; display: block;"></i>
                    <p style="font-size: 18px; color: #6b7280; font-weight: 600; margin: 0;">No hay materias registradas</p>
                    <?php if (!esDocente()): ?>
                        <p style="font-size: 14px; color: #9ca3af; margin-top: 8px;">Haz clic en "Añadir materia" para crear la primera</p>
                    <?php else: ?>
                        <p style="font-size: 14px; color: #9ca3af; margin-top: 8px;">No tienes materias asignadas</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($materias as $mat): ?>
                    <div class="subject-card" 
                         data-id="<?php echo $mat['id']; ?>"
                         data-codigo="<?php echo htmlspecialchars($mat['codigo']); ?>"
                         data-nombre="<?php echo htmlspecialchars($mat['nombre']); ?>"
                         data-descripcion="<?php echo htmlspecialchars($mat['descripcion'] ?? ''); ?>"
                         data-secciones="<?php echo htmlspecialchars($mat['secciones_nombres'] ?? 'Sin secciones'); ?>"
                         data-profesores="<?php echo htmlspecialchars($mat['profesores_nombres'] ?? 'Sin docentes'); ?>">
                        
                        <div class="card-header">
                            <div class="card-icon"><i class="fa-solid fa-book"></i></div>
                            <div class="card-code"><?php echo $mat['codigo']; ?></div>
                        </div>
                        
                        <h3 class="card-title"><?php echo $mat['nombre']; ?></h3>
                        
                        <div class="card-info">
                            <div class="info-item">
                                <i class="fa-solid fa-school"></i>
                                <span><?php echo $mat['secciones_nombres'] ?? 'Sin secciones asignadas'; ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fa-solid fa-chalkboard-user"></i>
                                <span><?php echo $mat['profesores_nombres'] ?? 'Sin docentes asignados'; ?></span>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="btn-card btn-view" onclick="verMateria(this)"><i class="fa-solid fa-eye"></i></button>
                            <?php if (!esDocente()): ?>
                                <button class="btn-card btn-edit" onclick="editarMateria(this)"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn-card btn-delete" onclick="eliminarMateria(<?php echo $mat['id']; ?>)"><i class="fa-solid fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php if (!esDocente()): ?>
    <!-- MODAL AÑADIR (SOLO NO DOCENTES) -->
    <dialog id="modalMateria" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Añadir Nueva Materia</h3>
        <form action="../actions/materias_action.php" method="POST" class="modal-form" id="formMateria" onsubmit="return validarFormularioMateria(this)">
            <input type="hidden" name="accion" value="agregar">
            
            <label>Código de Materia:</label>
            <input type="text" id="codigo_materia" name="codigo" readonly placeholder="Se generará automáticamente" style="background: #f3f4f6; cursor: not-allowed; opacity: 0.7;">
            <p style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                <i class="fa-solid fa-info-circle"></i> El código se genera automáticamente (MAT-001, MAT-002, etc.)
            </p>

            <label>Nombre de la Materia:</label>
            <input type="text" name="nombre" required placeholder="Ej: Matemáticas I">

            <label>Secciones (selecciona una o varias):</label>
            <div style="display: grid; grid-template-columns: 1fr; gap: 8px; max-height: 150px; overflow-y: auto; padding: 10px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px;">
                <?php if (empty($secciones)): ?>
                    <p style="text-align: center; color: #9ca3af; padding: 20px;">No hay secciones registradas</p>
                <?php else: ?>
                    <?php foreach ($secciones as $s): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="secciones[]" value="<?php echo $s['id']; ?>" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-weight: 500;"><?php echo htmlspecialchars($s['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <label>Docentes (opcional - selecciona uno o más):</label>
            <div style="display: grid; grid-template-columns: 1fr; gap: 8px; max-height: 150px; overflow-y: auto; padding: 10px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px;">
                <?php if (empty($profesoresLista)): ?>
                    <p style="text-align: center; color: #9ca3af; padding: 20px;">No hay docentes registrados</p>
                <?php else: ?>
                    <?php foreach ($profesoresLista as $p): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="profesores[]" value="<?php echo $p['id']; ?>" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-weight: 500;"><?php echo htmlspecialchars($p['nombre_completo']); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <label>Descripción:</label>
            <textarea name="descripcion" rows="3" placeholder="Descripción opcional de la materia..." style="resize: vertical;"></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalMateria').close()">Cancelar</button>
                <button type="submit" class="btn-save">Guardar Materia</button>
            </div>
        </form>
    </dialog>

    <!-- MODAL EDITAR (SOLO NO DOCENTES) -->
    <dialog id="modalEditar" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Editar Materia</h3>
        <form action="../actions/materias_action.php" method="POST" class="modal-form" id="formEditar" onsubmit="return validarFormularioMateria(this)">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="materia_id" id="edit_id">

            <label>Código:</label>
            <input type="text" id="edit_codigo" name="codigo" readonly style="background: #f3f4f6; cursor: not-allowed; opacity: 0.7;">
            <p style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                <i class="fa-solid fa-info-circle"></i> El código no se puede modificar
            </p>

            <label>Nombre:</label>
            <input type="text" id="edit_nombre" name="nombre" required>

            <label>Secciones:</label>
            <div id="edit_secciones_container" style="display: grid; grid-template-columns: 1fr; gap: 8px; max-height: 150px; overflow-y: auto; padding: 10px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px;">
                <?php foreach ($secciones as $s): ?>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px;">
                        <input type="checkbox" name="secciones[]" value="<?php echo $s['id']; ?>" class="edit_seccion_check" style="width: 18px; height: 18px; cursor: pointer;">
                        <span><?php echo htmlspecialchars($s['nombre']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Docentes (opcional):</label>
            <div id="edit_profesores_container" style="display: grid; grid-template-columns: 1fr; gap: 8px; max-height: 150px; overflow-y: auto; padding: 10px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px;">
                <?php foreach ($profesoresLista as $p): ?>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px;">
                        <input type="checkbox" name="profesores[]" value="<?php echo $p['id']; ?>" class="edit_profesor_check" style="width: 18px; height: 18px; cursor: pointer;">
                        <span><?php echo htmlspecialchars($p['nombre_completo']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Descripción:</label>
            <textarea id="edit_descripcion" name="descripcion" rows="3"></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
                <button type="submit" class="btn-save">Actualizar</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>

    <!-- MODAL VER (PARA TODOS) -->
    <dialog id="modalVer" class="modal">
        <form method="dialog" class="modal-header">
            <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <h3>Detalles de la Materia</h3>
        <div id="contenidoVerMateria" style="padding: 0 25px 25px;"></div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalVer').close()">Cerrar</button>
        </div>
    </dialog>

    <footer class="footer">
        <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
</body>
</html>