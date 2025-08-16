<?php
class Database {
    private $host = "localhost";
    private $db_name = "habithub";
    private $username = "root";
    private $password = "password";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Explicitly select the database to ensure we're using it
            $this->conn->exec("USE " . $this->db_name);
            
            return $this->conn;
        } catch(PDOException $e) {
            throw new Exception("Connection Error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
