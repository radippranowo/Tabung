<?php
class DB {
    private static $instance = null;
    private $conn = null;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                'mysql:host=localhost;dbname=tabung_lpg;charset=utf8mb4',
                'root', '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>