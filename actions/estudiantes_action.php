<?php
// actions/estudiantes_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php'; // ✅ AUDITORÍA

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
            
            // ✅ REGISTRAR EVENTO EN HISTORIAL
            $nombre_completo = $nombres . ' ' . $apellidos;
            $descripcion = "Estudiante nuevo '{$nombre_completo}' (NIE: {$nie}) creado con estado '{$estado}'";
            registrarEventoHistorial($pdo, $id_estudiante, 'estudiante_creado', $descripcion, [
                'nie' => $nie,
                'estado_inicial' => $estado
            ]);
            
            // ✅ AUDITORÍA
            registrarAuditoria($pdo, 'creacion', 'estudiantes', "Se creó el estudiante '{$nombre_completo}' (NIE: {$nie}) con estado '{$estado}'");
            
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
            $stmt = $pdo->prepare("SELECT nie, nombres, apellidos, estado FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            $datos_anteriores = $stmt->fetch(PDO::FETCH_ASSOC);
            $estado_anterior = $datos_anteriores['estado'] ?? '';
            $nombre_anterior = ($datos_anteriores['nombres'] ?? '') . ' ' . ($datos_anteriores['apellidos'] ?? '');
            $nie_anterior = $datos_anteriores['nie'] ?? '';
            
            // Actualizar estudiante
            $stmt = $pdo->prepare("UPDATE estudiantes SET nie=?, nombres=?, apellidos=?, estado=? WHERE id=?");
            $stmt->execute([$nie, $nombres, $apellidos, $estado, $id]);
            
            // ✅ REGISTRAR EVENTOS EN HISTORIAL (solo si hubo cambios)
            $nombre_completo = $nombres . ' ' . $apellidos;
            
            // Si cambió el estado
            if (strtolower($estado_anterior) !== strtolower($estado)) {
                $descripcion = "Estado de '{$nombre_completo}' (NIE: {$nie}) cambiado de '" . ucfirst($estado_anterior) . "' a '" . ucfirst($estado) . "'";
                registrarEventoHistorial($pdo, $id, 'estado_cambiado', $descripcion, [
                    'nie' => $nie,
                    'estado_anterior' => $estado_anterior,
                    'estado_nuevo' => $estado
                ]);
            }
            
            // Si cambió el nombre o NIE
            if ($nombre_anterior !== $nombre_completo || $nie_anterior !== $nie) {
                $descripcion = "Datos de estudiante actualizados: '{$nombre_completo}' (NIE: {$nie})";
                $cambios = [];
                if ($nombre_anterior !== $nombre_completo) {
                    $cambios['nombre_anterior'] = $nombre_anterior;
                    $cambios['nombre_nuevo'] = $nombre_completo;
                }
                if ($nie_anterior !== $nie) {
                    $cambios['nie_anterior'] = $nie_anterior;
                    $cambios['nie_nuevo'] = $nie;
                }
                registrarEventoHistorial($pdo, $id, 'matricula_modificada', $descripcion, $cambios);
            }
            
            // ✅ AUDITORÍA
            registrarAuditoria($pdo, 'modificacion', 'estudiantes', "Se modificó el estudiante '{$nombre_completo}' (NIE: {$nie})");
            
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
            // ✅ Obtener datos ANTES de eliminar (para el historial)
            $stmt = $pdo->prepare("SELECT nie, nombres, apellidos, estado FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            $datos_estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($datos_estudiante) {
                $nombre_completo = $datos_estudiante['nombres'] . ' ' . $datos_estudiante['apellidos'];
                $nie = $datos_estudiante['nie'];
                
                // ✅ Registrar evento ANTES de eliminar
                $descripcion = "Estudiante '{$nombre_completo}' (NIE: {$nie}) fue eliminado del sistema";
                registrarEventoHistorial($pdo, $id, 'estudiante_eliminado', $descripcion, [
                    'nie' => $nie,
                    'estado_al_eliminar' => $datos_estudiante['estado']
                ]);
                
                // ✅ AUDITORÍA
                registrarAuditoria($pdo, 'eliminacion', 'estudiantes', "Se eliminó al estudiante '{$nombre_completo}' (NIE: {$nie})");
            }
            
            $pdo->beginTransaction();
            
            // Desactivar restricciones de claves foráneas temporalmente
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // 1. Borrar responsables asociados a las matrículas del estudiante
            $stmt = $pdo->prepare("DELETE r FROM responsables r INNER JOIN matriculas m ON r.id_matricula = m.id WHERE m.id_estudiante = ?");
            $stmt->execute([$id]);

            // 2. Borrar matrículas del estudiante
            $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id_estudiante = ?");
            $stmt->execute([$id]);

            // 3. Borrar al estudiante (y sus calificaciones por CASCADE)
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