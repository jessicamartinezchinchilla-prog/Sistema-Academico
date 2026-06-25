<?php
// actions/obtener_codigo_materia.php
require_once '../config/database.php';

header('Content-Type: text/plain');

// Obtener el último código
$stmt = $pdo->query("SELECT codigo FROM materias WHERE codigo LIKE 'MAT-%' ORDER BY id DESC LIMIT 1");
$ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ultimo) {
    // Extraer el número del último código (MAT-001 -> 001 -> 1)
    $numero = intval(substr($ultimo['codigo'], 4));
    $nuevo_numero = $numero + 1;
} else {
    $nuevo_numero = 1;
}

// Formatear con ceros a la izquierda (001, 002, etc.)
echo 'MAT-' . str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);
?>