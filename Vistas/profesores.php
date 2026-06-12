<?php
// Vistas/profesores.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Consulta actualizada para traer TODOS los id_seccion (separados por coma)
$query = "SELECT 
            p.id, p.nombres, p.apellidos, p.dui, p.nip, p.correo, p.telefono,
            CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
            GROUP_CONCAT(DISTINCT m.nombre SEPARATOR ', ') as materias,
            GROUP_CONCAT(DISTINCT s.nombre SEPARATOR ', ') as secciones,
            MIN(pa.id_materia) as id_materia,
            GROUP_CONCAT(DISTINCT pa.id_seccion) as id_secciones
          FROM profesores p
          LEFT JOIN profesor_asignacion pa ON p.id = pa.id_profesor
          LEFT JOIN materias m ON pa.id_materia = m.id
          LEFT JOIN secciones s ON pa.id_seccion = s.id
          GROUP BY p.id
          ORDER BY p.nombres";
$profesores = $pdo->query($query)->fetchAll();

$totalProfesores = count($profesores);
$totalMateriasCubiertas = $pdo->query("SELECT COUNT(DISTINCT id_materia) FROM profesor_asignacion")->fetchColumn();

$materias = $pdo->query("SELECT * FROM materias ORDER BY nombre")->fetchAll();
$secciones = $pdo->query("SELECT * FROM secciones ORDER BY nombre")->fetchAll();
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../CSS/profesores.css" />
    <script src="../JS/profesores.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
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
                    <a href="../actions/profesores_action.php?accion=logout" style="color: #fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
      <h2>Gestión de Profesores</h2>
      <p>Administración de personal docente</p>

      <section class="actions-bar">
        <button type="button" class="btn-primary" onclick="abrirModalAgregar()">
          <i class="fa-solid fa-plus"></i> Agregar Profesor
        </button>
      </section>

      <section class="stats-summary">
        <article class="stat-item">
          <span class="stat-number"><?php echo $totalProfesores; ?></span>
          <span class="stat-label">Total profesores</span>
        </article>
        <article class="stat-item">
          <span class="stat-number"><?php echo $totalMateriasCubiertas; ?></span>
          <span class="stat-label">Materias cubiertas</span>
        </article>
      </section>

      <section class="filters-bar">
        <div class="busqueda">
          <input type="search" id="buscarProfesor" placeholder="Buscar por nombre, DUI o NIP..." />
        </div>
        <div class="filtros">
          <select id="filtroMateria">
            <option value="">Todas las materias</option>
            <?php foreach ($materias as $m): ?>
              <option value="<?php echo $m['nombre']; ?>"><?php echo $m['nombre']; ?></option>
            <?php endforeach; ?>
          </select>
          <select id="filtroSeccion">
            <option value="">Todas las secciones</option>
            <?php foreach ($secciones as $s): ?>
              <option value="<?php echo $s['nombre']; ?>"><?php echo $s['nombre']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </section>

      <section class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nombre completo</th>
              <th>Correo electrónico</th>
              <th>Materia asignada</th>
              <th>Secciones</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="listaProfesores">
            <?php if (empty($profesores)): ?>
              <tr><td colspan="5" style="text-align:center; padding:40px;">No hay profesores registrados.</td></tr>
            <?php else: ?>
              <?php foreach ($profesores as $prof): ?>
                <tr data-id="<?php echo $prof['id']; ?>" 
                    data-nombres="<?php echo htmlspecialchars($prof['nombres'] ?? ''); ?>"
                    data-apellidos="<?php echo htmlspecialchars($prof['apellidos'] ?? ''); ?>"
                    data-dui="<?php echo htmlspecialchars($prof['dui'] ?? ''); ?>"
                    data-nip="<?php echo htmlspecialchars($prof['nip'] ?? ''); ?>"
                    data-correo="<?php echo htmlspecialchars($prof['correo'] ?? ''); ?>"
                    data-telefono="<?php echo htmlspecialchars($prof['telefono'] ?? ''); ?>"
                    data-materia-id="<?php echo $prof['id_materia'] ?? ''; ?>"
                    data-seccion-ids="<?php echo htmlspecialchars($prof['id_secciones'] ?? ''); ?>"
                    data-materia-nombre="<?php echo htmlspecialchars($prof['materias'] ?? ''); ?>"
                    data-seccion-nombre="<?php echo htmlspecialchars($prof['secciones'] ?? ''); ?>">
                  <td><?php echo $prof['nombre_completo']; ?></td>
                  <td><?php echo $prof['correo'] ?? 'No registrado'; ?></td>
                  <td><span class="materia-badge"><?php echo $prof['materias'] ?? 'Sin asignar'; ?></span></td>
                  <td>
                    <div class="seccion-container">
                      <?php 
                      if ($prof['secciones']) {
                        foreach (explode(', ', $prof['secciones']) as $sec) {
                          echo '<span class="seccion-badge">' . $sec . '</span>';
                        }
                      } else { echo 'Sin asignar'; }
                      ?>
                    </div>
                  </td>
                  <td class="actions-cell">
                    <button class="btn-action see" onclick="verProfesor(this)" title="Ver detalles"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-action edit" onclick="editarProfesor(this)" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                    <a href="../actions/profesores_action.php?accion=eliminar&id=<?php echo $prof['id']; ?>" class="btn-action delete" onclick="return confirm('¿Estás seguro de eliminar a este profesor?')" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <p class="empty-state" id="mensajeVacio">No se encontraron profesores con esos filtros.</p>
    </main>

    <!-- MODAL 1: AGREGAR -->
    <dialog id="modalProfesor" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Agregar Nuevo Profesor</h3>
      <!-- onsubmit llama a la validación JS -->
      <form class="modal-form" method="POST" action="../actions/profesores_action.php" onsubmit="return validarFormulario(this)">
        <input type="hidden" name="accion" value="agregar">
        <h4>Datos personales</h4>
        <div class="form-row">
          <div class="form-col">
            <label>Nombres (2 nombres):</label>
            <input type="text" id="add_nombres" name="nombres" class="input-nombre" required placeholder="Ej: Juan Carlos" />
          </div>
          <div class="form-col">
            <label>Apellidos (2 apellidos):</label>
            <input type="text" id="add_apellidos" name="apellidos" class="input-nombre" required placeholder="Ej: Pérez López" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label>DUI:</label>
            <input type="text" id="add_dui" name="dui" class="input-dui" required maxlength="10" placeholder="00000000-0" pattern="\d{8}-\d{1}" title="Formato: 12345678-9" />
          </div>
          <div class="form-col">
            <label>NIP:</label>
            <input type="text" name="nip" required />
          </div>
        </div>
        <h4>Contacto</h4>
        <label>Correo (Solo Gmail):</label>
        <!-- En el modal de AGREGAR -->
        <input type="email" id="add_correo" name="correo" required 
          placeholder="usuario@gmail.com" 
          pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
          title="Debe ser un correo @gmail.com" 
        />
        <label>Teléfono:</label>
        <input type="tel" id="add_telefono" name="telefono" class="input-tel" required maxlength="9" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="Formato: 1234-5678" />
        
        <h4>Asignación</h4>
        <label>Materia:</label>
        <select name="id_materia" required>
          <option value="">Seleccione</option>
          <?php foreach ($materias as $m): ?><option value="<?php echo $m['id']; ?>"><?php echo $m['nombre']; ?></option><?php endforeach; ?>
        </select>
        
        <label style="margin-top: 10px; display:block;">Secciones (Seleccione al menos una):</label>
        <div class="checkbox-group" style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 5px;">
          <?php foreach ($secciones as $s): ?>
            <label style="display: flex; align-items: center; gap: 5px; font-weight: normal; cursor: pointer;">
              <input type="checkbox" name="id_seccion[]" value="<?php echo $s['id']; ?>">
              <?php echo $s['nombre']; ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="document.getElementById('modalProfesor').close()">Cancelar</button>
          <button type="submit" class="btn-save">Guardar</button>
        </div>
      </form>
    </dialog>

    <!-- MODAL 2: VER DETALLES -->
    <dialog id="modalVerProfesor" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Detalles del Profesor</h3>
      <div class="modal-form" id="contenidoVerProfesor"></div>
    </dialog>

    <!-- MODAL 3: EDITAR -->
    <dialog id="modalEditarProfesor" class="modal">
      <form method="dialog" class="modal-header">
        <button type="submit" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <h3>Editar Profesor</h3>
      <form class="modal-form" method="POST" action="../actions/profesores_action.php" onsubmit="return validarFormulario(this)">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_profesor" id="edit_id">
        
        <h4>Datos personales</h4>
        <div class="form-row">
          <div class="form-col"><label>Nombres:</label><input type="text" id="edit_nombres" name="nombres" class="input-nombre" required /></div>
          <div class="form-col"><label>Apellidos:</label><input type="text" id="edit_apellidos" name="apellidos" class="input-nombre" required /></div>
        </div>
        <div class="form-row">
          <div class="form-col"><label>DUI:</label><input type="text" id="edit_dui" name="dui" class="input-dui" required maxlength="10" pattern="\d{8}-\d{1}" /></div>
          <div class="form-col"><label>NIP:</label><input type="text" name="nip" id="edit_nip" required /></div>
        </div>
        <h4>Contacto</h4>
        <!-- En el modal de EDITAR -->
        <input type="email" id="edit_correo" name="correo" required 
          pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
          title="Debe ser un correo @gmail.com"
        />
        <label>Teléfono:</label><input type="tel" id="edit_telefono" name="telefono" class="input-tel" required maxlength="9" pattern="\d{4}-\d{4}" />
        
        <h4>Asignación</h4>
        <label>Materia:</label>
        <select name="id_materia" id="edit_materia" required>
          <?php foreach ($materias as $m): ?><option value="<?php echo $m['id']; ?>"><?php echo $m['nombre']; ?></option><?php endforeach; ?>
        </select>
        
        <label style="margin-top: 10px; display:block;">Secciones:</label>
        <div class="checkbox-group" id="edit_secciones_container" style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 5px;">
          <!-- Se llena dinámicamente con JS para mantener los valores -->
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditarProfesor').close()">Cancelar</button>
          <button type="submit" class="btn-save">Actualizar</button>
        </div>
      </form>
    </dialog>

    <footer class="footer">
      <p>&copy; 2026 Sistema Académico. Todos los derechos reservados.</p>
    </footer>
    <script>
    // Inyectamos las secciones del sistema para el modal de edición
    window.seccionesSistema = <?php echo json_encode($secciones); ?>;
    </script>
  </body>
</html>