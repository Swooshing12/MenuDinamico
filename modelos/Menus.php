<?php
require_once __DIR__ . "/../config/database.php";

class Menu {  // ⭐ Cambié el nombre de la clase para que coincida con el controlador
    private $conn;
    private $db;

    public $id_menu;
    public $nombre_menu;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->conn = $this->db; // Mantener compatibilidad
    }

    // ===== MÉTODOS BÁSICOS CRUD =====

    /**
     * Crear un nuevo menú
     */
    public function crearMenu($nombre_menu) {
        if (empty($nombre_menu)) {
            throw new Exception("El nombre del menú no puede estar vacío.");
        }

        // Verificar que no exista ya un menú con ese nombre
        if ($this->obtenerPorNombre($nombre_menu)) {
            throw new Exception("Ya existe un menú con el nombre: $nombre_menu");
        }

        $query = "INSERT INTO menus (nombre_menu) VALUES (:nombre_menu)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre_menu', $nombre_menu, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creando menú: " . $e->getMessage());
            throw new Exception("Error al crear el menú: " . $e->getMessage());
        }
    }

    /**
     * Obtener todos los menús
     */
    public function obtenerTodos() {
        $query = "SELECT id_menu, nombre_menu FROM menus ORDER BY nombre_menu ASC";
        
        try {
            $stmt = $this->db->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Error obteniendo todos los menús: " . $e->getMessage());
            throw new Exception("Error al obtener los menús");
        }
    }

    /**
     * Obtener un menú por su ID
     */
    public function obtenerPorId($id_menu) {
        $query = "SELECT id_menu, nombre_menu FROM menus WHERE id_menu = :id_menu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error obteniendo menú por ID: " . $e->getMessage());
            throw new Exception("Error al obtener el menú");
        }
    }

    /**
     * Obtener un menú por su nombre
     */
    public function obtenerPorNombre($nombre_menu) {
        $query = "SELECT id_menu, nombre_menu FROM menus WHERE LOWER(nombre_menu) = LOWER(:nombre_menu)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre_menu', $nombre_menu, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error obteniendo menú por nombre: " . $e->getMessage());
            throw new Exception("Error al verificar el nombre del menú");
        }
    }

    /**
     * Editar un menú existente
     */
    public function editarMenu($id_menu, $nombre_menu) {
        if (empty($nombre_menu)) {
            throw new Exception("El nombre del menú no puede estar vacío.");
        }

        // Verificar que el menú existe
        if (!$this->obtenerPorId($id_menu)) {
            throw new Exception("El menú con ID $id_menu no existe.");
        }

        // Verificar que no exista otro menú con el mismo nombre
        $menuExistente = $this->obtenerPorNombre($nombre_menu);
        if ($menuExistente && $menuExistente['id_menu'] != $id_menu) {
            throw new Exception("Ya existe otro menú con el nombre: $nombre_menu");
        }

        $query = "UPDATE menus SET nombre_menu = :nombre_menu WHERE id_menu = :id_menu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            $stmt->bindParam(':nombre_menu', $nombre_menu, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error editando menú: " . $e->getMessage());
            throw new Exception("Error al actualizar el menú: " . $e->getMessage());
        }
    }

    /**
     * Eliminar un menú
     */
    public function eliminarMenu($id_menu) {
        // Verificar que el menú existe
        if (!$this->obtenerPorId($id_menu)) {
            throw new Exception("El menú con ID $id_menu no existe.");
        }

        // Verificar si tiene submenús asociados
        if ($this->tieneSubmenus($id_menu)) {
            throw new Exception("No se puede eliminar el menú porque tiene submenús asociados.");
        }

        $query = "DELETE FROM menus WHERE id_menu = :id_menu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando menú: " . $e->getMessage());
            throw new Exception("Error al eliminar el menú: " . $e->getMessage());
        }
    }

    // ===== MÉTODOS PARA PAGINACIÓN Y BÚSQUEDA =====

    /**
     * Contar total de menús (con búsqueda opcional)
     */
    public function contarMenus($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM menus WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND nombre_menu LIKE :busqueda";
            $params['busqueda'] = "%{$busqueda}%";
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando menús: " . $e->getMessage());
            throw new Exception("Error al contar los menús");
        }
    }

    /**
     * Obtener menús paginados (con búsqueda opcional)
     */
    public function obtenerMenusPaginados($offset = 0, $limit = 10, $busqueda = '') {
        // Validar y limpiar parámetros
        $offset = max(0, (int)$offset);
        $limit = max(1, min(100, (int)$limit)); // Límite máximo de 100
        $busqueda = trim($busqueda);
        
        $sql = "SELECT id_menu, nombre_menu FROM menus WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND nombre_menu LIKE :busqueda";
            $params['busqueda'] = "%{$busqueda}%";
        }
        
        $sql .= " ORDER BY nombre_menu ASC LIMIT {$offset}, {$limit}";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            error_log("DEBUG SQL: $sql");
            error_log("DEBUG Params: " . json_encode($params));
            error_log("DEBUG Offset: $offset, Limit: $limit");
            
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG Menús encontrados: " . count($resultados));
            return $resultados;
            
        } catch (PDOException $e) {
            error_log("ERROR en obtenerMenusPaginados: " . $e->getMessage());
            error_log("SQL que falló: $sql");
            throw new Exception("Error al obtener los menús paginados: " . $e->getMessage());
        }
    }

    // ===== MÉTODOS DE VALIDACIÓN Y VERIFICACIÓN =====

    /**
     * Verificar si un menú tiene submenús asociados
     */
    public function tieneSubmenus($id_menu) {
        $query = "SELECT COUNT(*) as total FROM submenus WHERE id_menu = :id_menu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando submenús: " . $e->getMessage());
            // En caso de error, asumir que tiene submenús por seguridad
            return true;
        }
    }

    /**
     * Verificar si un nombre de menú está disponible
     */
    public function nombreDisponible($nombre_menu, $id_menu_excluir = null) {
        $query = "SELECT COUNT(*) as total FROM menus WHERE LOWER(nombre_menu) = LOWER(:nombre_menu)";
        $params = [':nombre_menu' => $nombre_menu];
        
        if ($id_menu_excluir) {
            $query .= " AND id_menu != :id_menu_excluir";
            $params[':id_menu_excluir'] = $id_menu_excluir;
        }
        
        try {
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0;
        } catch (PDOException $e) {
            error_log("Error verificando disponibilidad de nombre: " . $e->getMessage());
            throw new Exception("Error al verificar el nombre del menú");
        }
    }

    // ===== MÉTODOS DE BÚSQUEDA AVANZADA =====

    /**
     * Buscar menús por término
     */
    public function buscarMenus($termino, $limit = 10) {
        $query = "SELECT id_menu, nombre_menu 
                 FROM menus 
                 WHERE nombre_menu LIKE :termino 
                 ORDER BY nombre_menu ASC 
                 LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':termino', "%{$termino}%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando menús: " . $e->getMessage());
            throw new Exception("Error en la búsqueda de menús");
        }
    }

    /**
     * Obtener estadísticas de menús
     */
  // En Menu.php, corregir este método:

/**
 * Obtener estadísticas REALES de menús
 */
public function obtenerEstadisticas() {
    $query = "SELECT 
                COUNT(*) as total_menus,
                COUNT(*) as menus_activos,
                (SELECT COUNT(DISTINCT id_submenu) FROM submenus WHERE id_menu IN (SELECT id_menu FROM menus)) as total_submenus
              FROM menus";
    
    try {
        $stmt = $this->db->query($query);
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log para debugging
        error_log("DEBUG Estadísticas: " . json_encode($estadisticas));
        
        return [
            'total_menus' => (int)$estadisticas['total_menus'],
            'menus_activos' => (int)$estadisticas['menus_activos'], // Por ahora igual al total
            'total_submenus' => (int)$estadisticas['total_submenus']
        ];
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [
            'total_menus' => 0,
            'menus_activos' => 0,
            'total_submenus' => 0
        ];
    }
}

    // ===== MÉTODOS DE UTILIDAD =====

    /**
     * Validar formato de nombre de menú
     */
    public function validarNombreMenu($nombre_menu) {
        $errores = [];
        
        if (empty(trim($nombre_menu))) {
            $errores[] = "El nombre del menú no puede estar vacío";
        }
        
        if (strlen(trim($nombre_menu)) < 3) {
            $errores[] = "El nombre del menú debe tener al menos 3 caracteres";
        }
        
        if (strlen(trim($nombre_menu)) > 50) {
            $errores[] = "El nombre del menú no puede tener más de 50 caracteres";
        }
        
        // Verificar caracteres válidos (letras, números, espacios, algunos especiales)
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-_()]+$/', trim($nombre_menu))) {
            $errores[] = "El nombre del menú contiene caracteres no válidos";
        }
        
        return $errores;
    }

    /**
     * Sanitizar nombre de menú
     */
    public function sanitizarNombre($nombre_menu) {
        // Eliminar espacios al inicio y final
        $nombre_menu = trim($nombre_menu);
        
        // Eliminar espacios múltiples
        $nombre_menu = preg_replace('/\s+/', ' ', $nombre_menu);
        
        // Capitalizar primera letra de cada palabra
        $nombre_menu = ucwords(strtolower($nombre_menu));
        
        return $nombre_menu;
    }
}

// ===== ALIAS PARA COMPATIBILIDAD =====
class Menus extends Menu {
    // Mantener compatibilidad con código existente
}
?>