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

// ✅ FUNCIÓN: Registrar evento en historial académico
function registrarEventoHistorial($pdo, $id_estudiante, $tipo_evento, $descripcion, $datos_adicionales = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO historial_academico (id_estudiante, tipo_evento, descripcion, datos_adicionales) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_estudiante, 
            $tipo_evento, 
            $descripcion, 
            $datos_adicionales ? json_encode($datos_adicionales, JSON_UNESCAPED_UNICODE) : null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar evento en historial: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    // ==========================================
    // AGREGAR ESTUDIANTE
    // ==========================================
    if ($accion === 'agregar') {
        $nie = trim($_POST['nie']);
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $estado = $_POST['estado'];

        try {
            $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nie, $nombres, $apellidos, $estado]);
            $id_estudiante = $pdo->lastInsertId();
            
            // ✅ REGISTRAR EVENTO EN HISTORIAL (si aplica)
            // Solo si realmente se crean estudiantes desde este panel
            $nombre_completo = $nombres . ' ' . $apellidos;
            $descripcion = "Estudiante nuevo '{$nombre_completo}' (NIE: {$nie}) creado con estado '{$estado}'";
            registrarEventoHistorial($pdo, $id_estudiante, 'estudiante_creado', $descripcion, [
                'nie' => $nie,
                'estado_inicial' => $estado
            ]);
            
            responder('success', '1', $isAjax);
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'nie') !== false) responder('error', 'nie_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    // ==========================================
    // EDITAR ESTUDIANTE
    // ==========================================
    if ($accion === 'editar') {
        $id = $_POST['id_estudiante'];
        $nie = trim($_POST['nie']);
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $estado = $_POST['estado'];

        try {
            // ✅ Obtener datos ANTERIORES para comparar
            $stmt = $pdo->prepare("SELECT estado FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            $estado_anterior = $stmt->fetchColumn();
            
            // Actualizar estudiante
            $stmt = $pdo->prepare("UPDATE estudiantes SET nie=?, nombres=?, apellidos=?, estado=? WHERE id=?");
            $stmt->execute([$nie, $nombres, $apellidos, $estado, $id]);
            
            // ✅ REGISTRAR EVENTO: Solo si cambió el estado
            // (los cambios de nombre/NIE no son relevantes para el historial académico)
            if (strtolower($estado_anterior) !== strtolower($estado)) {
                $nombre_completo = $nombres . ' ' . $apellidos;
                $descripcion = "Estado de '{$nombre_completo}' (NIE: {$nie}) cambiado de '" . ucfirst($estado_anterior) . "' a '" . ucfirst($estado) . "'";
                registrarEventoHistorial($pdo, $id, 'estado_cambiado', $descripcion, [
                    'nie' => $nie,
                    'estado_anterior' => $estado_anterior,
                    'estado_nuevo' => $estado
                ]);
            }
            
            responder('success', 'editado', $isAjax);
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'nie') !== false) responder('error', 'nie_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    // ==========================================
    // ELIMINAR ESTUDIANTE
    // ==========================================
    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            // ❌ NO registrar evento de eliminación porque CASCADE lo borrará inmediatamente
            // El historial académico se elimina junto con el estudiante
            
            $pdo->beginTransaction();
            
            // Desactivar restricciones de claves foráneas temporalmente
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // 1. Borrar responsables asociados a las matrículas del estudiante
            $stmt = $pdo->prepare("DELETE r FROM responsables r INNER JOIN matriculas m ON r.id_matricula = m.id WHERE m.id_estudiante = ?");
            $stmt->execute([$id]);

            // 2. Borrar matrículas del estudiante
            $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id_estudiante = ?");
            $stmt->execute([$id]);

            // 3. Borrar al estudiante (y sus calificaciones/historial por CASCADE)
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