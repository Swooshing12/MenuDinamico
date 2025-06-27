<?php
require_once __DIR__ . "/../config/database.php";

class Pacientes {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== MÉTODOS CRUD BÁSICOS =====
    
    /**
     * Crear un nuevo paciente
     */
    public function crear($datos) {
        try {
            $query = "INSERT INTO pacientes (id_usuario, fecha_nacimiento, tipo_sangre, alergias, 
                                           antecedentes_medicos, contacto_emergencia, telefono_emergencia, numero_seguro) 
                      VALUES (:id_usuario, :fecha_nacimiento, :tipo_sangre, :alergias, 
                              :antecedentes_medicos, :contacto_emergencia, :telefono_emergencia, :numero_seguro)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_usuario' => $datos['id_usuario'],
                ':fecha_nacimiento' => $datos['fecha_nacimiento'],
                ':tipo_sangre' => $datos['tipo_sangre'],
                ':alergias' => $datos['alergias'],
                ':antecedentes_medicos' => $datos['antecedentes_medicos'],
                ':contacto_emergencia' => $datos['contacto_emergencia'],
                ':telefono_emergencia' => $datos['telefono_emergencia'],
                ':numero_seguro' => $datos['numero_seguro']
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando paciente: " . $e->getMessage());
            throw new Exception("Error al crear el paciente");
        }
    }
    
    /**
     * Obtener paciente por ID
     */
    public function obtenerPorId($id_paciente) {
        try {
            $query = "SELECT p.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad, 
                             u.correo, u.username, e.nombre_estado
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      LEFT JOIN estados e ON u.id_estado = e.id_estado
                      WHERE p.id_paciente = :id_paciente";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo paciente: " . $e->getMessage());
            throw new Exception("Error al obtener el paciente");
        }
    }
    
    /**
     * Obtener paciente por ID de usuario
     */
    public function obtenerPorIdUsuario($id_usuario) {
        try {
            $query = "SELECT p.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad, 
                             u.correo, u.username, e.nombre_estado
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      LEFT JOIN estados e ON u.id_estado = e.id_estado
                      WHERE p.id_usuario = :id_usuario";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo paciente por usuario: " . $e->getMessage());
            throw new Exception("Error al obtener el paciente");
        }
    }
    
    /**
     * Buscar paciente por cédula
     */
    public function buscarPorCedula($cedula) {
        try {
            $query = "SELECT p.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad, 
                             u.correo, u.username, e.nombre_estado,
                             TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      LEFT JOIN estados e ON u.id_estado = e.id_estado
                      WHERE u.cedula = :cedula";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':cedula' => $cedula]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando paciente por cédula: " . $e->getMessage());
            throw new Exception("Error al buscar el paciente");
        }
    }
    
    /**
     * Actualizar datos de paciente
     */
    public function actualizar($id_paciente, $datos) {
        try {
            $query = "UPDATE pacientes SET 
                        fecha_nacimiento = :fecha_nacimiento,
                        tipo_sangre = :tipo_sangre,
                        alergias = :alergias,
                        antecedentes_medicos = :antecedentes_medicos,
                        contacto_emergencia = :contacto_emergencia,
                        telefono_emergencia = :telefono_emergencia,
                        numero_seguro = :numero_seguro
                      WHERE id_paciente = :id_paciente";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_paciente' => $id_paciente,
                ':fecha_nacimiento' => $datos['fecha_nacimiento'],
                ':tipo_sangre' => $datos['tipo_sangre'],
                ':alergias' => $datos['alergias'],
                ':antecedentes_medicos' => $datos['antecedentes_medicos'],
                ':contacto_emergencia' => $datos['contacto_emergencia'],
                ':telefono_emergencia' => $datos['telefono_emergencia'],
                ':numero_seguro' => $datos['numero_seguro']
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando paciente: " . $e->getMessage());
            throw new Exception("Error al actualizar el paciente");
        }
    }
    
    /**
     * Eliminar paciente (desactivar usuario)
     */
    public function eliminar($id_paciente) {
        try {
            // Obtener ID de usuario del paciente
            $paciente = $this->obtenerPorId($id_paciente);
            if (!$paciente) {
                throw new Exception("Paciente no encontrado");
            }
            
            // Desactivar usuario en lugar de eliminar
            $query = "UPDATE usuarios SET id_estado = 4 WHERE id_usuario = :id_usuario"; // 4 = Inactivo
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':id_usuario' => $paciente['id_usuario']]);
        } catch (PDOException $e) {
            error_log("Error eliminando paciente: " . $e->getMessage());
            throw new Exception("Error al eliminar el paciente");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA ESPECÍFICOS =====
    
    /**
     * Obtener todos los pacientes con filtros
     */
    public function obtenerTodos($filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Filtro por estado
            if (!empty($filtros['estado'])) {
                $where_conditions[] = "u.id_estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            // Filtro por búsqueda de texto
            if (!empty($filtros['busqueda'])) {
                $where_conditions[] = "(u.cedula LIKE :busqueda 
                                     OR u.nombres LIKE :busqueda 
                                     OR u.apellidos LIKE :busqueda 
                                     OR u.correo LIKE :busqueda)";
                $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
            }
            
            // Filtro por tipo de sangre
            if (!empty($filtros['tipo_sangre'])) {
                $where_conditions[] = "p.tipo_sangre = :tipo_sangre";
                $params[':tipo_sangre'] = $filtros['tipo_sangre'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT p.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad, 
                             u.correo, u.username, e.nombre_estado,
                             TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_paciente = p.id_paciente) as total_citas
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      LEFT JOIN estados e ON u.id_estado = e.id_estado
                      $where_clause
                      ORDER BY u.nombres, u.apellidos";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo pacientes: " . $e->getMessage());
            throw new Exception("Error al obtener los pacientes");
        }
    }
    
    /**
     * Obtener pacientes paginados
     */
    public function obtenerPaginados($inicio, $limite, $busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Búsqueda de texto
            if (!empty($busqueda)) {
                $where_conditions[] = "(u.cedula LIKE :busqueda 
                                     OR u.nombres LIKE :busqueda 
                                     OR u.apellidos LIKE :busqueda 
                                     OR u.correo LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // Filtros adicionales
            foreach ($filtros as $campo => $valor) {
                if (!empty($valor)) {
                    if ($campo === 'estado') {
                        $where_conditions[] = "u.id_estado = :estado";
                        $params[':estado'] = $valor;
                    } elseif ($campo === 'tipo_sangre') {
                        $where_conditions[] = "p.tipo_sangre = :tipo_sangre";
                        $params[':tipo_sangre'] = $valor;
                    }
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT p.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad, 
                             u.correo, u.username, e.nombre_estado,
                             TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_paciente = p.id_paciente) as total_citas
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      LEFT JOIN estados e ON u.id_estado = e.id_estado
                      $where_clause
                      ORDER BY u.nombres, u.apellidos
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
            error_log("Error obteniendo pacientes paginados: " . $e->getMessage());
            throw new Exception("Error al obtener los pacientes");
        }
    }
    
    /**
     * Contar total de pacientes
     */
    public function contarTotal($busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($busqueda)) {
                $where_conditions[] = "(u.cedula LIKE :busqueda 
                                     OR u.nombres LIKE :busqueda 
                                     OR u.apellidos LIKE :busqueda 
                                     OR u.correo LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            foreach ($filtros as $campo => $valor) {
                if (!empty($valor)) {
                    if ($campo === 'estado') {
                        $where_conditions[] = "u.id_estado = :estado";
                        $params[':estado'] = $valor;
                    } elseif ($campo === 'tipo_sangre') {
                        $where_conditions[] = "p.tipo_sangre = :tipo_sangre";
                        $params[':tipo_sangre'] = $valor;
                    }
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      $where_clause";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando pacientes: " . $e->getMessage());
            throw new Exception("Error al contar los pacientes");
        }
    }
    
    // ===== MÉTODOS ESPECÍFICOS PARA RECEPCIONISTA =====
    
    /**
     * Buscar pacientes por texto (para autocomplete)
     */
    public function buscarPorTexto($texto, $limite = 10) {
        try {
            $query = "SELECT p.id_paciente, u.cedula, u.nombres, u.apellidos, u.correo,
                             TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                             CONCAT(u.nombres, ' ', u.apellidos, ' - ', u.cedula) as texto_completo
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      WHERE (u.cedula LIKE :texto 
                             OR u.nombres LIKE :texto 
                             OR u.apellidos LIKE :texto 
                             OR CONCAT(u.nombres, ' ', u.apellidos) LIKE :texto)
                        AND u.id_estado = 1
                      ORDER BY u.nombres, u.apellidos
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':texto', '%' . $texto . '%');
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando pacientes por texto: " . $e->getMessage());
            throw new Exception("Error al buscar pacientes");
        }
    }
    
    /**
     * Contar pacientes nuevos por fecha
     */
    public function contarPacientesNuevosPorFecha($fecha) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                      WHERE DATE(u.fecha_creacion) = :fecha";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':fecha' => $fecha]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando pacientes nuevos: " . $e->getMessage());
            throw new Exception("Error al contar pacientes nuevos");
        }
    }
    
    /**
     * Obtener historial médico del paciente
     */
    public function obtenerHistorialMedico($id_paciente) {
        try {
            $query = "SELECT cm.*, c.fecha_hora as fecha_cita, c.motivo as motivo_cita,
                             d.nombres as doctor_nombres, d.apellidos as doctor_apellidos,
                             e.nombre_especialidad, s.nombre_sucursal
                      FROM consultas_medicas cm
                      INNER JOIN citas c ON cm.id_cita = c.id_cita
                      INNER JOIN historiales_clinicos hc ON cm.id_historial = hc.id_historial
                      INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                      INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                      INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                      INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                      WHERE hc.id_paciente = :id_paciente
                      ORDER BY cm.fecha_hora DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial médico: " . $e->getMessage());
            throw new Exception("Error al obtener historial médico");
        }
    }
    
    /**
     * Crear historial clínico para un paciente
     */
    public function crearHistorialClinico($id_paciente) {
        try {
            $query = "INSERT INTO historiales_clinicos (id_paciente) VALUES (:id_paciente)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando historial clínico: " . $e->getMessage());
            throw new Exception("Error al crear historial clínico");
        }
    }
    
    /**
     * Obtener tipos de sangre disponibles
     */
    public function obtenerTiposSangre() {
        return [
            'A+' => 'A Positivo',
            'A-' => 'A Negativo',
            'B+' => 'B Positivo',
            'B-' => 'B Negativo',
            'AB+' => 'AB Positivo',
            'AB-' => 'AB Negativo',
            'O+' => 'O Positivo',
            'O-' => 'O Negativo'
        ];
    }
    
    /**
     * Obtener estadísticas de pacientes
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_pacientes,
                        SUM(CASE WHEN u.id_estado = 1 THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN u.id_estado = 4 THEN 1 ELSE 0 END) as inactivos,
                        SUM(CASE WHEN DATE(u.fecha_creacion) = CURDATE() THEN 1 ELSE 0 END) as nuevos_hoy,
                        SUM(CASE WHEN DATE(u.fecha_creacion) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as nuevos_semana
                      FROM pacientes p
                      INNER JOIN usuarios u ON p.id_usuario = u.id_usuario";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de pacientes: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Verificar si existe un paciente por ID de usuario
     */
    public function existePacientePorUsuario($id_usuario) {
        try {
            $query = "SELECT COUNT(*) as total FROM pacientes WHERE id_usuario = :id_usuario";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando existencia de paciente: " . $e->getMessage());
            throw new Exception("Error al verificar paciente");
        }
    }
    
    /**
     * Verificar si un paciente puede ser eliminado (no tiene citas pendientes)
     */
    public function puedeEliminar($id_paciente) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM citas 
                      WHERE id_paciente = :id_paciente 
                        AND estado IN ('Pendiente', 'Confirmada')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0; // true si no tiene citas pendientes
        } catch (PDOException $e) {
            error_log("Error verificando eliminación de paciente: " . $e->getMessage());
            throw new Exception("Error al verificar eliminación");
        }
    }
}
?>