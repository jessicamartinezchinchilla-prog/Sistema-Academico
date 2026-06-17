<?php
// actions/calificaciones_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php'; // ✅ AUDITORÍA

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

// ✅ FUNCIÓN: Registrar evento en historial académico
function registrarEventoHistorial($pdo, $id_estudiante, $tipo_evento, $descripcion, $datos_adicionales = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO historial_academico (id_estudiante, tipo_evento, descripcion, datos_adicionales) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_estudiante, 
            $tipo_evento, 
            $descripcion, 
            $datos_adicionales ? json_encode($datos_adicionales, JSON_UNESCAPED_UNICODE) : null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar evento en historial: " . $e->getMessage());
        return false;
    }
}

// ✅ FUNCIÓN: Obtener nombre de materia
function obtenerNombreMateria($pdo, $id_materia) {
    $stmt = $pdo->prepare("SELECT nombre FROM materias WHERE id = ?");
    $stmt->execute([$id_materia]);
    return $stmt->fetchColumn();
}

// ✅ FUNCIÓN: Obtener datos del estudiante
function obtenerDatosEstudiante($pdo, $id_estudiante) {
    $stmt = $pdo->prepare("SELECT nombres, apellidos, nie FROM estudiantes WHERE id = ?");
    $stmt->execute([$id_estudiante]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    // ==========================================
    // AGREGAR CALIFICACIÓN (MULTIPLES PERÍODOS)
    // ==========================================
    if ($accion === 'agregar') {
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $id_carrera = $_POST['id_carrera'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
        $anio = date('Y');

        $periodos = [];
        for ($i = 1; $i <= 4; $i++) {
            $nota = $_POST["periodo_{$i}"] ?? null;
            if ($nota !== null && $nota !== '') {
                $nota = floatval($nota);
                if ($nota >= 0 && $nota <= 10) {
                    $periodos[$i] = $nota;
                }
            }
        }

        if (empty($id_estudiante) || empty($id_materia) || empty($id_seccion) || empty($id_carrera) || empty($id_grado)) {
            responder('error', 'campos_incompletos', $isAjax);
        }

        if (empty($periodos)) {
            responder('error', 'sin_nota', $isAjax);
        }

        try {
            // ✅ Obtener datos ANTES de la transacción (para el historial)
            $nombre_materia = obtenerNombreMateria($pdo, $id_materia);
            $datos_estudiante = obtenerDatosEstudiante($pdo, $id_estudiante);
            $nombre_completo = ($datos_estudiante['nombres'] ?? '') . ' ' . ($datos_estudiante['apellidos'] ?? '');
            $nie = $datos_estudiante['nie'] ?? '';

            $pdo->beginTransaction();

            $periodos_registrados = [];
            
            foreach ($periodos as $periodo => $nota) {
                $stmt = $pdo->prepare("SELECT id FROM calificaciones 
                                       WHERE id_estudiante = ? 
                                       AND id_materia = ? 
                                       AND periodo = ? 
                                       AND anio = ?");
                $stmt->execute([$id_estudiante, $id_materia, $periodo, $anio]);
                
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE calificaciones SET nota = ? 
                                           WHERE id_estudiante = ? 
                                           AND id_materia = ? 
                                           AND periodo = ? 
                                           AND anio = ?");
                    $stmt->execute([$nota, $id_estudiante, $id_materia, $periodo, $anio]);
                    $periodos_registrados[] = ['periodo' => $periodo, 'nota' => $nota, 'tipo' => 'actualizada'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO calificaciones 
                                           (id_estudiante, id_materia, id_seccion, periodo, anio, nota) 
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id_estudiante, $id_materia, $id_seccion, $periodo, $anio, $nota]);
                    $periodos_registrados[] = ['periodo' => $periodo, 'nota' => $nota, 'tipo' => 'nueva'];
                }
            }

            $pdo->commit();
            
            // ✅ REGISTRAR EVENTOS EN HISTORIAL
            foreach ($periodos_registrados as $reg) {
                $descripcion = "Nota {$reg['nota']} agregada en '{$nombre_materia}', Período {$reg['periodo']} para {$nombre_completo}";
                registrarEventoHistorial($pdo, $id_estudiante, 'nota_agregada', $descripcion, [
                    'materia' => $nombre_materia,
                    'periodo' => $reg['periodo'],
                    'nota' => $reg['nota'],
                    'nie' => $nie
                ]);
            }
            
            // ✅ AUDITORÍA
            $totalPeriodos = count($periodos_registrados);
            registrarAuditoria($pdo, 'creacion', 'calificaciones', "Se agregaron {$totalPeriodos} calificación(es) al estudiante '{$nombre_completo}' (NIE: {$nie}) en la materia '{$nombre_materia}'");
            
            responder('success', '1', $isAjax);
            
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }

    // ==========================================
    // EDITAR CALIFICACIÓN (MULTIPLES PERÍODOS)
    // ==========================================
    if ($accion === 'editar') {
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $id_carrera = $_POST['id_carrera'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
        $anio = date('Y');

        $periodos = [];
        for ($i = 1; $i <= 4; $i++) {
            $nota = $_POST["periodo_{$i}"] ?? null;
            if ($nota !== null && $nota !== '') {
                $nota = floatval($nota);
                if ($nota >= 0 && $nota <= 10) {
                    $periodos[$i] = $nota;
                }
            }
        }

        if (empty($periodos)) {
            responder('error', 'sin_nota', $isAjax);
        }

        try {
            // ✅ Obtener datos ANTES de la transacción
            $nombre_materia = obtenerNombreMateria($pdo, $id_materia);
            $datos_estudiante = obtenerDatosEstudiante($pdo, $id_estudiante);
            $nombre_completo = ($datos_estudiante['nombres'] ?? '') . ' ' . ($datos_estudiante['apellidos'] ?? '');
            $nie = $datos_estudiante['nie'] ?? '';
            
            // ✅ Obtener notas ANTERIORES
            $stmt = $pdo->prepare("SELECT periodo, nota FROM calificaciones 
                                   WHERE id_estudiante = ? AND id_materia = ? AND anio = ?");
            $stmt->execute([$id_estudiante, $id_materia, $anio]);
            $notas_anteriores = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $notas_anteriores[$row['periodo']] = floatval($row['nota']);
            }

            $pdo->beginTransaction();

            $cambios_registrados = [];
            
            foreach ($periodos as $periodo => $nota_nueva) {
                $nota_anterior = $notas_anteriores[$periodo] ?? null;
                
                if ($nota_anterior !== null && $nota_anterior != $nota_nueva) {
                    $stmt = $pdo->prepare("UPDATE calificaciones SET nota = ? 
                                           WHERE id_estudiante = ? 
                                           AND id_materia = ? 
                                           AND periodo = ? 
                                           AND anio = ?");
                    $stmt->execute([$nota_nueva, $id_estudiante, $id_materia, $periodo, $anio]);
                    $cambios_registrados[] = [
                        'periodo' => $periodo,
                        'nota_anterior' => $nota_anterior,
                        'nota_nueva' => $nota_nueva
                    ];
                }
            }

            $pdo->commit();
            
            // ✅ REGISTRAR EVENTOS EN HISTORIAL
            foreach ($cambios_registrados as $cambio) {
                $descripcion = "Nota de '{$nombre_materia}' en Período {$cambio['periodo']} cambiada de {$cambio['nota_anterior']} a {$cambio['nota_nueva']} para {$nombre_completo}";
                registrarEventoHistorial($pdo, $id_estudiante, 'nota_modificada', $descripcion, [
                    'materia' => $nombre_materia,
                    'periodo' => $cambio['periodo'],
                    'nota_anterior' => $cambio['nota_anterior'],
                    'nota_nueva' => $cambio['nota_nueva'],
                    'nie' => $nie
                ]);
            }
            
            // ✅ AUDITORÍA
            $totalCambios = count($cambios_registrados);
            if ($totalCambios > 0) {
                registrarAuditoria($pdo, 'modificacion', 'calificaciones', "Se modificaron {$totalCambios} calificación(es) del estudiante '{$nombre_completo}' (NIE: {$nie}) en la materia '{$nombre_materia}'");
            }
            
            responder('success', 'editado', $isAjax);
            
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }

    // ==========================================
    // OBTENER NOTAS DE UN ESTUDIANTE
    // ==========================================
    if ($accion === 'obtener_notas_estudiante') {
        $id_estudiante = $_GET['id_estudiante'] ?? 0;
        $id_materia    = $_GET['id_materia'] ?? null;
        $anio          = date('Y');

        if (empty($id_estudiante)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['notas' => []]);
                exit;
            }
            exit;
        }

        try {
            $sql = "SELECT periodo, nota FROM calificaciones WHERE id_estudiante = ? AND anio = ?";
            $params = [$id_estudiante, $anio];
            
            if ($id_materia) {
                $sql .= " AND id_materia = ?";
                $params[] = $id_materia;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $notas = [
                'periodo_1' => null,
                'periodo_2' => null,
                'periodo_3' => null,
                'periodo_4' => null
            ];

            foreach ($calificaciones as $cal) {
                $notas["periodo_{$cal['periodo']}"] = $cal['nota'];
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['notas' => $notas]);
                exit;
            }
            
        } catch (PDOException $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['notas' => []]);
                exit;
            }
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
            exit;
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
    // OBTENER DETALLE DE CALIFICACIONES
    // ==========================================
    if ($accion === 'obtener_detalle') {
        $id_estudiante = $_GET['id_estudiante'] ?? 0;

        if (empty($id_estudiante)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'sin_estudiante']);
                exit;
            }
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT 
                                        c.id,
                                        c.periodo,
                                        c.nota,
                                        m.id as id_materia,
                                        m.nombre as materia,
                                        s.nombre as seccion
                                    FROM calificaciones c
                                    INNER JOIN materias m ON c.id_materia = m.id
                                    INNER JOIN secciones s ON c.id_seccion = s.id
                                    WHERE c.id_estudiante = ?
                                    ORDER BY m.nombre, c.periodo");
            $stmt->execute([$id_estudiante]);
            $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $materiasAgrupadas = [];
            foreach ($calificaciones as $cal) {
                $materia = $cal['materia'];
                if (!isset($materiasAgrupadas[$materia])) {
                    $materiasAgrupadas[$materia] = [
                        'periodos' => [1 => null, 2 => null, 3 => null, 4 => null],
                        'suma_total' => 0
                    ];
                }
                $materiasAgrupadas[$materia]['periodos'][$cal['periodo']] = $cal['nota'];
                $materiasAgrupadas[$materia]['suma_total'] += $cal['nota'];
            }

            foreach ($materiasAgrupadas as $materia => &$data) {
                $data['promedio_final'] = round($data['suma_total'] / 4, 2);
                $data['estado'] = $data['promedio_final'] >= 6 ? 'Aprobado' : 'Reprobado';
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'calificaciones' => $calificaciones,
                    'materias_agrupadas' => $materiasAgrupadas
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
}

if (!$isAjax) {
    header("Location: ../Vistas/calificaciones.php");
    exit;
}
?>