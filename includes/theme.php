<?php
// includes/theme.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

// Obtener configuración visual de la BD
$stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave IN ('visual_color_primario', 'visual_modo_oscuro')");
$stmt->execute();
$tema = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tema[$row['clave']] = $row['valor'];
}

$color_primario = $tema['visual_color_primario'] ?? '#2F6FED';
$modo_oscuro = ($tema['visual_modo_oscuro'] ?? '0') === '1';
?>

<!-- Estilos dinámicos inyectados en el HEAD -->
<style>
    :root {
        --color-primario: <?php echo $color_primario; ?>;
    }
</style>

<!-- Enlace al CSS global del tema -->
<link rel="stylesheet" href="../CSS/tema.css">