<?php
// Vistas/materias.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Obtener todas las materias con información completa
$query = "SELECT 
            m.id,
            m.codigo,
            m.nombre,
            m.descripcion,
            GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as carreras_nombres,
            GROUP_CONCAT(DISTINCT CONCAT(p.nombres, ' ', p.apellidos) SEPARATOR ', ') as docentes_nombres,
            COUNT(DISTINCT pa.id_profesor) as total_docentes
          FROM materias m
          LEFT JOIN materias_carreras mc ON m.id = mc.id_materia
          LEFT JOIN carreras c ON mc.id_carrera = c.id
          LEFT JOIN profesor_asignacion pa ON m.id = pa.id_materia
          LEFT JOIN profesores p ON pa.id_profesor = p.id
          GROUP BY m.id
          ORDER BY m.nombre";
$materias = $pdo->query($query)->fetchAll();

// Estadísticas
$totalMaterias = count($materias);
$totalDocentes = $pdo->query("SELECT COUNT(DISTINCT id_profesor) FROM profesor_asignacion")->fetchColumn();

// Obtener profesores para los checkboxes
$profesores = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM profesores ORDER BY nombres")->fetchAll();

// Obtener carreras para los checkboxes
$carreras = $pdo->query("SELECT * FROM carreras ORDER BY nombre")->fetchAll();

// Obtener secciones para los checkboxes
$secciones = $pdo->query("SELECT * FROM secciones ORDER BY nombre")->fetchAll();

// Obtener asignaciones existentes para el modal de editar
$asignaciones = $pdo->query("SELECT id_materia, GROUP_CONCAT(id_profesor) as docentes_ids, GROUP_CONCAT(id_seccion) as secciones_ids FROM profesor_asignacion GROUP BY id_materia")->fetchAll(PDO::FETCH_KEY_PAIR);
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
          <li><a href="materias.php" class="active"><i class="fa-solid fa-book-open"></i> Materias</a></li>
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
      <h2>Gestión de Materias</h2>
      <p>Administración del plan de estudios y asignación docente</p>

      <!-- BARRA DE ACCIONES -->
      <section class="actions-bar">
        <button type="button" class="button btn-secondary" onclick="document.getElementById('modalPromedios').showModal()">
          <i class="fa-solid fa-chart-line"></i> Ver promedios
        </button>
        <button type="button" class="button btn-primary" onclick="abrirModalAgregar()">
          <i class="fa-solid fa-plus"></i> Añadir materia
        </button>
      </section>

      <!-- ESTADÍSTICAS RÁPIDAS -->
      <section class="stats-summary">
        <article class="stat-item">
          <span class="stat-number"><?php echo $totalMaterias; ?></span>
          <span class="stat-label">Total materias</span>
        </article>
        <article class="stat-item">
          <span class="stat-number"><?php echo $totalDocentes; ?></span>
          <span class="stat-label">Docentes asignados</span>
        </article>
      </section>

      <!-- GRID DE MATERIAS -->
      <section class="subjects-grid" id="listaMaterias">
        <?php if (empty($materias)): ?>
          <p class="empty-state">No hay materias registradas en el sistema aún.</p>
        <?php else: ?>
          <?php foreach ($materias as $mat): ?>
            <div class="subject-card" 
                 data-id="<?php echo $mat['id']; ?>"
                 data-codigo="<?php echo htmlspecialchars($mat['codigo'] ?? ''); ?>"
                 data-nombre="<?php echo htmlspecialchars($mat['nombre'] ?? ''); ?>"
                 data-descripcion="<?php echo htmlspecialchars($mat['descripcion'] ?? ''); ?>"
                 data-carreras="<?php echo htmlspecialchars($mat['carreras_nombres'] ?? 'Sin carreras asignadas'); ?>"
                 data-docentes="<?php echo htmlspecialchars($mat['docentes_nombres'] ?? 'Sin docentes'); ?>"
                 data-total-docentes="<?php echo $mat['total_docentes']; ?>">
              
              <div class="card-header">
                <div class="card-icon">
                  <i class="fa-solid fa-book"></i>
                </div>
                <div class="card-code"><?php echo $mat['codigo']; ?></div>
              </div>
              
              <h3 class="card-title"><?php echo $mat['nombre']; ?></h3>
              
              <?php if ($mat['descripcion']): ?>
                <p class="card-description"><?php echo substr($mat['descripcion'], 0, 80); ?><?php echo strlen($mat['descripcion']) > 80 ? '...' : ''; ?></p>
              <?php endif; ?>
              
              <div class="card-info">
                <div class="info-item">
                  <i class="fa-solid fa-graduation-cap"></i>
                  <span><?php echo $mat['carreras_nombres'] ?? 'Sin carreras'; ?></span>
                </div>
                <div class="info-item">
                  <i class="fa-solid fa-chalkboard-user"></i>
                  <span><?php echo $mat['total_docentes']; ?> docente(s)</span>
                </div>
              </div>
              
              <div class="card-actions">
                <button class="btn-card btn-view" onclick="verMateria(this)" title="Ver detalles">
                  <i class="fa-solid fa-eye"></i>
                </button>
                <button class="btn-card btn-edit" onclick="editarMateria(this)" title="Editar">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <a href="../actions/materias_action.php?accion=eliminar&id=<?php echo $mat['id']; ?>" 
                   class="btn-card btn-delete" 
                   onclick="return confirm('¿Estás seguro de eliminar esta materia?')" 
                   title="Eliminar">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </main>

    <!-- MODAL: VER PROMEDIOS -->
    <dialog id="modalPromedios" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Promedios por Materia</h3>
      <div class="modal-body">
        <p style="text-align: center; color: #6b7280; padding: 40px 0;">
          <i class="fa-solid fa-chart-line" style="font-size: 48px; color: #2563eb; margin-bottom: 15px; display: block;"></i>
          Esta funcionalidad estará disponible próximamente.
        </p>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="document.getElementById('modalPromedios').close()">Cerrar</button>
      </div>
    </dialog>

    <!-- MODAL: VER DETALLES -->
    <dialog id="modalVerMateria" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Detalles de la Materia</h3>
      <div class="modal-body" id="contenidoVerMateria"></div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="document.getElementById('modalVerMateria').close()">Cerrar</button>
      </div>
    </dialog>

    <!-- MODAL: AÑADIR MATERIA -->
    <dialog id="modalMateria" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Añadir Nueva Materia</h3>
      <form action="../actions/materias_action.php" method="POST" class="modal-form" onsubmit="return validarFormularioMateria(this)">
        <input type="hidden" name="accion" value="agregar">
        
        <label>Código de Materia:</label>
        <input type="text" name="codigo" required placeholder="Ej: MAT001" maxlength="20">

        <label>Nombre de la Materia:</label>
        <input type="text" name="nombre" required placeholder="Ej: Matemáticas I">

        <label>Carreras (selecciona una o varias):</label>
        <div class="checkbox-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
          <?php foreach ($carreras as $c): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="carreras[]" value="<?php echo $c['id']; ?>">
              <?php echo htmlspecialchars($c['nombre']); ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label>Docentes (selecciona uno o más):</label>
        <div class="checkbox-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
          <?php foreach ($profesores as $p): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="docentes[]" value="<?php echo $p['id']; ?>">
              <?php echo htmlspecialchars($p['nombre_completo']); ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label>Secciones (selecciona una o varias):</label>
        <div class="checkbox-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
          <?php foreach ($secciones as $s): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="secciones[]" value="<?php echo $s['id']; ?>">
              <?php echo htmlspecialchars($s['nombre']); ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label>Descripción:</label>
        <textarea name="descripcion" rows="3" placeholder="Descripción opcional de la materia..."></textarea>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="document.getElementById('modalMateria').close()">Cancelar</button>
          <button type="submit" class="btn-save">Guardar Materia</button>
        </div>
      </form>
    </dialog>

    <!-- MODAL: EDITAR MATERIA -->
    <dialog id="modalEditar" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Editar Materia</h3>
      <form action="../actions/materias_action.php" method="POST" class="modal-form" onsubmit="return validarFormularioMateria(this)">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="materia_id" id="edit_materia_id">

        <label>Código de Materia:</label>
        <input type="text" id="edit_codigo" name="codigo" required maxlength="20">

        <label>Nombre de la Materia:</label>
        <input type="text" id="edit_nombre" name="nombre" required>

        <label>Carreras (selecciona una o varias):</label>
        <div class="checkbox-group" id="edit_carreras_container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
          <?php foreach ($carreras as $c): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="carreras[]" value="<?php echo $c['id']; ?>" class="edit_carrera_check">
              <?php echo htmlspecialchars($c['nombre']); ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label>Docentes (selecciona uno o más):</label>
        <div class="checkbox-group" id="edit_docentes_container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
          <?php foreach ($profesores as $p): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="docentes[]" value="<?php echo $p['id']; ?>" class="edit_docente_check">
              <?php echo htmlspecialchars($p['nombre_completo']); ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label>Secciones (selecciona una o varias):</label>
        <div class="checkbox-group" id="edit_secciones_container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
          <?php foreach ($secciones as $s): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="secciones[]" value="<?php echo $s['id']; ?>" class="edit_seccion_check">
              <?php echo htmlspecialchars($s['nombre']); ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label>Descripción:</label>
        <textarea id="edit_descripcion" name="descripcion" rows="3"></textarea>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditar').close()">Cancelar</button>
          <button type="submit" class="btn-save">Actualizar Cambios</button>
        </div>
      </form>
    </dialog>

    <footer class="footer">
      <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>

    <script>
      // Inyectamos las asignaciones existentes para el modal de edición
      window.asignacionesMaterias = <?php echo json_encode($asignaciones); ?>;
    </script>
  </body>
</html>