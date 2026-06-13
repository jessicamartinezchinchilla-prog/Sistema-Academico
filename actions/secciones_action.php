<?php
// actions/secciones_action.php
session_start();
require_once '../config/database.php';

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
        $nombre = trim($_POST['nombre']);
        $letra = strtoupper(trim($_POST['letra']));
        $id_carrera = $_POST['id_carrera'];
        $id_grado = $_POST['id_grado'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $profesores = $_POST['profesores'] ?? [];

        try {
            $pdo->beginTransaction();
            
            // Insertar sección
            $stmt = $pdo->prepare("INSERT INTO secciones (nombre, letra, id_carrera, id_grado, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $letra, $id_carrera, $id_grado, $descripcion]);
            $id_seccion = $pdo->lastInsertId();

            // Insertar asignaciones de profesores
            if (!empty($profesores)) {
                $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_seccion) VALUES (?, ?)");
                foreach ($profesores as $id_profesor) {
                    $stmt->execute([$id_profesor, $id_seccion]);
                }
            }

            $pdo->commit();
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
        $nombre = trim($_POST['nombre']);
        $letra = strtoupper(trim($_POST['letra']));
        $id_carrera = $_POST['id_carrera'];
        $id_grado = $_POST['id_grado'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $profesores = $_POST['profesores'] ?? [];

        try {
            $pdo->beginTransaction();
            
            // Actualizar sección
            $stmt = $pdo->prepare("UPDATE secciones SET nombre=?, letra=?, id_carrera=?, id_grado=?, descripcion=? WHERE id=?");
            $stmt->execute([$nombre, $letra, $id_carrera, $id_grado, $descripcion, $id]);

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
            $stmt = $pdo->prepare("DELETE FROM secciones WHERE id = ?");
            $stmt->execute([$id]);
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/secciones.php");
    exit;
}
?>