<?php
// actions/obtener_secciones_filtradas.php
require_once '../config/database.php';

header('Content-Type: application/json');

$id_grado = $_GET['id_grado'] ?? null;
$mostrar_todas = $_GET['mostrar_todas'] ?? '0';

try {
    if ($mostrar_todas === '1') {
        $stmt = $pdo->query("
            SELECT id, nombre, id_grado 
            FROM secciones 
            ORDER BY id_grado, nombre
        ");
    } else {
        if (!$id_grado) {
            echo json_encode(['secciones' => []]);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT id, nombre, id_grado 
            FROM secciones 
            WHERE id_grado = ?
            ORDER BY nombre
        ");
        $stmt->execute([$id_grado]);
    }
    
    $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'secciones' => $secciones,
        'total' => count($secciones)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['secciones' => [], 'error' => $e->getMessage()]);
}
?>