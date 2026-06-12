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
?>