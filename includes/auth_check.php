<?php
// includes/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión iniciada, lo regresa al login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Vistas/index.php?error=sesion");
    exit;
}

// ==========================================
// CARGAR INFORMACIÓN DEL USUARIO Y ROL
// ==========================================
require_once '../config/database.php';
require_once '../includes/permisos.php';

try {
    $stmt = $pdo->prepare("SELECT id, usuario, rol, id_profesor FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();
    
    if ($userData) {
        $_SESSION['user_rol'] = $userData['rol'];
        $_SESSION['username'] = $userData['usuario'];
        $_SESSION['id_profesor'] = $userData['id_profesor']; // ✅ Nuevo
        
        // Si es docente pero no tiene profesor vinculado, mostrar advertencia
        if (esDocente() && !$userData['id_profesor']) {
            $_SESSION['warning'] = 'Tu usuario de docente no tiene un perfil de profesor vinculado. Contacta al administrador.';
        }
    } else {
        session_destroy();
        header("Location: ../Vistas/index.php?error=sesion");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error cargando usuario: " . $e->getMessage());
    header("Location: ../Vistas/index.php?error=bd");
    exit;
}
?>