<?php
// actions/profesores_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php'; // ✅ AUDITORÍA

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/profesores.php?{$tipo}=" . urlencode($mensaje));
        exit;
    }
}

function validarGmail($correo) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $correo);
}

// ✅ FUNCIÓN: Validar que el DUI del profesor no exista en otras tablas
function validarDuiProfesor($pdo, $dui, $profesor_id_actual = null) {
    if (empty($dui)) return null;
    
    // Verificar en estudiantes
    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE dui = ?");
    $stmt->execute([$dui]);
    if ($stmt->fetch()) return 'dui_profesor_existe_estudiante';
    
    // Verificar en responsables
    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE dui = ?");
    $stmt->execute([$dui]);
    if ($stmt->fetch()) return 'dui_profesor_existe_responsable';
    
    // Verificar en profesores (excluyendo el actual)
    $sql = "SELECT id FROM profesores WHERE dui = ?";
    $params = [$dui];
    if ($profesor_id_actual) {
        $sql .= " AND id != ?";
        $params[] = $profesor_id_actual;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) return 'dui_duplicado';
    
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';
    
    // ==========================================
    // AGREGAR PROFESOR
    // ==========================================
    if ($accion === 'agregar') {
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $dui = trim($_POST['dui'] ?? '') ?: null;
        $nip = trim($_POST['nip'] ?? '') ?: null;
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $id_materias = $_POST['id_materias'] ?? [];

        if (!validarGmail($email)) {
            responder('error', 'gmail', $isAjax);
        }

        if (empty($id_materias)) {
            responder('error', 'sin_materias', $isAjax);
        }

        // ✅ Validar DUI único en TODO el sistema
        if ($dui) {
            $error_dui = validarDuiProfesor($pdo, $dui);
            if ($error_dui) {
                responder('error', $error_dui, $isAjax);
            }
        }

        try {
            $pdo->beginTransaction();

            if ($nip) {
                $stmt = $pdo->prepare("SELECT id FROM profesores WHERE nip = ?");
                $stmt->execute([$nip]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'nip_duplicado', $isAjax);
                }
            }

            $stmt = $pdo->prepare("SELECT id FROM profesores WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                responder('error', 'correo_duplicado', $isAjax);
            }

            $stmt = $pdo->prepare("INSERT INTO profesores (nombres, apellidos, dui, nip, telefono, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombres, $apellidos, $dui, $nip, $telefono, $email]);
            $id_profesor = $pdo->lastInsertId();

            // Insertar materias en profesor_materia
            $stmt = $pdo->prepare("INSERT INTO profesor_materia (id_profesor, id_materia) VALUES (?, ?)");
            foreach ($id_materias as $id_materia) {
                $stmt->execute([$id_profesor, $id_materia]);
            }

            $pdo->commit();
            
            // ✅ AUDITORÍA
            $nombre_completo = $nombres . ' ' . $apellidos;
            registrarAuditoria($pdo, 'creacion', 'profesores', "Se creó al profesor '{$nombre_completo}' con " . count($id_materias) . " materia(s) asignada(s)");
            
            responder('success', 'Profesor guardado exitosamente', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }
    
    // ==========================================
    // EDITAR PROFESOR
    // ==========================================
    if ($accion === 'editar') {
        $profesor_id = $_POST['profesor_id'] ?? 0;
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $dui = trim($_POST['dui'] ?? '') ?: null;
        $nip = trim($_POST['nip'] ?? '') ?: null;
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $id_materias = $_POST['id_materias'] ?? [];

        if (!validarGmail($email)) {
            responder('error', 'gmail', $isAjax);
        }

        if (empty($id_materias)) {
            responder('error', 'sin_materias', $isAjax);
        }

        // ✅ Validar DUI único en TODO el sistema
        if ($dui) {
            $error_dui = validarDuiProfesor($pdo, $dui, $profesor_id);
            if ($error_dui) {
                responder('error', $error_dui, $isAjax);
            }
        }

        try {
            $pdo->beginTransaction();

            if ($nip) {
                $stmt = $pdo->prepare("SELECT id FROM profesores WHERE nip = ? AND id != ?");
                $stmt->execute([$nip, $profesor_id]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'nip_duplicado', $isAjax);
                }
            }

            $stmt = $pdo->prepare("SELECT id FROM profesores WHERE email = ? AND id != ?");
            $stmt->execute([$email, $profesor_id]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                responder('error', 'correo_duplicado', $isAjax);
            }

            // Actualizar profesor
            $stmt = $pdo->prepare("UPDATE profesores SET nombres=?, apellidos=?, dui=?, nip=?, telefono=?, email=? WHERE id=?");
            $stmt->execute([$nombres, $apellidos, $dui, $nip, $telefono, $email, $profesor_id]);

            // ✅ Eliminar SOLO las asignaciones de profesor_materia (NO tocar asignaciones)
            $stmt = $pdo->prepare("DELETE FROM profesor_materia WHERE id_profesor = ?");
            $stmt->execute([$profesor_id]);

            // ✅ Insertar nuevas asignaciones en profesor_materia
            $stmt = $pdo->prepare("INSERT INTO profesor_materia (id_profesor, id_materia) VALUES (?, ?)");
            foreach ($id_materias as $id_materia) {
                $stmt->execute([$profesor_id, $id_materia]);
            }

            $pdo->commit();
            
            // ✅ AUDITORÍA
            $nombre_completo = $nombres . ' ' . $apellidos;
            registrarAuditoria($pdo, 'modificacion', 'profesores', "Se modificó al profesor '{$nombre_completo}' con " . count($id_materias) . " materia(s) asignada(s)");
            
            responder('success', 'Profesor actualizado exitosamente', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }
}

// ==========================================
// ELIMINAR PROFESOR
// ==========================================
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $id = $_GET['id'] ?? 0;
    
    try {
        // ✅ AUDITORÍA: Obtener datos ANTES de eliminar
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM profesores WHERE id = ?");
        $stmt->execute([$id]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmt = $pdo->prepare("DELETE FROM profesores WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // ✅ AUDITORÍA
        if ($datos) {
            $nombre_completo = $datos['nombres'] . ' ' . $datos['apellidos'];
            registrarAuditoria($pdo, 'eliminacion', 'profesores', "Se eliminó al profesor '{$nombre_completo}'");
        }
        
        header("Location: ../Vistas/profesores.php?success=eliminado");
        exit;
        
    } catch (PDOException $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: ../Vistas/profesores.php?error=bd");
        exit;
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/profesores.php");
    exit;
}
?>