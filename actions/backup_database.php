<?php
// actions/backup_database.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';

// Verificar sesión (solo usuarios logueados pueden descargar backups)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Vistas/index.php");
    exit;
}

try {
    // Nombre del archivo con fecha y hora
    $nombre_archivo = 'backup_sistema_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Headers para forzar la descarga
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = "-- ============================================\n";
    $output .= "-- Respaldo de Base de Datos\n";
    $output .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- ============================================\n\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";

    // Obtener todas las tablas
    $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);

    foreach ($tablas as $tabla_row) {
        $tabla = $tabla_row[0];
        
        // Estructura de la tabla
        $output .= "--\n-- Estructura de tabla para `{$tabla}`\n--\n\n";
        $create = $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch();
        $output .= "DROP TABLE IF EXISTS `{$tabla}`;\n";
        $output .= $create[1] . ";\n\n";

        // Datos de la tabla
        $output .= "--\n-- Volcado de datos para la tabla `{$tabla}`\n--\n\n";
        $filas = $pdo->query("SELECT * FROM `{$tabla}`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($filas) > 0) {
            foreach ($filas as $fila) {
                $valores = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($fila));
                
                $output .= "INSERT INTO `{$tabla}` VALUES (" . implode(', ', $valores) . ");\n";
            }
            $output .= "\n";
        }
    }

    $output .= "COMMIT;\n";

    // Imprimir el contenido (esto es lo que se descarga)
    echo $output;

    // ✅ AUDITORÍA
    $usuario = $_SESSION['username'] ?? 'Desconocido';
    // Nota: La auditoría se registra después de enviar los headers, 
    // pero como es un archivo de descarga, la conexión se mantiene abierta un momento.
    // Si falla, no es crítico.
    try {
        registrarAuditoria($pdo, 'exportacion', 'respaldos', "El usuario '{$usuario}' generó un respaldo de la base de datos");
    } catch (Exception $e) {
        // Ignorar errores de auditoría en descargas
    }

    exit;

} catch (PDOException $e) {
    die("Error al generar el respaldo: " . $e->getMessage());
}
?>