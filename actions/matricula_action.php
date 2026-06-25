<?php
// actions/matricula_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/matricula.php?{$tipo}=" . urlencode($mensaje));
        exit;
    }
}

function validarGmail($correo) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $correo);
}

function validarDuiUnicoSistema($pdo, $dui, $tabla_actual = null, $id_excluir = null) {
    if (empty($dui)) return null;
    
    $tablas = ['estudiantes', 'profesores', 'responsables'];
    
    foreach ($tablas as $tabla) {
        if ($tabla === $tabla_actual) continue;
        
        $sql = "SELECT id FROM {$tabla} WHERE dui = ?";
        $params = [$dui];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            return "dui_existe_en_{$tabla}";
        }
    }
    
    if ($tabla_actual) {
        $sql = "SELECT id FROM {$tabla_actual} WHERE dui = ?";
        $params = [$dui];
        
        if ($id_excluir) {
            $sql .= " AND id != ?";
            $params[] = $id_excluir;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            return "dui_duplicado_en_{$tabla_actual}";
        }
    }
    
    return null;
}

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

function obtenerDatosEstudiante($pdo, $id_estudiante) {
    $stmt = $pdo->prepare("SELECT nombres, apellidos, nie FROM estudiantes WHERE id = ?");
    $stmt->execute([$id_estudiante]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerNombreSeccion($pdo, $id_seccion) {
    $stmt = $pdo->prepare("SELECT nombre FROM secciones WHERE id = ?");
    $stmt->execute([$id_seccion]);
    return $stmt->fetchColumn();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';
    
    // ==========================================
    // AGREGAR MATRÍCULA
    // ==========================================
    if ($accion === 'agregar') {
        $tipo_estudiante = $_POST['tipo_estudiante'] ?? '';
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $estado = 'activo';
        
        // ✅ CORRECCIÓN: Inicializar variable para evitar warning
        $matriculas_eliminadas = 0;
        
        $resp_dui = trim($_POST['responsable_dui'] ?? '');
        $resp_nombres = trim($_POST['responsable_nombres'] ?? '');
        $resp_apellidos = trim($_POST['responsable_apellidos'] ?? '');
        $resp_ocupacion = trim($_POST['responsable_ocupacion'] ?? '');
        $resp_parentesco = $_POST['responsable_parentesco'] ?? '';
        $resp_email = trim($_POST['responsable_email'] ?? '');
        $resp_telefono = trim($_POST['responsable_telefono'] ?? '');
        $resp_direccion = trim($_POST['responsable_direccion'] ?? '');

        if (!validarGmail($resp_email)) {
            responder('error', 'gmail', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT id_carrera, id_grado, nombre FROM secciones WHERE id = ?");
            $stmt->execute([$id_seccion]);
            $seccion_data = $stmt->fetch();
            
            if (!$seccion_data) {
                $pdo->rollBack();
                responder('error', 'seccion_invalida', $isAjax);
            }
            
            $id_carrera = $seccion_data['id_carrera'];
            $id_grado = $seccion_data['id_grado'];
            $nombre_seccion = $seccion_data['nombre'];
            
            $estudiante_nuevo = false;
            
            // ==========================================
            // ESTUDIANTE EXISTENTE
            // ==========================================
            if ($tipo_estudiante === 'existente') {
                $id_estudiante = $_POST['id_estudiante_existente'] ?? 0;
                if (!$id_estudiante) {
                    $pdo->rollBack();
                    responder('error', 'sin_estudiante', $isAjax);
                }
                
                $stmt = $pdo->prepare("
                    SELECT id FROM matriculas 
                    WHERE id_estudiante = ? 
                    AND id_seccion = ? 
                    AND estado = 'Activo'
                ");
                $stmt->execute([$id_estudiante, $id_seccion]);
                
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'matricula_duplicada', $isAjax);
                }
                
                $stmt = $pdo->prepare("
                    SELECT m.id, s.nombre as seccion_anterior, s.id_grado as grado_anterior
                    FROM matriculas m
                    INNER JOIN secciones s ON m.id_seccion = s.id
                    WHERE m.id_estudiante = ? 
                    AND m.estado = 'Activo'
                ");
                $stmt->execute([$id_estudiante]);
                $matriculas_anteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($matriculas_anteriores as $mat_ant) {
                    $stmt = $pdo->prepare("DELETE FROM responsables WHERE id_matricula = ?");
                    $stmt->execute([$mat_ant['id']]);
                }
                
                $stmt = $pdo->prepare("
                    DELETE FROM matriculas 
                    WHERE id_estudiante = ? 
                    AND estado = 'Activo'
                ");
                $stmt->execute([$id_estudiante]);
                $matriculas_eliminadas = $stmt->rowCount();

                $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE id_estudiante = ?");
                $stmt->execute([$id_estudiante]);
                
                $stmt = $pdo->prepare("
                    SELECT s.id_grado 
                    FROM matriculas m
                    INNER JOIN secciones s ON m.id_seccion = s.id
                    WHERE m.id_estudiante = ?
                    ORDER BY m.id DESC
                    LIMIT 1
                ");
                $stmt->execute([$id_estudiante]);
                $ultimo_grado = $stmt->fetchColumn();
                
                $repite_anio = isset($_POST['repite_anio']) && $_POST['repite_anio'] === 'on';
                
                if ($ultimo_grado) {
                    if ($repite_anio) {
                        if ($seccion_data['id_grado'] != $ultimo_grado) {
                            $pdo->rollBack();
                            responder('error', 'grado_no_superior', $isAjax);
                        }
                    } else {
                        if ($seccion_data['id_grado'] <= $ultimo_grado) {
                            $pdo->rollBack();
                            responder('error', 'grado_no_superior', $isAjax);
                        }
                    }
                }
                
            } 
            // ==========================================
            // ESTUDIANTE NUEVO
            // ==========================================
            else {
                $estudiante_nuevo = true;
                $nie = trim($_POST['nie'] ?? '');
                $nombres = trim($_POST['nombres'] ?? '');
                $apellidos = trim($_POST['apellidos'] ?? '');
                $dui = trim($_POST['dui'] ?? '') ?: null;
                $edad = $_POST['edad'] ?? null;
                $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
                $telefono = trim($_POST['telefono'] ?? '') ?: null;
                $email = trim($_POST['email'] ?? '') ?: null;
                $direccion = trim($_POST['direccion'] ?? '') ?: null;

                if ($nie) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE nie = ?");
                    $stmt->execute([$nie]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'nie_duplicado', $isAjax);
                    }
                }

                if ($dui) {
                    $error_dui = validarDuiUnicoSistema($pdo, $dui, 'estudiantes');
                    if ($error_dui) {
                        $pdo->rollBack();
                        if (strpos($error_dui, 'profesores') !== false) {
                            responder('error', 'dui_estudiante_existe_profesor', $isAjax);
                        } elseif (strpos($error_dui, 'responsables') !== false) {
                            responder('error', 'dui_estudiante_existe_responsable', $isAjax);
                        } else {
                            responder('error', 'dui_estudiante_duplicado', $isAjax);
                        }
                    }
                }

                if ($telefono) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE telefono = ? AND telefono IS NOT NULL AND telefono != ''");
                    $stmt->execute([$telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'telefono_estudiante_duplicado', $isAjax);
                    }
                }

                if ($email) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE email = ? AND email IS NOT NULL AND email != ''");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'email_estudiante_duplicado', $isAjax);
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, dui, edad, fecha_nacimiento, telefono, email, direccion, estado, id_seccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?)");
                $stmt->execute([$nie, $nombres, $apellidos, $dui, $edad, $fecha_nacimiento, $telefono, $email, $direccion, $id_seccion]);
                $id_estudiante = $pdo->lastInsertId();
            }

            if ($resp_dui) {
                $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE dui = ?");
                $stmt->execute([$resp_dui]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'dui_responsable_existe_estudiante', $isAjax);
                }
                
                $stmt = $pdo->prepare("SELECT id FROM profesores WHERE dui = ?");
                $stmt->execute([$resp_dui]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'dui_responsable_existe_profesor', $isAjax);
                }
            }
            
            $stmt = $pdo->prepare("SELECT id, dui, nombres, apellidos, ocupacion, parentesco, email, telefono, direccion FROM responsables WHERE dui = ? LIMIT 1");
            $stmt->execute([$resp_dui]);
            $responsable_existente = $stmt->fetch();
            
            if (!$responsable_existente) {
                if ($resp_telefono) {
                    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE telefono = ? AND telefono IS NOT NULL AND telefono != ''");
                    $stmt->execute([$resp_telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'telefono_responsable_duplicado', $isAjax);
                    }
                }
                
                if ($resp_email) {
                    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE email = ? AND email IS NOT NULL AND email != ''");
                    $stmt->execute([$resp_email]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'email_responsable_duplicado', $isAjax);
                    }
                }
            }

            $stmt = $pdo->prepare("INSERT INTO matriculas (id_estudiante, id_carrera, id_grado, id_seccion, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_estudiante, $id_carrera, $id_grado, $id_seccion, 'Activo']);
            $id_matricula = $pdo->lastInsertId();

            if ($responsable_existente) {
                $stmt = $pdo->prepare("INSERT INTO responsables (id_matricula, dui, nombres, apellidos, ocupacion, parentesco, email, telefono, direccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id_matricula, 
                    $responsable_existente['dui'], 
                    $responsable_existente['nombres'], 
                    $responsable_existente['apellidos'], 
                    $responsable_existente['ocupacion'], 
                    $responsable_existente['parentesco'], 
                    $responsable_existente['email'], 
                    $responsable_existente['telefono'], 
                    $responsable_existente['direccion']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO responsables (id_matricula, dui, nombres, apellidos, ocupacion, parentesco, email, telefono, direccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_matricula, $resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion]);
            }

            $pdo->commit();
            
            $datos_estudiante = obtenerDatosEstudiante($pdo, $id_estudiante);
            $nombre_completo = ($datos_estudiante['nombres'] ?? '') . ' ' . ($datos_estudiante['apellidos'] ?? '');
            
            if ($estudiante_nuevo) {
                $descripcion = "Estudiante nuevo '{$nombre_completo}' (NIE: " . ($datos_estudiante['nie'] ?? '') . ") matriculado en {$nombre_seccion}";
            } else {
                if (!empty($matriculas_anteriores)) {
                    $seccion_anterior = $matriculas_anteriores[0]['seccion_anterior'];
                    $grado_anterior = $matriculas_anteriores[0]['grado_anterior'];
                    
                    if ($grado_anterior == $id_grado) {
                        $descripcion_historial = "Estudiante '{$nombre_completo}' (NIE: {$datos_estudiante['nie']}) repitió año y fue matriculado en {$nombre_seccion} (anteriormente en {$seccion_anterior}). Sus calificaciones del año anterior fueron reiniciadas.";
                        $tipo_evento = 'repitio_anio';
                    } else {
                        $descripcion_historial = "Estudiante '{$nombre_completo}' (NIE: {$datos_estudiante['nie']}) subió de año y fue matriculado en {$nombre_seccion} (anteriormente en {$seccion_anterior}). Sus calificaciones del año anterior fueron reiniciadas.";
                        $tipo_evento = 'cambio_seccion';
                    }
                    
                    registrarEventoHistorial($pdo, $id_estudiante, $tipo_evento, $descripcion_historial, [
                        'seccion_anterior' => $seccion_anterior,
                        'seccion_nueva' => $nombre_seccion,
                        'grado_anterior' => $grado_anterior,
                        'grado_nuevo' => $id_grado
                    ]);
                }
                
                $descripcion = "Estudiante '{$nombre_completo}' matriculado en {$nombre_seccion}";
                if ($matriculas_eliminadas > 0) {
                    $descripcion .= " (matrícula anterior eliminada)";
                }
            }

            registrarEventoHistorial($pdo, $id_estudiante, 'matricula_creada', $descripcion, [
                'seccion' => $nombre_seccion,
                'nie' => $datos_estudiante['nie'] ?? '',
                'estudiante_nuevo' => $estudiante_nuevo,
                'matriculas_eliminadas' => $matriculas_eliminadas ?? 0
            ]);
            
            $auditoria_msg = "Se matriculó al estudiante '{$nombre_completo}' (NIE: {$datos_estudiante['nie']}) en la sección '{$nombre_seccion}'";
            if ($matriculas_eliminadas > 0) {
                $auditoria_msg .= " (se eliminaron {$matriculas_eliminadas} matrícula(s) anterior(es) y sus calificaciones)";
            }
            registrarAuditoria($pdo, 'creacion', 'matriculas', $auditoria_msg);
            
            responder('success', 'Matrícula guardada exitosamente', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }
    
    // ==========================================
    // EDITAR MATRÍCULA
    // ==========================================
    if ($accion === 'editar') {
        $matricula_id = $_POST['matricula_id'] ?? 0;
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $estado = $_POST['estado'] ?? 'activo';
        
        $est_dui = trim($_POST['est_dui'] ?? '') ?: null;
        $est_telefono = trim($_POST['est_telefono'] ?? '') ?: null;
        $est_email = trim($_POST['est_email'] ?? '') ?: null;
        $est_direccion = trim($_POST['est_direccion'] ?? '') ?: null;
        
        $resp_dui = trim($_POST['responsable_dui'] ?? '');
        $resp_nombres = trim($_POST['responsable_nombres'] ?? '');
        $resp_apellidos = trim($_POST['responsable_apellidos'] ?? '');
        $resp_ocupacion = trim($_POST['responsable_ocupacion'] ?? '');
        $resp_parentesco = $_POST['responsable_parentesco'] ?? '';
        $resp_email = trim($_POST['responsable_email'] ?? '');
        $resp_telefono = trim($_POST['responsable_telefono'] ?? '');
        $resp_direccion = trim($_POST['responsable_direccion'] ?? '');

        if (!validarGmail($resp_email)) {
            responder('error', 'gmail', $isAjax);
        }

        if ($est_telefono && !preg_match('/^\d{4}-\d{4}$/', $est_telefono)) {
            responder('error', 'telefono_invalido', $isAjax);
        }

        if ($est_email && !validarGmail($est_email)) {
            responder('error', 'email_estudiante_invalido', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT id_seccion FROM matriculas WHERE id = ?");
            $stmt->execute([$matricula_id]);
            $matricula_anterior = $stmt->fetch();
            $seccion_anterior_id = $matricula_anterior['id_seccion'] ?? 0;
            $seccion_anterior_nombre = obtenerNombreSeccion($pdo, $seccion_anterior_id);
            
            $stmt = $pdo->prepare("SELECT estado FROM estudiantes WHERE id = ?");
            $stmt->execute([$id_estudiante]);
            $estado_anterior = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT id_carrera, id_grado, nombre FROM secciones WHERE id = ?");
            $stmt->execute([$id_seccion]);
            $seccion_data = $stmt->fetch();
            
            if ($seccion_data) {
                $id_carrera = $seccion_data['id_carrera'];
                $id_grado = $seccion_data['id_grado'];
                $seccion_nueva_nombre = $seccion_data['nombre'];
            } else {
                $pdo->rollBack();
                responder('error', 'seccion_invalida', $isAjax);
            }

            $stmt = $pdo->prepare("
                SELECT s.id_grado 
                FROM matriculas m
                INNER JOIN secciones s ON m.id_seccion = s.id
                WHERE m.id = ?
            ");
            $stmt->execute([$matricula_id]);
            $grado_actual_matricula = $stmt->fetchColumn();

            if ($grado_actual_matricula && $seccion_data['id_grado'] != $grado_actual_matricula) {
                $pdo->rollBack();
                responder('error', 'grado_diferente_editar', $isAjax);
            }

            $stmt = $pdo->prepare("
                SELECT m.id_seccion, m.estado as estado_matricula,
                       e.estado as estado_estudiante, e.dui, e.telefono, e.email, e.direccion,
                       r.dui as resp_dui, r.nombres as resp_nombres, r.apellidos as resp_apellidos,
                       r.ocupacion, r.parentesco, r.email as resp_email, r.telefono as resp_telefono, r.direccion as resp_direccion
                FROM matriculas m
                INNER JOIN estudiantes e ON m.id_estudiante = e.id
                LEFT JOIN responsables r ON m.id = r.id_matricula
                WHERE m.id = ?
            ");
            $stmt->execute([$matricula_id]);
            $datos_actuales = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($datos_actuales) {
                $sin_cambios = (
                    $datos_actuales['id_seccion'] == $id_seccion &&
                    strtolower($datos_actuales['estado_estudiante']) === strtolower($estado) &&
                    ($datos_actuales['dui'] ?? '') === ($est_dui ?? '') &&
                    ($datos_actuales['telefono'] ?? '') === ($est_telefono ?? '') &&
                    ($datos_actuales['email'] ?? '') === ($est_email ?? '') &&
                    ($datos_actuales['direccion'] ?? '') === ($est_direccion ?? '') &&
                    ($datos_actuales['resp_dui'] ?? '') === $resp_dui &&
                    ($datos_actuales['resp_nombres'] ?? '') === $resp_nombres &&
                    ($datos_actuales['resp_apellidos'] ?? '') === $resp_apellidos &&
                    ($datos_actuales['ocupacion'] ?? '') === $resp_ocupacion &&
                    ($datos_actuales['parentesco'] ?? '') === $resp_parentesco &&
                    ($datos_actuales['resp_email'] ?? '') === $resp_email &&
                    ($datos_actuales['resp_telefono'] ?? '') === $resp_telefono &&
                    ($datos_actuales['resp_direccion'] ?? '') === $resp_direccion
                );

                if ($sin_cambios) {
                    $pdo->rollBack();
                    responder('info', 'sin_cambios', $isAjax);
                }
            }

            if ($resp_dui) {
                $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE dui = ? AND id != ?");
                $stmt->execute([$resp_dui, $id_estudiante]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'dui_responsable_existe_estudiante', $isAjax);
                }
                
                $stmt = $pdo->prepare("SELECT id FROM profesores WHERE dui = ?");
                $stmt->execute([$resp_dui]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'dui_responsable_existe_profesor', $isAjax);
                }
            }

            $stmt = $pdo->prepare("UPDATE estudiantes SET estado=?, dui=?, telefono=?, email=?, direccion=? WHERE id=?");
            $stmt->execute([$estado, $est_dui, $est_telefono, $est_email, $est_direccion, $id_estudiante]);

            $stmt = $pdo->prepare("UPDATE matriculas SET id_estudiante=?, id_carrera=?, id_grado=?, id_seccion=? WHERE id=?");
            $stmt->execute([$id_estudiante, $id_carrera, $id_grado, $id_seccion, $matricula_id]);

            $stmt = $pdo->prepare("UPDATE responsables SET dui=?, nombres=?, apellidos=?, ocupacion=?, parentesco=?, email=?, telefono=?, direccion=? WHERE id_matricula=?");
            $stmt->execute([$resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion, $matricula_id]);

            $pdo->commit();
            
            $datos_estudiante = obtenerDatosEstudiante($pdo, $id_estudiante);
            $nombre_completo = ($datos_estudiante['nombres'] ?? '') . ' ' . ($datos_estudiante['apellidos'] ?? '');
            
            if ($seccion_anterior_id != $id_seccion) {
                $descripcion = "Sección de '{$nombre_completo}' cambiada de '{$seccion_anterior_nombre}' a '{$seccion_nueva_nombre}'";
                registrarEventoHistorial($pdo, $id_estudiante, 'seccion_cambiada', $descripcion, [
                    'seccion_anterior' => $seccion_anterior_nombre,
                    'seccion_nueva' => $seccion_nueva_nombre
                ]);
            }
            
            if (strtolower($estado_anterior) !== strtolower($estado)) {
                $descripcion = "Estado de '{$nombre_completo}' cambiado de '" . ucfirst($estado_anterior) . "' a '" . ucfirst($estado) . "'";
                registrarEventoHistorial($pdo, $id_estudiante, 'estado_cambiado', $descripcion, [
                    'estado_anterior' => $estado_anterior,
                    'estado_nuevo' => $estado
                ]);
            }
            
            $descripcion = "Matrícula de '{$nombre_completo}' en '{$seccion_nueva_nombre}' modificada";
            registrarEventoHistorial($pdo, $id_estudiante, 'matricula_modificada', $descripcion, [
                'seccion' => $seccion_nueva_nombre,
                'estado' => $estado
            ]);
            
            registrarAuditoria($pdo, 'modificacion', 'matriculas', "Se modificó la matrícula del estudiante '{$nombre_completo}' (NIE: {$datos_estudiante['nie']}) en la sección '{$seccion_nueva_nombre}'");
            
            responder('success', 'Matrícula actualizada exitosamente', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd: ' . $e->getMessage(), $isAjax);
        }
    }
}

// ==========================================
// ELIMINAR MATRÍCULA (GET)
// ==========================================
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $id = $_GET['id'] ?? 0;
    
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $stmt = $pdo->prepare("
            SELECT m.id_estudiante, e.nombres, e.apellidos, e.nie, s.nombre as seccion_nombre
            FROM matriculas m
            INNER JOIN estudiantes e ON m.id_estudiante = e.id
            INNER JOIN secciones s ON m.id_seccion = s.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $datos_eliminacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $id_estudiante = $datos_eliminacion['id_estudiante'] ?? null;
        $nombre_completo = ($datos_eliminacion['nombres'] ?? '') . ' ' . ($datos_eliminacion['apellidos'] ?? '');
        $seccion_nombre = $datos_eliminacion['seccion_nombre'] ?? '';
        $nie = $datos_eliminacion['nie'] ?? '';
        
        $stmt = $pdo->prepare("DELETE FROM responsables WHERE id_matricula = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($id_estudiante) {
            $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
            $stmt->execute([$id_estudiante]);
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        if ($id_estudiante) {
            $descripcion = "Matrícula del estudiante '{$nombre_completo}' (NIE: {$nie}) en '{$seccion_nombre}' fue eliminada";
            registrarEventoHistorial($pdo, $id_estudiante, 'matricula_eliminada', $descripcion, [
                'seccion' => $seccion_nombre,
                'nie' => $nie
            ]);
            
            registrarAuditoria($pdo, 'eliminacion', 'matriculas', "Se eliminó la matrícula del estudiante '{$nombre_completo}' (NIE: {$nie}) de la sección '{$seccion_nombre}'");
        }
        
        header("Location: ../Vistas/matricula.php?success=eliminado");
        exit;
        
    } catch (PDOException $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: ../Vistas/matricula.php?error=bd");
        exit;
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/matricula.php");
    exit;
}
?>