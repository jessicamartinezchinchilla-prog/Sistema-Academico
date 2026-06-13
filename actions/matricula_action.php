<?php
// actions/matricula_action.php
session_start();
require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/matricula.php?{$tipo}={$mensaje}");
        exit;
    }
}

function validarGmail($correo) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $correo);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        // Datos del estudiante
        $nie = trim($_POST['nie']);
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $dui = trim($_POST['dui']) ?: null;
        $edad = $_POST['edad'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $email = trim($_POST['email']);
        
        // Datos académicos
        $carrera_id = $_POST['carrera_id'];
        $grado_id = $_POST['grado_id'];
        $seccion_id = $_POST['seccion_id'];
        $estado = $_POST['estado'];
        
        // Datos del responsable
        $resp_dui = trim($_POST['responsable_dui']);
        $resp_nombres = trim($_POST['responsable_nombres']);
        $resp_apellidos = trim($_POST['responsable_apellidos']);
        $resp_ocupacion = trim($_POST['responsable_ocupacion']);
        $resp_parentesco = $_POST['responsable_parentesco'];
        $resp_email = trim($_POST['responsable_email']);
        $resp_telefono = trim($_POST['responsable_telefono']);
        $resp_direccion = trim($_POST['responsable_direccion']);

        // Validar correos Gmail
        if (!validarGmail($email) || !validarGmail($resp_email)) {
            responder('error', 'gmail', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            // 1. Insertar estudiante
            $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, dui, edad, fecha_nacimiento, telefono, direccion, email, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
            $stmt->execute([$nie, $nombres, $apellidos, $dui, $edad, $fecha_nacimiento, $telefono, $direccion, $email]);
            $id_estudiante = $pdo->lastInsertId();

            // 2. Insertar matrícula
            $stmt = $pdo->prepare("INSERT INTO matriculas (id_estudiante, id_carrera, id_grado, id_seccion, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_estudiante, $carrera_id, $grado_id, $seccion_id, $estado]);
            $id_matricula = $pdo->lastInsertId();

            // 3. Insertar responsable
            $stmt = $pdo->prepare("INSERT INTO responsables (id_matricula, dui, nombres, apellidos, ocupacion, parentesco, email, telefono, direccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_matricula, $resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion]);

            $pdo->commit();
            responder('success', '1', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'nie') !== false) responder('error', 'nie_duplicado', $isAjax);
            if (strpos($msg, 'dui') !== false) responder('error', 'dui_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'editar') {
        $matricula_id = $_POST['matricula_id'];
        
        // Obtener ID del estudiante de la matrícula
        $stmt = $pdo->prepare("SELECT id_estudiante FROM matriculas WHERE id = ?");
        $stmt->execute([$matricula_id]);
        $id_estudiante = $stmt->fetchColumn();

        // Datos del estudiante
        $nie = trim($_POST['nie']);
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $dui = trim($_POST['dui']) ?: null;
        $edad = $_POST['edad'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $email = trim($_POST['email']);
        
        // Datos académicos
        $carrera_id = $_POST['carrera_id'];
        $grado_id = $_POST['grado_id'];
        $seccion_id = $_POST['seccion_id'];
        $estado = $_POST['estado'];
        
        // Datos del responsable
        $resp_dui = trim($_POST['responsable_dui']);
        $resp_nombres = trim($_POST['responsable_nombres']);
        $resp_apellidos = trim($_POST['responsable_apellidos']);
        $resp_ocupacion = trim($_POST['responsable_ocupacion']);
        $resp_parentesco = $_POST['responsable_parentesco'];
        $resp_email = trim($_POST['responsable_email']);
        $resp_telefono = trim($_POST['responsable_telefono']);
        $resp_direccion = trim($_POST['responsable_direccion']);

        if (!validarGmail($email) || !validarGmail($resp_email)) {
            responder('error', 'gmail', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            // 1. Actualizar estudiante
            $stmt = $pdo->prepare("UPDATE estudiantes SET nombres=?, apellidos=?, dui=?, edad=?, fecha_nacimiento=?, telefono=?, direccion=?, email=? WHERE id=?");
            $stmt->execute([$nombres, $apellidos, $dui, $edad, $fecha_nacimiento, $telefono, $direccion, $email, $id_estudiante]);

            // 2. Actualizar matrícula
            $stmt = $pdo->prepare("UPDATE matriculas SET id_carrera=?, id_grado=?, id_seccion=?, estado=? WHERE id=?");
            $stmt->execute([$carrera_id, $grado_id, $seccion_id, $estado, $matricula_id]);

            // 3. Actualizar responsable
            $stmt = $pdo->prepare("UPDATE responsables SET dui=?, nombres=?, apellidos=?, ocupacion=?, parentesco=?, email=?, telefono=?, direccion=? WHERE id_matricula=?");
            $stmt->execute([$resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion, $matricula_id]);

            $pdo->commit();
            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'dui') !== false) responder('error', 'dui_duplicado', $isAjax);
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
            $stmt->execute([$id]);
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/matricula.php");
    exit;
}
?>