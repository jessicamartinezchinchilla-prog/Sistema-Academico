<?php
// actions/logout.php
session_start();
require_once '../config/database.php'; // ✅ AUDITORÍA
require_once '../includes/audit.php'; // ✅ AUDITORÍA

// ✅ AUDITORÍA: Obtener nombre del usuario ANTES de destruir la sesión
$usuario_logout = $_SESSION['username'] ?? 'Desconocido';

// ✅ AUDITORÍA: Registrar cierre de sesión
registrarAuditoria($pdo, 'cierre_sesion', 'sistema', "El usuario '{$usuario_logout}' cerró sesión");

session_unset(); // Borra todas las variables de sesión
session_destroy(); // Destruye la sesión
header("Location: ../Vistas/index.php");
exit;
?>