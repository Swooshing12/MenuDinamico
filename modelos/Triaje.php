<?php
/**
 * Modelo para gestión de Triaje
 * Maneja signos vitales, síntomas y nivel de urgencia
 */

require_once __DIR__ . "/../config/database.php";

class Triaje {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // ===== CREAR TRIAJE =====
    
    /**
     * Crear nuevo triaje con estado_triaje
     */
    public function crear(array $datos): array {
        try {
            $this->conn->beginTransaction();
            
            // ✅ Calcular estado del triaje basado en urgencia
            $estado_triaje = $this->calcularEstadoTriaje($datos['nivel_urgencia']);
            
            $query = "INSERT INTO triage 
                (id_cita, id_enfermero, nivel_urgencia, estado_triaje,
                 temperatura, presion_arterial, frecuencia_cardiaca, 
                 frecuencia_respiratoria, saturacion_oxigeno, peso, 
                 talla, imc, observaciones)
              VALUES 
                (:id_cita, :id_enfermero, :nivel_urgencia, :estado_triaje,
                 :temperatura, :presion_arterial, :frecuencia_cardiaca,
                 :frecuencia_respiratoria, :saturacion_oxigeno, :peso,
                 :talla, :imc, :observaciones)";
            
            $stmt = $this->conn->prepare($query);
            $resultado = $stmt->execute([
                ':id_cita' => $datos['id_cita'],
                ':id_enfermero' => $datos['id_enfermero'],
                ':nivel_urgencia' => $datos['nivel_urgencia'],
                ':estado_triaje' => $estado_triaje, // ⭐ NUEVO CAMPO
                ':temperatura' => $datos['temperatura'],
                ':presion_arterial' => $datos['presion_arterial'],
                ':frecuencia_cardiaca' => $datos['frecuencia_cardiaca'],
                ':frecuencia_respiratoria' => $datos['frecuencia_respiratoria'],
                ':saturacion_oxigeno' => $datos['saturacion_oxigeno'],
                ':peso' => $datos['peso'],
                ':talla' => $datos['talla'],
                ':imc' => $datos['imc'],
                ':observaciones' => $datos['observaciones']
            ]);
            
            if (!$resultado) {
                throw new Exception("Error al insertar triaje");
            }
            
            $id_triage = $this->conn->lastInsertId();
            
            // ✅ NO CAMBIAR EL ESTADO DE LA CITA (mantener Confirmada/Pendiente)
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'id_triage' => $id_triage,
                'estado_triaje' => $estado_triaje,
                'message' => 'Triaje registrado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error creando triaje: " . $e->getMessage());
            throw new Exception("Error al registrar el triaje: " . $e->getMessage());
        }
    }
    
    // ===== OBTENER TRIAJES =====
    
    /**
     * Obtener triaje por ID de cita (con estado_triaje)
     */
    public function obtenerPorCita(int $id_cita): ?array {
        try {
            $query = "SELECT t.*, t.estado_triaje, -- ⭐ INCLUIR ESTADO_TRIAJE
                             u.nombres, u.apellidos as apellidos_enfermero,
                             c.fecha_hora as fecha_cita,
                             p.nombres as nombres_paciente, p.apellidos as apellidos_paciente
                      FROM triage t
                      INNER JOIN usuarios u ON t.id_enfermero = u.id_usuario
                      INNER JOIN citas c ON t.id_cita = c.id_cita
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      WHERE t.id_cita = :id_cita";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_cita' => $id_cita]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error obteniendo triaje por cita: " . $e->getMessage());
            throw new Exception("Error al obtener el triaje");
        }
    }
    
    /**
     * ✅ MÉTODO MEJORADO: Obtener citas para triaje con búsqueda por cédula
     */
    public function obtenerCitasPendientesTriaje($fecha = null, $buscar_cedula = null): array {
    try {
        $fecha = $fecha ?: date('Y-m-d');
        
        $query = "SELECT c.id_cita, c.fecha_hora, c.motivo, c.estado,
                         c.id_paciente, -- ⭐ AGREGAR ID_PACIENTE
                         p.nombres as nombres_paciente, 
                         p.apellidos as apellidos_paciente,
                         p.cedula as cedula_paciente,
                         d.nombres as nombres_doctor, 
                         d.apellidos as apellidos_doctor,
                         e.nombre_especialidad,
                         s.nombre_sucursal,
                         t.id_triage,
                         t.nivel_urgencia,
                         t.estado_triaje, -- ⭐ INCLUIR ESTADO_TRIAJE
                         CASE WHEN t.id_triage IS NOT NULL THEN 1 ELSE 0 END as tiene_triaje
                  FROM citas c
                  INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                  INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                  INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                  INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                  INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                  INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                  LEFT JOIN triage t ON c.id_cita = t.id_cita
                  WHERE DATE(c.fecha_hora) = :fecha 
                  AND c.estado IN ('Pendiente', 'Confirmada')";
        
        $parametros = [':fecha' => $fecha];
        
        // ✅ BÚSQUEDA POR CÉDULA SI SE PROPORCIONA
        if (!empty($buscar_cedula)) {
            $query .= " AND p.cedula LIKE :cedula";
            $parametros[':cedula'] = "%{$buscar_cedula}%";
        }
        
        $query .= " ORDER BY 
                    CASE WHEN t.nivel_urgencia IS NOT NULL THEN t.nivel_urgencia ELSE 0 END DESC,
                    c.fecha_hora ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($parametros);
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ✅ DEBUG SIMPLE SIN CONSTANTES
        error_log("=== TRIAJE MODEL DEBUG ===");
        error_log("Fecha: $fecha");
        error_log("Búsqueda cédula: " . ($buscar_cedula ?: 'ninguna'));
        error_log("Resultados encontrados: " . count($resultados));
        
        return $resultados;
        
    } catch (PDOException $e) {
        error_log("ERROR SQL en obtenerCitasPendientesTriaje: " . $e->getMessage());
        throw new Exception("Error al obtener las citas: " . $e->getMessage());
    }
}
    
    /**
     * ✅ NUEVO: Buscar específicamente por cédula
     */
    public function buscarPorCedula(string $cedula, $fecha = null): array {
        try {
            $fecha = $fecha ?: date('Y-m-d');
            
            $query = "SELECT c.id_cita, c.fecha_hora, c.motivo, c.estado,
                             c.id_paciente,
                             p.nombres as nombres_paciente, 
                             p.apellidos as apellidos_paciente,
                             p.cedula as cedula_paciente,
                             d.nombres as nombres_doctor, 
                             d.apellidos as apellidos_doctor,
                             e.nombre_especialidad,
                             s.nombre_sucursal,
                             t.id_triage,
                             t.nivel_urgencia,
                             t.estado_triaje,
                             CASE WHEN t.id_triage IS NOT NULL THEN 1 ELSE 0 END as tiene_triaje
                      FROM citas c
                      INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                      INNER JOIN usuarios p ON pac.id_usuario = p.id_usuario
                      INNER JOIN doctores doc ON c.id_doctor = doc.id_doctor
                      INNER JOIN usuarios d ON doc.id_usuario = d.id_usuario
                      INNER JOIN especialidades e ON doc.id_especialidad = e.id_especialidad
                      INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                      LEFT JOIN triage t ON c.id_cita = t.id_cita
                      WHERE p.cedula LIKE :cedula
                      AND DATE(c.fecha_hora) = :fecha 
                      AND c.estado IN ('Pendiente', 'Confirmada')
                      ORDER BY c.fecha_hora ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':cedula' => "%{$cedula}%",
                ':fecha' => $fecha
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("ERROR SQL en buscarPorCedula: " . $e->getMessage());
            throw new Exception("Error al buscar por cédula: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar si una cita ya tiene triaje
     */
    public function citaTieneTriaje(int $id_cita): bool {
        try {
            $query = "SELECT COUNT(*) FROM triage WHERE id_cita = :id_cita";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_cita' => $id_cita]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error verificando triaje: " . $e->getMessage());
            return false;
        }
    }
    
    // ===== ACTUALIZAR TRIAJE =====
    
    /**
     * Actualizar triaje existente (incluyendo estado_triaje)
     */
    public function actualizar(int $id_triage, array $datos): bool {
        try {
            // ✅ Recalcular estado del triaje si cambió la urgencia
            $estado_triaje = $this->calcularEstadoTriaje($datos['nivel_urgencia']);
            
            $query = "UPDATE triage SET 
                        nivel_urgencia = :nivel_urgencia,
                        estado_triaje = :estado_triaje, -- ⭐ ACTUALIZAR ESTADO
                        temperatura = :temperatura,
                        presion_arterial = :presion_arterial,
                        frecuencia_cardiaca = :frecuencia_cardiaca,
                        frecuencia_respiratoria = :frecuencia_respiratoria,
                        saturacion_oxigeno = :saturacion_oxigeno,
                        peso = :peso,
                        talla = :talla,
                        imc = :imc,
                        observaciones = :observaciones
                      WHERE id_triage = :id_triage";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':id_triage' => $id_triage,
                ':nivel_urgencia' => $datos['nivel_urgencia'],
                ':estado_triaje' => $estado_triaje, // ⭐ NUEVO
                ':temperatura' => $datos['temperatura'],
                ':presion_arterial' => $datos['presion_arterial'],
                ':frecuencia_cardiaca' => $datos['frecuencia_cardiaca'],
                ':frecuencia_respiratoria' => $datos['frecuencia_respiratoria'],
                ':saturacion_oxigeno' => $datos['saturacion_oxigeno'],
                ':peso' => $datos['peso'],
                ':talla' => $datos['talla'],
                ':imc' => $datos['imc'],
                ':observaciones' => $datos['observaciones']
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando triaje: " . $e->getMessage());
            throw new Exception("Error al actualizar el triaje");
        }
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    /**
     * ✅ NUEVO: Calcular estado del triaje según nivel de urgencia
     */
    private function calcularEstadoTriaje(int $nivel_urgencia): string {
        return match($nivel_urgencia) {
            4 => 'Critico',
            3 => 'Urgente',
            2 => 'Pendiente_Atencion',
            default => 'Completado'
        };
    }
    
    /**
     * Calcular IMC automáticamente
     */
    public function calcularIMC(float $peso, int $talla): ?float {
        if ($peso <= 0 || $talla <= 0) {
            return null;
        }
        
        $altura_metros = $talla / 100;
        return round($peso / ($altura_metros * $altura_metros), 2);
    }
    
    /**
     * Determinar categoría de IMC
     */
    public function categorizarIMC(float $imc): string {
        if ($imc < 18.5) return 'Bajo peso';
        if ($imc < 25) return 'Peso normal';
        if ($imc < 30) return 'Sobrepeso';
        return 'Obesidad';
    }
    
    /**
     * Validar signos vitales normales
     */
    public function validarSignosVitales(array $signos): array {
        $alertas = [];
        
        // Temperatura
        if (isset($signos['temperatura']) && !empty($signos['temperatura'])) {
            $temp = (float)$signos['temperatura'];
            if ($temp < 35.0 || $temp > 42.0) {
                $alertas[] = 'Temperatura fuera del rango normal (35-42°C)';
            } elseif ($temp < 36.0 || $temp > 37.5) {
                $alertas[] = 'Temperatura ligeramente alterada';
            }
        }
        
        // Frecuencia cardíaca
        if (isset($signos['frecuencia_cardiaca']) && !empty($signos['frecuencia_cardiaca'])) {
            $fc = (int)$signos['frecuencia_cardiaca'];
            if ($fc < 50 || $fc > 120) {
                $alertas[] = 'Frecuencia cardíaca fuera del rango normal (50-120 lpm)';
            }
        }
        
        // Saturación de oxígeno
        if (isset($signos['saturacion_oxigeno']) && !empty($signos['saturacion_oxigeno'])) {
            $sat = (int)$signos['saturacion_oxigeno'];
            if ($sat < 95) {
                $alertas[] = 'Saturación de oxígeno baja (<95%) - REQUIERE ATENCIÓN';
            }
        }
        
        // Presión arterial (análisis básico)
        if (isset($signos['presion_arterial']) && !empty($signos['presion_arterial'])) {
            if (preg_match('/(\d+)\/(\d+)/', $signos['presion_arterial'], $matches)) {
                $sistolica = (int)$matches[1];
                $diastolica = (int)$matches[2];
                
                if ($sistolica > 140 || $diastolica > 90) {
                    $alertas[] = 'Presión arterial elevada (>140/90)';
                } elseif ($sistolica < 90 || $diastolica < 60) {
                    $alertas[] = 'Presión arterial baja (<90/60)';
                }
            }
        }
        
        return $alertas;
    }
    
    // ===== ESTADÍSTICAS =====
    
    /**
     * Obtener estadísticas de triaje (actualizada con estado_triaje)
     */
    public function obtenerEstadisticas($fecha_desde = null, $fecha_hasta = null): array {
        try {
            $fecha_desde = $fecha_desde ?: date('Y-m-d', strtotime('-30 days'));
            $fecha_hasta = $fecha_hasta ?: date('Y-m-d');
            
            $query = "SELECT 
                        COUNT(*) as total_triajes,
                        COUNT(CASE WHEN nivel_urgencia = 1 THEN 1 END) as urgencia_baja,
                        COUNT(CASE WHEN nivel_urgencia = 2 THEN 1 END) as urgencia_media,
                        COUNT(CASE WHEN nivel_urgencia = 3 THEN 1 END) as urgencia_alta,
                        COUNT(CASE WHEN nivel_urgencia = 4 THEN 1 END) as urgencia_critica,
                        COUNT(CASE WHEN estado_triaje = 'Completado' THEN 1 END) as completados,
                        COUNT(CASE WHEN estado_triaje = 'Urgente' THEN 1 END) as urgentes,
                        COUNT(CASE WHEN estado_triaje = 'Critico' THEN 1 END) as criticos,
                        AVG(temperatura) as temperatura_promedio,
                        AVG(imc) as imc_promedio
                      FROM triage 
                      WHERE DATE(fecha_hora) BETWEEN :fecha_desde AND :fecha_hasta";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':fecha_desde' => $fecha_desde,
                ':fecha_hasta' => $fecha_hasta
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de triaje: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * ✅ NUEVO: Obtener contadores rápidos para el dashboard
     */
    public function obtenerContadoresRapidos($fecha = null): array {
        try {
            $fecha = $fecha ?: date('Y-m-d');
            
            $query = "SELECT 
                        COUNT(c.id_cita) as total_citas,
                        COUNT(CASE WHEN t.id_triage IS NULL THEN 1 END) as pendientes,
                        COUNT(CASE WHEN t.id_triage IS NOT NULL THEN 1 END) as completados,
                        COUNT(CASE WHEN t.nivel_urgencia >= 3 THEN 1 END) as urgentes
                      FROM citas c
                      LEFT JOIN triage t ON c.id_cita = t.id_cita
                      WHERE DATE(c.fecha_hora) = :fecha 
                      AND c.estado IN ('Pendiente', 'Confirmada')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':fecha' => $fecha]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo contadores rápidos: " . $e->getMessage());
            throw new Exception("Error al obtener contadores");
        }
    }
}
?>