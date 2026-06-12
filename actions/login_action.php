<?php
// actions/login_action.php
session_start(); // Iniciamos la sesión para recordar al usuario
require_once '../config/database.php'; // Incluimos la conexión

// Verificamos que el formulario se haya enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validación básica
    if (empty($usuario) || empty($password)) {
        header("Location: ../Vistas/index.php?error=campos_vacios");
        exit;
    }

    try {
        // Buscamos el usuario en la base de datos (Sentencia preparada para evitar SQL Injection)
        $stmt = $pdo->prepare("SELECT id, usuario, password FROM usuarios WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        // Si el usuario existe Y la contraseña es correcta
        if ($user && password_verify($password, $user['password'])) {
            // Guardamos los datos en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            
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
    // Si intentan entrar a este archivo directamente sin hacer submit, los regresamos al login
    header("Location: ../Vistas/index.php");
    exit;
}
?>