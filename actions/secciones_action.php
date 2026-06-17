<?php
// actions/secciones_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php'; // ✅ AUDITORÍA

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/secciones.php?{$tipo}={$mensaje}");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $nombreCarrera = trim($_POST['carrera']);
        $letra = strtoupper(trim($_POST['letra']));
        $id_grado = $_POST['id_grado'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $profesores = $_POST['profesores'] ?? [];

        if (empty($nombreCarrera)) {
            responder('error', 'sin_carrera', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            // Obtener o crear la carrera
            $stmt = $pdo->prepare("SELECT id FROM carreras WHERE nombre = ?");
            $stmt->execute([$nombreCarrera]);
            $id_carrera = $stmt->fetchColumn();
            
            if (!$id_carrera) {
                $stmt = $pdo->prepare("INSERT INTO carreras (nombre) VALUES (?)");
                $stmt->execute([$nombreCarrera]);
                $id_carrera = $pdo->lastInsertId();
            }
            
            // Obtener nombre del grado
            $stmt = $pdo->prepare("SELECT nombre FROM grados WHERE id = ?");
            $stmt->execute([$id_grado]);
            $nombreGrado = $stmt->fetchColumn();
            
            // Generar nombre de la sección
            $nombreSeccion = "{$nombreCarrera} - {$nombreGrado} - {$letra}";
            
            // Insertar sección
            $stmt = $pdo->prepare("INSERT INTO secciones (nombre, letra, id_carrera, id_grado, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombreSeccion, $letra, $id_carrera, $id_grado, $descripcion]);
            $id_seccion = $pdo->lastInsertId();

            // Insertar asignaciones de profesores
            if (!empty($profesores)) {
                $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_seccion) VALUES (?, ?)");
                foreach ($profesores as $id_profesor) {
                    $stmt->execute([$id_profesor, $id_seccion]);
                }
            }

            $pdo->commit();
            
            // ✅ AUDITORÍA
            $descripcionAuditoria = "Se creó la sección '{$nombreSeccion}'";
            if (!empty($profesores)) {
                $descripcionAuditoria .= " con " . count($profesores) . " profesor(es) asignado(s)";
            }
            registrarAuditoria($pdo, 'creacion', 'secciones', $descripcionAuditoria);
            
            responder('success', '1', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'unique_seccion') !== false || strpos($msg, 'Duplicate') !== false) {
                responder('error', 'duplicado', $isAjax);
            } else {
                responder('error', 'bd', $isAjax);
            }
        }
    }

    if ($accion === 'editar') {
        $id = $_POST['seccion_id'];
        $nombreCarrera = trim($_POST['carrera']);
        $letra = strtoupper(trim($_POST['letra']));
        $id_grado = $_POST['id_grado'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $profesores = $_POST['profesores'] ?? [];

        if (empty($nombreCarrera)) {
            responder('error', 'sin_carrera', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            // Obtener o crear la carrera
            $stmt = $pdo->prepare("SELECT id FROM carreras WHERE nombre = ?");
            $stmt->execute([$nombreCarrera]);
            $id_carrera = $stmt->fetchColumn();
            
            if (!$id_carrera) {
                $stmt = $pdo->prepare("INSERT INTO carreras (nombre) VALUES (?)");
                $stmt->execute([$nombreCarrera]);
                $id_carrera = $pdo->lastInsertId();
            }
            
            // Obtener nombre del grado
            $stmt = $pdo->prepare("SELECT nombre FROM grados WHERE id = ?");
            $stmt->execute([$id_grado]);
            $nombreGrado = $stmt->fetchColumn();
            
            // Generar nombre de la sección
            $nombreSeccion = "{$nombreCarrera} - {$nombreGrado} - {$letra}";
            
            // Actualizar sección
            $stmt = $pdo->prepare("UPDATE secciones SET nombre=?, letra=?, id_carrera=?, id_grado=?, descripcion=? WHERE id=?");
            $stmt->execute([$nombreSeccion, $letra, $id_carrera, $id_grado, $descripcion, $id]);

            // Eliminar asignaciones anteriores
            $stmt = $pdo->prepare("DELETE FROM profesor_asignacion WHERE id_seccion = ?");
            $stmt->execute([$id]);

            // Insertar nuevas asignaciones
            if (!empty($profesores)) {
                $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_seccion) VALUES (?, ?)");
                foreach ($profesores as $id_profesor) {
                    $stmt->execute([$id_profesor, $id]);
                }
            }

            $pdo->commit();
            
            // ✅ AUDITORÍA
            $descripcionAuditoria = "Se modificó la sección '{$nombreSeccion}'";
            if (!empty($profesores)) {
                $descripcionAuditoria .= " con " . count($profesores) . " profesor(es) asignado(s)";
            }
            registrarAuditoria($pdo, 'modificacion', 'secciones', $descripcionAuditoria);
            
            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'unique_seccion') !== false || strpos($msg, 'Duplicate') !== false) {
                responder('error', 'duplicado', $isAjax);
            } else {
                responder('error', 'bd', $isAjax);
            }
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            // ✅ AUDITORÍA: Obtener datos ANTES de eliminar
            $stmt = $pdo->prepare("SELECT nombre FROM secciones WHERE id = ?");
            $stmt->execute([$id]);
            $nombreSeccion = $stmt->fetchColumn();
            
            $pdo->beginTransaction();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // 1. Obtener el id_carrera de esta sección
            $stmt = $pdo->prepare("SELECT id_carrera FROM secciones WHERE id = ?");
            $stmt->execute([$id]);
            $id_carrera = $stmt->fetchColumn();
            
            // 2. Eliminar asignaciones de profesores
            $stmt = $pdo->prepare("DELETE FROM profesor_asignacion WHERE id_seccion = ?");
            $stmt->execute([$id]);
            
            // 3. Eliminar la sección
            $stmt = $pdo->prepare("DELETE FROM secciones WHERE id = ?");
            $stmt->execute([$id]);
            
            // 4. Verificar si la carrera ya no tiene secciones
            if ($id_carrera) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM secciones WHERE id_carrera = ?");
                $stmt->execute([$id_carrera]);
                $count = $stmt->fetchColumn();
                
                // Si no tiene más secciones, eliminar la carrera
                if ($count == 0) {
                    $stmt = $pdo->prepare("DELETE FROM carreras WHERE id = ?");
                    $stmt->execute([$id_carrera]);
                }
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->commit();
            
            // ✅ AUDITORÍA
            if ($nombreSeccion) {
                registrarAuditoria($pdo, 'eliminacion', 'secciones', "Se eliminó la sección '{$nombreSeccion}'");
            }
            
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/secciones.php");
    exit;
}
?>