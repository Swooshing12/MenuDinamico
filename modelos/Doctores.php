<?php
require_once __DIR__ . "/../config/database.php";

class Doctores {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== MÉTODOS CRUD BÁSICOS =====
    
    /**
     * Crear un nuevo doctor (múltiples tablas)
     */
    /**
 * Crear un nuevo doctor con horarios - VERSIÓN CORREGIDA
 */
public function crearConHorarios($datosUsuario, $datosDoctor, $sucursales = [], $horarios = []) {
    try {
        $this->conn->beginTransaction();
        
        // 1. Crear usuario con rol de médico (id_rol = 70)
        $queryUsuario = "INSERT INTO usuarios 
                       (cedula, username, nombres, apellidos, sexo, nacionalidad, correo, password, id_rol, id_estado)
                       VALUES 
                       (:cedula, :username, :nombres, :apellidos, :sexo, :nacionalidad, :correo, :password, 70, :id_estado)";
        
        $stmtUsuario = $this->conn->prepare($queryUsuario);
        $stmtUsuario->execute([
            ':cedula' => $datosUsuario['cedula'],
            ':username' => $datosUsuario['username'],
            ':nombres' => $datosUsuario['nombres'],
            ':apellidos' => $datosUsuario['apellidos'],
            ':sexo' => $datosUsuario['sexo'],
            ':nacionalidad' => $datosUsuario['nacionalidad'],
            ':correo' => $datosUsuario['correo'],
            ':password' => $datosUsuario['password'],
            ':id_estado' => $datosUsuario['id_estado'] ?? 1
        ]);
        
        $id_usuario = $this->conn->lastInsertId();
        
        // 2. Crear doctor
        $queryDoctor = "INSERT INTO doctores 
                      (id_usuario, id_especialidad, titulo_profesional)
                      VALUES 
                      (:id_usuario, :id_especialidad, :titulo_profesional)";
        
        $stmtDoctor = $this->conn->prepare($queryDoctor);
        $stmtDoctor->execute([
            ':id_usuario' => $id_usuario,
            ':id_especialidad' => $datosDoctor['id_especialidad'],
            ':titulo_profesional' => $datosDoctor['titulo_profesional'] ?? null
        ]);
        
        $id_doctor = $this->conn->lastInsertId();
        
        // 3. Asignar sucursales
        if (!empty($sucursales)) {
            foreach ($sucursales as $id_sucursal) {
                $this->asignarASucursal($id_doctor, (int)$id_sucursal);
            }
        }
        
        // 4. 🕒 CREAR HORARIOS - MÉTODO CORREGIDO
        if (!empty($horarios)) {
            $this->insertarHorarios($id_doctor, $horarios);
        }
        
        $this->conn->commit();
        return $id_doctor;
        
    } catch (PDOException $e) {
        $this->conn->rollback();
        error_log("Error creando doctor con horarios: " . $e->getMessage());
        throw new Exception("Error al crear el doctor: " . $e->getMessage());
    }
}

/**
 * Insertar horarios en la tabla doctor_horarios - MÉTODO CORREGIDO
 */
private function insertarHorarios($id_doctor, $horarios) {
    try {
        // Query para insertar en doctor_horarios
        $query = "INSERT INTO doctor_horarios 
                  (id_doctor, id_sucursal, dia_semana, hora_inicio, hora_fin, duracion_cita, activo, fecha_creacion)
                  VALUES 
                  (:id_doctor, :id_sucursal, :dia_semana, :hora_inicio, :hora_fin, :duracion_cita, 1, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($horarios as $horario) {
            // Validar que el horario tenga todos los campos requeridos
            if (!isset($horario['id_sucursal']) || !isset($horario['dia_semana']) || 
                !isset($horario['hora_inicio']) || !isset($horario['hora_fin'])) {
                error_log("❌ Horario inválido - faltan campos: " . json_encode($horario));
                continue;
            }
            
            // Preparar parámetros
            $parametros = [
                ':id_doctor' => (int)$id_doctor,
                ':id_sucursal' => (int)$horario['id_sucursal'],
                ':dia_semana' => (int)$horario['dia_semana'],
                ':hora_inicio' => $horario['hora_inicio'],
                ':hora_fin' => $horario['hora_fin'],
                ':duracion_cita' => isset($horario['duracion_cita']) ? (int)$horario['duracion_cita'] : 30
            ];
            
            // Ejecutar inserción
            $resultado = $stmt->execute($parametros);
            
            if ($resultado) {
                error_log("✅ Horario insertado: Doctor={$id_doctor}, Sucursal={$horario['id_sucursal']}, Día={$horario['dia_semana']}, {$horario['hora_inicio']}-{$horario['hora_fin']}");
            } else {
                error_log("❌ Error insertando horario: " . implode(", ", $stmt->errorInfo()));
            }
        }
        
    } catch (PDOException $e) {
        error_log("❌ Error PDO insertando horarios: " . $e->getMessage());
        throw new Exception("Error al insertar horarios: " . $e->getMessage());
    }
}

/**
 * Obtener horarios de un doctor - MÉTODO MEJORADO
 */
/**
 * Obtener horarios de un doctor - MÉTODO CORREGIDO
 */
public function obtenerHorarios($id_doctor, $id_sucursal = null) {
    try {
        $query = "SELECT dh.*, s.nombre_sucursal,
                         CASE dh.dia_semana 
                             WHEN 1 THEN 'Lunes'
                             WHEN 2 THEN 'Martes'
                             WHEN 3 THEN 'Miércoles'
                             WHEN 4 THEN 'Jueves'
                             WHEN 5 THEN 'Viernes'
                             WHEN 6 THEN 'Sábado'
                             WHEN 7 THEN 'Domingo'
                         END as nombre_dia
                  FROM doctor_horarios dh
                  INNER JOIN sucursales s ON dh.id_sucursal = s.id_sucursal
                  WHERE dh.id_doctor = :id_doctor AND dh.activo = 1";
        
        $params = [':id_doctor' => $id_doctor];
        
        if ($id_sucursal) {
            $query .= " AND dh.id_sucursal = :id_sucursal";
            $params[':id_sucursal'] = $id_sucursal;
        }
        
        $query .= " ORDER BY dh.dia_semana, dh.hora_inicio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug
        error_log("📅 Horarios obtenidos para doctor {$id_doctor}: " . count($horarios));
        error_log("📅 Query ejecutada: {$query}");
        error_log("📅 Datos: " . json_encode($horarios));
        
        return $horarios;
        
    } catch (PDOException $e) {
        error_log("Error obteniendo horarios: " . $e->getMessage());
        throw new Exception("Error al obtener horarios");
    }
}
/**
 * Actualizar horarios de un doctor existente
 */
public function actualizarHorarios($id_doctor, $horarios) {
    try {
        $this->conn->beginTransaction();
        
        // Eliminar horarios anteriores
        $queryEliminar = "UPDATE doctor_horarios SET activo = 0 WHERE id_doctor = :id_doctor";
        $stmt = $this->conn->prepare($queryEliminar);
        $stmt->execute([':id_doctor' => $id_doctor]);
        
        // Insertar nuevos horarios
        if (!empty($horarios)) {
            $this->insertarHorarios($id_doctor, $horarios);
        }
        
        $this->conn->commit();
        return true;
        
    } catch (PDOException $e) {
        $this->conn->rollback();
        error_log("Error actualizando horarios: " . $e->getMessage());
        throw new Exception("Error al actualizar horarios");
    }
}
    /**
     * Obtener doctor por ID con toda la información
     */
    public function obtenerPorId($id_doctor) {
        try {
            $query = "SELECT d.*, u.cedula, u.username, u.nombres, u.apellidos, u.sexo, 
                             u.nacionalidad, u.correo, u.id_estado,
                             e.nombre_especialidad, est.nombre_estado
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      LEFT JOIN estados est ON u.id_estado = est.id_estado
                      WHERE d.id_doctor = :id_doctor";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctor) {
                // Obtener sucursales asignadas
                $doctor['sucursales'] = $this->obtenerSucursales($id_doctor);
            }
            
            return $doctor;
        } catch (PDOException $e) {
            error_log("Error obteniendo doctor: " . $e->getMessage());
            throw new Exception("Error al obtener el doctor");
        }
    }
    
    /**
     * Actualizar doctor (múltiples tablas)
     */
    public function actualizar($id_doctor, $datosUsuario, $datosDoctor, $sucursales = []) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Obtener id_usuario del doctor
            $queryIdUsuario = "SELECT id_usuario FROM doctores WHERE id_doctor = :id_doctor";
            $stmt = $this->conn->prepare($queryIdUsuario);
            $stmt->execute([':id_doctor' => $id_doctor]);
            $id_usuario = $stmt->fetchColumn();
            
            if (!$id_usuario) {
                throw new Exception("Doctor no encontrado");
            }
            
            // 2. Actualizar usuario
            $queryUsuario = "UPDATE usuarios SET 
                           cedula = :cedula,
                           username = :username,
                           nombres = :nombres,
                           apellidos = :apellidos,
                           sexo = :sexo,
                           nacionalidad = :nacionalidad,
                           correo = :correo,
                           id_estado = :id_estado
                           WHERE id_usuario = :id_usuario";
            
            $stmtUsuario = $this->conn->prepare($queryUsuario);
            $stmtUsuario->execute([
                ':cedula' => $datosUsuario['cedula'],
                ':username' => $datosUsuario['username'],
                ':nombres' => $datosUsuario['nombres'],
                ':apellidos' => $datosUsuario['apellidos'],
                ':sexo' => $datosUsuario['sexo'],
                ':nacionalidad' => $datosUsuario['nacionalidad'],
                ':correo' => $datosUsuario['correo'],
                ':id_estado' => $datosUsuario['id_estado'],
                ':id_usuario' => $id_usuario
            ]);
            
            // 3. Actualizar doctor
            $queryDoctor = "UPDATE doctores SET 
                          id_especialidad = :id_especialidad,
                          titulo_profesional = :titulo_profesional
                          WHERE id_doctor = :id_doctor";
            
            $stmtDoctor = $this->conn->prepare($queryDoctor);
            $stmtDoctor->execute([
                ':id_especialidad' => $datosDoctor['id_especialidad'],
                ':titulo_profesional' => $datosDoctor['titulo_profesional'],
                ':id_doctor' => $id_doctor
            ]);
            
            // 4. Actualizar sucursales
            // Eliminar asignaciones actuales
            $this->eliminarTodasSucursales($id_doctor);
            
            // Asignar nuevas sucursales
            if (!empty($sucursales)) {
                foreach ($sucursales as $id_sucursal) {
                    $this->asignarASucursal($id_doctor, $id_sucursal);
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Error actualizando doctor: " . $e->getMessage());
            throw new Exception("Error al actualizar el doctor: " . $e->getMessage());
        }
    }
    
    /**
     * Cambiar estado del doctor (usuario)
     */
    public function cambiarEstado($id_doctor, $nuevo_estado) {
        try {
            $query = "UPDATE usuarios u 
                      INNER JOIN doctores d ON u.id_usuario = d.id_usuario 
                      SET u.id_estado = :estado 
                      WHERE d.id_doctor = :id_doctor";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':estado' => $nuevo_estado
            ]);
        } catch (PDOException $e) {
            error_log("Error cambiando estado de doctor: " . $e->getMessage());
            throw new Exception("Error al cambiar estado del doctor");
        }
    }
    
    /**
     * Eliminar doctor (soft delete)
     */
    public function eliminar($id_doctor) {
        try {
            // Verificar si puede ser eliminado
            if (!$this->puedeEliminar($id_doctor)) {
                throw new Exception("No se puede eliminar el doctor porque tiene citas programadas o historial médico");
            }
            
            // Cambiar estado a inactivo en lugar de eliminar físicamente
            return $this->cambiarEstado($id_doctor, 4); // Estado "Inactivo"
        } catch (PDOException $e) {
            error_log("Error eliminando doctor: " . $e->getMessage());
            throw new Exception("Error al eliminar el doctor");
        }
    }
    
    // ===== MÉTODOS DE CONSULTA =====
    
    /**
     * Obtener todos los doctores con filtros
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
                                     OR e.nombre_especialidad LIKE :busqueda
                                     OR d.titulo_profesional LIKE :busqueda)";
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
                                     OR e.nombre_especialidad LIKE :busqueda
                                     OR d.titulo_profesional LIKE :busqueda)";
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
                    } elseif ($campo === 'id_sucursal') {
                        $where_conditions[] = "ds.id_sucursal = :id_sucursal";
                        $params[':id_sucursal'] = $valor;
                    }
                }
            }
            
            $join_sucursal = !empty($filtros['id_sucursal']) ? 
                "INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor" : 
                "LEFT JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor";
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT d.*, u.cedula, u.nombres, u.apellidos, u.sexo, u.correo, u.id_estado,
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
                      ORDER BY u.nombres, u.apellidos
                      LIMIT :inicio, :limite";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind de parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo doctores paginados: " . $e->getMessage());
            throw new Exception("Error al obtener doctores paginados");
        }
    }
    
    /**
     * Contar doctores con filtros
     */
    public function contar($busqueda = '', $filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Búsqueda de texto
            if (!empty($busqueda)) {
                $where_conditions[] = "(u.cedula LIKE :busqueda 
                                     OR u.nombres LIKE :busqueda 
                                     OR u.apellidos LIKE :busqueda 
                                     OR e.nombre_especialidad LIKE :busqueda
                                     OR d.titulo_profesional LIKE :busqueda)";
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
                    } elseif ($campo === 'id_sucursal') {
                        $where_conditions[] = "ds.id_sucursal = :id_sucursal";
                        $params[':id_sucursal'] = $valor;
                    }
                }
            }
            
            $join_sucursal = !empty($filtros['id_sucursal']) ? 
                "INNER JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor" : 
                "LEFT JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor";
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(DISTINCT d.id_doctor) as total 
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      $join_sucursal
                      $where_clause";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Error contando doctores: " . $e->getMessage());
            throw new Exception("Error al contar doctores");
        }
    }
    
    // ===== MÉTODOS DE GESTIÓN DE SUCURSALES =====
    
    /**
     * Asignar doctor a sucursal
     */
    public function asignarASucursal($id_doctor, $id_sucursal) {
        try {
            // Verificar si ya existe la asignación
            if ($this->existeEnSucursal($id_doctor, $id_sucursal)) {
                return true; // Ya existe, no hacer nada
            }
            
            $query = "INSERT INTO doctores_sucursales (id_doctor, id_sucursal) 
                      VALUES (:id_doctor, :id_sucursal)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':id_sucursal' => $id_sucursal
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error asignando doctor a sucursal: " . $e->getMessage());
            throw new Exception("Error al asignar doctor a sucursal");
        }
    }
    
    /**
     * Desasignar doctor de sucursal
     */
    public function desasignarDeSucursal($id_doctor, $id_sucursal) {
        try {
            $query = "DELETE FROM doctores_sucursales 
                      WHERE id_doctor = :id_doctor AND id_sucursal = :id_sucursal";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':id_sucursal' => $id_sucursal
            ]);
        } catch (PDOException $e) {
            error_log("Error desasignando doctor de sucursal: " . $e->getMessage());
            throw new Exception("Error al desasignar doctor de sucursal");
        }
    }
    
    /**
     * Eliminar todas las sucursales de un doctor
     */
    public function eliminarTodasSucursales($id_doctor) {
        try {
            $query = "DELETE FROM doctores_sucursales WHERE id_doctor = :id_doctor";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':id_doctor' => $id_doctor]);
        } catch (PDOException $e) {
            error_log("Error eliminando sucursales del doctor: " . $e->getMessage());
            throw new Exception("Error al eliminar sucursales del doctor");
        }
    }
    
    /**
     * Obtener sucursales asignadas a un doctor
     */
    public function obtenerSucursales($id_doctor) {
        try {
            $query = "SELECT s.*, ds.id_doctor_sucursal
                      FROM sucursales s
                      INNER JOIN doctores_sucursales ds ON s.id_sucursal = ds.id_sucursal
                      WHERE ds.id_doctor = :id_doctor
                      ORDER BY s.nombre_sucursal";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales del doctor: " . $e->getMessage());
            throw new Exception("Error al obtener sucursales del doctor");
        }
    }
    
    /**
     * Verificar si doctor está asignado a sucursal
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
            return false;
        }
    }
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    /**
     * Verificar si existe usuario por cédula
     */
    public function existeUsuarioPorCedula($cedula, $id_excluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE cedula = :cedula";
            $params = [':cedula' => $cedula];
            
            if ($id_excluir) {
                $query .= " AND id_usuario != :id_excluir";
                $params[':id_excluir'] = $id_excluir;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando cédula: " . $e->getMessage());
            throw new Exception("Error al verificar cédula");
        }
    }
    
    /**
     * Verificar si existe usuario por username
     */
    public function existeUsuarioPorUsername($username, $id_excluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE username = :username";
            $params = [':username' => $username];
            
            if ($id_excluir) {
                $query .= " AND id_usuario != :id_excluir";
                $params[':id_excluir'] = $id_excluir;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando username: " . $e->getMessage());
            throw new Exception("Error al verificar username");
        }
    }
    
    /**
     * Verificar si existe usuario por correo
     */
    public function existeUsuarioPorCorreo($correo, $id_excluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE correo = :correo";
            $params = [':correo' => $correo];
            
            if ($id_excluir) {
                $query .= " AND id_usuario != :id_excluir";
                $params[':id_excluir'] = $id_excluir;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando correo: " . $e->getMessage());
            throw new Exception("Error al verificar correo");
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
    
    // ===== MÉTODOS ESPECÍFICOS PARA SISTEMA DE CITAS =====
    
    /**
     * Obtener doctores por especialidad y sucursal
     */
    public function obtenerPorEspecialidadYSucursal($id_especialidad, $id_sucursal) {
        try {
            $query = "SELECT d.*, u.nombres, u.apellidos, u.cedula,
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
     * Obtener estadísticas de doctores
     */
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_doctores,
                        COUNT(CASE WHEN u.id_estado = 1 THEN 1 END) as doctores_activos,
                        COUNT(CASE WHEN u.id_estado = 4 THEN 1 END) as doctores_inactivos,
                        COUNT(DISTINCT d.id_especialidad) as especialidades_cubiertas,
                        COUNT(DISTINCT ds.id_sucursal) as sucursales_con_doctores
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      LEFT JOIN doctores_sucursales ds ON d.id_doctor = ds.id_doctor";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de doctores: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }

    /**
 * Verificar si tiene citas activas
 */
public function tieneCitasActivas($id_doctor) {
    try {
        $query = "SELECT COUNT(*) as total 
                  FROM citas 
                  WHERE id_doctor = :id_doctor 
                    AND estado IN ('Pendiente', 'Confirmada') 
                    AND fecha_hora >= NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_doctor' => $id_doctor]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    } catch (PDOException $e) {
        error_log("Error verificando citas activas: " . $e->getMessage());
        return 0;
    }
}
}
?>