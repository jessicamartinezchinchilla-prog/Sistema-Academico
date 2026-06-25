<?php
// actions/obtener_ultimo_grado.php
require_once '../config/database.php';

header('Content-Type: application/json');

$id_estudiante = $_GET['id_estudiante'] ?? 0;

if (!$id_estudiante) {
    echo json_encode(['encontrado' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT s.id_grado, g.nombre as nombre_grado
        FROM matriculas m
        INNER JOIN secciones s ON m.id_seccion = s.id
        INNER JOIN grados g ON s.id_grado = g.id
        WHERE m.id_estudiante = ?
        ORDER BY m.estado = 'Activo' DESC, m.id DESC
        LIMIT 1
    ");
    $stmt->execute([$id_estudiante]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        echo json_encode([
            'encontrado' => true,
            'id_grado' => (int)$resultado['id_grado'],
            'nombre_grado' => $resultado['nombre_grado']
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['encontrado' => false, 'error' => $e->getMessage()]);
}
?>