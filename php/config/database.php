<?php
// PHP/config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "sistema_academico";
    private $username = "root";
    private $password = "123456789";
    private $port = "3306";
    
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            
            // Configurar PDO
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>