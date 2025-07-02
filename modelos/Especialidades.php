<?php
require_once __DIR__ . "/../config/database.php";

class Especialidades {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== MÉTODOS CRUD BÁSICOS =====
    
    /**
     * Crear una nueva especialidad
     */
    public function crear($datos) {
        try {
            $query = "INSERT INTO especialidades (nombre_especialidad, descripcion) 
                      VALUES (:nombre_especialidad, :descripcion)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':nombre_especialidad' => $datos['nombre_especialidad'],
                ':descripcion' => $datos['descripcion'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando especialidad: " . $e->getMessage());
            throw new Exception("Error al crear la especialidad");
        }
    }
    
    /**
     * Obtener especialidad por ID
     */
    public function obtenerPorId($id_especialidad) {
        try {
            $query = "SELECT e.*,
                             (SELECT COUNT(*) FROM doctores d WHERE d.id_especialidad = e.id_especialidad) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      WHERE e.id_especialidad = :id_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidad: " . $e->getMessage());
            throw new Exception("Error al obtener la especialidad");
        }
    }
    
    /**
     * Actualizar una especialidad
     */
    public function actualizar($id_especialidad, $datos) {
        try {
            $query = "UPDATE especialidades SET 
                        nombre_especialidad = :nombre_especialidad,
                        descripcion = :descripcion
                      WHERE id_especialidad = :id_especialidad";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_especialidad' => $id_especialidad,
                ':nombre_especialidad' => $datos['nombre_especialidad'],
                ':descripcion' => $datos['descripcion']
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando especialidad: " . $e->getMessage());
            throw new Exception("Error al actualizar la especialidad");
        }
    }
    
    /**
     * Eliminar una especialidad
     */
    public function eliminar($id_especialidad) {
        try {
            // Verificar si tiene doctores asignados
            if (!$this->puedeEliminar($id_especialidad)) {
                throw new Exception("No se puede eliminar la especialidad porque tiene doctores asignados");
            }
            
            $query = "DELETE FROM especialidades WHERE id_especialidad = :id_especialidad";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':id_especialidad' => $id_especialidad]);
        } catch (PDOException $e) {
            error_log("Error eliminando especialidad: " . $e->getMessage());
            throw new Exception("Error al eliminar la especialidad");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA ESPECÍFICOS =====
    
    /**
     * Obtener todas las especialidades
     */
    public function obtenerTodas() {
        try {
            $query = "SELECT e.*,
                             (SELECT COUNT(*) FROM doctores d WHERE d.id_especialidad = e.id_especialidad) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      ORDER BY e.nombre_especialidad";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades: " . $e->getMessage());
            throw new Exception("Error al obtener las especialidades");
        }
    }
    
    /**
     * Obtener especialidades con paginación
     */
    public function obtenerPaginadas($inicio, $limite, $busqueda = '') {
        try {
            $where_clause = '';
            $params = [];
            
            if (!empty($busqueda)) {
                $where_clause = "WHERE e.nombre_especialidad LIKE :busqueda OR e.descripcion LIKE :busqueda";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $query = "SELECT e.*,
                             (SELECT COUNT(*) FROM doctores d WHERE d.id_especialidad = e.id_especialidad) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      $where_clause
                      ORDER BY e.nombre_especialidad
                      LIMIT :inicio, :limite";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades paginadas: " . $e->getMessage());
            throw new Exception("Error al obtener las especialidades");
        }
    }
    
    /**
     * Contar total de especialidades
     */
    public function contarTotal($busqueda = '') {
        try {
            $where_clause = '';
            $params = [];
            
            if (!empty($busqueda)) {
                $where_clause = "WHERE nombre_especialidad LIKE :busqueda OR descripcion LIKE :busqueda";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $query = "SELECT COUNT(*) as total FROM especialidades $where_clause";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando especialidades: " . $e->getMessage());
            throw new Exception("Error al contar las especialidades");
        }
    }
    
    // ===== MÉTODOS ESPECÍFICOS PARA RECEPCIONISTA =====
    
    /**
     * Obtener especialidades disponibles por sucursal
     */
    public function obtenerPorSucursal($id_sucursal) {
        try {
            $query = "SELECT e.*, es.id_especialidad_sucursal,
                             (SELECT COUNT(*) FROM doctores d 
                              INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor
                              WHERE d.id_especialidad = e.id_especialidad 
                                AND ds.id_sucursal = :id_sucursal) as doctores_disponibles
                      FROM especialidades e
                      INNER JOIN especialidades_sucursales es ON e.id_especialidad = es.id_especialidad
                      WHERE es.id_sucursal = :id_sucursal
                      ORDER BY e.nombre_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades por sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades de la sucursal");
        }
    }
    
    /**
     * Obtener especialidades con doctores disponibles por sucursal
     */
    public function obtenerConDoctoresPorSucursal($id_sucursal) {
        try {
            $query = "SELECT DISTINCT e.*, es.id_especialidad_sucursal,
                             COUNT(d.id_doctor) as total_doctores
                      FROM especialidades e
                      INNER JOIN especialidades_sucursales es ON e.id_especialidad = es.id_especialidad
                      INNER JOIN doctores d ON e.id_especialidad = d.id_especialidad
                      INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor
                      WHERE es.id_sucursal = :id_sucursal 
                        AND ds.id_sucursal = :id_sucursal
                      GROUP BY e.id_especialidad
                      HAVING total_doctores > 0
                      ORDER BY e.nombre_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades con doctores: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades con doctores");
        }
    }
    
    /**
     * Buscar especialidades por texto (para autocomplete)
     */
    public function buscarPorTexto($texto, $limite = 10) {
        try {
            $query = "SELECT id_especialidad, nombre_especialidad, descripcion
                      FROM especialidades
                      WHERE nombre_especialidad LIKE :texto 
                         OR descripcion LIKE :texto
                      ORDER BY nombre_especialidad
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':texto', '%' . $texto . '%');
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando especialidades: " . $e->getMessage());
            throw new Exception("Error al buscar especialidades");
        }
    }
    
    /**
     * Obtener especialidades más demandadas (con más citas)
     */
    public function obtenerMasDemandadas($limite = 5) {
        try {
            $query = "SELECT e.*, COUNT(c.id_cita) as total_citas
                      FROM especialidades e
                      INNER JOIN doctores d ON e.id_especialidad = d.id_especialidad
                      INNER JOIN citas c ON d.id_doctor = c.id_doctor
                      WHERE c.fecha_hora >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                      GROUP BY e.id_especialidad
                      ORDER BY total_citas DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades más demandadas: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades más demandadas");
        }
    }
    
    // ===== MÉTODOS PARA GESTIÓN DE SUCURSALES =====
    
    /**
     * Asignar especialidad a sucursal
     */
    public function asignarASucursal($id_especialidad, $id_sucursal) {
        try {
            // Verificar si ya existe la relación
            if ($this->existeEnSucursal($id_especialidad, $id_sucursal)) {
                throw new Exception("La especialidad ya está asignada a esta sucursal");
            }
            
            $query = "INSERT INTO especialidades_sucursales (id_especialidad, id_sucursal) 
                      VALUES (:id_especialidad, :id_sucursal)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_especialidad' => $id_especialidad,
                ':id_sucursal' => $id_sucursal
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error asignando especialidad a sucursal: " . $e->getMessage());
            throw new Exception("Error al asignar especialidad a sucursal");
        }
    }
    
    /**
     * Desasignar especialidad de sucursal
     */
    public function desasignarDeSucursal($id_especialidad, $id_sucursal) {
        try {
            $query = "DELETE FROM especialidades_sucursales 
                      WHERE id_especialidad = :id_especialidad AND id_sucursal = :id_sucursal";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_especialidad' => $id_especialidad,
                ':id_sucursal' => $id_sucursal
            ]);
        } catch (PDOException $e) {
            error_log("Error desasignando especialidad de sucursal: " . $e->getMessage());
            throw new Exception("Error al desasignar especialidad de sucursal");
        }
    }
    
    /**
     * Obtener sucursales donde está disponible una especialidad
     */
    public function obtenerSucursales($id_especialidad) {
        try {
            $query = "SELECT s.*, es.id_especialidad_sucursal,
                             (SELECT COUNT(*) FROM doctores d 
                              INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor
                              WHERE d.id_especialidad = :id_especialidad 
                                AND ds.id_sucursal = s.id_sucursal) as doctores_disponibles
                      FROM sucursales s
                      INNER JOIN especialidades_sucursales es ON s.id_sucursal = es.id_sucursal
                      WHERE es.id_especialidad = :id_especialidad
                        AND s.estado = 1
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales de especialidad: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales de la especialidad");
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Verificar si existe una especialidad por nombre
     */
    public function existePorNombre($nombre_especialidad, $id_excluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM especialidades WHERE nombre_especialidad = :nombre";
            $params = [':nombre' => $nombre_especialidad];
            
            if ($id_excluir) {
                $query .= " AND id_especialidad != :id_excluir";
                $params[':id_excluir'] = $id_excluir;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando existencia de especialidad: " . $e->getMessage());
            throw new Exception("Error al verificar especialidad");
        }
    }
    
    /**
     * Verificar si una especialidad existe en una sucursal
     */
    public function existeEnSucursal($id_especialidad, $id_sucursal) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM especialidades_sucursales 
                      WHERE id_especialidad = :id_especialidad AND id_sucursal = :id_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_especialidad' => $id_especialidad,
                ':id_sucursal' => $id_sucursal
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando especialidad en sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar especialidad en sucursal");
        }
    }
    
    /**
     * Verificar si una especialidad puede ser eliminada
     */
    public function puedeEliminar($id_especialidad) {
        try {
            // Verificar si tiene doctores asignados
            $query = "SELECT COUNT(*) as total FROM doctores WHERE id_especialidad = :id_especialidad";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0;
        } catch (PDOException $e) {
            error_log("Error verificando eliminación de especialidad: " . $e->getMessage());
            throw new Exception("Error al verificar eliminación");
        }
    }
    
    // ===== MÉTODOS DE ESTADÍSTICAS =====
    
    /**
     * Obtener estadísticas de especialidades
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_especialidades,
                        (SELECT COUNT(*) FROM doctores) as total_doctores,
                        (SELECT COUNT(DISTINCT id_sucursal) FROM especialidades_sucursales) as sucursales_con_especialidades,
                        AVG(doctores_por_especialidad.total) as promedio_doctores_por_especialidad
                      FROM especialidades e
                      LEFT JOIN (
                          SELECT id_especialidad, COUNT(*) as total 
                          FROM doctores 
                          GROUP BY id_especialidad
                      ) doctores_por_especialidad ON e.id_especialidad = doctores_por_especialidad.id_especialidad";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de especialidades: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Obtener distribución de especialidades por sucursal
     */
    public function obtenerDistribucionPorSucursal() {
        try {
            $query = "SELECT s.nombre_sucursal, COUNT(es.id_especialidad) as total_especialidades
                      FROM sucursales s
                      LEFT JOIN especialidades_sucursales es ON s.id_sucursal = es.id_sucursal
                      WHERE s.estado = 1
                      GROUP BY s.id_sucursal, s.nombre_sucursal
                      ORDER BY total_especialidades DESC";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo distribución por sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener distribución por sucursal");
        }
    }
    
}
?>