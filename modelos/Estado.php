<?php
require_once "config/database.php";

class Estado {
    private $conn;

    public $id_estado;
    public $nombre_estado;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // Obtener todos los estados
    public function obtenerTodos() {
        $query = "SELECT id_estado, nombre_estado FROM estados";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
