<?php
// includes/audit.php

/**
 * Registra una acción en el sistema de auditoría
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param string $accion Tipo de acción (inicio_sesion, creacion, modificacion, eliminacion, exportacion)
 * @param string $modulo Módulo afectado (estudiantes, profesores, calificaciones, etc.)
 * @param string $descripcion Descripción legible de la acción
 * @return bool True si se registró correctamente, false en caso de error
 */
function registrarAuditoria($pdo, $accion, $modulo, $descripcion) {
    try {
        // Obtener usuario de la sesión (si existe)
        $usuario = 'Sistema';
        if (isset($_SESSION['usuario_nombre'])) {
            $usuario = $_SESSION['usuario_nombre'];
        } elseif (isset($_SESSION['username'])) {
            $usuario = $_SESSION['username'];
        } elseif (isset($_SESSION['user'])) {
            $usuario = $_SESSION['user'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario, accion, modulo, descripcion) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$usuario, $accion, $modulo, $descripcion]);
        
    } catch (PDOException $e) {
        // Registrar error pero no romper la operación principal
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}
?>