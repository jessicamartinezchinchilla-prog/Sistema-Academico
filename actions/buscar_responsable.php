<?php
// actions/buscar_responsable.php
require_once '../config/database.php';

header('Content-Type: application/json');

$dui = $_GET['dui'] ?? '';

if (empty($dui)) {
    echo json_encode(['encontrado' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM responsables WHERE dui = ? LIMIT 1");
    $stmt->execute([$dui]);
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