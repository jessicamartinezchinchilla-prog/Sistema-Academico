<?php
// actions/buscar_estudiante.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$id_estudiante = $_GET['id_estudiante'] ?? 0;

if (!$id_estudiante) {
    echo json_encode(['encontrado' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nie, nombres, apellidos, dui, edad, telefono, email, direccion, estado
        FROM estudiantes 
        WHERE id = ?
    ");
    $stmt->execute([$id_estudiante]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estudiante) {
        echo json_encode([
            'encontrado' => true,
            'datos' => $estudiante
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['encontrado' => false, 'error' => $e->getMessage()]);
}
?>