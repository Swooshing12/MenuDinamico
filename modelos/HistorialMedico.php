<?php
require_once __DIR__ . "/../config/database.php";

class HistorialMedico {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    /**
     * 🔍 Buscar paciente por cédula y obtener información básica
     */
    public function buscarPacientePorCedula($cedula) {
        try {
            $query = "SELECT 
                        p.id_paciente,
                        u.nombres,
                        u.apellidos,
                        u.cedula,
                        u.correo,
                        u.sexo,
                        u.nacionalidad,
                        p.fecha_nacimiento,
                        p.tipo_sangre,
                        p.alergias,
                        p.antecedentes_medicos,
                        p.contacto_emergencia,
                        p.telefono_emergencia,
                        p.telefono,
                        TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad
                     FROM pacientes p
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
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
     * 📋 Obtener historial médico completo del paciente con filtros
     */
    public function obtenerHistorialCompleto($id_paciente, $filtros = []) {
        try {
            $whereConditions = ["c.id_paciente = :id_paciente"];
            $parametros = [':id_paciente' => $id_paciente];
            
            // ⭐ FILTRO POR FECHAS
            if (!empty($filtros['fecha_desde'])) {
                $whereConditions[] = "DATE(c.fecha_hora) >= :fecha_desde";
                $parametros[':fecha_desde'] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $whereConditions[] = "DATE(c.fecha_hora) <= :fecha_hasta";
                $parametros[':fecha_hasta'] = $filtros['fecha_hasta'];
            }
            
            // ⭐ FILTRO POR ESPECIALIDAD
            if (!empty($filtros['id_especialidad'])) {
                $whereConditions[] = "e.id_especialidad = :id_especialidad";
                $parametros[':id_especialidad'] = $filtros['id_especialidad'];
            }
            
            // ⭐ FILTRO POR DOCTOR
            if (!empty($filtros['id_doctor'])) {
                $whereConditions[] = "c.id_doctor = :id_doctor";
                $parametros[':id_doctor'] = $filtros['id_doctor'];
            }
            
            // ⭐ FILTRO POR ESTADO DE CITA
            if (!empty($filtros['estado'])) {
                $whereConditions[] = "c.estado = :estado";
                $parametros[':estado'] = $filtros['estado'];
            }
            
            // ⭐ FILTRO POR SUCURSAL
            if (!empty($filtros['id_sucursal'])) {
                $whereConditions[] = "c.id_sucursal = :id_sucursal";
                $parametros[':id_sucursal'] = $filtros['id_sucursal'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $query = "SELECT 
                        -- Datos de la cita
                        c.id_cita,
                        c.fecha_hora,
                        c.motivo as motivo_cita,
                        c.estado as estado_cita,
                        c.tipo_cita,
                        c.notas as notas_cita,
                        
                        -- Datos del doctor
                        CONCAT(ud.nombres, ' ', ud.apellidos) as doctor_nombre,
                        e.nombre_especialidad,
                        
                        -- Datos de la sucursal
                        s.nombre_sucursal,
                        s.direccion as direccion_sucursal,
                        
                        -- Datos del triaje (si existe)
                        t.id_triage,
                        t.fecha_hora as fecha_triaje,
                        t.nivel_urgencia,
                        t.estado_triaje,
                        t.temperatura,
                        t.presion_arterial,
                        t.frecuencia_cardiaca,
                        t.frecuencia_respiratoria,
                        t.saturacion_oxigeno,
                        t.peso,
                        t.talla,
                        t.imc,
                        t.observaciones as observaciones_triaje,
                        CONCAT(ue.nombres, ' ', ue.apellidos) as enfermero_triaje,
                        
                        -- Datos de la consulta médica (si existe)
                        cm.id_consulta,
                        cm.fecha_hora as fecha_consulta,
                        cm.motivo_consulta,
                        cm.sintomatologia,
                        cm.diagnostico,
                        cm.tratamiento,
                        cm.observaciones as observaciones_consulta,
                        cm.fecha_seguimiento,
                        
                        -- Estado general del proceso
                        CASE 
                            WHEN cm.id_consulta IS NOT NULL THEN 'Consulta Completada'
                            WHEN t.id_triage IS NOT NULL THEN 'Triaje Completado'
                            ELSE 'Cita Programada'
                        END as estado_proceso
                        
                     FROM citas c
                     INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                     INNER JOIN usuarios ud ON d.id_usuario = ud.id_usuario
                     INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                     INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                     LEFT JOIN triage t ON c.id_cita = t.id_cita
                     LEFT JOIN usuarios ue ON t.id_enfermero = ue.id_usuario
                     LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                     
                     WHERE {$whereClause}
                     
                     ORDER BY c.fecha_hora DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($parametros);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo historial completo: " . $e->getMessage());
            throw new Exception("Error al obtener el historial médico");
        }
    }
    
    /**
     * 📊 Obtener estadísticas del historial del paciente
     */
    public function obtenerEstadisticasHistorial($id_paciente) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_citas,
                        SUM(CASE WHEN c.estado = 'Completada' THEN 1 ELSE 0 END) as citas_completadas,
                        SUM(CASE WHEN cm.id_consulta IS NOT NULL THEN 1 ELSE 0 END) as consultas_realizadas,
                        SUM(CASE WHEN t.id_triage IS NOT NULL THEN 1 ELSE 0 END) as triajes_realizados,
                        COUNT(DISTINCT c.id_doctor) as doctores_diferentes,
                        COUNT(DISTINCT e.id_especialidad) as especialidades_visitadas,
                        MIN(c.fecha_hora) as primera_cita,
                        MAX(c.fecha_hora) as ultima_cita
                     FROM citas c
                     LEFT JOIN doctores d ON c.id_doctor = d.id_doctor
                     LEFT JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                     LEFT JOIN triage t ON c.id_cita = t.id_cita
                     LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                     WHERE c.id_paciente = :id_paciente";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas del historial: " . $e->getMessage());
            throw new Exception("Error al obtener las estadísticas");
        }
    }
    
    /**
     * 🏥 Obtener especialidades para filtros
     */
    public function obtenerEspecialidades() {
        try {
            $query = "SELECT id_especialidad, nombre_especialidad 
                     FROM especialidades 
                     ORDER BY nombre_especialidad";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades: " . $e->getMessage());
            throw new Exception("Error al obtener las especialidades");
        }
    }
    
    /**
     * 🏨 Obtener sucursales para filtros
     */
    public function obtenerSucursales() {
        try {
            $query = "SELECT id_sucursal, nombre_sucursal 
                     FROM sucursales 
                     WHERE estado = 1 
                     ORDER BY nombre_sucursal";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo sucursales: " . $e->getMessage());
            throw new Exception("Error al obtener las sucursales");
        }
    }
    
    /**
     * 👨‍⚕️ Obtener doctores que han atendido al paciente para filtros
     */
    public function obtenerDoctoresPaciente($id_paciente) {
        try {
            $query = "SELECT DISTINCT 
                        d.id_doctor,
                        CONCAT(u.nombres, ' ', u.apellidos) as doctor_nombre,
                        e.nombre_especialidad
                     FROM citas c
                     INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                     INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                     INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                     WHERE c.id_paciente = :id_paciente
                     ORDER BY doctor_nombre";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo doctores del paciente: " . $e->getMessage());
            throw new Exception("Error al obtener los doctores");
        }
    }
    
    /**
     * 🔍 Buscar en el historial por término general
     */
    public function buscarEnHistorial($id_paciente, $termino_busqueda) {
        try {
            $termino = "%{$termino_busqueda}%";
            
            $query = "SELECT 
                        c.id_cita,
                        c.fecha_hora,
                        c.motivo as motivo_cita,
                        CONCAT(ud.nombres, ' ', ud.apellidos) as doctor_nombre,
                        e.nombre_especialidad,
                        cm.diagnostico,
                        cm.tratamiento,
                        t.observaciones as observaciones_triaje,
                        cm.observaciones as observaciones_consulta
                     FROM citas c
                     INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                     INNER JOIN usuarios ud ON d.id_usuario = ud.id_usuario
                     INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                     LEFT JOIN triage t ON c.id_cita = t.id_cita
                     LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                     WHERE c.id_paciente = :id_paciente
                     AND (
                        c.motivo LIKE :termino
                        OR cm.diagnostico LIKE :termino
                        OR cm.tratamiento LIKE :termino
                        OR cm.sintomatologia LIKE :termino
                        OR t.observaciones LIKE :termino
                        OR cm.observaciones LIKE :termino
                        OR e.nombre_especialidad LIKE :termino
                     )
                     ORDER BY c.fecha_hora DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_paciente' => $id_paciente,
                ':termino' => $termino
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error buscando en historial: " . $e->getMessage());
            throw new Exception("Error al buscar en el historial");
        }
    }
    
   /**
 * 📄 Obtener detalle completo de una cita específica - CORREGIDO
 */
public function obtenerDetalleCita($id_cita) {
    try {
        $query = "SELECT 
                    -- Datos específicos de la cita
                    c.id_cita,
                    c.id_paciente,
                    c.id_doctor,
                    c.id_sucursal,
                    c.id_tipo_cita,
                    c.fecha_hora as fecha_hora_cita,
                    c.motivo,
                    c.tipo_cita,
                    c.estado,
                    c.fecha_creacion,
                    c.notas,
                    c.enlace_virtual,
                    c.sala_virtual,
                    
                    -- Datos del paciente
                    CONCAT(up.nombres, ' ', up.apellidos) as paciente_nombre,
                    up.cedula as paciente_cedula,
                    up.sexo,
                    up.nacionalidad,
                    up.correo,
                    p.fecha_nacimiento,
                    p.tipo_sangre,
                    p.alergias,
                    p.telefono,
                    p.contacto_emergencia,
                    p.telefono_emergencia,
                    p.antecedentes_medicos,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                    
                    -- Datos del doctor
                    CONCAT(ud.nombres, ' ', ud.apellidos) as doctor_nombre,
                    e.nombre_especialidad,
                    
                    -- Datos de la sucursal
                    s.nombre_sucursal,
                    s.direccion as direccion_sucursal,
                    
                    -- Datos completos del triaje
                    t.id_triage,
                    t.fecha_hora as fecha_hora_triaje,
                    t.nivel_urgencia,
                    t.estado_triaje,
                    t.temperatura,
                    t.presion_arterial,
                    t.frecuencia_cardiaca,
                    t.frecuencia_respiratoria,
                    t.saturacion_oxigeno,
                    t.peso,
                    t.talla,
                    t.imc,
                    t.observaciones as observaciones_triaje,
                    CONCAT(ue.nombres, ' ', ue.apellidos) as enfermero_nombre,
                    
                    -- Datos completos de la consulta médica
                    cm.id_consulta,
                    cm.fecha_hora as fecha_hora_consulta,
                    cm.motivo_consulta,
                    cm.sintomatologia,
                    cm.diagnostico,
                    cm.tratamiento,
                    cm.observaciones as observaciones_consulta,
                    cm.fecha_seguimiento
                    
                 FROM citas c
                 INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                 INNER JOIN usuarios up ON p.id_usuario = up.id_usuario
                 INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                 INNER JOIN usuarios ud ON d.id_usuario = ud.id_usuario
                 INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                 INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                 LEFT JOIN triage t ON c.id_cita = t.id_cita
                 LEFT JOIN usuarios ue ON t.id_enfermero = ue.id_usuario
                 LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                 
                 WHERE c.id_cita = :id_cita";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_cita' => $id_cita]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error obteniendo detalle de cita: " . $e->getMessage());
        throw new Exception("Error al obtener el detalle de la cita");
    }
}
}
?>