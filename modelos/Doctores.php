<?php
require_once __DIR__ . "/../config/database.php";

class Doctores {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== MÉTODOS CRUD BÁSICOS =====
    
    /**
     * Crear un nuevo doctor
     */
    public function crear($datos) {
        try {
            $query = "INSERT INTO doctores (id_usuario, id_especialidad, titulo_profesional) 
                      VALUES (:id_usuario, :id_especialidad, :titulo_profesional)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_usuario' => $datos['id_usuario'],
                ':id_especialidad' => $datos['id_especialidad'],
                ':titulo_profesional' => $datos['titulo_profesional'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando doctor: " . $e->getMessage());
            throw new Exception("Error al crear el doctor");
        }
    }
    
    /**
     * Obtener doctor por ID
     */
    public function obtenerPorId($id_doctor) {
        try {
            $query = "SELECT d.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad,
                             u.correo, u.username, e.nombre_especialidad, e.descripcion as especialidad_descripcion,
                             est.nombre_estado,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_doctor = d.id_doctor) as total_citas,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_doctor = d.id_doctor AND DATE(c.fecha_hora) = CURDATE()) as citas_hoy
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      LEFT JOIN estados est ON u.id_estado = est.id_estado
                      WHERE d.id_doctor = :id_doctor";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctor: " . $e->getMessage());
            throw new Exception("Error al obtener el doctor");
        }
    }
    
    /**
     * Obtener doctor por ID de usuario
     */
    public function obtenerPorIdUsuario($id_usuario) {
        try {
            $query = "SELECT d.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.nacionalidad,
                             u.correo, u.username, e.nombre_especialidad, est.nombre_estado
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      LEFT JOIN estados est ON u.id_estado = est.id_estado
                      WHERE d.id_usuario = :id_usuario";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctor por usuario: " . $e->getMessage());
            throw new Exception("Error al obtener el doctor");
        }
    }
    
    /**
     * Actualizar un doctor
     */
    public function actualizar($id_doctor, $datos) {
        try {
            $query = "UPDATE doctores SET 
                        id_especialidad = :id_especialidad,
                        titulo_profesional = :titulo_profesional
                      WHERE id_doctor = :id_doctor";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':id_especialidad' => $datos['id_especialidad'],
                ':titulo_profesional' => $datos['titulo_profesional']
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando doctor: " . $e->getMessage());
            throw new Exception("Error al actualizar el doctor");
        }
    }
    
    /**
     * Eliminar doctor (desactivar usuario)
     */
    public function eliminar($id_doctor) {
        try {
            // Obtener ID de usuario del doctor
            $doctor = $this->obtenerPorId($id_doctor);
            if (!$doctor) {
                throw new Exception("Doctor no encontrado");
            }
            
            // Verificar si puede ser eliminado
            if (!$this->puedeEliminar($id_doctor)) {
                throw new Exception("No se puede eliminar el doctor porque tiene citas programadas");
            }
            
            // Desactivar usuario en lugar de eliminar
            $query = "UPDATE usuarios SET id_estado = 4 WHERE id_usuario = :id_usuario"; // 4 = Inactivo
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':id_usuario' => $doctor['id_usuario']]);
        } catch (PDOException $e) {
            error_log("Error eliminando doctor: " . $e->getMessage());
            throw new Exception("Error al eliminar el doctor");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA ESPECÍFICOS =====
    
    /**
     * Obtener todos los doctores
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
            
            // Filtro por especialidad
            if (!empty($filtros['id_especialidad'])) {
                $where_conditions[] = "d.id_especialidad = :id_especialidad";
                $params[':id_especialidad'] = $filtros['id_especialidad'];
            }
            
            // Filtro por sucursal
            if (!empty($filtros['id_sucursal'])) {
                $where_conditions[] = "ds.id_sucursal = :id_sucursal";
                $params[':id_sucursal'] = $filtros['id_sucursal'];
            }
            
            // Filtro por búsqueda de texto
            if (!empty($filtros['busqueda'])) {
                $where_conditions[] = "(u.cedula LIKE :busqueda 
                                     OR u.nombres LIKE :busqueda 
                                     OR u.apellidos LIKE :busqueda 
                                     OR e.nombre_especialidad LIKE :busqueda)";
                $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
            }
            
            $join_sucursal = !empty($filtros['id_sucursal']) ? 
                "INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor" : 
                "LEFT JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor";
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT d.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.correo,
                             e.nombre_especialidad, est.nombre_estado,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_doctor = d.id_doctor) as total_citas,
                             (SELECT COUNT(DISTINCT ds2.id_sucursal) FROM doctores_sucursales ds2 WHERE ds2.id_doctor = d.id_doctor) as total_sucursales
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      LEFT JOIN estados est ON u.id_estado = est.id_estado
                      $join_sucursal
                      $where_clause
                      GROUP BY d.id_doctor
                      ORDER BY u.nombres, u.apellidos";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctores: " . $e->getMessage());
            throw new Exception("Error al obtener los doctores");
        }
    }
    
    /**
     * Obtener doctores paginados
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
                                     OR e.nombre_especialidad LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // Filtros adicionales
            foreach ($filtros as $campo => $valor) {
                if (!empty($valor)) {
                    if ($campo === 'estado') {
                        $where_conditions[] = "u.id_estado = :estado";
                        $params[':estado'] = $valor;
                    } elseif ($campo === 'id_especialidad') {
                        $where_conditions[] = "d.id_especialidad = :id_especialidad";
                        $params[':id_especialidad'] = $valor;
                    }
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT d.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.correo,
                             e.nombre_especialidad, est.nombre_estado,
                             (SELECT COUNT(*) FROM citas c WHERE c.id_doctor = d.id_doctor) as total_citas,
                             (SELECT COUNT(DISTINCT ds.id_sucursal) FROM doctores_sucursales ds WHERE ds.id_doctor = d.id_doctor) as total_sucursales
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      LEFT JOIN estados est ON u.id_estado = est.id_estado
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
            error_log("Error obteniendo doctores paginados: " . $e->getMessage());
            throw new Exception("Error al obtener los doctores");
        }
    }
    
    /**
     * Contar total de doctores
     */
    public function contarTotal($busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($busqueda)) {
                $where_conditions[] = "(u.cedula LIKE :busqueda 
                                     OR u.nombres LIKE :busqueda 
                                     OR u.apellidos LIKE :busqueda 
                                     OR e.nombre_especialidad LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            foreach ($filtros as $campo => $valor) {
                if (!empty($valor)) {
                    if ($campo === 'estado') {
                        $where_conditions[] = "u.id_estado = :estado";
                        $params[':estado'] = $valor;
                    } elseif ($campo === 'id_especialidad') {
                        $where_conditions[] = "d.id_especialidad = :id_especialidad";
                        $params[':id_especialidad'] = $valor;
                    }
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      $where_clause";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            error_log("Error contando doctores: " . $e->getMessage());
            throw new Exception("Error al contar los doctores");
        }
    }
    
    // ===== MÉTODOS ESPECÍFICOS PARA RECEPCIONISTA =====
    
    /**
     * Obtener doctores por especialidad y sucursal
     */
    public function obtenerPorEspecialidadYSucursal($id_especialidad, $id_sucursal) {
        try {
            $query = "SELECT d.*, u.nombres, u.apellidos, u.cedula,
                             ds.dias_atencion, ds.horario_inicio, ds.horario_fin,
                             e.nombre_especialidad
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
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
            error_log("Error obteniendo doctores por especialidad y sucursal: " . $e->getMessage());
            throw new Exception("Error al obtener doctores");
        }
    }
    
    /**
     * Obtener horarios disponibles de un doctor en una fecha
     */
    public function obtenerHorariosDisponibles($id_doctor, $fecha) {
        try {
            // Primero obtener los horarios del doctor en la sucursal
            $query_horarios = "SELECT ds.horario_inicio, ds.horario_fin, ds.dias_atencion
                               FROM doctores_sucursales ds
                               WHERE ds.id_doctor = :id_doctor
                               LIMIT 1";
            
            $stmt = $this->conn->prepare($query_horarios);
            $stmt->execute([':id_doctor' => $id_doctor]);
            $horario_doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$horario_doctor) {
                return [];
            }
            
            // Obtener citas ya programadas para esa fecha
            $query_citas = "SELECT TIME(fecha_hora) as hora_ocupada
                            FROM citas
                            WHERE id_doctor = :id_doctor 
                              AND DATE(fecha_hora) = :fecha
                              AND estado IN ('Pendiente', 'Confirmada')";
            
            $stmt = $this->conn->prepare($query_citas);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':fecha' => $fecha
            ]);
            $citas_ocupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Generar horarios disponibles (cada 30 minutos por defecto)
            $horarios_disponibles = [];
            $hora_inicio = new DateTime($horario_doctor['horario_inicio']);
            $hora_fin = new DateTime($horario_doctor['horario_fin']);
            $intervalo = new DateInterval('PT30M'); // 30 minutos
            
            while ($hora_inicio < $hora_fin) {
                $hora_str = $hora_inicio->format('H:i:s');
                
                // Verificar si esta hora no está ocupada
                if (!in_array($hora_str, $citas_ocupadas)) {
                    $horarios_disponibles[] = [
                        'hora' => $hora_str,
                        'hora_formato' => $hora_inicio->format('H:i'),
                        'disponible' => true
                    ];
                }
                
                $hora_inicio->add($intervalo);
            }
            
            return $horarios_disponibles;
        } catch (PDOException $e) {
            error_log("Error obteniendo horarios disponibles: " . $e->getMessage());
            throw new Exception("Error al obtener horarios disponibles");
        }
    }
    
    /**
     * Verificar disponibilidad de un doctor en fecha y hora específica
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
            error_log("Error verificando disponibilidad del doctor: " . $e->getMessage());
            throw new Exception("Error al verificar disponibilidad");
        }
    }
    
    /**
     * Buscar doctores por texto (para autocomplete)
     */
    public function buscarPorTexto($texto, $limite = 10) {
        try {
            $query = "SELECT d.id_doctor, u.nombres, u.apellidos, u.cedula, e.nombre_especialidad,
                             CONCAT(u.nombres, ' ', u.apellidos, ' - ', e.nombre_especialidad) as texto_completo
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      WHERE (u.nombres LIKE :texto 
                             OR u.apellidos LIKE :texto 
                             OR u.cedula LIKE :texto
                             OR CONCAT(u.nombres, ' ', u.apellidos) LIKE :texto
                             OR e.nombre_especialidad LIKE :texto)
                        AND u.id_estado = 1
                      ORDER BY u.nombres, u.apellidos
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':texto', '%' . $texto . '%');
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando doctores por texto: " . $e->getMessage());
            throw new Exception("Error al buscar doctores");
        }
    }
    
    /**
     * Obtener agenda del doctor para una fecha específica
     */
    public function obtenerAgenda($id_doctor, $fecha) {
        try {
            $query = "SELECT c.*, p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                             u_paciente.cedula as paciente_cedula, s.nombre_sucursal
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN usuarios u_paciente ON pac.id_usuario = u_paciente.id_usuario
                      INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                      WHERE c.id_doctor = :id_doctor 
                        AND DATE(c.fecha_hora) = :fecha
                        AND c.estado IN ('Pendiente', 'Confirmada')
                      ORDER BY c.fecha_hora";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':fecha' => $fecha
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo agenda del doctor: " . $e->getMessage());
            throw new Exception("Error al obtener agenda del doctor");
        }
    }
    
    // ===== MÉTODOS PARA GESTIÓN DE SUCURSALES =====
    
    /**
     * Asignar doctor a sucursal
     */
    public function asignarASucursal($id_doctor, $id_sucursal, $datos_horario = []) {
        try {
            // Verificar si ya existe la asignación
            if ($this->existeEnSucursal($id_doctor, $id_sucursal)) {
                throw new Exception("El doctor ya está asignado a esta sucursal");
            }
            
            $query = "INSERT INTO doctores_sucursales (id_doctor, id_sucursal, dias_atencion, horario_inicio, horario_fin) 
                      VALUES (:id_doctor, :id_sucursal, :dias_atencion, :horario_inicio, :horario_fin)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':id_sucursal' => $id_sucursal,
                ':dias_atencion' => $datos_horario['dias_atencion'] ?? null,
                ':horario_inicio' => $datos_horario['horario_inicio'] ?? null,
                ':horario_fin' => $datos_horario['horario_fin'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error asignando doctor a sucursal: " . $e->getMessage());
            throw new Exception("Error al asignar doctor a sucursal");
        }
    }
    
    /**
     * Obtener sucursales donde trabaja un doctor
     */
    public function obtenerSucursales($id_doctor) {
        try {
            $query = "SELECT s.*, ds.dias_atencion, ds.horario_inicio, ds.horario_fin
                      FROM sucursales s
                      INNER JOIN doctores_sucursales ds ON s.id_sucursal = ds.id_sucursal
                      WHERE ds.id_doctor = :id_doctor AND s.estado = 1
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales del doctor: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales del doctor");
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Verificar si existe un doctor por ID de usuario
     */
    public function existeDoctorPorUsuario($id_usuario) {
        try {
            $query = "SELECT COUNT(*) as total FROM doctores WHERE id_usuario = :id_usuario";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando existencia de doctor: " . $e->getMessage());
            throw new Exception("Error al verificar doctor");
        }
    }
    
    /**
     * Verificar si un doctor existe en una sucursal
     */
    public function existeEnSucursal($id_doctor, $id_sucursal) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM doctores_sucursales 
                      WHERE id_doctor = :id_doctor AND id_sucursal = :id_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':id_sucursal' => $id_sucursal
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando doctor en sucursal: " . $e->getMessage());
            throw new Exception("Error al verificar doctor en sucursal");
        }
    }
    
    /**
     * Verificar si un doctor puede ser eliminado
     */
    public function puedeEliminar($id_doctor) {
        try {
            // Verificar si tiene citas futuras programadas
            $query = "SELECT COUNT(*) as total 
                      FROM citas 
                      WHERE id_doctor = :id_doctor 
                        AND fecha_hora > NOW()
                        AND estado IN ('Pendiente', 'Confirmada')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] === 0;
        } catch (PDOException $e) {
            error_log("Error verificando eliminación de doctor: " . $e->getMessage());
            throw new Exception("Error al verificar eliminación");
        }
    }
    
    // ===== MÉTODOS DE ESTADÍSTICAS =====
    
    /**
     * Obtener estadísticas de doctores
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_doctores,
                        SUM(CASE WHEN u.id_estado = 1 THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN u.id_estado != 1 THEN 1 ELSE 0 END) as inactivos,
                        COUNT(DISTINCT d.id_especialidad) as especialidades_cubiertas,
                        AVG(citas_por_doctor.total) as promedio_citas_por_doctor
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      LEFT JOIN (
                          SELECT id_doctor, COUNT(*) as total 
                          FROM citas 
                          WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                          GROUP BY id_doctor
                      ) citas_por_doctor ON d.id_doctor = citas_por_doctor.id_doctor";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de doctores: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Obtener doctores más activos (con más citas)
     */
    public function obtenerMasActivos($limite = 5) {
        try {
            $query = "SELECT d.*, u.nombres, u.apellidos, e.nombre_especialidad,
                             COUNT(c.id_cita) as total_citas_mes
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      LEFT JOIN citas c ON d.id_doctor = c.id_doctor 
                                        AND c.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                      WHERE u.id_estado = 1
                      GROUP BY d.id_doctor
                      ORDER BY total_citas_mes DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctores más activos: " . $e->getMessage());
            throw new Exception("Error al obtener doctores más activos");
        }
    }

    
}

?>