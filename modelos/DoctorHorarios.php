<?php
require_once __DIR__ . "/../config/database.php";

class DoctorHorarios {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    /**
     * Obtener horarios completos de un doctor para una semana específica
     */
    public function obtenerHorariosSemanales($id_doctor, $id_sucursal, $fecha_inicio) {
        try {
            // Calcular fecha fin de la semana
            $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +6 days'));
            
            // Obtener horarios regulares del doctor
            $query_horarios = "
                SELECT dh.*, 
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
                WHERE dh.id_doctor = :id_doctor 
                  AND dh.id_sucursal = :id_sucursal 
                  AND dh.activo = 1
                ORDER BY dh.dia_semana, dh.hora_inicio
            ";
            
            $stmt = $this->conn->prepare($query_horarios);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':id_sucursal' => $id_sucursal
            ]);
            $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener citas ya programadas para esa semana
            $query_citas = "
                SELECT DATE(fecha_hora) as fecha, 
                       TIME(fecha_hora) as hora,
                       estado,
                       motivo
                FROM citas 
                WHERE id_doctor = :id_doctor 
                  AND DATE(fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                  AND estado IN ('Pendiente', 'Confirmada')
                ORDER BY fecha_hora
            ";
            
            $stmt = $this->conn->prepare($query_citas);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin
            ]);
            $citas_ocupadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener excepciones para esa semana
            $query_excepciones = "
                SELECT fecha, tipo, hora_inicio, hora_fin, motivo
                FROM doctor_excepciones 
                WHERE id_doctor = :id_doctor 
                  AND fecha BETWEEN :fecha_inicio AND :fecha_fin
                  AND activo = 1
            ";
            
            $stmt = $this->conn->prepare($query_excepciones);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin
            ]);
            $excepciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'horarios' => $horarios,
                'citas_ocupadas' => $citas_ocupadas,
                'excepciones' => $excepciones,
                'semana' => [
                    'inicio' => $fecha_inicio,
                    'fin' => $fecha_fin
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error obteniendo horarios semanales: " . $e->getMessage());
            throw new Exception("Error al obtener horarios del doctor");
        }
    }
    
    /**
     * Crear horario para un doctor
     */
    public function crear($datos) {
        try {
            $query = "INSERT INTO doctor_horarios 
                      (id_doctor, id_sucursal, dia_semana, hora_inicio, hora_fin, duracion_cita, activo) 
                      VALUES (:id_doctor, :id_sucursal, :dia_semana, :hora_inicio, :hora_fin, :duracion_cita, 1)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $datos['id_doctor'],
                ':id_sucursal' => $datos['id_sucursal'],
                ':dia_semana' => $datos['dia_semana'],
                ':hora_inicio' => $datos['hora_inicio'],
                ':hora_fin' => $datos['hora_fin'],
                ':duracion_cita' => $datos['duracion_cita'] ?? 30
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error creando horario: " . $e->getMessage());
            throw new Exception("Error al crear horario del doctor");
        }
    }
    
    /**
     * Generar slots de tiempo disponibles
     */
    public function generarSlotsDisponibles($horarios, $citas_ocupadas, $excepciones, $fecha) {
        $slots = [];
        $dia_semana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
        
        // Verificar si es día de excepción
        $excepcion = array_filter($excepciones, function($e) use ($fecha) {
            return $e['fecha'] === $fecha;
        });
        
        if (!empty($excepcion)) {
            $excepcion = array_values($excepcion)[0];
            if ($excepcion['tipo'] === 'no_laborable' || $excepcion['tipo'] === 'vacaciones' || $excepcion['tipo'] === 'feriado') {
                return []; // No hay slots disponibles
            }
            // Para horario_especial, usar los horarios de la excepción
            if ($excepcion['tipo'] === 'horario_especial') {
                $horarios_dia = [[
                    'hora_inicio' => $excepcion['hora_inicio'],
                    'hora_fin' => $excepcion['hora_fin'],
                    'duracion_cita' => 30
                ]];
            }
        } else {
            // Usar horarios regulares
            $horarios_dia = array_filter($horarios, function($h) use ($dia_semana) {
                return $h['dia_semana'] == $dia_semana;
            });
        }
        
        foreach ($horarios_dia as $horario) {
            $hora_actual = new DateTime($horario['hora_inicio']);
            $hora_fin = new DateTime($horario['hora_fin']);
            $duracion = $horario['duracion_cita'] ?? 30;
            
            while ($hora_actual < $hora_fin) {
                $hora_str = $hora_actual->format('H:i:s');
                
                // Verificar si está ocupado
                $ocupado = array_filter($citas_ocupadas, function($cita) use ($fecha, $hora_str) {
                    return $cita['fecha'] === $fecha && $cita['hora'] === $hora_str;
                });
                
                $slots[] = [
                    'fecha' => $fecha,
                    'hora' => $hora_str,
                    'hora_formato' => $hora_actual->format('H:i'),
                    'disponible' => empty($ocupado),
                    'ocupado' => !empty($ocupado),
                    'cita_info' => !empty($ocupado) ? array_values($ocupado)[0] : null
                ];
                
                $hora_actual->add(new DateInterval('PT' . $duracion . 'M'));
            }
        }
        
        return $slots;
    }
    
    /**
     * Crear excepción para un doctor
     */
    public function crearExcepcion($datos) {
        try {
            $query = "INSERT INTO doctor_excepciones 
                      (id_doctor, fecha, tipo, hora_inicio, hora_fin, motivo, activo) 
                      VALUES (:id_doctor, :fecha, :tipo, :hora_inicio, :hora_fin, :motivo, 1)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_doctor' => $datos['id_doctor'],
                ':fecha' => $datos['fecha'],
                ':tipo' => $datos['tipo'],
                ':hora_inicio' => $datos['hora_inicio'] ?? null,
                ':hora_fin' => $datos['hora_fin'] ?? null,
                ':motivo' => $datos['motivo'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error creando excepción: " . $e->getMessage());
            throw new Exception("Error al crear excepción");
        }
    }
    
    /**
     * Obtener días de la semana con sus números
     */
    public static function getDiasSemana() {
        return [
            1 => 'Lunes',
            2 => 'Martes', 
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];
    }
    
    /**
     * Validar si un horario es válido
     */
    public function validarHorario($hora_inicio, $hora_fin, $duracion_cita = 30) {
        $inicio = new DateTime($hora_inicio);
        $fin = new DateTime($hora_fin);
        
        if ($inicio >= $fin) {
            throw new Exception("La hora de inicio debe ser menor que la hora de fin");
        }
        
        $diferencia = $fin->diff($inicio);
        $minutos_totales = ($diferencia->h * 60) + $diferencia->i;
        
        if ($minutos_totales < $duracion_cita) {
            throw new Exception("El rango de tiempo debe ser mayor a la duración de una cita");
        }
        
        return true;
    }

    /**
     * Obtener horarios por doctor
     */
    public function obtenerPorDoctor($id_doctor) {
        try {
            $query = "SELECT dh.*,
                             s.nombre_sucursal,
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
                      WHERE dh.id_doctor = :id_doctor AND dh.activo = 1
                      ORDER BY dh.dia_semana, dh.hora_inicio";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id_doctor' => $id_doctor]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo horarios: " . $e->getMessage());
            throw new Exception("Error al obtener horarios del doctor");
        }
    }
    
}
?>