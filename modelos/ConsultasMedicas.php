<?php
/**
 * Modelo para gestión de Consultas Médicas
 * Maneja todas las operaciones CRUD de las consultas médicas
 */

require_once __DIR__ . "/../config/database.php";

class ConsultasMedicas {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    /**
     * Crear nueva consulta médica
     */
    public function crearConsulta($datos) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Insertar consulta médica
            $query = "INSERT INTO consultas_medicas (
                        id_cita, id_historial, motivo_consulta, sintomatologia, 
                        diagnostico, tratamiento, observaciones, fecha_seguimiento
                      ) VALUES (
                        :id_cita, :id_historial, :motivo_consulta, :sintomatologia, 
                        :diagnostico, :tratamiento, :observaciones, :fecha_seguimiento
                      )";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_cita' => $datos['id_cita'],
                ':id_historial' => $datos['id_historial'],
                ':motivo_consulta' => $datos['motivo_consulta'],
                ':sintomatologia' => $datos['sintomatologia'],
                ':diagnostico' => $datos['diagnostico'],
                ':tratamiento' => $datos['tratamiento'],
                ':observaciones' => $datos['observaciones'],
                ':fecha_seguimiento' => $datos['fecha_seguimiento'] ?: null
            ]);
            
            $id_consulta = $this->conn->lastInsertId();
            
            // 2. Actualizar estado de la cita
            $queryUpdate = "UPDATE citas SET estado = 'Completada' WHERE id_cita = :id_cita";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->execute([':id_cita' => $datos['id_cita']]);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'id_consulta' => $id_consulta,
                'message' => 'Consulta médica registrada exitosamente'
            ];
            
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Error creando consulta médica: " . $e->getMessage());
            throw new Exception("Error al registrar la consulta médica");
        }
    }
    
    /**
     * Obtener citas con triaje completado para médico
     */
    /**
 * Obtener citas con triaje completado para médico
 */
/**
 * Obtener citas con triaje completado para médico
 */
// En el archivo ConsultasMedicas.php, modificar la consulta:
public function obtenerCitasConTriaje($id_doctor, $fecha = null) {
    try {
        $fecha = $fecha ?: date('Y-m-d');
        
        $query = "SELECT c.id_cita, c.fecha_hora, c.motivo, c.estado,
                         c.id_paciente,  -- ⭐ IMPORTANTE: AGREGAR ESTA LÍNEA
                         p.nombres as nombres_paciente, p.apellidos as apellidos_paciente,
                         p.cedula as cedula_paciente,
                         pac.fecha_nacimiento, pac.tipo_sangre, pac.alergias,
                         e.nombre_especialidad,
                         s.nombre_sucursal,
                         t.peso, t.talla, t.presion_arterial, t.frecuencia_cardiaca,
                         t.temperatura, t.frecuencia_respiratoria, t.saturacion_oxigeno,
                         t.nivel_urgencia, t.observaciones, t.imc,
                         CASE 
                           WHEN t.nivel_urgencia >= 4 THEN 'Urgente'
                           WHEN t.nivel_urgencia = 3 THEN 'Moderada' 
                           ELSE 'Baja'
                         END as prioridad,
                         hc.id_historial,
                         CASE WHEN cm.id_consulta IS NOT NULL THEN 1 ELSE 0 END as tiene_consulta
                  FROM citas c
                  INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                  INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                  INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                  INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                  INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                  INNER JOIN triage t ON c.id_cita = t.id_cita
                  LEFT JOIN historiales_clinicos hc ON pac.id_paciente = hc.id_paciente
                  LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                  WHERE c.id_doctor = :id_doctor 
                  AND DATE(c.fecha_hora) = :fecha
                  AND c.estado IN ('Pendiente', 'Confirmada')
                  ORDER BY 
                    t.nivel_urgencia DESC,
                    c.fecha_hora ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id_doctor' => $id_doctor,
            ':fecha' => $fecha
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("ERROR SQL en obtenerCitasConTriaje: " . $e->getMessage());
        throw new Exception("Error al obtener las citas: " . $e->getMessage());
    }
}
    
    /**
     * Obtener historial clínico del paciente
     */
    public function obtenerHistorialPaciente($id_paciente) {
        try {
            $query = "SELECT cm.*, c.fecha_hora as fecha_cita, c.motivo as motivo_cita,
                             d.nombres as doctor_nombres, d.apellidos as doctor_apellidos,
                             e.nombre_especialidad
                      FROM consultas_medicas cm
                      INNER JOIN citas c ON cm.id_cita = c.id_cita
                      INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                      INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                      INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                      INNER JOIN historiales_clinicos hc ON cm.id_historial = hc.id_historial
                      WHERE hc.id_paciente = :id_paciente
                      ORDER BY cm.fecha_hora DESC
                      LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            throw new Exception("Error al obtener el historial");
        }
    }
    
    /**
     * Crear historial clínico si no existe
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
     * Obtener consulta específica
     */
    public function obtenerConsultaPorId($id_consulta) {
        try {
            $query = "SELECT cm.*, c.fecha_hora as fecha_cita, c.motivo as motivo_cita,
                             p.nombres as nombres_paciente, p.apellidos as apellidos_paciente,
                             p.cedula as cedula_paciente
                      FROM consultas_medicas cm
                      INNER JOIN citas c ON cm.id_cita = c.id_cita
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      WHERE cm.id_consulta = :id_consulta";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_consulta' => $id_consulta]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo consulta: " . $e->getMessage());
            throw new Exception("Error al obtener la consulta");
        }
    }
    
    /**
     * Obtener estadísticas del médico
     */
    public function obtenerEstadisticasMedico($id_doctor) {
        try {
            $query = "SELECT 
                        COUNT(CASE WHEN DATE(c.fecha_hora) = CURDATE() THEN 1 END) as citas_hoy,
                        COUNT(CASE WHEN DATE(c.fecha_hora) = CURDATE() AND cm.id_consulta IS NOT NULL THEN 1 END) as consultas_hoy,
                        COUNT(CASE WHEN DATE(c.fecha_hora) = CURDATE() AND t.id_triage IS NOT NULL AND cm.id_consulta IS NULL THEN 1 END) as pendientes_hoy,
                        COUNT(CASE WHEN DATE(c.fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as citas_semana
                      FROM citas c
                      LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                      LEFT JOIN triage t ON c.id_cita = t.id_cita
                      WHERE c.id_doctor = :id_doctor";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Verificar si el médico tiene acceso a la cita
     */
    public function verificarAccesoCita($id_cita, $id_doctor) {
        try {
            $query = "SELECT COUNT(*) FROM citas WHERE id_cita = :id_cita AND id_doctor = :id_doctor";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_cita' => $id_cita,
                ':id_doctor' => $id_doctor
            ]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error verificando acceso: " . $e->getMessage());
            return false;
        }
    }
}
?>