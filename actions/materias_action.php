<?php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function responder($tipo, $mensaje, $isAjax) {
    if ($isAjax) {
        echo strtoupper($tipo) . ":" . $mensaje;
        exit;
    } else {
        header("Location: ../Vistas/materias.php?{$tipo}={$mensaje}");
        exit;
    }
}

// ✅ FUNCIÓN: Generar código automático de materia
function generarCodigoMateria($pdo) {
    // Obtener el último código
    $stmt = $pdo->query("SELECT codigo FROM materias WHERE codigo LIKE 'MAT-%' ORDER BY id DESC LIMIT 1");
    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimo) {
        // Extraer el número del último código (MAT-001 -> 001 -> 1)
        $numero = intval(substr($ultimo['codigo'], 4)); // "MAT-" tiene 4 caracteres
        $nuevo_numero = $numero + 1;
    } else {
        $nuevo_numero = 1;
    }
    
    // Formatear con ceros a la izquierda (001, 002, etc.)
    return 'MAT-' . str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    if ($accion === 'agregar') {
        // ✅ Generar código automáticamente
        $codigo = generarCodigoMateria($pdo);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $secciones = $_POST['secciones'] ?? [];
        $profesores = $_POST['profesores'] ?? [];

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO materias (codigo, nombre, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $descripcion]);
            $id_materia = $pdo->lastInsertId();

            // Si hay profesores, crear asignaciones completas
            if (!empty($profesores) && !empty($secciones)) {
                $stmt = $pdo->prepare("INSERT INTO asignaciones (id_materia, id_seccion, id_profesor) VALUES (?, ?, ?)");
                foreach ($secciones as $id_seccion) {
                    foreach ($profesores as $id_profesor) {
                        $stmt->execute([$id_materia, $id_seccion, $id_profesor]);
                    }
                }
            } 
            // Si solo hay secciones (sin profesores), crear asignaciones con profesor NULL
            else if (!empty($secciones)) {
                $stmt = $pdo->prepare("INSERT INTO asignaciones (id_materia, id_seccion, id_profesor) VALUES (?, ?, NULL)");
                foreach ($secciones as $id_seccion) {
                    $stmt->execute([$id_materia, $id_seccion]);
                }
            }

            $pdo->commit();
            
            // ✅ AUDITORÍA
            $totalAsignaciones = count($secciones) * max(count($profesores), 1);
            $descripcionAuditoria = "Se creó la materia '{$nombre}' (código: {$codigo})";
            if (!empty($secciones)) {
                $descripcionAuditoria .= " asignada a " . count($secciones) . " sección(es)";
            }
            if (!empty($profesores)) {
                $descripcionAuditoria .= " con " . count($profesores) . " profesor(es)";
            }
            registrarAuditoria($pdo, 'creacion', 'materias', $descripcionAuditoria);
            
            responder('success', '1', $isAjax);
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'editar') {
        $id = $_POST['materia_id'];
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $secciones = $_POST['secciones'] ?? [];
        $profesores = $_POST['profesores'] ?? [];

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE materias SET codigo=?, nombre=?, descripcion=? WHERE id=?");
            $stmt->execute([$codigo, $nombre, $descripcion, $id]);

            // Borrar asignaciones viejas
            $stmt = $pdo->prepare("DELETE FROM asignaciones WHERE id_materia = ?");
            $stmt->execute([$id]);

            // Crear nuevas asignaciones
            if (!empty($profesores) && !empty($secciones)) {
                $stmt = $pdo->prepare("INSERT INTO asignaciones (id_materia, id_seccion, id_profesor) VALUES (?, ?, ?)");
                foreach ($secciones as $id_seccion) {
                    foreach ($profesores as $id_profesor) {
                        $stmt->execute([$id, $id_seccion, $id_profesor]);
                    }
                }
            } 
            else if (!empty($secciones)) {
                $stmt = $pdo->prepare("INSERT INTO asignaciones (id_materia, id_seccion, id_profesor) VALUES (?, ?, NULL)");
                foreach ($secciones as $id_seccion) {
                    $stmt->execute([$id, $id_seccion]);
                }
            }

            $pdo->commit();
            
            // ✅ AUDITORÍA
            $descripcionAuditoria = "Se modificó la materia '{$nombre}' (código: {$codigo})";
            if (!empty($secciones)) {
                $descripcionAuditoria .= " con " . count($secciones) . " sección(es)";
            }
            if (!empty($profesores)) {
                $descripcionAuditoria .= " y " . count($profesores) . " profesor(es)";
            }
            registrarAuditoria($pdo, 'modificacion', 'materias', $descripcionAuditoria);
            
            responder('success', 'editado', $isAjax);
        } catch (PDOException $e) {
            $pdo->rollBack();
            responder('error', 'bd', $isAjax);
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            // ✅ AUDITORÍA: Obtener datos ANTES de eliminar
            $stmt = $pdo->prepare("SELECT nombre, codigo FROM materias WHERE id = ?");
            $stmt->execute([$id]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $stmt = $pdo->prepare("DELETE FROM materias WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // ✅ AUDITORÍA
            if ($datos) {
                registrarAuditoria($pdo, 'eliminacion', 'materias', "Se eliminó la materia '{$datos['nombre']}' (código: {$datos['codigo']})");
            }
            
            responder('success', 'eliminado', $isAjax);
        } catch (PDOException $e) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            responder('error', 'bd', $isAjax);
        }
    }
}

if (!$isAjax) {
    header("Location: ../Vistas/materias.php");
    exit;
}
?>