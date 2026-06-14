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
        $tipo_estudiante = $_POST['tipo_estudiante'];
        $id_seccion = $_POST['id_seccion'];
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

        if (!validarGmail($resp_email)) {
            responder('error', 'gmail', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            $id_estudiante = null;
            
            if ($tipo_estudiante === 'existente') {
                // Usar estudiante existente
                $id_estudiante = $_POST['id_estudiante_existente'];
                if (!$id_estudiante) {
                    $pdo->rollBack();
                    responder('error', 'sin_estudiante', $isAjax);
                }
            } else {
                // Crear estudiante nuevo
                $nie = trim($_POST['nie']);
                $nombres = trim($_POST['nombres']);
                $apellidos = trim($_POST['apellidos']);
                $dui = trim($_POST['dui']) ?: null;
                $edad = $_POST['edad'] ?: null;
                $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
                $telefono = trim($_POST['telefono']) ?: null;
                $email = trim($_POST['email']) ?: null;
                $direccion = trim($_POST['direccion']) ?: null;

                $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, dui, edad, fecha_nacimiento, telefono, email, direccion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
                $stmt->execute([$nie, $nombres, $apellidos, $dui, $edad, $fecha_nacimiento, $telefono, $email, $direccion]);
                $id_estudiante = $pdo->lastInsertId();
            }

            // Insertar matrícula
            $stmt = $pdo->prepare("INSERT INTO matriculas (id_estudiante, id_seccion, estado) VALUES (?, ?, ?)");
            $stmt->execute([$id_estudiante, $id_seccion, $estado]);
            $id_matricula = $pdo->lastInsertId();

            // Insertar responsable
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
        $id_estudiante = $_POST['id_estudiante'];
        $id_seccion = $_POST['id_seccion'];
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

        if (!validarGmail($resp_email)) {
            responder('error', 'gmail', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE matriculas SET id_estudiante=?, id_seccion=?, estado=? WHERE id=?");
            $stmt->execute([$id_estudiante, $id_seccion, $estado, $matricula_id]);

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
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $stmt = $pdo->prepare("DELETE FROM responsables WHERE id_matricula = ?");
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/matricula.php");
    exit;
}
?>