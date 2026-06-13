<?php
// actions/materias_action.php
session_start();
require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/materias.php?{$tipo}={$mensaje}");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $carreras = $_POST['carreras'] ?? [];
        $docentes = $_POST['docentes'] ?? [];
        $secciones = $_POST['secciones'] ?? [];

        if (empty($carreras)) {
            responder('error', 'sin_carreras', $isAjax);
        }

        if (empty($docentes)) {
            responder('error', 'sin_docentes', $isAjax);
        }

        if (empty($secciones)) {
            responder('error', 'sin_secciones', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            // Insertar materia
            $stmt = $pdo->prepare("INSERT INTO materias (codigo, nombre, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $descripcion]);
            $id_materia = $pdo->lastInsertId();

            // Insertar relaciones con carreras
            $stmt = $pdo->prepare("INSERT INTO materias_carreras (id_materia, id_carrera) VALUES (?, ?)");
            foreach ($carreras as $id_carrera) {
                $stmt->execute([$id_materia, $id_carrera]);
            }

            // Insertar asignaciones de docentes y secciones
            $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_materia, id_seccion) VALUES (?, ?, ?)");
            foreach ($docentes as $id_docente) {
                foreach ($secciones as $id_seccion) {
                    $stmt->execute([$id_docente, $id_materia, $id_seccion]);
                }
            }

            $pdo->commit();
            responder('success', '1', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'codigo') !== false) responder('error', 'codigo_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'editar') {
        $id = $_POST['materia_id'];
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $carreras = $_POST['carreras'] ?? [];
        $docentes = $_POST['docentes'] ?? [];
        $secciones = $_POST['secciones'] ?? [];

        if (empty($carreras)) {
            responder('error', 'sin_carreras', $isAjax);
        }

        if (empty($docentes)) {
            responder('error', 'sin_docentes', $isAjax);
        }

        if (empty($secciones)) {
            responder('error', 'sin_secciones', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            // Actualizar materia
            $stmt = $pdo->prepare("UPDATE materias SET codigo=?, nombre=?, descripcion=? WHERE id=?");
            $stmt->execute([$codigo, $nombre, $descripcion, $id]);

            // Eliminar relaciones anteriores con carreras
            $stmt = $pdo->prepare("DELETE FROM materias_carreras WHERE id_materia = ?");
            $stmt->execute([$id]);

            // Insertar nuevas relaciones con carreras
            $stmt = $pdo->prepare("INSERT INTO materias_carreras (id_materia, id_carrera) VALUES (?, ?)");
            foreach ($carreras as $id_carrera) {
                $stmt->execute([$id, $id_carrera]);
            }

            // Eliminar asignaciones anteriores de docentes
            $stmt = $pdo->prepare("DELETE FROM profesor_asignacion WHERE id_materia = ?");
            $stmt->execute([$id]);

            // Insertar nuevas asignaciones
            $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_materia, id_seccion) VALUES (?, ?, ?)");
            foreach ($docentes as $id_docente) {
                foreach ($secciones as $id_seccion) {
                    $stmt->execute([$id_docente, $id, $id_seccion]);
                }
            }

            $pdo->commit();
            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'codigo') !== false) responder('error', 'codigo_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM materias WHERE id = ?");
            $stmt->execute([$id]);
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/materias.php");
    exit;
}
?>