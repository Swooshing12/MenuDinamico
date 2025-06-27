<?php
require_once __DIR__ . "/../config/database.php";

class Roles {
    private $conn;

    public $id_rol;
    public $nombre_rol;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // ===== MÉTODOS BÁSICOS CRUD =====

    /**
     * Obtener todos los roles
     */
    public function obtenerTodos() {
        $query = "SELECT id_rol, nombre_rol, 
                         DATE_FORMAT(fecha_creacion, '%Y-%m-%d %H:%i:%s') as fecha_creacion 
                  FROM roles 
                  ORDER BY nombre_rol ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un rol por su ID
     */
    public function obtenerPorId($id_rol) {
        $query = "SELECT id_rol, nombre_rol, fecha_creacion 
                  FROM roles 
                  WHERE id_rol = :id_rol";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un rol por su nombre
     */
    public function obtenerPorNombre($nombre_rol) {
        $query = "SELECT id_rol, nombre_rol, fecha_creacion 
                  FROM roles 
                  WHERE LOWER(nombre_rol) = LOWER(:nombre_rol)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear un nuevo rol
     */
    public function crearRol($nombre_rol) {
        $query = "INSERT INTO roles (nombre_rol, fecha_creacion) 
                  VALUES (:nombre_rol, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Editar un rol existente
     */
    public function editarRol(int $id_rol, string $nombre_rol): bool {
        $query = "UPDATE roles SET nombre_rol = :nombre_rol WHERE id_rol = :id_rol";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Eliminar un rol
     */
    public function eliminarRol(int $id_rol): bool {
        try {
            // Primero eliminar permisos asociados
            $this->eliminarSubmenusRol($id_rol);
            
            // Luego eliminar el rol
            $query = "DELETE FROM roles WHERE id_rol = :id_rol";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error eliminando rol: " . $e->getMessage());
            return false;
        }
    }

    
    // ===== MÉTODOS DE VERIFICACIÓN =====

    /**
     * Verificar si existe un rol por nombre
     */
    public function existeRolPorNombre($nombre_rol) {
        $query = "SELECT COUNT(*) FROM roles WHERE LOWER(nombre_rol) = LOWER(:nombre_rol)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // ===== MÉTODOS PARA PAGINACIÓN Y BÚSQUEDA =====

    /**
     * Contar total de roles (con búsqueda opcional)
     */
    public function contarRoles($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM roles WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND nombre_rol LIKE :busqueda";
            $params['busqueda'] = "%{$busqueda}%";
        }
        
        try {
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando roles: " . $e->getMessage());
            throw new Exception("Error al contar los roles");
        }
    }

    /**
     * Obtener roles paginados (con búsqueda opcional)
     */
    public function obtenerRolesPaginados($offset = 0, $limit = 10, $busqueda = '') {
        // Validar y limpiar parámetros
        $offset = max(0, (int)$offset);
        $limit = max(1, min(100, (int)$limit));
        $busqueda = trim($busqueda);
        
        $sql = "SELECT id_rol, nombre_rol, 
                       DATE_FORMAT(fecha_creacion, '%Y-%m-%d %H:%i:%s') as fecha_creacion
                FROM roles WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND nombre_rol LIKE :busqueda";
            $params['busqueda'] = "%{$busqueda}%";
        }
        
        $sql .= " ORDER BY nombre_rol ASC LIMIT {$offset}, {$limit}";
        
        try {
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("ERROR en obtenerRolesPaginados: " . $e->getMessage());
            throw new Exception("Error al obtener los roles paginados: " . $e->getMessage());
        }
    }

    // ===== MÉTODOS DE ESTADÍSTICAS =====

    /**
     * Contar roles que tienen usuarios asignados
     */
    public function contarRolesConUsuarios() {
        $query = "SELECT COUNT(DISTINCT r.id_rol) as total 
                  FROM roles r 
                  INNER JOIN usuarios u ON r.id_rol = u.id_rol";
        
        try {
            $stmt = $this->conn->query($query);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando roles con usuarios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar total de permisos asignados a todos los roles
     */
    public function contarPermisosAsignados() {
        $query = "SELECT COUNT(*) as total FROM permisos_roles_submenus";
        
        try {
            $stmt = $this->conn->query($query);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando permisos asignados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar permisos específicos de un rol
     */
    public function contarPermisosPorRol($id_rol) {
        $query = "SELECT COUNT(*) as total 
                  FROM permisos_roles_submenus p
                  INNER JOIN roles_submenus rs ON p.id_roles_submenus = rs.id_roles_submenus
                  WHERE rs.id_rol = :id_rol";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando permisos por rol: " . $e->getMessage());
            return 0;
        }
    }

    // ===== MÉTODOS DE PERMISOS =====

    /**
     * Asociar un submenú a un rol
     */
    public function asociarSubmenu($id_rol, $id_submenu) {
        $query = "INSERT INTO roles_submenus (id_rol, id_submenu) VALUES (:id_rol, :id_submenu)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obtener ID de roles_submenus para asociar permisos
     */
    public function obtenerIdRolesSubmenus($id_rol, $id_submenu) {
        $query = "SELECT id_roles_submenus FROM roles_submenus 
                  WHERE id_rol = :id_rol AND id_submenu = :id_submenu";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['id_roles_submenus'] : null;
    }

    /**
     * Crear permisos para un rol en un submenú
     */
    public function crearPermisos($id_roles_submenus, $permisos) {
        $query = "INSERT INTO permisos_roles_submenus 
                  (id_roles_submenus, puede_crear, puede_editar, puede_eliminar)
                  VALUES (:id_roles_submenus, :crear, :editar, :eliminar)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_roles_submenus', $id_roles_submenus, PDO::PARAM_INT);
        $stmt->bindValue(':crear', $permisos['puede_crear'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':editar', $permisos['puede_editar'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':eliminar', $permisos['puede_eliminar'] ? 1 : 0, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Eliminar todos los submenús y permisos de un rol
     */
    public function eliminarSubmenusRol(int $id_rol): void {
        try {
            $this->conn->prepare("DELETE p 
                FROM permisos_roles_submenus p
                INNER JOIN roles_submenus rs ON p.id_roles_submenus = rs.id_roles_submenus
                WHERE rs.id_rol = ?")
                ->execute([$id_rol]);

            $this->conn->prepare("DELETE FROM roles_submenus WHERE id_rol = ?")
                ->execute([$id_rol]);
        } catch (PDOException $e) {
            error_log("Error eliminando submenús del rol: " . $e->getMessage());
            throw new Exception("Error al eliminar permisos del rol");
        }
    }

    /**
     * Actualizar permisos completos de un rol
     */
    public function actualizarPermisosRol($id_rol, array $permisos) {
        try {
            // 1) Borrar relaciones previas
            $this->eliminarSubmenusRol($id_rol);

            // 2) Insertar nuevas relaciones y permisos
            $insRs = $this->conn->prepare("INSERT INTO roles_submenus (id_rol, id_submenu) VALUES (?,?)");
            $insP = $this->conn->prepare("
                INSERT INTO permisos_roles_submenus
                (id_roles_submenus, puede_crear, puede_editar, puede_eliminar)
                VALUES (?,?,?,?)");

            foreach ($permisos as $id_submenu => $acciones) {
                if (!empty($acciones)) {
                    // Insertar relación rol-submenú
                    $insRs->execute([$id_rol, $id_submenu]);
                    $id_roles_submenus = $this->conn->lastInsertId();

                    // Insertar permisos específicos
                    $insP->execute([
                        $id_roles_submenus,
                        in_array('crear', $acciones) ? 1 : 0,
                        in_array('editar', $acciones) ? 1 : 0,
                        in_array('eliminar', $acciones) ? 1 : 0
                    ]);
                }
            }
        } catch (PDOException $e) {
            error_log("Error actualizando permisos del rol: " . $e->getMessage());
            throw new Exception("Error al actualizar permisos del rol");
        }
    }

    /**
     * Obtener permisos completos por rol (para edición/visualización)
     */
    public function obtenerPermisosPorRol($id_rol): array {
        $query = "SELECT m.id_menu, m.nombre_menu FROM menus m ORDER BY m.nombre_menu";
        $menus = $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        $estructura = [];
        foreach ($menus as $menu) {
            $stmt = $this->conn->prepare("
                SELECT s.id_submenu, s.nombre_submenu,
                       COALESCE(p.puede_crear, 0) AS puede_crear,
                       COALESCE(p.puede_editar, 0) AS puede_editar,
                       COALESCE(p.puede_eliminar, 0) AS puede_eliminar
                FROM submenus s
                LEFT JOIN roles_submenus rs ON rs.id_submenu = s.id_submenu AND rs.id_rol = :id_rol
                LEFT JOIN permisos_roles_submenus p ON p.id_roles_submenus = rs.id_roles_submenus
                WHERE s.id_menu = :id_menu
                ORDER BY s.nombre_submenu
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

    /**
     * Obtener menús y submenús asociados a un rol (para sidebar)
     */
    public function obtenerMenusPorRol($id_rol) {
        $query = "
            SELECT m.id_menu, m.nombre_menu, s.id_submenu, s.nombre_submenu, s.url_submenu
            FROM roles_submenus rs
            INNER JOIN submenus s ON rs.id_submenu = s.id_submenu
            INNER JOIN menus m ON s.id_menu = m.id_menu
            WHERE rs.id_rol = :id_rol
            ORDER BY m.nombre_menu, s.nombre_submenu
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===== MÉTODOS DE UTILIDAD =====

    /**
     * Obtener nombre de un rol por ID
     */
    public function obtenerNombreRol($id_rol) {
        $query = "SELECT nombre_rol FROM roles WHERE id_rol = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_rol]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['nombre_rol'] : 'Sin Rol';
    }

    /**
     * Verificar si un rol puede ser eliminado (no tiene usuarios asignados)
     */
    public function puedeEliminarRol($id_rol) {
        $query = "SELECT COUNT(*) FROM usuarios WHERE id_rol = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_rol]);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Obtener todos los roles con información adicional
     */
    public function obtenerRolesConInfo() {
        $query = "SELECT r.id_rol, r.nombre_rol, r.fecha_creacion,
                         COUNT(DISTINCT u.id_usuario) as usuarios_asignados,
                         COUNT(DISTINCT rs.id_submenu) as submenus_asignados
                  FROM roles r
                  LEFT JOIN usuarios u ON r.id_rol = u.id_rol
                  LEFT JOIN roles_submenus rs ON r.id_rol = rs.id_rol
                  GROUP BY r.id_rol, r.nombre_rol, r.fecha_creacion
                  ORDER BY r.nombre_rol";
        
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    
}



// ===== ALIAS PARA COMPATIBILIDAD =====
// Mantener por si algún código anterior usa esta clase
class Rol extends Roles {
    // Clase alias
}
?>