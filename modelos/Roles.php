<?php
require_once __DIR__ . "/../config/database.php";  // Corregir la ruta

class Roles {
    private $conn;

    public $id_rol;
    public $nombre_rol;

    public function __construct() {
        $this->conn = Database::getConnection();  // Asegúrate de que Database está configurado correctamente
    }

    // Obtener todos los roles
    public function obtenerTodos() {
        $query = "SELECT id_rol, nombre_rol FROM roles";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un rol por su ID
    public function obtenerPorId($id_rol) {
        $query = "SELECT nombre_rol FROM roles WHERE id_rol = :id_rol";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Función para obtener los menús y submenús asociados a un rol
    public function obtenerMenusPorRol($id_rol) {
        // Consulta para obtener los menús y sus submenús asociados a un rol específico
        $query = "
            SELECT m.id_menu, m.nombre_menu, s.id_submenu, s.nombre_submenu, s.url_submenu
            FROM roles_submenus rs
            INNER JOIN submenus s ON rs.id_submenu = s.id_submenu
            INNER JOIN menus m ON s.id_menu = m.id_menu
            WHERE rs.id_rol = :id_rol
            ORDER BY m.id_menu, s.id_submenu
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Función para crear un nuevo rol
    public function crearRol($nombre_rol) {
        $query = "INSERT INTO roles (nombre_rol) VALUES (:nombre_rol)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();  // Devuelve el ID del nuevo rol
        }
        return false;
    }

    // Función para asociar un submenu a un rol
    public function asociarSubmenu($id_rol, $id_submenu) {
        $query = "INSERT INTO roles_submenus (id_rol, id_submenu) VALUES (:id_rol, :id_submenu)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Función para obtener el ID de roles_submenus para asociar permisos
    public function obtenerIdRolesSubmenus($id_rol, $id_submenu) {
        $query = "SELECT id_roles_submenus FROM roles_submenus WHERE id_rol = :id_rol AND id_submenu = :id_submenu";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['id_roles_submenus'] : null;
    }

    // Función para crear permisos para un rol en un submenu
    public function crearPermisos($id_roles_submenus, $permisos) {
        $query = "INSERT INTO permisos_roles_submenus (id_roles_submenus, puede_crear, puede_editar, puede_eliminar)
                  VALUES (:id_roles_submenus, :crear, :editar, :eliminar)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_roles_submenus', $id_roles_submenus, PDO::PARAM_INT);
        $stmt->bindValue(':crear', $permisos['puede_crear'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':editar', $permisos['puede_editar'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':eliminar', $permisos['puede_eliminar'] ? 1 : 0, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Obtener nombre de un rol por ID
    public function obtenerNombreRol($id_rol) {
        $query = "SELECT nombre_rol FROM roles WHERE id_rol = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_rol]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['nombre_rol'] : 'Sin Rol';
    }
    public function existeRolPorNombre($nombre) {
    $query = "SELECT COUNT(*) FROM roles WHERE nombre_rol = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->execute([$nombre]);
    return $stmt->fetchColumn() > 0;
}

public function editarRol(int $id, string $n): bool {
      $q = $this->conn->prepare("UPDATE roles SET nombre_rol = ? WHERE id_rol = ?");
      return $q->execute([$n,$id]);
    }

    public function eliminarRol(int $id): bool {
      // borrado físico (o cambia a lógico si prefieres)
      $q = $this->conn->prepare("DELETE FROM roles WHERE id_rol = ?");
      return $q->execute([$id]);
    }

    // Métodos de permisos ya tenías: asociarSubmenu, obtenerIdRolesSubmenus, crearPermisos,...
    // AÑADIR:
    public function eliminarSubmenusRol(int $id_rol): void {
      $this->conn->prepare("DELETE rs, p 
          FROM roles_submenus rs
          LEFT JOIN permisos_roles_submenus p USING(id_roles_submenus)
          WHERE rs.id_rol = ?")
        ->execute([$id_rol]);
    }

   /**
 * Devuelve un array con:
 *  - 'nombre_rol'   => string
 *  - 'menus'        => [id_menu, …]
 *  - 'permisos'     => [ ['id_submenu'=>…, 'puede_crear'=>…, 'puede_editar'=>…, 'puede_eliminar'=>…], … ]
 */
// Devuelve para un rol dado la estructura completa de menús->submenús con permisos
public function obtenerPermisosPorRol($id_rol): array {
    $menus = $this->conn->query("SELECT id_menu, nombre_menu FROM menus ORDER BY id_menu")
                        ->fetchAll(PDO::FETCH_ASSOC);
    $estructura = [];
    foreach ($menus as $menu) {
        $stmt = $this->conn->prepare("
            SELECT s.id_submenu, s.nombre_submenu,
                   IFNULL(p.puede_crear,0) AS puede_crear,
                   IFNULL(p.puede_editar,0) AS puede_editar,
                   IFNULL(p.puede_eliminar,0) AS puede_eliminar
            FROM submenus s
            LEFT JOIN roles_submenus rs ON rs.id_submenu = s.id_submenu AND rs.id_rol = :id_rol
            LEFT JOIN permisos_roles_submenus p ON p.id_roles_submenus = rs.id_roles_submenus
            WHERE s.id_menu = :id_menu
        ");
        $stmt->execute([
            ':id_rol' => $id_rol,
            ':id_menu' => $menu['id_menu']
        ]);
        $submenus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $estructura[] = [
            'id_menu' => $menu['id_menu'],
            'nombre_menu' => $menu['nombre_menu'],
            'submenus' => $submenus
        ];
    }
    return $estructura;
}

// Borra todas las asignaciones de submenús de un rol
public function borrarSubmenusPorRol(int $id_rol): bool {
    $stmt = $this->conn->prepare("DELETE FROM roles_submenus WHERE id_rol = ?");
    return $stmt->execute([$id_rol]);
}

public function actualizarPermisosRol($id_rol, array $permisos) {
    // $permisos: [ id_submenu => ['crear'=>bool,'leer'=>bool,'editar'=>bool,'eliminar'=>bool], ... ]
    // 1) Borrar relaciones previas
    $this->conn->prepare("DELETE rs,p
        FROM roles_submenus rs
        LEFT JOIN permisos_roles_submenus p ON p.id_roles_submenus=rs.id_roles_submenus
        WHERE rs.id_rol = ?")->execute([$id_rol]);

    // 2) Insertar nuevas
    $insRs = $this->conn->prepare("INSERT INTO roles_submenus (id_rol, id_submenu) VALUES (?,?)");
    $insP  = $this->conn->prepare("
        INSERT INTO permisos_roles_submenus
          (id_roles_submenus, puede_crear, puede_editar, puede_eliminar)
        VALUES (?,?,?,?)");
    foreach ($permisos as $id_sub => $acc) {
        $insRs->execute([$id_rol, $id_sub]);
        $idr = $this->conn->lastInsertId();
        $insP->execute([
            $idr,
            $acc['crear']?1:0,
            $acc['editar']?1:0,
            $acc['eliminar']?1:0
        ]);
    }
}


}

?>
