<?php
// actions/profesores_action.php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['accion'])) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    // Función para validar correo Gmail
    function validarGmail($correo) {
        return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $correo);
    }
    
    if ($accion === 'agregar') {
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $dui = trim($_POST['dui']);
        $nip = trim($_POST['nip']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $id_materia = $_POST['id_materia'];
        $secciones = $_POST['id_seccion'];

        // VALIDAR QUE SEA GMAIL
        if (!validarGmail($correo)) {
            header("Location: ../Vistas/profesores.php?error=gmail");
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO profesores (nombres, apellidos, dui, nip, correo, telefono) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombres, $apellidos, $dui, $nip, $correo, $telefono]);
            $id_profesor = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_materia, id_seccion) VALUES (?, ?, ?)");
            foreach ($secciones as $id_sec) {
                $stmt->execute([$id_profesor, $id_materia, $id_sec]);
            }

            $pdo->commit();
            header("Location: ../Vistas/profesores.php?success=1");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error al agregar: " . $e->getMessage());
        }
    }

    if ($accion === 'editar') {
        $id = $_POST['id_profesor'];
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $dui = trim($_POST['dui']);
        $nip = trim($_POST['nip']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $id_materia = $_POST['id_materia'];
        $secciones = $_POST['id_seccion'];

        // VALIDAR QUE SEA GMAIL
        if (!validarGmail($correo)) {
            header("Location: ../Vistas/profesores.php?error=gmail");
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE profesores SET nombres=?, apellidos=?, dui=?, nip=?, correo=?, telefono=? WHERE id=?");
            $stmt->execute([$nombres, $apellidos, $dui, $nip, $correo, $telefono, $id]);

            $stmt = $pdo->prepare("DELETE FROM profesor_asignacion WHERE id_profesor = ?");
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare("INSERT INTO profesor_asignacion (id_profesor, id_materia, id_seccion) VALUES (?, ?, ?)");
            foreach ($secciones as $id_sec) {
                $stmt->execute([$id, $id_materia, $id_sec]);
            }

            $pdo->commit();
            header("Location: ../Vistas/profesores.php?success=editado");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error al editar: " . $e->getMessage());
        }
    }

    if ($accion === 'eliminar') {
        $id = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM profesores WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ../Vistas/profesores.php?success=eliminado");
            exit;
        } catch (PDOException $e) {
            die("Error al eliminar: " . $e->getMessage());
        }
    }
}

header("Location: ../Vistas/profesores.php");
exit;
?>