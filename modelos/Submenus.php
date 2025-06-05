<?php
require_once __DIR__ . "/../config/database.php";

class Submenu {  // ⭐ Cambié el nombre para que coincida con el controlador
    private $conn;
    private $db;

    public $id_submenu;
    public $nombre_submenu;
    public $url_submenu;
    public $id_menu;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->conn = $this->db; // Mantener compatibilidad
    }

    // ===== MÉTODOS BÁSICOS CRUD =====

    /**
     * Crear un nuevo submenú
     */
    public function crearSubmenu($nombre_submenu, $url_submenu, $id_menu) {
        if (empty($nombre_submenu) || empty($url_submenu) || empty($id_menu)) {
            throw new Exception("Todos los campos son requeridos para crear un submenú.");
        }

        // Verificar que no exista ya un submenú con ese nombre
        if ($this->obtenerPorNombre($nombre_submenu)) {
            throw new Exception("Ya existe un submenú con el nombre: $nombre_submenu");
        }

        // Verificar que no exista ya un submenú con esa URL
        if ($this->obtenerPorUrl($url_submenu)) {
            throw new Exception("Ya existe un submenú con la URL: $url_submenu");
        }

        $query = "INSERT INTO submenus (nombre_submenu, url_submenu, id_menu) VALUES (:nombre_submenu, :url_submenu, :id_menu)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre_submenu', $nombre_submenu, PDO::PARAM_STR);
            $stmt->bindParam(':url_submenu', $url_submenu, PDO::PARAM_STR);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creando submenú: " . $e->getMessage());
            throw new Exception("Error al crear el submenú: " . $e->getMessage());
        }
    }

    /**
     * Obtener todos los submenús con información del menú padre
     */
    public function obtenerTodos() {
        $query = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu, m.nombre_menu
                  FROM submenus s
                  LEFT JOIN menus m ON s.id_menu = m.id_menu
                  ORDER BY m.nombre_menu ASC, s.nombre_submenu ASC";
        
        try {
            $stmt = $this->db->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Error obteniendo todos los submenús: " . $e->getMessage());
            throw new Exception("Error al obtener los submenús");
        }
    }

    /**
     * Obtener un submenú por su ID
     */
    public function obtenerPorId($id_submenu) {
        $query = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu, m.nombre_menu
                  FROM submenus s
                  LEFT JOIN menus m ON s.id_menu = m.id_menu
                  WHERE s.id_submenu = :id_submenu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error obteniendo submenú por ID: " . $e->getMessage());
            throw new Exception("Error al obtener el submenú");
        }
    }

    /**
     * Obtener un submenú por su nombre
     */
    public function obtenerPorNombre($nombre_submenu) {
        $query = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu
                  FROM submenus s
                  WHERE LOWER(s.nombre_submenu) = LOWER(:nombre_submenu)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre_submenu', $nombre_submenu, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error obteniendo submenú por nombre: " . $e->getMessage());
            throw new Exception("Error al verificar el nombre del submenú");
        }
    }

    /**
     * Obtener un submenú por su URL
     */
    public function obtenerPorUrl($url_submenu) {
        $query = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu
                  FROM submenus s
                  WHERE LOWER(s.url_submenu) = LOWER(:url_submenu)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':url_submenu', $url_submenu, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error obteniendo submenú por URL: " . $e->getMessage());
            throw new Exception("Error al verificar la URL del submenú");
        }
    }

    /**
     * Obtener submenús por menú padre
     */
    public function obtenerPorMenu($id_menu) {
        $query = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu
                  FROM submenus s
                  WHERE s.id_menu = :id_menu
                  ORDER BY s.nombre_submenu ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo submenús por menú: " . $e->getMessage());
            throw new Exception("Error al obtener los submenús del menú");
        }
    }

    /**
     * Editar un submenú existente
     */
    public function editarSubmenu($id_submenu, $nombre_submenu, $url_submenu, $id_menu) {
        if (empty($nombre_submenu) || empty($url_submenu) || empty($id_menu)) {
            throw new Exception("Todos los campos son requeridos para editar un submenú.");
        }

        // Verificar que el submenú existe
        if (!$this->obtenerPorId($id_submenu)) {
            throw new Exception("El submenú con ID $id_submenu no existe.");
        }

        // Verificar que no exista otro submenú con el mismo nombre
        $submenuExistente = $this->obtenerPorNombre($nombre_submenu);
        if ($submenuExistente && $submenuExistente['id_submenu'] != $id_submenu) {
            throw new Exception("Ya existe otro submenú con el nombre: $nombre_submenu");
        }

        // Verificar que no exista otro submenú con la misma URL
        $urlExistente = $this->obtenerPorUrl($url_submenu);
        if ($urlExistente && $urlExistente['id_submenu'] != $id_submenu) {
            throw new Exception("Ya existe otro submenú con la URL: $url_submenu");
        }

        $query = "UPDATE submenus SET nombre_submenu = :nombre_submenu, url_submenu = :url_submenu, id_menu = :id_menu 
                  WHERE id_submenu = :id_submenu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
            $stmt->bindParam(':nombre_submenu', $nombre_submenu, PDO::PARAM_STR);
            $stmt->bindParam(':url_submenu', $url_submenu, PDO::PARAM_STR);
            $stmt->bindParam(':id_menu', $id_menu, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error editando submenú: " . $e->getMessage());
            throw new Exception("Error al actualizar el submenú: " . $e->getMessage());
        }
    }

    /**
     * Eliminar un submenú
     */
    public function eliminarSubmenu($id_submenu) {
        // Verificar que el submenú existe
        if (!$this->obtenerPorId($id_submenu)) {
            throw new Exception("El submenú con ID $id_submenu no existe.");
        }

        // Verificar si tiene permisos asociados
        if ($this->tienePermisos($id_submenu)) {
            throw new Exception("No se puede eliminar el submenú porque tiene permisos asociados.");
        }

        $query = "DELETE FROM submenus WHERE id_submenu = :id_submenu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando submenú: " . $e->getMessage());
            throw new Exception("Error al eliminar el submenú: " . $e->getMessage());
        }
    }

    // ===== MÉTODOS PARA PAGINACIÓN Y BÚSQUEDA =====

    /**
     * Contar total de submenús (con búsqueda opcional)
     */
    public function contarSubmenus($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM submenus s 
                LEFT JOIN menus m ON s.id_menu = m.id_menu WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND (s.nombre_submenu LIKE :busqueda OR s.url_submenu LIKE :busqueda OR m.nombre_menu LIKE :busqueda)";
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
            error_log("Error contando submenús: " . $e->getMessage());
            throw new Exception("Error al contar los submenús");
        }
    }

    /**
     * Obtener submenús paginados (con búsqueda opcional)
     */
    public function obtenerSubmenusPaginados($offset = 0, $limit = 10, $busqueda = '') {
        // Validar y limpiar parámetros
        $offset = max(0, (int)$offset);
        $limit = max(1, min(100, (int)$limit)); // Límite máximo de 100
        $busqueda = trim($busqueda);
        
        $sql = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu, m.nombre_menu
                FROM submenus s
                LEFT JOIN menus m ON s.id_menu = m.id_menu WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND (s.nombre_submenu LIKE :busqueda OR s.url_submenu LIKE :busqueda OR m.nombre_menu LIKE :busqueda)";
            $params['busqueda'] = "%{$busqueda}%";
        }
        
        $sql .= " ORDER BY m.nombre_menu ASC, s.nombre_submenu ASC LIMIT {$offset}, {$limit}";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            error_log("DEBUG SQL Submenús: $sql");
            error_log("DEBUG Params: " . json_encode($params));
            error_log("DEBUG Offset: $offset, Limit: $limit");
            
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG Submenús encontrados: " . count($resultados));
            return $resultados;
            
        } catch (PDOException $e) {
            error_log("ERROR en obtenerSubmenusPaginados: " . $e->getMessage());
            error_log("SQL que falló: $sql");
            throw new Exception("Error al obtener los submenús paginados: " . $e->getMessage());
        }
    }

    // ===== MÉTODOS DE ESTADÍSTICAS =====

    /**
     * Obtener estadísticas REALES de submenús
     */
    public function obtenerEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total_submenus,
                    COUNT(*) as submenus_activos,
                    COUNT(DISTINCT s.id_menu) as menus_con_submenus
                  FROM submenus s
                  LEFT JOIN menus m ON s.id_menu = m.id_menu";
        
        try {
            $stmt = $this->db->query($query);
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log para debugging
            error_log("DEBUG Estadísticas Submenús: " . json_encode($estadisticas));
            
            return [
                'total_submenus' => (int)$estadisticas['total_submenus'],
                'submenus_activos' => (int)$estadisticas['submenus_activos'], // Por ahora igual al total
                'menus_con_submenus' => (int)$estadisticas['menus_con_submenus']
            ];
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de submenús: " . $e->getMessage());
            return [
                'total_submenus' => 0,
                'submenus_activos' => 0,
                'menus_con_submenus' => 0
            ];
        }
    }

    // ===== MÉTODOS DE VALIDACIÓN Y VERIFICACIÓN =====

    /**
     * Verificar si un submenú tiene permisos asociados
     */
    public function tienePermisos($id_submenu) {
        $query = "SELECT COUNT(*) as total FROM permisos WHERE id_submenu = :id_submenu";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_submenu', $id_submenu, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando permisos: " . $e->getMessage());
            // En caso de error, asumir que tiene permisos por seguridad
            return true;
        }
    }

    /**
     * Verificar si un nombre de submenú está disponible
     */
    public function nombreDisponible($nombre_submenu, $id_submenu_excluir = null) {
        $query = "SELECT COUNT(*) as total FROM submenus WHERE LOWER(nombre_submenu) = LOWER(:nombre_submenu)";
        $params = [':nombre_submenu' => $nombre_submenu];
        
        if ($id_submenu_excluir) {
            $query .= " AND id_submenu != :id_submenu_excluir";
            $params[':id_submenu_excluir'] = $id_submenu_excluir;
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
            throw new Exception("Error al verificar el nombre del submenú");
        }
    }

    /**
     * Verificar si una URL de submenú está disponible
     */
    public function urlDisponible($url_submenu, $id_submenu_excluir = null) {
        $query = "SELECT COUNT(*) as total FROM submenus WHERE LOWER(url_submenu) = LOWER(:url_submenu)";
        $params = [':url_submenu' => $url_submenu];
        
        if ($id_submenu_excluir) {
            $query .= " AND id_submenu != :id_submenu_excluir";
            $params[':id_submenu_excluir'] = $id_submenu_excluir;
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
            error_log("Error verificando disponibilidad de URL: " . $e->getMessage());
            throw new Exception("Error al verificar la URL del submenú");
        }
    }

    // ===== MÉTODOS DE BÚSQUEDA AVANZADA =====

    /**
     * Buscar submenús por término
     */
    public function buscarSubmenus($termino, $limit = 10) {
        $query = "SELECT s.id_submenu, s.nombre_submenu, s.url_submenu, s.id_menu, m.nombre_menu
                 FROM submenus s
                 LEFT JOIN menus m ON s.id_menu = m.id_menu
                 WHERE s.nombre_submenu LIKE :termino OR s.url_submenu LIKE :termino OR m.nombre_menu LIKE :termino
                 ORDER BY m.nombre_menu ASC, s.nombre_submenu ASC 
                 LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':termino', "%{$termino}%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando submenús: " . $e->getMessage());
            throw new Exception("Error en la búsqueda de submenús");
        }
    }

    /**
     * Obtener submenús agrupados por menú
     */
    public function obtenerAgrupadosPorMenu() {
        $query = "SELECT m.id_menu, m.nombre_menu,
                         s.id_submenu, s.nombre_submenu, s.url_submenu
                  FROM menus m
                  LEFT JOIN submenus s ON m.id_menu = s.id_menu
                  ORDER BY m.nombre_menu ASC, s.nombre_submenu ASC";
        
        try {
            $stmt = $this->db->query($query);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por menú
            $agrupados = [];
            foreach ($resultados as $fila) {
                $id_menu = $fila['id_menu'];
                
                if (!isset($agrupados[$id_menu])) {
                    $agrupados[$id_menu] = [
                        'id_menu' => $id_menu,
                        'nombre_menu' => $fila['nombre_menu'],
                        'submenus' => []
                    ];
                }
                
                if ($fila['id_submenu']) {
                    $agrupados[$id_menu]['submenus'][] = [
                        'id_submenu' => $fila['id_submenu'],
                        'nombre_submenu' => $fila['nombre_submenu'],
                        'url_submenu' => $fila['url_submenu']
                    ];
                }
            }
            
            return array_values($agrupados);
        } catch (PDOException $e) {
            error_log("Error obteniendo submenús agrupados: " . $e->getMessage());
            throw new Exception("Error al obtener los submenús agrupados");
        }
    }

    // ===== MÉTODOS DE UTILIDAD =====

    /**
     * Validar formato de datos del submenú
     */
    public function validarDatosSubmenu($nombre_submenu, $url_submenu, $id_menu) {
        $errores = [];
        
        if (empty(trim($nombre_submenu))) {
            $errores[] = "El nombre del submenú no puede estar vacío";
        }
        
        if (strlen(trim($nombre_submenu)) < 3) {
            $errores[] = "El nombre del submenú debe tener al menos 3 caracteres";
        }
        
        if (strlen(trim($nombre_submenu)) > 50) {
            $errores[] = "El nombre del submenú no puede tener más de 50 caracteres";
        }
        
        if (empty(trim($url_submenu))) {
            $errores[] = "La URL del submenú no puede estar vacía";
        }
        
        if (strlen(trim($url_submenu)) < 5) {
            $errores[] = "La URL del submenú debe tener al menos 5 caracteres";
        }
        
        if (!is_numeric($id_menu) || $id_menu <= 0) {
            $errores[] = "Debe seleccionar un menú padre válido";
        }
        
        // Verificar caracteres válidos para nombres
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-_()]+$/', trim($nombre_submenu))) {
            $errores[] = "El nombre del submenú contiene caracteres no válidos";
        }
        
        // Verificar caracteres válidos para URLs
        if (!preg_match('/^[a-zA-Z0-9\-_.\/@]+$/', trim($url_submenu))) {
            $errores[] = "La URL del submenú contiene caracteres no válidos";
        }
        
        return $errores;
    }

    /**
     * Sanitizar datos del submenú
     */
    public function sanitizarDatos($nombre_submenu, $url_submenu) {
        // Limpiar nombre
        $nombre_submenu = trim($nombre_submenu);
        $nombre_submenu = preg_replace('/\s+/', ' ', $nombre_submenu);
        $nombre_submenu = ucwords(strtolower($nombre_submenu));
        
        // Limpiar URL
        $url_submenu = trim($url_submenu);
        $url_submenu = strtolower($url_submenu);
        $url_submenu = preg_replace('/\/{2,}/', '/', $url_submenu);
        
        return [
            'nombre_submenu' => $nombre_submenu,
            'url_submenu' => $url_submenu
        ];
    }
}

// ===== ALIAS PARA COMPATIBILIDAD =====
class Submenus extends Submenu {
    // Mantener compatibilidad con código existente
}
?>