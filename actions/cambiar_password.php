<?php
// actions/cambiar_password.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Vistas/configuracion.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$password_actual = $_POST['password_actual'] ?? '';
$nueva_password = $_POST['nueva_password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';

// 1. Validaciones básicas
if (empty($password_actual) || empty($nueva_password) || empty($confirmar_password)) {
    header("Location: ../Vistas/configuracion.php?error=campos_vacios");
    exit;
}

if ($nueva_password !== $confirmar_password) {
    header("Location: ../Vistas/configuracion.php?error=password_no_coincide");
    exit;
}

if (strlen($nueva_password) < 8) {
    header("Location: ../Vistas/configuracion.php?error=password_corta");
    exit;
}

try {
    // 2. Obtener contraseña actual de la BD
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: ../Vistas/configuracion.php?error=usuario_no_encontrado");
        exit;
    }

    // 3. Verificar contraseña actual
    if (!password_verify($password_actual, $user['password'])) {
        header("Location: ../Vistas/configuracion.php?error=password_incorrecta");
        exit;
    }

    // 4. Encriptar y guardar nueva contraseña
    $nueva_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->execute([$nueva_hash, $user_id]);

    // ✅ AUDITORÍA
    $usuario = $_SESSION['username'] ?? 'Desconocido';
    registrarAuditoria($pdo, 'modificacion', 'seguridad', "El usuario '{$usuario}' cambió su contraseña");

    header("Location: ../Vistas/configuracion.php?success=password_cambiada");
    exit;

} catch (PDOException $e) {
    header("Location: ../Vistas/configuracion.php?error=bd");
    exit;
}
?>