<?php
// config/database.php
$host = 'localhost';
$dbname = 'sistema_academico';
$username = 'root'; // Cambia esto si tu usuario de MySQL es diferente
$password = '1234';     // Cambia esto si tu MySQL tiene contraseña

try {
    // Creamos la conexión
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Configuramos PDO para que lance excepciones si hay errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión con la base de datos: " . $e->getMessage());
}
?>