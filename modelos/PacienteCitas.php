<?php
require_once __DIR__ . "/../config/database.php";

class PacienteCitas {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    /**
     * Obtener historial completo de citas de un paciente con filtros
     */
    public function obtenerHistorialCitas($id_paciente, $filtros = []) {
        try {
            $conditions = ["c.id_paciente = :id_paciente"];
            $params = [':id_paciente' => $id_paciente];
            
            // Filtro por fecha
            if (!empty($filtros['fecha_desde'])) {
                $conditions[] = "DATE(c.fecha_hora) >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $conditions[] = "DATE(c.fecha_hora) <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }
            
            // Filtro por estado
            if (!empty($filtros['estado'])) {
                $conditions[] = "c.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            // Filtro por tipo de cita
            if (!empty($filtros['tipo_cita'])) {
                $conditions[] = "c.tipo_cita = :tipo_cita";
                $params[':tipo_cita'] = $filtros['tipo_cita'];
            }
            
            // Filtro por especialidad
            if (!empty($filtros['especialidad'])) {
                $conditions[] = "e.id_especialidad = :especialidad";
                $params[':especialidad'] = $filtros['especialidad'];
            }
            
            // Búsqueda por texto
            if (!empty($filtros['busqueda'])) {
                $conditions[] = "(c.motivo LIKE :busqueda OR 
                                CONCAT(u.nombres, ' ', u.apellidos) LIKE :busqueda OR 
                                e.nombre_especialidad LIKE :busqueda OR 
                                s.nombre_sucursal LIKE :busqueda)";
                $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            $query = "
                SELECT 
                    c.id_cita,
                    c.fecha_hora,
                    c.motivo,
                    c.tipo_cita,
                    c.estado,
                    c.notas,
                    c.enlace_virtual,
                    c.sala_virtual,
                    c.fecha_creacion,
                    
                    -- Datos del doctor
                    CONCAT(u.nombres, ' ', u.apellidos) as doctor_nombre,
                    u.correo as doctor_correo,
                    d.titulo_profesional,
                    
                    -- Datos de la especialidad
                    e.nombre_especialidad,
                    e.descripcion as especialidad_descripcion,
                    
                    -- Datos de la sucursal
                    s.nombre_sucursal,
                    s.direccion as sucursal_direccion,
                    s.telefono as sucursal_telefono,
                    s.horario_atencion,
                    
                    -- Datos del triaje (si existe)
                    t.id_triage,
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
                    t.observaciones as triaje_observaciones,
                    t.fecha_hora as triaje_fecha,
                    
                    -- Datos del tipo de cita
                    tc.nombre_tipo,
                    tc.descripcion as tipo_descripcion
                    
                FROM citas c
                INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                INNER JOIN tipos_cita tc ON c.id_tipo_cita = tc.id_tipo_cita
                LEFT JOIN triage t ON c.id_cita = t.id_cita
                
                WHERE {$whereClause}
                ORDER BY c.fecha_hora DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada cita para agregar información adicional
            foreach ($citas as &$cita) {
                // Formatear fechas
                $cita['fecha_formateada'] = $this->formatearFecha($cita['fecha_hora']);
                $cita['hora_formateada'] = $this->formatearHora($cita['fecha_hora']);
                
                // Determinar estado visual
                $cita['estado_clase'] = $this->obtenerClaseEstado($cita['estado']);
                $cita['estado_color'] = $this->obtenerColorEstado($cita['estado']);
                
                // Verificar si la cita ya pasó
                $cita['cita_pasada'] = strtotime($cita['fecha_hora']) < time();
                
                // Obtener consultas médicas asociadas (si existen)
                $cita['consultas'] = $this->obtenerConsultasPorCita($cita['id_cita']);
                
                // Obtener recetas médicas (si existen)
                $cita['recetas'] = $this->obtenerRecetasPorCita($cita['id_cita']);
            }
            
            return $citas;
            
        } catch (PDOException $e) {
            error_log("Error obteniendo historial de citas: " . $e->getMessage());
            throw new Exception("Error al obtener el historial de citas");
        }
    }
    
    /**
     * Obtener detalles específicos de una cita - CONSULTA CORREGIDA
     */
    /**
 * Obtener detalles específicos de una cita - CON VALIDACIONES MEJORADAS
 */
public function obtenerDetalleCita($id_cita, $id_paciente) {
    try {
        $query = "
            SELECT 
                c.*,
                
                -- Datos del doctor
                CONCAT(u.nombres, ' ', u.apellidos) as doctor_nombre,
                u.correo as doctor_correo,
                d.titulo_profesional,
                
                -- Datos de la especialidad
                e.nombre_especialidad,
                e.descripcion as especialidad_descripcion,
                
                -- Datos de la sucursal
                s.nombre_sucursal,
                s.direccion as sucursal_direccion,
                s.telefono as sucursal_telefono,
                s.email as sucursal_email,
                s.horario_atencion,
                
                -- Datos del triaje completo
                t.id_triage,
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
                t.observaciones as triaje_observaciones,
                t.fecha_hora as triaje_fecha,
                CONCAT(enf.nombres, ' ', enf.apellidos) as enfermero_nombre,
                
                -- Datos del tipo de cita
                tc.nombre_tipo,
                tc.descripcion as tipo_descripcion
                
            FROM citas c
            INNER JOIN doctores d ON c.id_doctor = d.id_doctor
            INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
            INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
            INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
            INNER JOIN tipos_cita tc ON c.id_tipo_cita = tc.id_tipo_cita
            LEFT JOIN triage t ON c.id_cita = t.id_cita
            LEFT JOIN usuarios enf ON t.id_enfermero = enf.id_usuario
            
            WHERE c.id_cita = :id_cita AND c.id_paciente = :id_paciente
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id_cita' => $id_cita,
            ':id_paciente' => $id_paciente
        ]);
        
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cita) {
            // Agregar información adicional CON VALIDACIONES
            $cita['fecha_formateada'] = $this->formatearFecha($cita['fecha_hora']);
            $cita['hora_formateada'] = $this->formatearHora($cita['fecha_hora']);
            $cita['estado_clase'] = $this->obtenerClaseEstado($cita['estado']);
            
            // Validar fecha antes de usar strtotime
            if (!empty($cita['fecha_hora'])) {
                $cita['cita_pasada'] = strtotime($cita['fecha_hora']) < time();
            } else {
                $cita['cita_pasada'] = false;
            }
            
            // Obtener consultas médicas
            $cita['consultas'] = $this->obtenerConsultasPorCita($id_cita);
            
            // Obtener recetas médicas
            $cita['recetas'] = $this->obtenerRecetasPorCita($id_cita);
            
            // Obtener historial clínico relacionado
            $cita['historial'] = $this->obtenerHistorialPorCita($id_cita);
        }
        
        return $cita;
        
    } catch (PDOException $e) {
        error_log("Error obteniendo detalle de cita: " . $e->getMessage());
        throw new Exception("Error al obtener los detalles de la cita");
    }
}
    
    /**
     * Obtener próximas citas del paciente
     */
    public function obtenerProximasCitas($id_paciente, $limite = 5) {
        try {
            $query = "
                SELECT 
                    c.id_cita,
                    c.fecha_hora,
                    c.motivo,
                    c.tipo_cita,
                    c.estado,
                    CONCAT(u.nombres, ' ', u.apellidos) as doctor_nombre,
                    e.nombre_especialidad,
                    s.nombre_sucursal
                    
                FROM citas c
                INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                
                WHERE c.id_paciente = :id_paciente 
                AND c.fecha_hora >= NOW()
                AND c.estado IN ('Pendiente', 'Confirmada')
                
                ORDER BY c.fecha_hora ASC
                LIMIT :limite
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_paciente', $id_paciente, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo próximas citas: " . $e->getMessage());
            throw new Exception("Error al obtener las próximas citas");
        }
    }
    
    /**
     * Obtener estadísticas del paciente
     */
    public function obtenerEstadisticasPaciente($id_paciente) {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_citas,
                    SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) as citas_completadas,
                    SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as citas_pendientes,
                    SUM(CASE WHEN estado = 'Cancelada' THEN 1 ELSE 0 END) as citas_canceladas,
                    SUM(CASE WHEN tipo_cita = 'virtual' THEN 1 ELSE 0 END) as citas_virtuales,
                    SUM(CASE WHEN tipo_cita = 'presencial' THEN 1 ELSE 0 END) as citas_presenciales
                FROM citas 
                WHERE id_paciente = :id_paciente
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Buscar citas por rango de fechas
     */
    public function buscarPorRangoFechas($id_paciente, $fecha_inicio, $fecha_fin) {
        return $this->obtenerHistorialCitas($id_paciente, [
            'fecha_desde' => $fecha_inicio,
            'fecha_hasta' => $fecha_fin
        ]);
    }
    
    /**
     * Obtener especialidades que ha visitado el paciente
     */
    public function obtenerEspecialidadesVisitadas($id_paciente) {
        try {
            $query = "
                SELECT DISTINCT 
                    e.id_especialidad,
                    e.nombre_especialidad,
                    COUNT(c.id_cita) as total_citas
                FROM citas c
                INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                WHERE c.id_paciente = :id_paciente
                GROUP BY e.id_especialidad, e.nombre_especialidad
                ORDER BY total_citas DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_paciente' => $id_paciente]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades visitadas: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades");
        }
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    /**
     * Obtener consultas médicas de una cita
     */
    /**
 * Obtener consultas médicas de una cita - CAMPOS CORREGIDOS
 */
/**
 * Obtener consultas médicas de una cita - CORREGIDO
 */
private function obtenerConsultasPorCita($id_cita) {
    try {
        // Verificar si existe tabla consultas_medicas
        $query = "SHOW TABLES LIKE 'consultas_medicas'";
        $stmt = $this->conn->query($query);
        
        if ($stmt->rowCount() === 0) {
            return []; // La tabla no existe aún
        }
        
        // Debug - Ver qué consultas existen para esta cita
        error_log("Buscando consultas para cita ID: " . $id_cita);
        
        $query = "
            SELECT 
                cm.id_consulta,
                cm.id_cita,
                cm.id_historial,
                cm.fecha_hora as fecha_consulta,
                cm.motivo_consulta,
                cm.sintomatologia,
                cm.diagnostico,
                cm.tratamiento,
                cm.observaciones,
                cm.fecha_seguimiento,
                CONCAT(u.nombres, ' ', u.apellidos) as medico_nombre
            FROM consultas_medicas cm
            INNER JOIN citas c ON cm.id_cita = c.id_cita
            INNER JOIN doctores d ON c.id_doctor = d.id_doctor
            INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
            WHERE cm.id_cita = :id_cita
            ORDER BY cm.fecha_hora DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_cita' => $id_cita]);
        
        $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug
        error_log("Consultas encontradas: " . count($consultas));
        if (count($consultas) > 0) {
            error_log("Primera consulta: " . print_r($consultas[0], true));
        }
        
        return $consultas;
        
    } catch (PDOException $e) {
        error_log("Error obteniendo consultas: " . $e->getMessage());
        return [];
    }
}
    
    /**
     * Obtener recetas médicas de una cita
     */
    private function obtenerRecetasPorCita($id_cita) {
        try {
            // Verificar si existe tabla recetas_medicas
            $query = "SHOW TABLES LIKE 'recetas_medicas'";
            $stmt = $this->conn->query($query);
            
            if ($stmt->rowCount() === 0) {
                return []; // La tabla no existe aún
            }
            
            $query = "
                SELECT rm.*
                FROM recetas_medicas rm
                WHERE rm.id_cita = :id_cita
                ORDER BY rm.fecha_creacion DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_cita' => $id_cita]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo recetas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener historial clínico relacionado
     */
    private function obtenerHistorialPorCita($id_cita) {
        try {
            $query = "
                SELECT hc.*
                FROM historiales_clinicos hc
                INNER JOIN citas c ON hc.id_paciente = c.id_paciente
                WHERE c.id_cita = :id_cita
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_cita' => $id_cita]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Formatear fecha para mostrar
     */
    /**
 * Formatear fecha para mostrar - CON VALIDACIÓN
 */
private function formatearFecha($fecha_hora) {
    // Validar que la fecha no sea null o vacía
    if (empty($fecha_hora) || is_null($fecha_hora)) {
        return 'Fecha no disponible';
    }
    
    try {
        $fecha = new DateTime($fecha_hora);
        $dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        $dia_semana = $dias_semana[$fecha->format('w')];
        $dia = $fecha->format('d');
        $mes = $meses[$fecha->format('n')];
        $año = $fecha->format('Y');
        
        return "{$dia_semana}, {$dia} de {$mes} de {$año}";
    } catch (Exception $e) {
        error_log("Error formateando fecha: " . $e->getMessage());
        return 'Fecha inválida';
    }
}

/**
 * Formatear hora para mostrar - CON VALIDACIÓN
 */
private function formatearHora($fecha_hora) {
    // Validar que la fecha no sea null o vacía
    if (empty($fecha_hora) || is_null($fecha_hora)) {
        return '--:--';
    }
    
    try {
        $fecha = new DateTime($fecha_hora);
        return $fecha->format('H:i');
    } catch (Exception $e) {
        error_log("Error formateando hora: " . $e->getMessage());
        return '--:--';
    }
}
    /**
     * Obtener clase CSS según estado
     */
    private function obtenerClaseEstado($estado) {
        $clases = [
            'Pendiente' => 'warning',
            'Confirmada' => 'info',
            'Completada' => 'success',
            'Cancelada' => 'danger',
            'No Asistio' => 'secondary'
        ];
        
        return $clases[$estado] ?? 'secondary';
    }
    
    /**
     * Obtener color según estado
     */
    private function obtenerColorEstado($estado) {
        $colores = [
            'Pendiente' => '#ffd166',
            'Confirmada' => '#4cc9f0',
            'Completada' => '#06d6a0',
            'Cancelada' => '#ef476f',
            'No Asistio' => '#6c757d'
        ];
        
        return $colores[$estado] ?? '#6c757d';
    }
}
?>