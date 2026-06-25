<?php
// actions/login_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($usuario) || empty($password)) {
        header("Location: ../Vistas/index.php?error=campos_vacios");
        exit;
    }

    try {
        // Buscamos el usuario con su rol
        $stmt = $pdo->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Guardamos los datos en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['user_rol'] = $user['rol'];
            
            // ✅ AUDITORÍA: Registrar inicio de sesión exitoso
            require_once '../includes/permisos.php';
            registrarAuditoria($pdo, 'inicio_sesion', 'sistema', 
                "El usuario '{$user['usuario']}' (" . getRolNombre($user['rol']) . ") inició sesión");
            
            // Redirigimos al panel principal
            header("Location: ../Vistas/panel_principal.php");
            exit;
        } else {
            // Credenciales incorrectas
            header("Location: ../Vistas/index.php?error=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    header("Location: ../Vistas/index.php");
    exit;
}
?>