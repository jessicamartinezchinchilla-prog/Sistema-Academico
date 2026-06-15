<?php
// actions/calificaciones_action.php
session_start();
require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/calificaciones.php?{$tipo}={$mensaje}");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    // ==========================================
    // AGREGAR CALIFICACIÓN
    // ==========================================
    if ($accion === 'agregar') {
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $periodo = $_POST['periodo'] ?? 0;
        $nota = $_POST['nota'] ?? null;
        $anio = date('Y'); // Año actual por defecto

        // Validaciones básicas
        if (empty($id_estudiante) || empty($id_materia) || empty($id_seccion) || empty($periodo)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        if ($nota === null || $nota === '') {
            responder('error', 'sin_nota', $isAjax);
        }

        // Validar rango de nota (0 a 10)
        $nota = floatval($nota);
        if ($nota < 0 || $nota > 10) {
            responder('error', 'nota_invalida', $isAjax);
        }

        // Validar período (1 a 4)
        if ($periodo < 1 || $periodo > 4) {
            responder('error', 'periodo_invalido', $isAjax);
        }

        try {
            // Verificar si ya existe esta calificación (mismo estudiante, materia, sección, período, año)
            $stmt = $pdo->prepare("SELECT id FROM calificaciones 
                                   WHERE id_estudiante = ? 
                                   AND id_materia = ? 
                                   AND id_seccion = ? 
                                   AND periodo = ? 
                                   AND anio = ?");
            $stmt->execute([$id_estudiante, $id_materia, $id_seccion, $periodo, $anio]);
            
            if ($stmt->fetch()) {
                responder('error', 'duplicado', $isAjax);
            }

            // Insertar calificación
            $stmt = $pdo->prepare("INSERT INTO calificaciones 
                                   (id_estudiante, id_materia, id_seccion, periodo, anio, nota) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_estudiante, $id_materia, $id_seccion, $periodo, $anio, $nota]);

            responder('success', '1', $isAjax);
            
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'unique_calificacion') !== false || strpos($msg, 'Duplicate') !== false) {
                responder('error', 'duplicado', $isAjax);
            }
            responder('error', 'bd', $isAjax);
        }
    }

    // ==========================================
    // EDITAR CALIFICACIÓN
    // ==========================================
    if ($accion === 'editar') {
        $id = $_POST['calificacion_id'] ?? 0;
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $periodo = $_POST['periodo'] ?? 0;
        $nota = $_POST['nota'] ?? null;
        $anio = date('Y');

        if (empty($id)) {
            responder('error', 'sin_id', $isAjax);
        }

        if ($nota === null || $nota === '') {
            responder('error', 'sin_nota', $isAjax);
        }

        $nota = floatval($nota);
        if ($nota < 0 || $nota > 10) {
            responder('error', 'nota_invalida', $isAjax);
        }

        try {
            // Verificar si existe otra calificación igual (excluyendo la actual)
            $stmt = $pdo->prepare("SELECT id FROM calificaciones 
                                   WHERE id_estudiante = ? 
                                   AND id_materia = ? 
                                   AND id_seccion = ? 
                                   AND periodo = ? 
                                   AND anio = ?
                                   AND id != ?");
            $stmt->execute([$id_estudiante, $id_materia, $id_seccion, $periodo, $anio, $id]);
            
            if ($stmt->fetch()) {
                responder('error', 'duplicado', $isAjax);
            }

            // Actualizar calificación
            $stmt = $pdo->prepare("UPDATE calificaciones 
                                   SET id_estudiante = ?, 
                                       id_materia = ?, 
                                       id_seccion = ?, 
                                       periodo = ?, 
                                       anio = ?, 
                                       nota = ? 
                                   WHERE id = ?");
            $stmt->execute([$id_estudiante, $id_materia, $id_seccion, $periodo, $anio, $nota, $id]);

            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }

    // ==========================================
    // ELIMINAR CALIFICACIÓN
    // ==========================================
    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;

        if (empty($id)) {
            responder('error', 'sin_id', $isAjax);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE id = ?");
            $stmt->execute([$id]);

            responder('success', 'eliminado', $isAjax);
            
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }

    // ==========================================
    // OBTENER CALIFICACIONES DE UN ESTUDIANTE (para modal de detalle)
    // ==========================================
    if ($accion === 'obtener_detalle') {
        $id_estudiante = $_GET['id_estudiante'] ?? 0;

        if (empty($id_estudiante)) {
            responder('error', 'sin_estudiante', $isAjax);
        }

        try {
            $stmt = $pdo->prepare("SELECT 
                                        c.id,
                                        c.periodo,
                                        c.nota,
                                        m.nombre as materia,
                                        s.nombre as seccion
                                    FROM calificaciones c
                                    INNER JOIN materias m ON c.id_materia = m.id
                                    INNER JOIN secciones s ON c.id_seccion = s.id
                                    WHERE c.id_estudiante = ?
                                    ORDER BY m.nombre, c.periodo");
            $stmt->execute([$id_estudiante]);
            $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($calificaciones);
                exit;
            } else {
                echo json_encode($calificaciones);
                exit;
            }
            
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/calificaciones.php");
    exit;
}
?>