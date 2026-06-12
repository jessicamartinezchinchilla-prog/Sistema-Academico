<?php
// actions/profesores_action.php
session_start();
require_once '../config/database.php';

// Detectar si la petición es AJAX (desde JavaScript)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        // Enviar en MAYÚSCULAS para que el JS lo detecte bien
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/profesores.php?{$tipo}={$mensaje}");
        exit;
    }
}

function validarGmail($correo) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $correo);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $dui = trim($_POST['dui']);
        $nip = trim($_POST['nip']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $id_materia = $_POST['id_materia'];
        $secciones = $_POST['id_seccion'];

        if (!validarGmail($correo)) responder('error', 'gmail', $isAjax);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO profesores (nombres, apellidos, dui, nip, correo, telefono) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombres, $apellidos, $dui, $nip, $correo, $telefono]);
            $id_profesor = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_materia, id_seccion) VALUES (?, ?, ?)");
            foreach ($secciones as $id_sec) {
                $stmt->execute([$id_profesor, $id_materia, $id_sec]);
            }
            $pdo->commit();
            responder('success', '1', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            
            // Detectar qué campo causó el duplicado
            if (strpos($msg, 'dui') !== false) responder('error', 'dui_duplicado', $isAjax);
            if (strpos($msg, 'nip') !== false) responder('error', 'nip_duplicado', $isAjax);
            if (strpos($msg, 'correo') !== false) responder('error', 'correo_duplicado', $isAjax);
            if (strpos($msg, 'telefono') !== false) responder('error', 'telefono_duplicado', $isAjax); // <--- ¡AGREGA ESTO!
            
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'editar') {
        $id = $_POST['id_profesor'];
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $dui = trim($_POST['dui']);
        $nip = trim($_POST['nip']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $id_materia = $_POST['id_materia'];
        $secciones = $_POST['id_seccion'];

        if (!validarGmail($correo)) responder('error', 'gmail', $isAjax);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE profesores SET nombres=?, apellidos=?, dui=?, nip=?, correo=?, telefono=? WHERE id=?");
            $stmt->execute([$nombres, $apellidos, $dui, $nip, $correo, $telefono, $id]);

            $stmt = $pdo->prepare("DELETE FROM profesor_asignacion WHERE id_profesor = ?");
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_materia, id_seccion) VALUES (?, ?, ?)");
            foreach ($secciones as $id_sec) {
                $stmt->execute([$id, $id_materia, $id_sec]);
            }
            $pdo->commit();
            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'dui') !== false) responder('error', 'dui_duplicado', $isAjax);
            if (strpos($msg, 'nip') !== false) responder('error', 'nip_duplicado', $isAjax);
            if (strpos($msg, 'correo') !== false) responder('error', 'correo_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM profesores WHERE id = ?");
            $stmt->execute([$id]);
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }
}

// Si llega aquí, redirigir por defecto
if (!$isAjax) {
    header("Location: ../Vistas/profesores.php");
    exit;
}
?>