<?php
require_once __DIR__ . "/../config/database.php";

class Citas {
    private $conn;
    
     public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== MÉTODOS CRUD BÁSICOS =====
    
    /**
     * Crear una nueva cita
     */
    public function crear($datos) {
        try {
            $query = "INSERT INTO citas (id_paciente, id_doctor, id_sucursal, fecha_hora, motivo, estado, notas) 
                      VALUES (:id_paciente, :id_doctor, :id_sucursal, :fecha_hora, :motivo, :estado, :notas)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_paciente' => $datos['id_paciente'],
                ':id_doctor' => $datos['id_doctor'],
                ':id_sucursal' => $datos['id_sucursal'],
                ':fecha_hora' => $datos['fecha_hora'],
                ':motivo' => $datos['motivo'],
                ':estado' => $datos['estado'] ?? 'Pendiente',
                ':notas' => $datos['notas'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando cita: " . $e->getMessage());
            throw new Exception("Error al crear la cita");
        }
    }
    
    /**
     * Obtener cita por ID
     */
    public function obtenerPorId($id_cita) {
        try {
            $query = "SELECT c.*, 
                             p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                             u_paciente.cedula as paciente_cedula, u_paciente.correo as paciente_correo,
                             d.nombres as doctor_nombres, d.apellidos as doctor_apellidos,
                             e.nombre_especialidad,
                             s.nombre_sucursal, s.direccion as sucursal_direccion
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN usuarios u_paciente ON pac.id_usuario = u_paciente.id_usuario
                      INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                      INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                      INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                      INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                      WHERE c.id_cita = :id_cita";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_cita' => $id_cita]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo cita: " . $e->getMessage());
            throw new Exception("Error al obtener la cita");
        }
    }
    
    /**
     * Actualizar una cita
     */
    public function actualizar($id_cita, $datos) {
        try {
            $query = "UPDATE citas SET 
                        id_paciente = :id_paciente,
                        id_doctor = :id_doctor,
                        id_sucursal = :id_sucursal,
                        fecha_hora = :fecha_hora,
                        motivo = :motivo,
                        estado = :estado,
                        notas = :notas
                      WHERE id_cita = :id_cita";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_cita' => $id_cita,
                ':id_paciente' => $datos['id_paciente'],
                ':id_doctor' => $datos['id_doctor'],
                ':id_sucursal' => $datos['id_sucursal'],
                ':fecha_hora' => $datos['fecha_hora'],
                ':motivo' => $datos['motivo'],
                ':estado' => $datos['estado'],
                ':notas' => $datos['notas'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando cita: " . $e->getMessage());
            throw new Exception("Error al actualizar la cita");
        }
    }
    
    /**
     * Cambiar estado de una cita
     */
    public function cambiarEstado($id_cita, $nuevo_estado) {
        try {
            $query = "UPDATE citas SET estado = :estado WHERE id_cita = :id_cita";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_cita' => $id_cita,
                ':estado' => $nuevo_estado
            ]);
        } catch (PDOException $e) {
            error_log("Error cambiando estado de cita: " . $e->getMessage());
            throw new Exception("Error al cambiar estado de la cita");
        }
    }
    
    /**
     * Eliminar una cita (cancelar)
     */
    public function eliminar($id_cita) {
        try {
            // Cambiar estado en lugar de eliminar físicamente
            return $this->cambiarEstado($id_cita, 'Cancelada');
        } catch (PDOException $e) {
            error_log("Error eliminando cita: " . $e->getMessage());
            throw new Exception("Error al eliminar la cita");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA ESPECÍFICOS =====
    
    /**
     * Obtener todas las citas con filtros opcionales
     */
    public function obtenerTodas($filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Construir condiciones WHERE según filtros
            if (!empty($filtros['estado'])) {
                $where_conditions[] = "c.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            if (!empty($filtros['fecha_desde'])) {
                $where_conditions[] = "DATE(c.fecha_hora) >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $where_conditions[] = "DATE(c.fecha_hora) <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }
            
            if (!empty($filtros['id_sucursal'])) {
                $where_conditions[] = "c.id_sucursal = :id_sucursal";
                $params[':id_sucursal'] = $filtros['id_sucursal'];
            }
            
            if (!empty($filtros['id_doctor'])) {
                $where_conditions[] = "c.id_doctor = :id_doctor";
                $params[':id_doctor'] = $filtros['id_doctor'];
            }
            
            if (!empty($filtros['cedula_paciente'])) {
                $where_conditions[] = "u_paciente.cedula LIKE :cedula";
                $params[':cedula'] = '%' . $filtros['cedula_paciente'] . '%';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT c.*, 
                             p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                             u_paciente.cedula as paciente_cedula, u_paciente.correo as paciente_correo,
                             d.nombres as doctor_nombres, d.apellidos as doctor_apellidos,
                             e.nombre_especialidad,
                             s.nombre_sucursal, s.direccion as sucursal_direccion,
                             CASE 
                                WHEN c.estado = 'Pendiente' THEN 'warning'
                                WHEN c.estado = 'Confirmada' THEN 'success'
                                WHEN c.estado = 'Completada' THEN 'info'
                                WHEN c.estado = 'Cancelada' THEN 'danger'
                                ELSE 'secondary'
                             END as estado_badge
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN usuarios u_paciente ON pac.id_usuario = u_paciente.id_usuario
                      INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                      INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                      INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                      INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                      $where_clause
                      ORDER BY c.fecha_hora DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo citas: " . $e->getMessage());
            throw new Exception("Error al obtener las citas");
        }
    }
    
    /**
     * Obtener citas paginadas
     */
    public function obtenerPaginadas($inicio, $limite, $busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Agregar búsqueda por texto
            if (!empty($busqueda)) {
                $where_conditions[] = "(u_paciente.cedula LIKE :busqueda 
                                     OR p.nombres LIKE :busqueda 
                                     OR p.apellidos LIKE :busqueda 
                                     OR d.nombres LIKE :busqueda 
                                     OR d.apellidos LIKE :busqueda 
                                     OR c.motivo LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // Agregar filtros adicionales
            foreach ($filtros as $campo => $valor) {
                if (!empty($valor)) {
                    $where_conditions[] = "c.$campo = :$campo";
                    $params[":$campo"] = $valor;
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT c.*, 
                             p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                             u_paciente.cedula as paciente_cedula,
                             d.nombres as doctor_nombres, d.apellidos as doctor_apellidos,
                             e.nombre_especialidad,
                             s.nombre_sucursal,
                             CASE 
                                WHEN c.estado = 'Pendiente' THEN 'warning'
                                WHEN c.estado = 'Confirmada' THEN 'success'
                                WHEN c.estado = 'Completada' THEN 'info'
                                WHEN c.estado = 'Cancelada' THEN 'danger'
                                ELSE 'secondary'
                             END as estado_badge
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN usuarios u_paciente ON pac.id_usuario = u_paciente.id_usuario
                      INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                      INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                      INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                      INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                      $where_clause
                      ORDER BY c.fecha_hora DESC
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
            error_log("Error obteniendo citas paginadas: " . $e->getMessage());
            throw new Exception("Error al obtener las citas");
        }
    }
    
    /**
     * Contar total de citas con filtros
     */
    public function contarTotal($busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($busqueda)) {
                $where_conditions[] = "(u_paciente.cedula LIKE :busqueda 
                                     OR p.nombres LIKE :busqueda 
                                     OR p.apellidos LIKE :busqueda 
                                     OR c.motivo LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            foreach ($filtros as $campo => $valor) {
                if (!empty($valor)) {
                    $where_conditions[] = "c.$campo = :$campo";
                    $params[":$campo"] = $valor;
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN usuarios u_paciente ON pac.id_usuario = u_paciente.id_usuario
                      $where_clause";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando citas: " . $e->getMessage());
            throw new Exception("Error al contar las citas");
        }
    }
    
    // ===== MÉTODOS ESPECÍFICOS PARA RECEPCIONISTA =====
    
    /**
     * Contar citas por fecha específica
     */
    public function contarCitasPorFecha($fecha) {
        try {
            $query = "SELECT COUNT(*) as total FROM citas WHERE DATE(fecha_hora) = :fecha";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':fecha' => $fecha]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando citas por fecha: " . $e->getMessage());
            throw new Exception("Error al contar citas por fecha");
        }
    }
    
    /**
     * Contar citas por estado
     */
    public function contarCitasPorEstado($estado) {
        try {
            $query = "SELECT COUNT(*) as total FROM citas WHERE estado = :estado";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':estado' => $estado]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando citas por estado: " . $e->getMessage());
            throw new Exception("Error al contar citas por estado");
        }
    }
    
    /**
     * Obtener citas de hoy
     */
    public function obtenerCitasHoy() {
        try {
            $fecha_hoy = date('Y-m-d');
            return $this->obtenerTodas(['fecha_desde' => $fecha_hoy, 'fecha_hasta' => $fecha_hoy]);
        } catch (Exception $e) {
            throw new Exception("Error al obtener citas de hoy: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener próximas citas de un doctor
     */
    public function obtenerProximasCitasDoctor($id_doctor, $limite = 10) {
        try {
            $query = "SELECT c.*, 
                             p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                             u_paciente.cedula as paciente_cedula
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN usuarios u_paciente ON pac.id_usuario = u_paciente.id_usuario
                      WHERE c.id_doctor = :id_doctor 
                        AND c.fecha_hora >= NOW()
                        AND c.estado IN ('Pendiente', 'Confirmada')
                      ORDER BY c.fecha_hora ASC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_doctor', $id_doctor, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo próximas citas del doctor: " . $e->getMessage());
            throw new Exception("Error al obtener próximas citas del doctor");
        }
    }
    
    /**
     * Verificar disponibilidad de horario
     */
    public function verificarDisponibilidad($id_doctor, $fecha_hora) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM citas 
                      WHERE id_doctor = :id_doctor 
                        AND fecha_hora = :fecha_hora 
                        AND estado IN ('Pendiente', 'Confirmada')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':fecha_hora' => $fecha_hora
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0; // true si está disponible
        } catch (PDOException $e) {
            error_log("Error verificando disponibilidad: " . $e->getMessage());
            throw new Exception("Error al verificar disponibilidad");
        }
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_citas,
                        SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
                        SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) as completadas,
                        SUM(CASE WHEN estado = 'Cancelada' THEN 1 ELSE 0 END) as canceladas,
                        SUM(CASE WHEN DATE(fecha_hora) = CURDATE() THEN 1 ELSE 0 END) as hoy
                      FROM citas";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Validar si existe una cita
     */
    public function existeCita($id_cita) {
        try {
            $query = "SELECT COUNT(*) as total FROM citas WHERE id_cita = :id_cita";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_cita' => $id_cita]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error validando existencia de cita: " . $e->getMessage());
            throw new Exception("Error al validar cita");
        }
    }
    
    /**
     * Obtener estados disponibles
     */
    public function obtenerEstados() {
        return [
            'Pendiente' => ['color' => 'warning', 'texto' => 'Pendiente'],
            'Confirmada' => ['color' => 'success', 'texto' => 'Confirmada'],
            'Completada' => ['color' => 'info', 'texto' => 'Completada'],
            'Cancelada' => ['color' => 'danger', 'texto' => 'Cancelada'],
            'No_Asistio' => ['color' => 'secondary', 'texto' => 'No Asistió']
        ];
    }
}
?>