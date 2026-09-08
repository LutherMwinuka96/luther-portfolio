<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = getenv('PORTFOLIO_DB_HOST') ?: 'localhost';
        $this->db_name = getenv('PORTFOLIO_DB_NAME') ?: 'portfolio_db';
        $this->username = getenv('PORTFOLIO_DB_USER') ?: 'root';
        $this->password = getenv('PORTFOLIO_DB_PASSWORD') ?: '';
    }

    public function getConnection() {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
        $this->conn = new PDO($dsn, $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->conn;
    }
}
?>
