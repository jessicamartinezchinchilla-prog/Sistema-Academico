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
        header("Location: ../Vistas/matricula.php?{$tipo}=" . urlencode($mensaje));
        exit;
    }
}

function validarGmail($correo) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $correo);
}

// ✅ FUNCIÓN: Validar DUI único en TODO el sistema
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
        // Si falla el registro del historial, no rompemos la operación principal
        error_log("Error al registrar evento en historial: " . $e->getMessage());
        return false;
    }
}

// ✅ FUNCIÓN: Obtener datos del estudiante
function obtenerDatosEstudiante($pdo, $id_estudiante) {
    $stmt = $pdo->prepare("SELECT nombres, apellidos, nie FROM estudiantes WHERE id = ?");
    $stmt->execute([$id_estudiante]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ✅ FUNCIÓN: Obtener nombre de sección
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
            
            // ✅ CORRECCIÓN: Agregamos 'nombre' a la consulta
            $stmt = $pdo->prepare("SELECT id_carrera, id_grado, nombre FROM secciones WHERE id = ?");
            $stmt->execute([$id_seccion]);
            $seccion_data = $stmt->fetch();
            
            if (!$seccion_data) {
                $pdo->rollBack();
                responder('error', 'seccion_invalida', $isAjax);
            }
            
            $id_carrera = $seccion_data['id_carrera'];
            $id_grado = $seccion_data['id_grado'];
            $nombre_seccion = $seccion_data['nombre']; // ✅ Guardamos el nombre de la sección
            
            // ==========================================
            // PROCESAR ESTUDIANTE
            // ==========================================
            $estudiante_nuevo = false;
            
            if ($tipo_estudiante === 'existente') {
                $id_estudiante = $_POST['id_estudiante_existente'] ?? 0;
                if (!$id_estudiante) {
                    $pdo->rollBack();
                    responder('error', 'sin_estudiante', $isAjax);
                }
            } else {
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

                $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE nie = ?");
                $stmt->execute([$nie]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    responder('error', 'nie_duplicado', $isAjax);
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
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE telefono = ?");
                    $stmt->execute([$telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'telefono_estudiante_duplicado', $isAjax);
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, dui, edad, fecha_nacimiento, telefono, email, direccion, estado, id_seccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?)");
                $stmt->execute([$nie, $nombres, $apellidos, $dui, $edad, $fecha_nacimiento, $telefono, $email, $direccion, $id_seccion]);
                $id_estudiante = $pdo->lastInsertId();
            }

            // ==========================================
            // PROCESAR RESPONSABLE
            // ==========================================
            
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
                    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE telefono = ?");
                    $stmt->execute([$resp_telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'telefono_responsable_duplicado', $isAjax);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE telefono = ?");
                    $stmt->execute([$resp_telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'telefono_existe_estudiante', $isAjax);
                    }
                }
                
                if ($resp_email) {
                    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE email = ?");
                    $stmt->execute([$resp_email]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'email_responsable_duplicado', $isAjax);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE email = ?");
                    $stmt->execute([$resp_email]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'email_existe_estudiante', $isAjax);
                    }
                }
            }

            // ==========================================
            // INSERTAR MATRÍCULA Y RESPONSABLE
            // ==========================================
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
            
            // ✅ REGISTRAR EVENTO EN HISTORIAL (después del commit exitoso)
            $datos_estudiante = obtenerDatosEstudiante($pdo, $id_estudiante);
            $nombre_completo = ($datos_estudiante['nombres'] ?? '') . ' ' . ($datos_estudiante['apellidos'] ?? '');
            
            if ($estudiante_nuevo) {
                $descripcion = "Estudiante nuevo '{$nombre_completo}' (NIE: " . ($datos_estudiante['nie'] ?? '') . ") matriculado en {$nombre_seccion}";
            } else {
                $descripcion = "Estudiante '{$nombre_completo}' matriculado en {$nombre_seccion}";
            }
            
            registrarEventoHistorial($pdo, $id_estudiante, 'matricula_creada', $descripcion, [
                'seccion' => $nombre_seccion,
                'nie' => $datos_estudiante['nie'] ?? '',
                'estudiante_nuevo' => $estudiante_nuevo
            ]);
            
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
            
            // ✅ OBTENER DATOS ANTERIORES PARA COMPARAR
            $stmt = $pdo->prepare("SELECT id_seccion FROM matriculas WHERE id = ?");
            $stmt->execute([$matricula_id]);
            $matricula_anterior = $stmt->fetch();
            $seccion_anterior_id = $matricula_anterior['id_seccion'] ?? 0;
            $seccion_anterior_nombre = obtenerNombreSeccion($pdo, $seccion_anterior_id);
            
            // ✅ Obtener estado anterior del estudiante
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
            
            // Actualizar estado del estudiante
            $stmt = $pdo->prepare("UPDATE estudiantes SET estado=? WHERE id=?");
            $stmt->execute([$estado, $id_estudiante]);

            // Actualizar matrícula
            $stmt = $pdo->prepare("UPDATE matriculas SET id_estudiante=?, id_carrera=?, id_grado=?, id_seccion=? WHERE id=?");
            $stmt->execute([$id_estudiante, $id_carrera, $id_grado, $id_seccion, $matricula_id]);

            // Actualizar responsable
            $stmt = $pdo->prepare("UPDATE responsables SET dui=?, nombres=?, apellidos=?, ocupacion=?, parentesco=?, email=?, telefono=?, direccion=? WHERE id_matricula=?");
            $stmt->execute([$resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion, $matricula_id]);

            $pdo->commit();
            
            // ✅ REGISTRAR EVENTOS EN HISTORIAL
            $datos_estudiante = obtenerDatosEstudiante($pdo, $id_estudiante);
            $nombre_completo = ($datos_estudiante['nombres'] ?? '') . ' ' . ($datos_estudiante['apellidos'] ?? '');
            
            // ✅ Evento: Cambio de sección (si cambió)
            if ($seccion_anterior_id != $id_seccion) {
                $descripcion = "Sección de '{$nombre_completo}' cambiada de '{$seccion_anterior_nombre}' a '{$seccion_nueva_nombre}'";
                registrarEventoHistorial($pdo, $id_estudiante, 'seccion_cambiada', $descripcion, [
                    'seccion_anterior' => $seccion_anterior_nombre,
                    'seccion_nueva' => $seccion_nueva_nombre
                ]);
            }
            
            // ✅ Evento: Cambio de estado (si cambió)
            if (strtolower($estado_anterior) !== strtolower($estado)) {
                $descripcion = "Estado de '{$nombre_completo}' cambiado de '" . ucfirst($estado_anterior) . "' a '" . ucfirst($estado) . "'";
                registrarEventoHistorial($pdo, $id_estudiante, 'estado_cambiado', $descripcion, [
                    'estado_anterior' => $estado_anterior,
                    'estado_nuevo' => $estado
                ]);
            }
            
            // ✅ Evento: Matrícula modificada (evento general)
            $descripcion = "Matrícula de '{$nombre_completo}' en '{$seccion_nueva_nombre}' modificada";
            registrarEventoHistorial($pdo, $id_estudiante, 'matricula_modificada', $descripcion, [
                'seccion' => $seccion_nueva_nombre,
                'estado' => $estado
            ]);
            
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
        
        // ✅ OBTENER DATOS ANTES DE ELIMINAR (para el historial)
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
        
        // ✅ REGISTRAR EVENTO EN HISTORIAL (después de eliminar)
        if ($id_estudiante) {
            $descripcion = "Matrícula del estudiante '{$nombre_completo}' (NIE: {$nie}) en '{$seccion_nombre}' fue eliminada";
            registrarEventoHistorial($pdo, $id_estudiante, 'matricula_eliminada', $descripcion, [
                'seccion' => $seccion_nombre,
                'nie' => $nie
            ]);
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