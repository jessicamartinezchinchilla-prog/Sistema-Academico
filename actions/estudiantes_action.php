<?php
// actions/estudiantes_action.php
session_start();
require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/estudiantes.php?{$tipo}={$mensaje}");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $nie = trim($_POST['nie']);
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $estado = $_POST['estado'];

        try {
            $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nie, $nombres, $apellidos, $estado]);
            responder('success', '1', $isAjax);
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'nie') !== false) responder('error', 'nie_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'editar') {
        $id = $_POST['id_estudiante'];
        $nie = trim($_POST['nie']);
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $estado = $_POST['estado'];

        try {
            $stmt = $pdo->prepare("UPDATE estudiantes SET nie=?, nombres=?, apellidos=?, estado=? WHERE id=?");
            $stmt->execute([$nie, $nombres, $apellidos, $estado, $id]);
            responder('success', 'editado', $isAjax);
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'nie') !== false) responder('error', 'nie_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            $pdo->beginTransaction();
            
            // Desactivar restricciones de claves foráneas temporalmente
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // 1. Borrar responsables asociados a las matrículas del estudiante
            $stmt = $pdo->prepare("DELETE r FROM responsables r INNER JOIN matriculas m ON r.id_matricula = m.id WHERE m.id_estudiante = ?");
            $stmt->execute([$id]);

            // 2. Borrar matrículas del estudiante
            $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id_estudiante = ?");
            $stmt->execute([$id]);

            // 3. Borrar al estudiante
            $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);

            // Reactivar restricciones
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $pdo->commit();
            responder('success', 'eliminado', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/estudiantes.php");
    exit;
}
?>