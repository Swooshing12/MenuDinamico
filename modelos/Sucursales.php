<?php
require_once __DIR__ . "/../config/database.php";

class Sucursales {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== MÉTODOS CRUD BÁSICOS =====
    
    /**
     * Crear una nueva sucursal
     */
    public function crear($datos) {
        try {
            $query = "INSERT INTO sucursales (nombre_sucursal, direccion, telefono, email, horario_atencion, estado) 
                      VALUES (:nombre_sucursal, :direccion, :telefono, :email, :horario_atencion, :estado)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':nombre_sucursal' => $datos['nombre_sucursal'],
                ':direccion' => $datos['direccion'],
                ':telefono' => $datos['telefono'],
                ':email' => $datos['email'] ?? null,
                ':horario_atencion' => $datos['horario_atencion'] ?? null,
                ':estado' => $datos['estado'] ?? 1
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando sucursal: " . $e->getMessage());
            throw new Exception("Error al crear la sucursal");
        }
    }
    
    /**
     * Obtener sucursal por ID
     */
    public function obtenerPorId($id_sucursal) {
        try {
            $query = "SELECT s.*,
                             (SELECT COUNT(*) FROM especialidades_sucursales es WHERE es.id_sucursal = s.id_sucursal) as total_especialidades,
                             (SELECT COUNT(DISTINCT ds.id_doctor) FROM doctores_sucursales ds WHERE ds.id_sucursal = s.id_sucursal) as total_doctores,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_sucursal = s.id_sucursal) as total_citas
                      FROM sucursales s
                      WHERE s.id_sucursal = :id_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener la sucursal");
        }
    }
    
    /**
     * Actualizar una sucursal
     */
    public function actualizar($id_sucursal, $datos) {
        try {
            $query = "UPDATE sucursales SET 
                        nombre_sucursal = :nombre_sucursal,
                        direccion = :direccion,
                        telefono = :telefono,
                        email = :email,
                        horario_atencion = :horario_atencion,
                        estado = :estado
                      WHERE id_sucursal = :id_sucursal";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_sucursal' => $id_sucursal,
                ':nombre_sucursal' => $datos['nombre_sucursal'],
                ':direccion' => $datos['direccion'],
                ':telefono' => $datos['telefono'],
                ':email' => $datos['email'],
                ':horario_atencion' => $datos['horario_atencion'],
                ':estado' => $datos['estado']
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando sucursal: " . $e->getMessage());
            throw new Exception("Error al actualizar la sucursal");
        }
    }
    
    /**
     * Cambiar estado de una sucursal
     */
    public function cambiarEstado($id_sucursal, $nuevo_estado) {
        try {
            $query = "UPDATE sucursales SET estado = :estado WHERE id_sucursal = :id_sucursal";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_sucursal' => $id_sucursal,
                ':estado' => $nuevo_estado
            ]);
        } catch (PDOException $e) {
            error_log("Error cambiando estado de sucursal: " . $e->getMessage());
            throw new Exception("Error al cambiar estado de la sucursal");
        }
    }
    
    /**
     * Eliminar una sucursal (desactivar)
     */
    public function eliminar($id_sucursal) {
        try {
            // Verificar si puede ser eliminada
            if (!$this->puedeEliminar($id_sucursal)) {
                throw new Exception("No se puede eliminar la sucursal porque tiene citas programadas o doctores asignados");
            }
            
            // Desactivar en lugar de eliminar físicamente
            return $this->cambiarEstado($id_sucursal, 0);
        } catch (PDOException $e) {
            error_log("Error eliminando sucursal: " . $e->getMessage());
            throw new Exception("Error al eliminar la sucursal");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA ESPECÍFICOS =====
    
    /**
     * Obtener todas las sucursales
     */
    public function obtenerTodas($incluir_inactivas = false) {
        try {
            $where_clause = $incluir_inactivas ? '' : 'WHERE s.estado = 1';
            
            $query = "SELECT s.*,
                             (SELECT COUNT(*) FROM especialidades_sucursales es WHERE es.id_sucursal = s.id_sucursal) as total_especialidades,
                             (SELECT COUNT(DISTINCT ds.id_doctor) FROM doctores_sucursales ds WHERE ds.id_sucursal = s.id_sucursal) as total_doctores,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_sucursal = s.id_sucursal AND DATE(c.fecha_hora) = CURDATE()) as citas_hoy
                      FROM sucursales s
                      $where_clause
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales: " . $e->getMessage());
            throw new Exception("Error al obtener las sucursales");
        }
    }
    
    /**
     * Obtener sucursales activas (para formularios)
     */
    public function obtenerActivas() {
        try {
            $query = "SELECT id_sucursal, nombre_sucursal, direccion, telefono, email, horario_atencion
                      FROM sucursales
                      WHERE estado = 1
                      ORDER BY nombre_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales activas: " . $e->getMessage());
            throw new Exception("Error al obtener las sucursales activas");
        }
    }
    
    /**
     * Obtener sucursales paginadas
     */
    public function obtenerPaginadas($inicio, $limite, $busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Búsqueda por texto
            if (!empty($busqueda)) {
                $where_conditions[] = "(s.nombre_sucursal LIKE :busqueda 
                                     OR s.direccion LIKE :busqueda 
                                     OR s.telefono LIKE :busqueda 
                                     OR s.email LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // Filtro por estado
            if (isset($filtros['estado']) && $filtros['estado'] !== '') {
                $where_conditions[] = "s.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT s.*,
                             (SELECT COUNT(*) FROM especialidades_sucursales es WHERE es.id_sucursal = s.id_sucursal) as total_especialidades,
                             (SELECT COUNT(DISTINCT ds.id_doctor) FROM doctores_sucursales ds WHERE ds.id_sucursal = s.id_sucursal) as total_doctores,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_sucursal = s.id_sucursal AND DATE(c.fecha_hora) = CURDATE()) as citas_hoy,
                             CASE WHEN s.estado = 1 THEN 'Activa' ELSE 'Inactiva' END as estado_texto,
                             CASE WHEN s.estado = 1 THEN 'success' ELSE 'danger' END as estado_badge
                      FROM sucursales s
                      $where_clause
                      ORDER BY s.nombre_sucursal
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
            error_log("Error obteniendo sucursales paginadas: " . $e->getMessage());
            throw new Exception("Error al obtener las sucursales");
        }
    }
    
    /**
     * Contar total de sucursales
     */
    public function contarTotal($busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($busqueda)) {
                $where_conditions[] = "(nombre_sucursal LIKE :busqueda 
                                     OR direccion LIKE :busqueda 
                                     OR telefono LIKE :busqueda 
                                     OR email LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            if (isset($filtros['estado']) && $filtros['estado'] !== '') {
                $where_conditions[] = "estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total FROM sucursales $where_clause";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando sucursales: " . $e->getMessage());
            throw new Exception("Error al contar las sucursales");
        }
    }
    
    // ===== MÉTODOS ESPECÍFICOS PARA RECEPCIONISTA =====
    
    /**
     * Obtener sucursales con especialidades disponibles
     */
    public function obtenerConEspecialidades() {
        try {
            $query = "SELECT DISTINCT s.*
                      FROM sucursales s
                      INNER JOIN especialidades_sucursales es ON s.id_sucursal = es.id_sucursal
                      WHERE s.estado = 1
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales con especialidades: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales con especialidades");
        }
    }
    
    /**
     * Obtener sucursales con doctores disponibles
     */
    public function obtenerConDoctores() {
        try {
            $query = "SELECT DISTINCT s.*,
                             COUNT(DISTINCT ds.id_doctor) as total_doctores_activos
                      FROM sucursales s
                      INNER JOIN doctores_sucursales ds ON s.id_sucursal = ds.id_sucursal
                      INNER JOIN doctores d ON ds.id_doctor = d.id_doctor
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      WHERE s.estado = 1 AND u.id_estado = 1
                      GROUP BY s.id_sucursal
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales con doctores: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales con doctores");
        }
    }
    
    /**
     * Buscar sucursales por texto (para autocomplete)
     */
    public function buscarPorTexto($texto, $limite = 10) {
        try {
            $query = "SELECT id_sucursal, nombre_sucursal, direccion, telefono
                      FROM sucursales
                      WHERE estado = 1 AND (
                          nombre_sucursal LIKE :texto 
                          OR direccion LIKE :texto
                      )
                      ORDER BY nombre_sucursal
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':texto', '%' . $texto . '%');
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando sucursales: " . $e->getMessage());
            throw new Exception("Error al buscar sucursales");
        }
    }
    
    /**
     * Obtener horarios de atención de una sucursal
     */
    public function obtenerHorarios($id_sucursal) {
        try {
            $query = "SELECT horario_atencion FROM sucursales WHERE id_sucursal = :id_sucursal";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['horario_atencion'] ?? '';
        } catch (PDOException $e) {
            error_log("Error obteniendo horarios: " . $e->getMessage());
            throw new Exception("Error al obtener horarios");
        }
    }
    
    /**
     * Obtener citas programadas para hoy por sucursal
     */
    public function obtenerCitasHoy($id_sucursal) {
        try {
            $query = "SELECT COUNT(*) as total_citas,
                             SUM(CASE WHEN c.estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                             SUM(CASE WHEN c.estado = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
                             SUM(CASE WHEN c.estado = 'Completada' THEN 1 ELSE 0 END) as completadas
                      FROM citas c
                      WHERE c.id_sucursal = :id_sucursal 
                        AND DATE(c.fecha_hora) = CURDATE()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo citas de hoy: " . $e->getMessage());
            throw new Exception("Error al obtener citas de hoy");
        }
    }
    
    // ===== MÉTODOS PARA GESTIÓN DE ESPECIALIDADES =====
    
    /**
     * Obtener especialidades disponibles en una sucursal
     */
    public function obtenerEspecialidades($id_sucursal) {
        try {
            $query = "SELECT e.*, es.id_especialidad_sucursal
                      FROM especialidades e
                      INNER JOIN especialidades_sucursales es ON e.id_especialidad = es.id_especialidad
                      WHERE es.id_sucursal = :id_sucursal
                      ORDER BY e.nombre_especialidad";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades de sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades de la sucursal");
        }
    }
    
    /**
     * Obtener doctores de una sucursal
     */
    public function obtenerDoctores($id_sucursal) {
        try {
            $query = "SELECT d.*, u.nombres, u.apellidos, e.nombre_especialidad,
                             ds.dias_atencion, ds.horario_inicio, ds.horario_fin
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor
                      WHERE ds.id_sucursal = :id_sucursal AND u.id_estado = 1
                      ORDER BY u.nombres, u.apellidos";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctores de sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener doctores de la sucursal");
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Verificar si existe una sucursal por nombre
     */
    public function existePorNombre($nombre_sucursal, $id_excluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM sucursales WHERE nombre_sucursal = :nombre";
            $params = [':nombre' => $nombre_sucursal];
            
            if ($id_excluir) {
                $query .= " AND id_sucursal != :id_excluir";
                $params[':id_excluir'] = $id_excluir;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando existencia de sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar sucursal");
        }
    }
    
    /**
     * Verificar si una sucursal puede ser eliminada
     */
    public function puedeEliminar($id_sucursal) {
        try {
            // Verificar si tiene citas futuras programadas
            $query = "SELECT COUNT(*) as total 
                      FROM citas 
                      WHERE id_sucursal = :id_sucursal 
                        AND fecha_hora > NOW()
                        AND estado IN ('Pendiente', 'Confirmada')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0;
        } catch (PDOException $e) {
            error_log("Error verificando eliminación de sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar eliminación");
        }
    }
    
    /**
     * Verificar si una sucursal está activa
     */
    public function estaActiva($id_sucursal) {
        try {
            $query = "SELECT estado FROM sucursales WHERE id_sucursal = :id_sucursal";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado && (int)$resultado['estado'] === 1;
        } catch (PDOException $e) {
            error_log("Error verificando estado de sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar estado de sucursal");
        }
    }
    
    // ===== MÉTODOS DE ESTADÍSTICAS =====
    
    /**
     * Obtener estadísticas generales de sucursales
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_sucursales,
                        SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as activas,
                        SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as inactivas,
                        AVG(especialidades_por_sucursal.total) as promedio_especialidades,
                        AVG(doctores_por_sucursal.total) as promedio_doctores
                      FROM sucursales s
                      LEFT JOIN (
                          SELECT id_sucursal, COUNT(*) as total 
                          FROM especialidades_sucursales 
                          GROUP BY id_sucursal
                      ) especialidades_por_sucursal ON s.id_sucursal = especialidades_por_sucursal.id_sucursal
                      LEFT JOIN (
                          SELECT id_sucursal, COUNT(DISTINCT id_doctor) as total 
                          FROM doctores_sucursales 
                          GROUP BY id_sucursal
                      ) doctores_por_sucursal ON s.id_sucursal = doctores_por_sucursal.id_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de sucursales: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Obtener sucursales más activas (con más citas)
     */
    public function obtenerMasActivas($limite = 5) {
        try {
            $query = "SELECT s.*, COUNT(c.id_cita) as total_citas_mes
                      FROM sucursales s
                      LEFT JOIN citas c ON s.id_sucursal = c.id_sucursal 
                                        AND c.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                      WHERE s.estado = 1
                      GROUP BY s.id_sucursal
                      ORDER BY total_citas_mes DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales más activas: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales más activas");
        }
    }
    
    /**
     * Obtener resumen de actividad por sucursal
     */
    public function obtenerResumenActividad() {
        try {
            $query = "SELECT s.nombre_sucursal,
                             COUNT(DISTINCT es.id_especialidad) as especialidades,
                             COUNT(DISTINCT ds.id_doctor) as doctores,
                             COUNT(DISTINCT CASE WHEN DATE(c.fecha_hora) = CURDATE() THEN c.id_cita END) as citas_hoy,
                             COUNT(DISTINCT CASE WHEN c.fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN c.id_cita END) as citas_semana
                      FROM sucursales s
                      LEFT JOIN especialidades_sucursales es ON s.id_sucursal = es.id_sucursal
                      LEFT JOIN doctores_sucursales ds ON s.id_sucursal = ds.id_sucursal
                      LEFT JOIN citas c ON s.id_sucursal = c.id_sucursal
                      WHERE s.estado = 1
                      GROUP BY s.id_sucursal, s.nombre_sucursal
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo resumen de actividad: " . $e->getMessage());
            throw new Exception("Error al obtener resumen de actividad");
        }
    }


/**
     * Contar sucursales con filtros
     */
    public function contar($busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Búsqueda por texto
            if (!empty($busqueda)) {
                $where_conditions[] = "(s.nombre_sucursal LIKE :busqueda 
                                     OR s.direccion LIKE :busqueda 
                                     OR s.telefono LIKE :busqueda 
                                     OR s.email LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // Filtro por estado
            if (isset($filtros['estado']) && $filtros['estado'] !== '') {
                $where_conditions[] = "s.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total FROM sucursales s $where_clause";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Error contando sucursales: " . $e->getMessage());
            throw new Exception("Error al contar las sucursales");
        }
    }
    

    
    /**
     * Verificar si existe una sucursal por teléfono
     */
    public function existePorTelefono($telefono, $excluir_id = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM sucursales WHERE telefono = :telefono";
            $params = [':telefono' => $telefono];
            
            if ($excluir_id) {
                $query .= " AND id_sucursal != :excluir_id";
                $params[':excluir_id'] = $excluir_id;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando teléfono de sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar el teléfono de la sucursal");
        }
    }
    
    /**
     * Verificar relaciones antes de eliminar
     */
    public function verificarRelaciones($id_sucursal) {
        try {
            // Verificar doctores asignados
            $query_doctores = "SELECT COUNT(*) as total FROM doctores_sucursales WHERE id_sucursal = :id_sucursal";
            $stmt = $this->conn->prepare($query_doctores);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            $doctores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Verificar citas activas
            $query_citas = "SELECT COUNT(*) as total FROM citas WHERE id_sucursal = :id_sucursal AND fecha_hora >= CURDATE()";
            $stmt = $this->conn->prepare($query_citas);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            $citas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Verificar especialidades asignadas
            $query_especialidades = "SELECT COUNT(*) as total FROM especialidades_sucursales WHERE id_sucursal = :id_sucursal";
            $stmt = $this->conn->prepare($query_especialidades);
            $stmt->execute([':id_sucursal' => $id_sucursal]);
            $especialidades = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $puede_eliminar = ($doctores === 0 && $citas === 0);
            
            $mensaje = '';
            if (!$puede_eliminar) {
                $problemas = [];
                if ($doctores > 0) $problemas[] = "$doctores doctor(es) asignado(s)";
                if ($citas > 0) $problemas[] = "$citas cita(s) programada(s)";
                
                $mensaje = "No se puede eliminar la sucursal porque tiene: " . implode(', ', $problemas);
            }
            
            return [
                'puede_eliminar' => $puede_eliminar,
                'mensaje' => $mensaje,
                'relaciones' => [
                    'doctores' => $doctores,
                    'citas' => $citas,
                    'especialidades' => $especialidades
                ]
            ];
        } catch (PDOException $e) {
            error_log("Error verificando relaciones de sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar las relaciones de la sucursal");
        }
    }
    
  
}


?>