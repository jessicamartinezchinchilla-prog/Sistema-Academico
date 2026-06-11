<?php
require_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "✅ Conexión exitosa a la base de datos 'sistema_academico'";
} else {
    echo "❌ Error de conexión";
}
?>