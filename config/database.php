<?php
class database {
    private $host = "localhost"; // Cambia si es necesario
    private $dbname = "menudinamico";  // Nombre de la base de datos
    private $username = "root"; // Usuario de MySQL
    private $password = "";     // Contraseña de MySQL (déjala vacía si no usas clave en local)
    private static $instance = null;
    public $conn;

    private function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error en la conexión: " . $e->getMessage());
        }
    }

    public static function getConnection() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
?>
