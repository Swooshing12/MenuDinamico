<?php
require_once __DIR__ . "/../config/database.php";

class Permisos {
    public $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // Obtener permisos de un rol sobre un submenu
    public function obtenerPermisos($id_rol, $id_submenu) {
        $query = "
            SELECT p.*
            FROM permisos_roles_submenus p
            INNER JOIN roles_submenus rs ON p.id_roles_submenus = rs.id_roles_submenus
            WHERE rs.id_rol = ? AND rs.id_submenu = ?
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_rol, $id_submenu]);
        $permiso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $permiso ? $permiso : ['puede_crear' => 0, 'puede_leer' => 0, 'puede_editar' => 0, 'puede_eliminar' => 0];
    }

    
}
?>
