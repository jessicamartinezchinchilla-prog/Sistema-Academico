<?php
// actions/usuarios_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';
require_once '../includes/permisos.php';

// Solo administradores pueden acceder
if (!esAdmin()) {
    header("Location: ../Vistas/panel_principal.php?error=acceso_denegado");
    exit;
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/usuarios.php?{$tipo}={$mensaje}");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = $_POST['rol'] ?? '';
        $id_profesor = !empty($_POST['id_profesor']) ? intval($_POST['id_profesor']) : null;

        if (empty($usuario) || empty($password) || empty($rol)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        if (strlen($password) < 6) {
            responder('error', 'password_corta', $isAjax);
        }

        // Si es docente, debe tener profesor vinculado
        if ($rol === 'docente' && !$id_profesor) {
            responder('error', 'docente_sin_profesor', $isAjax);
        }

        try {
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetch()) {
                responder('error', 'usuario_existente', $isAjax);
            }

            // Hashear contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, rol, id_profesor) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario, $hash, $rol, $id_profesor]);

            // Auditoría
            registrarAuditoria($pdo, 'creacion', 'usuarios', 
                "Se creó el usuario '{$usuario}' con rol '{$rol}'" . 
                ($id_profesor ? " vinculado al profesor ID {$id_profesor}" : ""));

            responder('success', '1', $isAjax);

        } catch (PDOException $e) {
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }

    if ($accion === 'editar') {
        $id = intval($_POST['usuario_id'] ?? 0);
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = $_POST['rol'] ?? '';
        $id_profesor = !empty($_POST['id_profesor']) ? intval($_POST['id_profesor']) : null;

        if (empty($usuario) || empty($rol)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        // Si es docente, debe tener profesor vinculado
        if ($rol === 'docente' && !$id_profesor) {
            responder('error', 'docente_sin_profesor', $isAjax);
        }

        try {
            // Verificar que el usuario existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                responder('error', 'usuario_no_encontrado', $isAjax);
            }

            // Preparar actualización
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    responder('error', 'password_corta', $isAjax);
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET usuario=?, password=?, rol=?, id_profesor=? WHERE id=?");
                $stmt->execute([$usuario, $hash, $rol, $id_profesor, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET usuario=?, rol=?, id_profesor=? WHERE id=?");
                $stmt->execute([$usuario, $rol, $id_profesor, $id]);
            }

            // Auditoría
            registrarAuditoria($pdo, 'modificacion', 'usuarios', 
                "Se modificó el usuario '{$usuario}' con rol '{$rol}'");

            responder('success', 'editado', $isAjax);

        } catch (PDOException $e) {
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }

    if ($accion === 'eliminar') {
        $id = intval($_GET['id'] ?? 0);

        try {
            // Obtener datos antes de eliminar
            $stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $usuarioData = $stmt->fetch();

            if (!$usuarioData) {
                responder('error', 'usuario_no_encontrado', $isAjax);
            }

            // No permitir eliminar el admin principal
            if ($usuarioData['usuario'] === 'admin') {
                responder('error', 'no_se_puede_eliminar_admin', $isAjax);
            }

            // Eliminar usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);

            // Auditoría
            registrarAuditoria($pdo, 'eliminacion', 'usuarios', 
                "Se eliminó el usuario '{$usuarioData['usuario']}'");

            responder('success', 'eliminado', $isAjax);

        } catch (PDOException $e) {
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/usuarios.php");
    exit;
}
?>