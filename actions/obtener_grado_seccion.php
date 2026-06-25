<?php
// actions/obtener_grado_seccion.php
require_once '../config/database.php';

header('Content-Type: application/json');

$id_seccion = $_GET['id_seccion'] ?? 0;

if (!$id_seccion) {
    echo json_encode(['encontrado' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_grado 
        FROM secciones 
        WHERE id = ?
    ");
    $stmt->execute([$id_seccion]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        echo json_encode([
            'encontrado' => true,
            'id_grado' => (int)$resultado['id_grado']
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['encontrado' => false, 'error' => $e->getMessage()]);
}
?>