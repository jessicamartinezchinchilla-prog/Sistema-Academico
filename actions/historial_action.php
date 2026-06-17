<?php
// actions/historial_action.php
session_start();
require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/historial_academico.php?{$tipo}={$mensaje}");
        exit;
    }
}

// ==========================================
// FUNCIÓN: REGISTRAR EVENTO EN HISTORIAL
// ==========================================
function registrarEvento($pdo, $id_estudiante, $tipo_evento, $descripcion, $datos_adicionales = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO historial_academico (id_estudiante, tipo_evento, descripcion, datos_adicionales) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_estudiante, 
            $tipo_evento, 
            $descripcion, 
            $datos_adicionales ? json_encode($datos_adicionales) : null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar evento en historial: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    // ==========================================
    // OBTENER HISTORIAL DE UN ESTUDIANTE
    // ==========================================
    if ($accion === 'obtener_historial') {
        $id_estudiante = $_GET['id_estudiante'] ?? 0;

        if (empty($id_estudiante)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Estudiante no especificado']);
                exit;
            }
        }

        try {
            // Verificar que el estudiante existe
            $stmt = $pdo->prepare("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre FROM estudiantes WHERE id = ?");
            $stmt->execute([$id_estudiante]);
            $estudiante = $stmt->fetch();
            
            if (!$estudiante) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Estudiante no encontrado']);
                    exit;
                }
            }

            // Obtener eventos ordenados por fecha (más reciente primero)
            $stmt = $pdo->prepare("
                SELECT id, tipo_evento, descripcion, datos_adicionales, fecha_registro
                FROM historial_academico
                WHERE id_estudiante = ?
                ORDER BY fecha_registro DESC
            ");
            $stmt->execute([$id_estudiante]);
            $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'estudiante' => $estudiante['nombre'],
                    'eventos' => $eventos,
                    'total' => count($eventos)
                ]);
                exit;
            }
            
        } catch (PDOException $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Error en la base de datos']);
                exit;
            }
        }
    }
    
    // ==========================================
    // REGISTRAR EVENTO (uso interno)
    // ==========================================
    if ($accion === 'registrar_evento') {
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $tipo_evento = $_POST['tipo_evento'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $datos_adicionales = $_POST['datos_adicionales'] ?? null;

        if (empty($id_estudiante) || empty($tipo_evento) || empty($descripcion)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        try {
            $resultado = registrarEvento($pdo, $id_estudiante, $tipo_evento, $descripcion, $datos_adicionales);
            
            if ($resultado) {
                responder('success', 'Evento registrado', $isAjax);
            } else {
                responder('error', 'bd', $isAjax);
            }
            
        } catch (PDOException $e) {
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/historial_academico.php");
    exit;
}
?>