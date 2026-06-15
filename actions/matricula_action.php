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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';
    
    // ==========================================
    // AGREGAR MATRÍCULA
    // ==========================================
    if ($accion === 'agregar') {
        $tipo_estudiante = $_POST['tipo_estudiante'] ?? '';
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $estado = 'Activo';
        
        $resp_dui = trim($_POST['responsable_dui'] ?? '');
        $resp_nombres = trim($_POST['responsable_nombres'] ?? '');
        $resp_apellidos = trim($_POST['responsable_apellidos'] ?? '');
        $resp_ocupacion = trim($_POST['responsable_ocupacion'] ?? '');
        $resp_parentesco = $_POST['responsable_parentesco'] ?? '';
        $resp_email = trim($_POST['responsable_email'] ?? '');
        $resp_telefono = trim($_POST['responsable_telefono'] ?? '');
        $resp_direccion = trim($_POST['responsable_direccion'] ?? '');

        if (!validarGmail($resp_email)) {
            responder('error', 'El correo debe ser @gmail.com', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT id_carrera, id_grado FROM secciones WHERE id = ?");
            $stmt->execute([$id_seccion]);
            $seccion_data = $stmt->fetch();
            
            if (!$seccion_data) {
                $pdo->rollBack();
                responder('error', 'Sección inválida', $isAjax);
            }
            
            $id_carrera = $seccion_data['id_carrera'];
            $id_grado = $seccion_data['id_grado'];
            
            if ($tipo_estudiante === 'existente') {
                $id_estudiante = $_POST['id_estudiante_existente'] ?? 0;
                if (!$id_estudiante) {
                    $pdo->rollBack();
                    responder('error', 'Debes seleccionar un estudiante', $isAjax);
                }
            } else {
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
                    responder('error', 'Ya existe un estudiante con ese NIE', $isAjax);
                }

                if ($dui) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE dui = ?");
                    $stmt->execute([$dui]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'Ya existe un estudiante con ese DUI', $isAjax);
                    }
                }

                if ($telefono) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE telefono = ?");
                    $stmt->execute([$telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'Ya existe un estudiante con ese teléfono', $isAjax);
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO estudiantes (nie, nombres, apellidos, dui, edad, fecha_nacimiento, telefono, email, direccion, estado, id_seccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?)");
                $stmt->execute([$nie, $nombres, $apellidos, $dui, $edad, $fecha_nacimiento, $telefono, $email, $direccion, $id_seccion]);
                $id_estudiante = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("SELECT id FROM responsables WHERE dui = ?");
            $stmt->execute([$resp_dui]);
            $responsable_existente = $stmt->fetch();
            
            if ($responsable_existente) {
                if ($resp_dui) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE dui = ?");
                    $stmt->execute([$resp_dui]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'El DUI del responsable ya está registrado como estudiante', $isAjax);
                    }
                }
            } else {
                if ($resp_telefono) {
                    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE telefono = ?");
                    $stmt->execute([$resp_telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'Ya existe un responsable con ese teléfono', $isAjax);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE telefono = ?");
                    $stmt->execute([$resp_telefono]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'El teléfono ya está registrado en un estudiante', $isAjax);
                    }
                }
                
                if ($resp_email) {
                    $stmt = $pdo->prepare("SELECT id FROM responsables WHERE email = ?");
                    $stmt->execute([$resp_email]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'Ya existe un responsable con ese correo', $isAjax);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE email = ?");
                    $stmt->execute([$resp_email]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'El correo ya está registrado en un estudiante', $isAjax);
                    }
                }
                
                if ($resp_dui) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE dui = ?");
                    $stmt->execute([$resp_dui]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        responder('error', 'El DUI ya está registrado como estudiante', $isAjax);
                    }
                }
            }

            $stmt = $pdo->prepare("INSERT INTO matriculas (id_estudiante, id_carrera, id_grado, id_seccion, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_estudiante, $id_carrera, $id_grado, $id_seccion, $estado]);
            $id_matricula = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO responsables (id_matricula, dui, nombres, apellidos, ocupacion, parentesco, email, telefono, direccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_matricula, $resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion]);

            $pdo->commit();
            responder('success', 'Matrícula guardada exitosamente', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'Error en la base de datos', $isAjax);
        }
    }
    
    // ==========================================
    // EDITAR MATRÍCULA
    // ==========================================
    if ($accion === 'editar') {
        $matricula_id = $_POST['matricula_id'] ?? 0;
        $id_estudiante = $_POST['id_estudiante'] ?? 0;
        $id_seccion = $_POST['id_seccion'] ?? 0;
        $estado = $_POST['estado'] ?? 'Activo';
        
        $resp_dui = trim($_POST['responsable_dui'] ?? '');
        $resp_nombres = trim($_POST['responsable_nombres'] ?? '');
        $resp_apellidos = trim($_POST['responsable_apellidos'] ?? '');
        $resp_ocupacion = trim($_POST['responsable_ocupacion'] ?? '');
        $resp_parentesco = $_POST['responsable_parentesco'] ?? '';
        $resp_email = trim($_POST['responsable_email'] ?? '');
        $resp_telefono = trim($_POST['responsable_telefono'] ?? '');
        $resp_direccion = trim($_POST['responsable_direccion'] ?? '');

        if (!validarGmail($resp_email)) {
            responder('error', 'El correo debe ser @gmail.com', $isAjax);
        }

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT id_carrera, id_grado FROM secciones WHERE id = ?");
            $stmt->execute([$id_seccion]);
            $seccion_data = $stmt->fetch();
            
            if ($seccion_data) {
                $id_carrera = $seccion_data['id_carrera'];
                $id_grado = $seccion_data['id_grado'];
            } else {
                responder('error', 'Sección inválida', $isAjax);
            }
            
            $stmt = $pdo->prepare("UPDATE matriculas SET id_estudiante=?, id_carrera=?, id_grado=?, id_seccion=?, estado=? WHERE id=?");
            $stmt->execute([$id_estudiante, $id_carrera, $id_grado, $id_seccion, $estado, $matricula_id]);

            $stmt = $pdo->prepare("UPDATE responsables SET dui=?, nombres=?, apellidos=?, ocupacion=?, parentesco=?, email=?, telefono=?, direccion=? WHERE id_matricula=?");
            $stmt->execute([$resp_dui, $resp_nombres, $resp_apellidos, $resp_ocupacion, $resp_parentesco, $resp_email, $resp_telefono, $resp_direccion, $matricula_id]);

            $pdo->commit();
            responder('success', 'Matrícula actualizada exitosamente', $isAjax);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'Error en la base de datos', $isAjax);
        }
    }
}

// ==========================================
// ELIMINAR MATRÍCULA (GET)
// ==========================================
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $id = $_GET['id'] ?? 0;
    
    try {
        // Desactivar restricciones de claves foráneas
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // 1. Obtener el ID del estudiante asociado a esta matrícula
        $stmt = $pdo->prepare("SELECT id_estudiante FROM matriculas WHERE id = ?");
        $stmt->execute([$id]);
        $id_estudiante = $stmt->fetchColumn();
        
        // 2. Eliminar responsable (depende de matrícula)
        $stmt = $pdo->prepare("DELETE FROM responsables WHERE id_matricula = ?");
        $stmt->execute([$id]);
        
        // 3. Eliminar matrícula
        $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
        $stmt->execute([$id]);
        
        // 4. Eliminar estudiante (si existe)
        if ($id_estudiante) {
            $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
            $stmt->execute([$id_estudiante]);
        }
        
        // Reactivar restricciones
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
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