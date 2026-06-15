<?php
// actions/buscar_responsable_por_estudiante.php
require_once '../config/database.php';

header('Content-Type: application/json');

$id_estudiante = $_GET['id_estudiante'] ?? '';

if (empty($id_estudiante)) {
    echo json_encode(['encontrado' => false]);
    exit;
}

try {
    // Buscar la matrícula activa del estudiante y su responsable
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM responsables r
        INNER JOIN matriculas m ON r.id_matricula = m.id
        WHERE m.id_estudiante = ? AND m.estado = 'Activo'
        LIMIT 1
    ");
    $stmt->execute([$id_estudiante]);
    $responsable = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($responsable) {
        echo json_encode([
            'encontrado' => true,
            'datos' => $responsable
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['encontrado' => false, 'error' => $e->getMessage()]);
}
?>