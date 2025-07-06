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
                ':descripcion' => $datos['descripcion']
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando especialidad: " . $e->getMessage());
            throw new Exception("Error al crear la especialidad");
        }
    }
    
    /**
     * Crear especialidad con sucursales asignadas
     */
    public function crearConSucursales($datos, $sucursales = []) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Crear la especialidad
            $query = "INSERT INTO especialidades (nombre_especialidad, descripcion) 
                      VALUES (:nombre_especialidad, :descripcion)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':nombre_especialidad' => $datos['nombre_especialidad'],
                ':descripcion' => $datos['descripcion']
            ]);
            
            $id_especialidad = $this->conn->lastInsertId();
            
            // 2. Asignar a sucursales
            if (!empty($sucursales)) {
                foreach ($sucursales as $id_sucursal) {
                    $this->asignarASucursal($id_especialidad, (int)$id_sucursal);
                }
            }
            
            $this->conn->commit();
            return $id_especialidad;
            
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Error creando especialidad con sucursales: " . $e->getMessage());
            throw new Exception("Error al crear la especialidad con sucursales");
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
                ':nombre_especialidad' => $datos['nombre_especialidad'],
                ':descripcion' => $datos['descripcion'],
                ':id_especialidad' => $id_especialidad
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando especialidad: " . $e->getMessage());
            throw new Exception("Error al actualizar la especialidad");
        }
    }
    
    /**
     * Actualizar especialidad con sucursales
     */
    public function actualizarConSucursales($id_especialidad, $datos, $sucursales = []) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Actualizar la especialidad
            $query = "UPDATE especialidades SET 
                      nombre_especialidad = :nombre_especialidad,
                      descripcion = :descripcion
                      WHERE id_especialidad = :id_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':nombre_especialidad' => $datos['nombre_especialidad'],
                ':descripcion' => $datos['descripcion'],
                ':id_especialidad' => $id_especialidad
            ]);
            
            // 2. Eliminar asignaciones actuales de sucursales
            $this->eliminarTodasSucursales($id_especialidad);
            
            // 3. Asignar nuevas sucursales
            if (!empty($sucursales)) {
                foreach ($sucursales as $id_sucursal) {
                    $this->asignarASucursal($id_especialidad, (int)$id_sucursal);
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Error actualizando especialidad con sucursales: " . $e->getMessage());
            throw new Exception("Error al actualizar la especialidad con sucursales");
        }
    }
    
    /**
     * Eliminar una especialidad
     */
    public function eliminar($id_especialidad) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Eliminar asignaciones de sucursales
            $this->eliminarTodasSucursales($id_especialidad);
            
            // 2. Eliminar la especialidad
            $query = "DELETE FROM especialidades WHERE id_especialidad = :id_especialidad";
            $stmt = $this->conn->prepare($query);
            $resultado = $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            $this->conn->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Error eliminando especialidad: " . $e->getMessage());
            throw new Exception("Error al eliminar la especialidad");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA =====
    
    /**
     * Obtener especialidad por ID
     */
    public function obtenerPorId($id_especialidad) {
        try {
            $query = "SELECT e.*,
                             (SELECT COUNT(*) FROM doctores d 
                              INNER JOIN usuarios u ON d.id_usuario = u.id_usuario 
                              WHERE d.id_especialidad = e.id_especialidad AND u.id_estado = 1) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es 
                              WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      WHERE e.id_especialidad = :id_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidad por ID: " . $e->getMessage());
            throw new Exception("Error al obtener la especialidad");
        }
    }
    
    /**
     * Obtener todas las especialidades
     */
    public function obtenerTodas() {
        try {
            $query = "SELECT e.*,
                             (SELECT COUNT(*) FROM doctores d 
                              INNER JOIN usuarios u ON d.id_usuario = u.id_usuario 
                              WHERE d.id_especialidad = e.id_especialidad AND u.id_estado = 1) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es 
                              WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      ORDER BY e.nombre_especialidad";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo todas las especialidades: " . $e->getMessage());
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
                             (SELECT COUNT(*) FROM doctores d 
                              INNER JOIN usuarios u ON d.id_usuario = u.id_usuario 
                              WHERE d.id_especialidad = e.id_especialidad AND u.id_estado = 1) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es 
                              WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      $where_clause
                      ORDER BY e.nombre_especialidad
                      LIMIT :inicio, :limite";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros de búsqueda
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            // Bind parámetros de paginación
            $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades paginadas: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades paginadas");
        }
    }
    
    /**
     * Contar total de registros (para paginación)
     */
    public function contarTotal($busqueda = '') {
        try {
            $where_clause = '';
            $params = [];
            
            if (!empty($busqueda)) {
                $where_clause = "WHERE e.nombre_especialidad LIKE :busqueda OR e.descripcion LIKE :busqueda";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $query = "SELECT COUNT(*) as total FROM especialidades e $where_clause";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando especialidades: " . $e->getMessage());
            throw new Exception("Error al contar especialidades");
        }
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_especialidades,
                        COUNT(CASE WHEN (
                            SELECT COUNT(*) FROM doctores d 
                            INNER JOIN usuarios u ON d.id_usuario = u.id_usuario 
                            WHERE d.id_especialidad = e.id_especialidad AND u.id_estado = 1
                        ) > 0 THEN 1 END) as con_doctores,
                        (SELECT COUNT(*) FROM doctores d 
                         INNER JOIN usuarios u ON d.id_usuario = u.id_usuario 
                         WHERE u.id_estado = 1) as total_doctores
                      FROM especialidades e";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Verificar si existe especialidad por nombre (con exclusión opcional)
     */
    public function existePorNombre($nombre, $id_excluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM especialidades WHERE nombre_especialidad = :nombre";
            $params = [':nombre' => $nombre];
            
            if ($id_excluir) {
                $query .= " AND id_especialidad != :id_excluir";
                $params[':id_excluir'] = $id_excluir;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando nombre de especialidad: " . $e->getMessage());
            throw new Exception("Error al verificar nombre de especialidad");
        }
    }
    
    /**
     * Contar doctores asignados a una especialidad
     */
    public function contarDoctores($id_especialidad) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      WHERE d.id_especialidad = :id_especialidad AND u.id_estado = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando doctores de especialidad: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verificar si una especialidad puede ser eliminada
     */
    public function puedeEliminar($id_especialidad) {
        try {
            // Verificar si tiene doctores asignados
            $query = "SELECT COUNT(*) as total 
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      WHERE d.id_especialidad = :id_especialidad AND u.id_estado = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0;
        } catch (PDOException $e) {
            error_log("Error verificando eliminación de especialidad: " . $e->getMessage());
            return false;
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
                return true; // Ya existe, no hacer nada
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
     * Eliminar todas las asignaciones de sucursales de una especialidad
     */
    public function eliminarTodasSucursales($id_especialidad) {
        try {
            $query = "DELETE FROM especialidades_sucursales WHERE id_especialidad = :id_especialidad";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':id_especialidad' => $id_especialidad]);
        } catch (PDOException $e) {
            error_log("Error eliminando sucursales de especialidad: " . $e->getMessage());
            throw new Exception("Error al eliminar sucursales de la especialidad");
        }
    }
    
    /**
     * Obtener sucursales donde está disponible una especialidad
     */
    public function obtenerSucursales($id_especialidad) {
        try {
            $query = "SELECT s.*, es.id_especialidad_sucursal
                      FROM sucursales s
                      INNER JOIN especialidades_sucursales es ON s.id_sucursal = es.id_sucursal
                      WHERE es.id_especialidad = :id_especialidad AND s.estado = 1
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_especialidad' => $id_especialidad]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales de especialidad: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales de la especialidad");
        }
    }
    
    /**
     * Verificar si especialidad existe en sucursal
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
            return false;
        }
    }
    
    /**
     * Obtener especialidades por sucursal
     */
    public function obtenerPorSucursal($id_sucursal) {
        try {
            $query = "SELECT e.*, es.id_especialidad_sucursal,
                             (SELECT COUNT(*) FROM doctores d 
                              INNER JOIN usuarios u ON d.id_usuario = u.id_usuario 
                              WHERE d.id_especialidad = e.id_especialidad AND u.id_estado = 1) as total_doctores
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
     * Obtener doctores de una especialidad en una sucursal específica
     */
    public function obtenerDoctoresPorSucursal($id_especialidad, $id_sucursal) {
        try {
            $query = "SELECT d.*, u.nombres, u.apellidos, u.correo, d.titulo_profesional
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor
                      WHERE d.id_especialidad = :id_especialidad 
                        AND ds.id_sucursal = :id_sucursal
                        AND u.id_estado = 1
                      ORDER BY u.nombres, u.apellidos";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_especialidad' => $id_especialidad,
                ':id_sucursal' => $id_sucursal
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctores de especialidad por sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener doctores de la especialidad");
        }
    }
    
    /**
     * Obtener especialidades más demandadas (con más doctores)
     */
    public function obtenerMasDemandadas($limite = 5) {
        try {
            $query = "SELECT e.*, 
                             COUNT(d.id_doctor) as total_doctores,
                             (SELECT COUNT(*) FROM especialidades_sucursales es 
                              WHERE es.id_especialidad = e.id_especialidad) as total_sucursales
                      FROM especialidades e
                      LEFT JOIN doctores d ON e.id_especialidad = d.id_especialidad
                      LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario AND u.id_estado = 1
                      GROUP BY e.id_especialidad
                      ORDER BY total_doctores DESC, e.nombre_especialidad
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
    
    /**
     * Obtener especialidades disponibles para una sucursal específica
     * (que no estén ya asignadas a esa sucursal)
     */
    public function obtenerDisponiblesParaSucursal($id_sucursal) {
        try {
            $query = "SELECT e.*
                      FROM especialidades e
                      WHERE e.id_especialidad NOT IN (
                          SELECT es.id_especialidad 
                          FROM especialidades_sucursales es 
                          WHERE es.id_sucursal = :id_sucursal
                      )
                      ORDER BY e.nombre_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades disponibles para sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades disponibles");
        }
    }
}
?>