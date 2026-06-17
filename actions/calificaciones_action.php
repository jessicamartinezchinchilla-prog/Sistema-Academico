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
    // ✅ NUEVO: OBTENER NOTA EXISTENTE (para precargar)
    // ==========================================
    if ($accion === 'obtener_nota_existente') {
        $id_estudiante = $_GET['id_estudiante'] ?? 0;
        $id_materia = $_GET['id_materia'] ?? 0;
        $periodo = $_GET['periodo'] ?? 0;
        $anio = date('Y');

        try {
            $stmt = $pdo->prepare("SELECT id, nota FROM calificaciones 
                                   WHERE id_estudiante = ? 
                                   AND id_materia = ? 
                                   AND periodo = ? 
                                   AND anio = ?
                                   LIMIT 1");
            $stmt->execute([$id_estudiante, $id_materia, $periodo, $anio]);
            $calificacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($isAjax) {
                header('Content-Type: application/json');
                if ($calificacion) {
                    echo json_encode([
                        'existe' => true,
                        'id' => $calificacion['id'],
                        'nota' => $calificacion['nota']
                    ]);
                } else {
                    echo json_encode(['existe' => false]);
                }
                exit;
            }
        } catch (PDOException $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['existe' => false]);
                exit;
            }
        }
    }
    
    // ==========================================
    // AGREGAR CALIFICACIÓN
    // ==========================================
    if ($accion === 'agregar') {
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $id_carrera = $_POST['id_carrera'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
        $periodo = $_POST['periodo'] ?? 0;
        $nota = $_POST['nota'] ?? null;
        $anio = date('Y');

        if (empty($id_estudiante) || empty($id_materia) || empty($id_seccion) || empty($id_carrera) || empty($id_grado) || empty($periodo)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        if ($nota === null || $nota === '') {
            responder('error', 'sin_nota', $isAjax);
        }

        $nota = floatval($nota);
        if ($nota < 0 || $nota > 10) {
            responder('error', 'nota_invalida', $isAjax);
        }

        if ($periodo < 1 || $periodo > 4) {
            responder('error', 'periodo_invalido', $isAjax);
        }

        try {
            $stmt = $pdo->prepare("
                SELECT m.id 
                FROM materias m
                INNER JOIN asignaciones a ON m.id = a.id_materia
                INNER JOIN secciones s ON a.id_seccion = s.id
                WHERE m.id = ? AND s.id_carrera = ? AND s.id_grado = ?
                LIMIT 1
            ");
            $stmt->execute([$id_materia, $id_carrera, $id_grado]);
            
            if (!$stmt->fetch()) {
                responder('error', 'materia_no_pertenece', $isAjax);
            }

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
            responder('error', 'bd: ' . $msg, $isAjax);
        }
    }

    // ==========================================
    // EDITAR CALIFICACIÓN (individual - cuando se precargó una existente)
    // ==========================================
    if ($accion === 'editar') {
        $id = $_POST['calificacion_id'] ?? 0;
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $id_carrera = $_POST['id_carrera'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
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
            $stmt = $pdo->prepare("UPDATE calificaciones 
                                   SET nota = ? 
                                   WHERE id = ?");
            $stmt->execute([$nota, $id]);

            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            responder('error', 'bd', $isAjax);
        }
    }

    // ==========================================
    // EDITAR MÚLTIPLES CALIFICACIONES
    // ==========================================
    if ($accion === 'editar_multiples') {
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $id_carrera = $_POST['id_carrera'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
        $calificacion_ids = $_POST['calificacion_ids'] ?? [];
        $periodos = $_POST['periodos'] ?? [];
        $notas = $_POST['notas'] ?? [];
        $anio = date('Y');

        if (empty($id_estudiante) || empty($id_materia) || empty($id_seccion) || empty($id_carrera) || empty($id_grado)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        try {
            $stmt = $pdo->prepare("
                SELECT m.id 
                FROM materias m
                INNER JOIN asignaciones a ON m.id = a.id_materia
                INNER JOIN secciones s ON a.id_seccion = s.id
                WHERE m.id = ? AND s.id_carrera = ? AND s.id_grado = ?
                LIMIT 1
            ");
            $stmt->execute([$id_materia, $id_carrera, $id_grado]);
            
            if (!$stmt->fetch()) {
                responder('error', 'materia_no_pertenece', $isAjax);
            }

            $pdo->beginTransaction();

            for ($i = 0; $i < count($periodos); $i++) {
                $periodo = $periodos[$i];
                $nota = $notas[$i] ?? '';
                $calif_id = $calificacion_ids[$i] ?? '';
                
                if (empty($nota) && !empty($calif_id)) {
                    $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE id = ?");
                    $stmt->execute([$calif_id]);
                    continue;
                }
                
                if (empty($nota)) {
                    continue;
                }
                
                $nota = floatval($nota);
                if ($nota < 0 || $nota > 10) {
                    $pdo->rollBack();
                    responder('error', 'nota_invalida', $isAjax);
                }
                
                if (!empty($calif_id)) {
                    $stmt = $pdo->prepare("UPDATE calificaciones SET nota = ? WHERE id = ?");
                    $stmt->execute([$nota, $calif_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO calificaciones 
                                           (id_estudiante, id_materia, id_seccion, periodo, anio, nota) 
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id_estudiante, $id_materia, $id_seccion, $periodo, $anio, $nota]);
                }
            }

            $pdo->commit();
            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
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
    // OBTENER MATERIAS POR CARRERA Y GRADO
    // ==========================================
    if ($accion === 'obtener_materias_carrera') {
        $id_carrera = $_GET['id_carrera'] ?? 0;
        $id_grado = $_GET['id_grado'] ?? 0;

        if (empty($id_carrera) || empty($id_grado)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }
        }

        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT m.id, m.nombre
                FROM materias m
                INNER JOIN asignaciones a ON m.id = a.id_materia
                INNER JOIN secciones s ON a.id_seccion = s.id
                WHERE s.id_carrera = ? AND s.id_grado = ?
                ORDER BY m.nombre
            ");
            $stmt->execute([$id_carrera, $id_grado]);
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($materias);
                exit;
            }
            
        } catch (PDOException $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }
        }
    }

    // ==========================================
    // OBTENER CALIFICACIONES DE UNA MATERIA ESPECÍFICA
    // ==========================================
    if ($accion === 'obtener_calificaciones_materia') {
        $id_estudiante = $_GET['id_estudiante'] ?? 0;
        $materia = $_GET['materia'] ?? '';

        if (empty($id_estudiante) || empty($materia)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }
        }

        try {
            $stmt = $pdo->prepare("SELECT 
                                        c.id,
                                        c.periodo,
                                        c.nota,
                                        m.nombre as materia
                                    FROM calificaciones c
                                    INNER JOIN materias m ON c.id_materia = m.id
                                    WHERE c.id_estudiante = ? AND m.nombre = ?
                                    ORDER BY c.periodo");
            $stmt->execute([$id_estudiante, $materia]);
            $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($calificaciones);
                exit;
            }
            
        } catch (PDOException $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }
        }
    }

    // ==========================================
    // OBTENER CALIFICACIONES DE UN ESTUDIANTE
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