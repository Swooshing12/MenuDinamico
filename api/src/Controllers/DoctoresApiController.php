<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\ResponseUtil;
use App\Validators\CedulaValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class DoctoresApiController {
    
    public function manejarSolicitud() {
        // Habilitar CORS
        $this->habilitarCors();
        
        $metodo = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'listar':
                    return $this->listarDoctores();
                case 'crear':
                    return $this->crearDoctor();
                case 'obtener':
                    return $this->obtenerDoctor();
                case 'actualizar':
                    return $this->actualizarDoctor();
                case 'eliminar':
                    return $this->eliminarDoctor();
                case 'obtenerHorarios':
                    return $this->obtenerHorarios();
                case 'guardarHorarios':
                    return $this->guardarHorarios();
                case 'obtenerDisponibilidad':
                    return $this->obtenerDisponibilidad();
                case 'obtenerExcepciones':
                    return $this->obtenerExcepciones();
                case 'guardarExcepcion':
                    return $this->guardarExcepcion();
                case 'cambiarEstado':
                    return $this->cambiarEstadoDoctor();
                case 'eliminarExcepcion':
                    return $this->eliminarExcepcion();
                case 'estadisticas':
                    return $this->obtenerEstadisticas();
                case 'buscarPorCedula':
                    return $this->buscarPorCedula();
                case 'editarHorario':
                    return $this->editarHorario();
                case 'eliminarHorario':
                    return $this->eliminarHorario();
                default:
                    return ResponseUtil::badRequest('Acción no válida');
            }
        } catch (Exception $e) {
            error_log("Error en DoctoresApiController: " . $e->getMessage());
            return ResponseUtil::error('Error interno del servidor: ' . $e->getMessage());
        }
    }
    
    private function habilitarCors() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Listar todos los médicos con paginación y filtros
     */
    public function listarDoctores() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $search = $_GET['search'] ?? '';
            $especialidad = (int)($_GET['especialidad'] ?? 0);
            $sucursal = (int)($_GET['sucursal'] ?? 0);
            $estado = $_GET['estado'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $query = DB::table('doctores as d')
                ->select([
                    'd.id_doctor',
                    'd.id_usuario', 
                    'd.titulo_profesional',
                    'u.cedula',
                    'u.nombres',
                    'u.apellidos',
                    'u.correo',
                    'u.sexo',
                    'u.nacionalidad',
                    'u.id_estado',
                    'e.id_especialidad',
                    'e.nombre_especialidad',
                    DB::raw('CONCAT(u.nombres, " ", u.apellidos) as nombre_completo'),
                    DB::raw('CASE 
                        WHEN u.id_estado = 1 THEN "Activo"
                        WHEN u.id_estado = 2 THEN "Inactivo" 
                        WHEN u.id_estado = 3 THEN "Pendiente"
                        WHEN u.id_estado = 4 THEN "Bloqueado"
                        ELSE "Desconocido"
                    END as estado_texto')
                ])
                ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
                ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad');
            
            // Aplicar filtros
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('u.nombres', 'LIKE', "%{$search}%")
                      ->orWhere('u.apellidos', 'LIKE', "%{$search}%")
                      ->orWhere('u.cedula', 'LIKE', "%{$search}%")
                      ->orWhere('u.correo', 'LIKE', "%{$search}%")
                      ->orWhere('e.nombre_especialidad', 'LIKE', "%{$search}%");
                });
            }
            
            if ($especialidad > 0) {
                $query->where('d.id_especialidad', $especialidad);
            }
            
            if (!empty($estado)) {
                $estadoId = match($estado) {
                    'Activo' => 1,
                    'Inactivo' => 2,
                    'Pendiente' => 3,
                    'Bloqueado' => 4,
                    default => null
                };
                if ($estadoId) {
                    $query->where('u.id_estado', $estadoId);
                }
            }
            
            // Filtro por sucursal si se especifica
            if ($sucursal > 0) {
                $query->join('doctores_sucursales as ds', 'd.id_doctor', '=', 'ds.id_doctor')
                      ->where('ds.id_sucursal', $sucursal);
            }
            
            // Contar total
            $total = $query->count();
            
            // Obtener registros paginados
            $doctores = $query->orderBy('u.nombres')
                            ->offset($offset)
                            ->limit($limit)
                            ->get()
                            ->toArray();
            
            // Para cada doctor, obtener sus sucursales y horarios
            foreach ($doctores as &$doctor) {
                // Obtener sucursales
                $sucursales = DB::table('doctores_sucursales as ds')
                    ->select(['s.id_sucursal', 's.nombre_sucursal'])
                    ->join('sucursales as s', 'ds.id_sucursal', '=', 's.id_sucursal')
                    ->where('ds.id_doctor', $doctor->id_doctor)
                    ->get()
                    ->toArray();
                
                $doctor->sucursales = $sucursales;
                
                // Contar horarios activos
                $totalHorarios = DB::table('doctor_horarios')
                    ->where('id_doctor', $doctor->id_doctor)
                    ->where('activo', 1)
                    ->count();
                
                $doctor->total_horarios = $totalHorarios;
            }
            
            return ResponseUtil::success([
                'doctores' => $doctores,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                    'has_next' => $page < ceil($total / $limit),
                    'has_prev' => $page > 1
                ],
                'filters_applied' => [
                    'search' => $search,
                    'especialidad' => $especialidad,
                    'sucursal' => $sucursal,
                    'estado' => $estado
                ]
            ], 'Médicos obtenidos exitosamente');
            
        } catch (Exception $e) {
            error_log("Error listando médicos: " . $e->getMessage());
            return ResponseUtil::error('Error obteniendo la lista de médicos: ' . $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo médico con horarios
     */
    public function crearDoctor() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }
            
            // Validar datos requeridos
            $errores = $this->validarDatosDoctor($data);
            if (!empty($errores)) {
                return ResponseUtil::badRequest('Datos inválidos', $errores);
            }
            
            DB::beginTransaction();
            
            // 1. Crear usuario
            $usuarioData = [
                'cedula' => $data['cedula'],
                'username' => $data['username'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'correo' => $data['correo'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'sexo' => $data['sexo'],
                'nacionalidad' => $data['nacionalidad'],
                'id_estado' => 1, // Activo
                'id_rol' => 70, // Rol médico (según tu BD)
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];
            
            $idUsuario = DB::table('usuarios')->insertGetId($usuarioData);
            
            // 2. Crear doctor
            $doctorData = [
                'id_usuario' => $idUsuario,
                'id_especialidad' => $data['id_especialidad'],
                'titulo_profesional' => $data['titulo_profesional'] ?? null
            ];
            
            $idDoctor = DB::table('doctores')->insertGetId($doctorData);
            
            // 3. Asignar sucursales
            if (!empty($data['sucursales'])) {
                foreach ($data['sucursales'] as $idSucursal) {
                    DB::table('doctores_sucursales')->insert([
                        'id_doctor' => $idDoctor,
                        'id_sucursal' => $idSucursal
                    ]);
                }
            }
            
            // 4. Guardar horarios si se proporcionan
            if (!empty($data['horarios'])) {
                $this->guardarHorariosDoctor($idDoctor, $data['horarios']);
            }
            
            DB::commit();
            
            // Obtener el doctor creado completo
            $doctorCreado = $this->obtenerDoctorCompleto($idDoctor);
            
            return ResponseUtil::success($doctorCreado, 'Médico creado exitosamente');
            
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Error creando médico: " . $e->getMessage());
            return ResponseUtil::error('Error creando el médico: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener médico específico con todos sus datos
     */
    public function obtenerDoctor() {
        try {
            $idDoctor = (int)($_GET['id'] ?? 0);
            if (!$idDoctor) {
                return ResponseUtil::badRequest('ID de médico requerido');
            }
            
            $doctor = $this->obtenerDoctorCompleto($idDoctor);
            
            if (!$doctor) {
                return ResponseUtil::notFound('Médico no encontrado');
            }
            
            return ResponseUtil::success($doctor, 'Médico obtenido exitosamente');
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error obteniendo el médico: ' . $e->getMessage());
        }
    }
    
    /**
 * Obtener horarios de un médico
 */
public function obtenerHorarios() {
    try {
        $idDoctor = (int)($_GET['id_doctor'] ?? 0);
        $idSucursal = (int)($_GET['id_sucursal'] ?? 0);
        
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID de médico requerido');
        }
        
        $query = DB::table('doctor_horarios as dh')
            ->select([
                'dh.id_horario',
                'dh.id_doctor',
                'dh.id_sucursal',
                'dh.dia_semana',
                'dh.hora_inicio',
                'dh.hora_fin',
                'dh.duracion_cita', // 🔥 INCLUIR duracion_cita
                'dh.activo',
                'dh.fecha_creacion',
                's.nombre_sucursal',
                DB::raw('CASE dh.dia_semana 
                    WHEN 1 THEN "Lunes"
                    WHEN 2 THEN "Martes" 
                    WHEN 3 THEN "Miércoles"
                    WHEN 4 THEN "Jueves"
                    WHEN 5 THEN "Viernes"
                    WHEN 6 THEN "Sábado"
                    WHEN 7 THEN "Domingo"
                END as nombre_dia'),
                DB::raw('CONCAT(dh.hora_inicio, " - ", dh.hora_fin) as horario_completo'),
                // 🔥 Calcular citas estimadas por día
                DB::raw('FLOOR((TIME_TO_SEC(dh.hora_fin) - TIME_TO_SEC(dh.hora_inicio)) / (dh.duracion_cita * 60)) as citas_estimadas_dia')
            ])
            ->join('sucursales as s', 'dh.id_sucursal', '=', 's.id_sucursal')
            ->where('dh.id_doctor', $idDoctor)
            ->where('dh.activo', 1);
        
        if ($idSucursal > 0) {
            $query->where('dh.id_sucursal', $idSucursal);
        }
        
        $horarios = $query->orderBy('dh.id_sucursal')
                        ->orderBy('dh.dia_semana')
                        ->orderBy('dh.hora_inicio')
                        ->get()
                        ->toArray();
        
        // Agrupar por sucursal para mejor presentación
        $horariosPorSucursal = [];
        $estadisticasGenerales = [
            'total_horarios' => count($horarios),
            'total_horas_semanales' => 0,
            'total_citas_estimadas_semana' => 0,
            'duraciones_utilizadas' => []
        ];
        
        foreach ($horarios as $horario) {
            $sucursalId = $horario->id_sucursal;
            if (!isset($horariosPorSucursal[$sucursalId])) {
                $horariosPorSucursal[$sucursalId] = [
                    'id_sucursal' => $sucursalId,
                    'nombre_sucursal' => $horario->nombre_sucursal,
                    'horarios' => [],
                    'estadisticas' => [
                        'total_horarios' => 0,
                        'horas_semanales' => 0,
                        'citas_estimadas_semana' => 0
                    ]
                ];
            }
            
            // Calcular horas de este horario
            $horasDelHorario = (strtotime($horario->hora_fin) - strtotime($horario->hora_inicio)) / 3600;
            
            $horariosPorSucursal[$sucursalId]['horarios'][] = $horario;
            $horariosPorSucursal[$sucursalId]['estadisticas']['total_horarios']++;
            $horariosPorSucursal[$sucursalId]['estadisticas']['horas_semanales'] += $horasDelHorario;
            $horariosPorSucursal[$sucursalId]['estadisticas']['citas_estimadas_semana'] += $horario->citas_estimadas_dia;
            
            // Estadísticas generales
            $estadisticasGenerales['total_horas_semanales'] += $horasDelHorario;
            $estadisticasGenerales['total_citas_estimadas_semana'] += $horario->citas_estimadas_dia;
            
            // Registrar duraciones utilizadas
            if (!in_array($horario->duracion_cita, $estadisticasGenerales['duraciones_utilizadas'])) {
                $estadisticasGenerales['duraciones_utilizadas'][] = $horario->duracion_cita;
            }
        }
        
        sort($estadisticasGenerales['duraciones_utilizadas']);
        
        return ResponseUtil::success([
            'horarios_raw' => $horarios,
            'horarios_por_sucursal' => array_values($horariosPorSucursal),
            'estadisticas' => $estadisticasGenerales,
            'info_duraciones' => [
                'duracion_por_defecto' => 30,
                'duraciones_permitidas' => [15, 20, 30, 45, 60, 90, 120],
                'duraciones_en_uso' => $estadisticasGenerales['duraciones_utilizadas']
            ]
        ], 'Horarios obtenidos exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo horarios: ' . $e->getMessage());
    }
}

/**
 * Guardar/actualizar horarios de un médico
 */
public function guardarHorarios() {
    try {
        // Obtener datos JSON del body o POST
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $idDoctor = (int)($data['id_doctor'] ?? 0);
        $horarios = $data['horarios'] ?? [];
        $idSucursal = (int)($data['id_sucursal'] ?? 0);
        
        if (!$idDoctor || empty($horarios)) {
            return ResponseUtil::badRequest('ID de médico y horarios son requeridos');
        }
        
        // Validar cada horario antes de procesar
        foreach ($horarios as $index => $horario) {
            $errores = [];
            
            if (empty($horario['id_sucursal'])) {
                $errores[] = "Sucursal requerida en horario " . ($index + 1);
            }
            
            if (empty($horario['dia_semana']) || $horario['dia_semana'] < 1 || $horario['dia_semana'] > 7) {
                $errores[] = "Día de semana inválido en horario " . ($index + 1) . " (debe ser 1-7)";
            }
            
            if (empty($horario['hora_inicio']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $horario['hora_inicio'])) {
                $errores[] = "Hora de inicio inválida en horario " . ($index + 1);
            }
            
            if (empty($horario['hora_fin']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $horario['hora_fin'])) {
                $errores[] = "Hora de fin inválida en horario " . ($index + 1);
            }
            
            // Validar duración de cita
            $duracion = (int)($horario['duracion_cita'] ?? 30);
            if ($duracion < 15 || $duracion > 120) {
                $errores[] = "Duración de cita debe estar entre 15 y 120 minutos en horario " . ($index + 1);
            }
            
            if (!empty($errores)) {
                return ResponseUtil::badRequest('Errores en horarios', $errores);
            }
        }
        
        DB::beginTransaction();
        
        // Si se especifica sucursal, limpiar horarios existentes de esa sucursal
        if ($idSucursal > 0) {
            DB::table('doctor_horarios')
                ->where('id_doctor', $idDoctor)
                ->where('id_sucursal', $idSucursal)
                ->update(['activo' => 0]);
        }
        
        // Guardar nuevos horarios
        $horariosGuardados = $this->guardarHorariosDoctor($idDoctor, $horarios);
        
        DB::commit();
        
        return ResponseUtil::success([
            'horarios_guardados' => $horariosGuardados,
            'total' => count($horariosGuardados),
            'resumen' => [
                'creados' => count(array_filter($horariosGuardados, fn($h) => ($h['accion'] ?? '') === 'creado')),
                'actualizados' => count(array_filter($horariosGuardados, fn($h) => ($h['accion'] ?? '') === 'actualizado')),
                'duracion_promedio' => count($horariosGuardados) > 0 ? 
                    array_sum(array_column($horariosGuardados, 'duracion_cita')) / count($horariosGuardados) : 0
            ]
        ], 'Horarios guardados exitosamente');
        
    } catch (Exception $e) {
        DB::rollBack();
        return ResponseUtil::error('Error guardando horarios: ' . $e->getMessage());
    }
}
    
    /**
     * Obtener disponibilidad de un médico para una fecha/rango
     */
    public function obtenerDisponibilidad() {
        try {
            $idDoctor = (int)($_GET['id_doctor'] ?? 0);
            $idSucursal = (int)($_GET['id_sucursal'] ?? 0);
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $fechaInicio = $_GET['fecha_inicio'] ?? $fecha;
            $fechaFin = $_GET['fecha_fin'] ?? $fecha;
            
            if (!$idDoctor) {
                return ResponseUtil::badRequest('ID de médico requerido');
            }
            
            $disponibilidad = $this->calcularDisponibilidadDoctor($idDoctor, $idSucursal, $fechaInicio, $fechaFin);
            
            return ResponseUtil::success($disponibilidad, 'Disponibilidad obtenida exitosamente');
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error obteniendo disponibilidad: ' . $e->getMessage());
        }
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    private function validarDatosDoctor($data) {
        $errores = [];
        
        // Validar campos requeridos
        $requeridos = ['cedula', 'nombres', 'apellidos', 'correo', 'id_especialidad', 'sexo'];
        foreach ($requeridos as $campo) {
            if (empty($data[$campo])) {
                $errores[$campo] = "El campo {$campo} es requerido";
            }
        }
        
        // Validar cédula ecuatoriana
        if (!empty($data['cedula'])) {
            if (!$this->validarCedulaEcuatoriana($data['cedula'])) {
                $errores['cedula'] = 'Cédula ecuatoriana inválida';
            }
            
            // Verificar que no exista
            $existe = DB::table('usuarios')->where('cedula', $data['cedula'])->exists();
            if ($existe) {
                $errores['cedula'] = 'Ya existe un usuario con esta cédula';
            }
        }
        
        // Validar correo
        if (!empty($data['correo'])) {
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'Formato de correo inválido';
            }
            
            // Verificar que no exista
            $existe = DB::table('usuarios')->where('correo', $data['correo'])->exists();
            if ($existe) {
                $errores['correo'] = 'Ya existe un usuario con este correo';
            }
        }
        
        return $errores;
    }
    
    private function validarCedulaEcuatoriana($cedula) {
        if (strlen($cedula) != 10 || !ctype_digit($cedula)) {
            return false;
        }
        
        $digitos = str_split($cedula);
        $verificador = array_pop($digitos);
        
        $suma = 0;
        foreach ($digitos as $i => $digito) {
            $resultado = $digito * (($i % 2) ? 1 : 2);
            $suma += ($resultado > 9) ? ($resultado - 9) : $resultado;
        }
        
        $modulo = $suma % 10;
        return ($modulo == 0) ? ($verificador == 0) : ($verificador == (10 - $modulo));
    }
    
    private function obtenerDoctorCompleto($idDoctor) {
        $doctor = DB::table('doctores as d')
            ->select([
                'd.id_doctor',
                'd.id_usuario',
                'd.titulo_profesional',
                'u.cedula',
                'u.username',
                'u.nombres',
                'u.apellidos',
                'u.correo',
                'u.sexo',
                'u.nacionalidad',
                'u.id_estado',
                'e.id_especialidad',
                'e.nombre_especialidad',
                DB::raw('CONCAT(u.nombres, " ", u.apellidos) as nombre_completo')
            ])
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->where('d.id_doctor', $idDoctor)
            ->first();
        
        if ($doctor) {
            // Obtener sucursales
            $sucursales = DB::table('doctores_sucursales as ds')
                ->select(['s.id_sucursal', 's.nombre_sucursal'])
                ->join('sucursales as s', 'ds.id_sucursal', '=', 's.id_sucursal')
                ->where('ds.id_doctor', $idDoctor)
                ->get()
                ->toArray();
            
            $doctor->sucursales = $sucursales;
        }
        
        return $doctor;
    }
    
    private function guardarHorariosDoctor($idDoctor, $horarios) {
    $horariosGuardados = [];
    
    foreach ($horarios as $horario) {
        // Validar datos requeridos del horario
        if (empty($horario['id_sucursal']) || empty($horario['dia_semana']) || 
            empty($horario['hora_inicio']) || empty($horario['hora_fin'])) {
            throw new Exception('Datos incompletos en horario: se requiere sucursal, día, hora inicio y hora fin');
        }
        
        $datosHorario = [
            'id_doctor' => $idDoctor,
            'id_sucursal' => (int)$horario['id_sucursal'],
            'dia_semana' => (int)$horario['dia_semana'], // 1-7 (Lunes a Domingo)
            'hora_inicio' => $horario['hora_inicio'], // Formato HH:MM:SS
            'hora_fin' => $horario['hora_fin'], // Formato HH:MM:SS
            'duracion_cita' => (int)($horario['duracion_cita'] ?? 30), // 🔥 Por defecto 30 minutos
            'activo' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        // Validar que la hora de inicio sea menor que la de fin
        if ($datosHorario['hora_inicio'] >= $datosHorario['hora_fin']) {
            throw new Exception("Hora de inicio ({$datosHorario['hora_inicio']}) debe ser menor que hora de fin ({$datosHorario['hora_fin']})");
        }
        
        // Validar duración de cita (entre 15 y 120 minutos)
        if ($datosHorario['duracion_cita'] < 15 || $datosHorario['duracion_cita'] > 120) {
            throw new Exception("Duración de cita debe estar entre 15 y 120 minutos. Valor recibido: {$datosHorario['duracion_cita']}");
        }
        
        // Verificar si ya existe un horario similar
        $horarioExistente = DB::table('doctor_horarios')
            ->where('id_doctor', $idDoctor)
            ->where('id_sucursal', $datosHorario['id_sucursal'])
            ->where('dia_semana', $datosHorario['dia_semana'])
            ->where('hora_inicio', $datosHorario['hora_inicio'])
            ->where('activo', 1)
            ->first();
        
        if ($horarioExistente) {
            // Actualizar el existente en lugar de crear duplicado
            DB::table('doctor_horarios')
                ->where('id_horario', $horarioExistente->id_horario)
                ->update([
                    'hora_fin' => $datosHorario['hora_fin'],
                    'duracion_cita' => $datosHorario['duracion_cita']
                ]);
            $datosHorario['id_horario'] = $horarioExistente->id_horario;
            $datosHorario['accion'] = 'actualizado';
        } else {
            // Crear nuevo horario
            $idHorario = DB::table('doctor_horarios')->insertGetId($datosHorario);
            $datosHorario['id_horario'] = $idHorario;
            $datosHorario['accion'] = 'creado';
        }
        
        $horariosGuardados[] = $datosHorario;
    }
    
    return $horariosGuardados;
}
    /**
 * Actualizar médico existente
 */
public function actualizarDoctor() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        
        $idDoctor = (int)($data['id_doctor'] ?? 0);
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID de médico requerido');
        }
        
        // Verificar que el médico existe
        $doctorExistente = DB::table('doctores')->where('id_doctor', $idDoctor)->first();
        if (!$doctorExistente) {
            return ResponseUtil::notFound('Médico no encontrado');
        }
        
        DB::beginTransaction();
        
        // Actualizar datos de usuario
        $usuarioData = [];
        if (!empty($data['nombres'])) $usuarioData['nombres'] = $data['nombres'];
        if (!empty($data['apellidos'])) $usuarioData['apellidos'] = $data['apellidos'];
        if (!empty($data['correo'])) {
            // Verificar que el correo no esté en uso por otro usuario
            $correoExiste = DB::table('usuarios')
                ->where('correo', $data['correo'])
                ->where('id_usuario', '!=', $doctorExistente->id_usuario)
                ->exists();
            if ($correoExiste) {
                return ResponseUtil::badRequest('El correo ya está en uso por otro usuario');
            }
            $usuarioData['correo'] = $data['correo'];
        }
        if (!empty($data['sexo'])) $usuarioData['sexo'] = $data['sexo'];
        if (!empty($data['nacionalidad'])) $usuarioData['nacionalidad'] = $data['nacionalidad'];
        if (isset($data['id_estado'])) $usuarioData['id_estado'] = (int)$data['id_estado'];
        
        // Actualizar contraseña si se proporciona
        if (!empty($data['password'])) {
            $usuarioData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (!empty($usuarioData)) {
            DB::table('usuarios')
                ->where('id_usuario', $doctorExistente->id_usuario)
                ->update($usuarioData);
        }
        
        // Actualizar datos del médico
        $doctorData = [];
        if (isset($data['id_especialidad'])) $doctorData['id_especialidad'] = (int)$data['id_especialidad'];
        if (isset($data['titulo_profesional'])) $doctorData['titulo_profesional'] = $data['titulo_profesional'];
        
        if (!empty($doctorData)) {
            DB::table('doctores')
                ->where('id_doctor', $idDoctor)
                ->update($doctorData);
        }
        
        // Actualizar sucursales si se proporcionan
        if (isset($data['sucursales']) && is_array($data['sucursales'])) {
            // Eliminar asignaciones existentes
            DB::table('doctores_sucursales')
                ->where('id_doctor', $idDoctor)
                ->delete();
            
            // Agregar nuevas asignaciones
            foreach ($data['sucursales'] as $idSucursal) {
                DB::table('doctores_sucursales')->insert([
                    'id_doctor' => $idDoctor,
                    'id_sucursal' => (int)$idSucursal
                ]);
            }
        }
        
        DB::commit();
        
        // Obtener el médico actualizado
        $doctorActualizado = $this->obtenerDoctorCompleto($idDoctor);
        
        return ResponseUtil::success($doctorActualizado, 'Médico actualizado exitosamente');
        
    } catch (Exception $e) {
        DB::rollBack();
        error_log("Error actualizando médico: " . $e->getMessage());
        return ResponseUtil::error('Error actualizando el médico: ' . $e->getMessage());
    }
}

/**
 * Eliminar un horario específico
 */
public function eliminarHorario()
{
    try {
        $idHorario = $_GET['id'] ?? null;
        if (!$idHorario) {
            return ResponseUtil::badRequest('ID de horario no proporcionado');
        }

        // Verificar si el horario existe
        $horario = DB::table('doctor_horarios')
            ->where('id_horario', $idHorario)
            ->first();

        if (!$horario) {
            return ResponseUtil::badRequest('Horario no encontrado');
        }

        // Eliminar el horario
        DB::table('doctor_horarios')
            ->where('id_horario', $idHorario)
            ->delete();

        return ResponseUtil::success(null, 'Horario eliminado correctamente');

    } catch (Exception $e) {
        error_log("Error eliminando horario: " . $e->getMessage());
        return ResponseUtil::error('Error eliminando el horario: ' . $e->getMessage());
    }
}

/**
 * Eliminar médico (desactivar)
 */
public function eliminarDoctor() {
    try {
        $idDoctor = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID de médico requerido');
        }
        
        // Verificar que el médico existe
        $doctor = DB::table('doctores as d')
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->where('d.id_doctor', $idDoctor)
            ->first(['d.id_doctor', 'd.id_usuario', 'u.nombres', 'u.apellidos']);
        
        if (!$doctor) {
            return ResponseUtil::notFound('Médico no encontrado');
        }
        
        // Verificar si tiene citas futuras
        $citasFuturas = DB::table('citas')
            ->where('id_doctor', $idDoctor)
            ->where('fecha_hora', '>', date('Y-m-d H:i:s'))
            ->whereIn('estado', ['Pendiente', 'Confirmada'])
            ->count();
        
        if ($citasFuturas > 0) {
            return ResponseUtil::badRequest(
                "No se puede eliminar el médico porque tiene {$citasFuturas} citas futuras programadas. " .
                "Cancele o reasigne las citas primero."
            );
        }
        
        DB::beginTransaction();
        
        // Desactivar usuario en lugar de eliminar
        DB::table('usuarios')
            ->where('id_usuario', $doctor->id_usuario)
            ->update(['id_estado' => 2]); // Estado inactivo
        
        // Desactivar horarios
        DB::table('doctor_horarios')
            ->where('id_doctor', $idDoctor)
            ->update(['activo' => 0]);
        
        // Desactivar excepciones
        DB::table('doctor_excepciones')
            ->where('id_doctor', $idDoctor)
            ->update(['activo' => 0]);
        
        DB::commit();
        
        return ResponseUtil::success([
            'id_doctor' => $idDoctor,
            'nombre_completo' => $doctor->nombres . ' ' . $doctor->apellidos,
            'estado' => 'Desactivado'
        ], 'Médico desactivado exitosamente');
        
    } catch (Exception $e) {
        DB::rollBack();
        error_log("Error eliminando médico: " . $e->getMessage());
        return ResponseUtil::error('Error desactivando el médico: ' . $e->getMessage());
    }
}

/**
 * Obtener excepciones de un médico (vacaciones, días especiales, etc.)
 */
public function obtenerExcepciones() {
    try {
        $idDoctor = (int)($_GET['id_doctor'] ?? 0);
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d', strtotime('+1 month'));
        
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID de médico requerido');
        }
        
        $excepciones = DB::table('doctor_excepciones')
            ->where('id_doctor', $idDoctor)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('activo', 1)
            ->orderBy('fecha')
            ->get()
            ->map(function($excepcion) {
                $excepcion->tipo_display = match($excepcion->tipo) {
                    'no_laborable' => 'Día no laborable',
                    'horario_especial' => 'Horario especial',
                    'vacaciones' => 'Vacaciones',
                    'feriado' => 'Feriado',
                    default => ucfirst($excepcion->tipo)
                };
                return $excepcion;
            })
            ->toArray();
        
        // Agrupar por tipo para estadísticas
        $estadisticas = [
            'total' => count($excepciones),
            'por_tipo' => []
        ];
        
        foreach ($excepciones as $excepcion) {
            $tipo = $excepcion->tipo;
            if (!isset($estadisticas['por_tipo'][$tipo])) {
                $estadisticas['por_tipo'][$tipo] = [
                    'count' => 0,
                    'display' => $excepcion->tipo_display
                ];
            }
            $estadisticas['por_tipo'][$tipo]['count']++;
        }
        
        return ResponseUtil::success([
            'excepciones' => $excepciones,
            'estadisticas' => $estadisticas,
            'rango' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]
        ], 'Excepciones obtenidas exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo excepciones: ' . $e->getMessage());
    }
}

/**
 * Guardar nueva excepción (vacaciones, día especial, etc.)
 */
public function guardarExcepcion() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        
        $idDoctor = (int)($data['id_doctor'] ?? 0);
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID de médico requerido');
        }
        
        // Validar datos requeridos
        $errores = [];
        if (empty($data['fecha'])) $errores['fecha'] = 'Fecha es requerida';
        if (empty($data['tipo'])) $errores['tipo'] = 'Tipo de excepción es requerido';
        
        // Validar tipo
        $tiposValidos = ['no_laborable', 'horario_especial', 'vacaciones', 'feriado'];
        if (!in_array($data['tipo'], $tiposValidos)) {
            $errores['tipo'] = 'Tipo de excepción inválido';
        }
        
        // Si es horario especial, validar horas
        if ($data['tipo'] === 'horario_especial') {
            if (empty($data['hora_inicio']) || empty($data['hora_fin'])) {
                $errores['horario'] = 'Hora de inicio y fin son requeridas para horario especial';
            } elseif ($data['hora_inicio'] >= $data['hora_fin']) {
                $errores['horario'] = 'Hora de inicio debe ser menor que hora de fin';
            }
        }
        
        if (!empty($errores)) {
            return ResponseUtil::badRequest('Datos inválidos', $errores);
        }
        
        // Verificar si ya existe una excepción para esa fecha
        $existeExcepcion = DB::table('doctor_excepciones')
            ->where('id_doctor', $idDoctor)
            ->where('fecha', $data['fecha'])
            ->where('activo', 1)
            ->exists();
        
        if ($existeExcepcion) {
            return ResponseUtil::badRequest('Ya existe una excepción para esa fecha');
        }
        
        // Preparar datos para insertar
        $excepcionData = [
            'id_doctor' => $idDoctor,
            'fecha' => $data['fecha'],
            'tipo' => $data['tipo'],
            'motivo' => $data['motivo'] ?? null,
            'activo' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        // Agregar horarios si es horario especial
        if ($data['tipo'] === 'horario_especial') {
            $excepcionData['hora_inicio'] = $data['hora_inicio'];
            $excepcionData['hora_fin'] = $data['hora_fin'];
        }
        
        $idExcepcion = DB::table('doctor_excepciones')->insertGetId($excepcionData);
        
        // Obtener la excepción creada
        $excepcionCreada = DB::table('doctor_excepciones')
            ->where('id_excepcion', $idExcepcion)
            ->first();
        
        $excepcionCreada->tipo_display = match($excepcionCreada->tipo) {
            'no_laborable' => 'Día no laborable',
            'horario_especial' => 'Horario especial',
            'vacaciones' => 'Vacaciones',
            'feriado' => 'Feriado',
            default => ucfirst($excepcionCreada->tipo)
        };
        
        return ResponseUtil::success($excepcionCreada, 'Excepción guardada exitosamente');
        
    } catch (Exception $e) {
        error_log("Error guardando excepción: " . $e->getMessage());
        return ResponseUtil::error('Error guardando la excepción: ' . $e->getMessage());
    }
}

public function editarHorario() {
    $data = json_decode(file_get_contents('php://input'), true);
    $idHorario = $data['id_horario'] ?? 0;

    if (!$idHorario) return ResponseUtil::badRequest("ID de horario requerido");

    $updateData = [
        'hora_inicio' => $data['hora_inicio'],
        'hora_fin' => $data['hora_fin'],
        'duracion_cita' => $data['duracion_cita'],
        'dia_semana' => $data['dia_semana'],
        'actualizado_en' => date('Y-m-d H:i:s')
    ];

    DB::table('doctor_horarios')->where('id_horario', $idHorario)->update($updateData);

    return ResponseUtil::success($updateData, "Horario actualizado");
}


/**
 * Obtener estadísticas de médicos
 */
public function obtenerEstadisticas() {
    try {
        $estadisticas = [];
        
        // Médicos por estado
        $estadisticas['por_estado'] = DB::table('doctores as d')
            ->select([
                'u.id_estado',
                DB::raw('CASE 
                    WHEN u.id_estado = 1 THEN "Activo"
                    WHEN u.id_estado = 2 THEN "Inactivo"
                    WHEN u.id_estado = 3 THEN "Pendiente"
                    WHEN u.id_estado = 4 THEN "Bloqueado"
                    ELSE "Desconocido"
                END as estado_texto'),
                DB::raw('COUNT(*) as total')
            ])
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->groupBy('u.id_estado')
            ->get()
            ->toArray();
        
        // Médicos por especialidad
        $estadisticas['por_especialidad'] = DB::table('doctores as d')
            ->select(['e.nombre_especialidad', DB::raw('COUNT(*) as total')])
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->where('u.id_estado', 1) // Solo activos
            ->groupBy('e.id_especialidad', 'e.nombre_especialidad')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        
        // Total de médicos
        $estadisticas['totales'] = [
            'total_medicos' => DB::table('doctores')->count(),
            'medicos_activos' => DB::table('doctores as d')
                ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
                ->where('u.id_estado', 1)
                ->count(),
            'total_especialidades' => DB::table('especialidades')->count(),
            'total_horarios_activos' => DB::table('doctor_horarios')
                ->where('activo', 1)
                ->count()
        ];
        
        // Médicos con más citas este mes
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');
        
        $estadisticas['top_medicos_mes'] = DB::table('citas as c')
            ->select([
                'u.nombres',
                'u.apellidos',
                'e.nombre_especialidad',
                DB::raw('COUNT(*) as total_citas')
            ])
            ->join('doctores as d', 'c.id_doctor', '=', 'd.id_doctor')
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->whereBetween(DB::raw('DATE(c.fecha_hora)'), [$inicioMes, $finMes])
            ->groupBy('c.id_doctor', 'u.nombres', 'u.apellidos', 'e.nombre_especialidad')
            ->orderBy('total_citas', 'DESC')
            ->limit(5)
            ->get()
            ->toArray();
        
        return ResponseUtil::success($estadisticas, 'Estadísticas obtenidas exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo estadísticas: ' . $e->getMessage());
    }
}

/**
 * Eliminar excepción específica
 */
public function eliminarExcepcion() {
    try {
        $idExcepcion = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$idExcepcion) {
            return ResponseUtil::badRequest('ID de excepción requerido');
        }
        
        // Verificar que la excepción existe
        $excepcion = DB::table('doctor_excepciones')
            ->where('id_excepcion', $idExcepcion)
            ->where('activo', 1)
            ->first();
        
        if (!$excepcion) {
            return ResponseUtil::notFound('Excepción no encontrada');
        }
        
        // Desactivar en lugar de eliminar
        DB::table('doctor_excepciones')
            ->where('id_excepcion', $idExcepcion)
            ->update(['activo' => 0]);
        
        return ResponseUtil::success([
            'id_excepcion' => $idExcepcion,
            'fecha' => $excepcion->fecha,
            'tipo' => $excepcion->tipo
        ], 'Excepción eliminada exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error eliminando excepción: ' . $e->getMessage());
    }
}

// 🔥 AGREGAR ESTE NUEVO MÉTODO
/**
 * Buscar médico por cédula
 */
public function buscarPorCedula() {
    try {
        $cedula = $_GET['cedula'] ?? '';
        
        if (empty($cedula)) {
            return ResponseUtil::badRequest('Cédula es requerida');
        }
        
        if (!$this->validarCedulaEcuatoriana($cedula)) {
            return ResponseUtil::badRequest('Cédula ecuatoriana inválida');
        }
        
        $doctor = DB::table('doctores as d')
            ->select([
                'd.id_doctor',
                'd.id_usuario',
                'd.titulo_profesional',
                'u.cedula',
                'u.username',
                'u.nombres',
                'u.apellidos',
                'u.correo',
                'u.sexo',
                'u.nacionalidad',
                'u.id_estado',
                'e.id_especialidad',
                'e.nombre_especialidad',
                DB::raw('CONCAT(u.nombres, " ", u.apellidos) as nombre_completo'),
                DB::raw('CASE 
                    WHEN u.id_estado = 1 THEN "Activo"
                    WHEN u.id_estado = 2 THEN "Inactivo" 
                    WHEN u.id_estado = 3 THEN "Pendiente"
                    WHEN u.id_estado = 4 THEN "Bloqueado"
                    ELSE "Desconocido"
                END as estado_texto')
            ])
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->where('u.cedula', $cedula)
            ->first();
        
        if (!$doctor) {
            return ResponseUtil::notFound('No se encontró un médico con esa cédula');
        }
        
        // Obtener sucursales del médico
        $sucursales = DB::table('doctores_sucursales as ds')
            ->select(['s.id_sucursal', 's.nombre_sucursal'])
            ->join('sucursales as s', 'ds.id_sucursal', '=', 's.id_sucursal')
            ->where('ds.id_doctor', $doctor->id_doctor)
            ->get()
            ->toArray();
        
        $doctor->sucursales = $sucursales;
        
        // Contar horarios activos
        $totalHorarios = DB::table('doctor_horarios')
            ->where('id_doctor', $doctor->id_doctor)
            ->where('activo', 1)
            ->count();
        
        $doctor->total_horarios = $totalHorarios;
        
        return ResponseUtil::success($doctor, 'Médico encontrado exitosamente');
        
    } catch (Exception $e) {
        error_log("Error buscando médico por cédula: " . $e->getMessage());
        return ResponseUtil::error('Error buscando el médico: ' . $e->getMessage());
    }
}

/**
 * Activar/Desactivar médico
 */
public function cambiarEstadoDoctor() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        
        $idDoctor = (int)($data['id_doctor'] ?? 0);
        $nuevoEstado = (int)($data['estado'] ?? 1);
        
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID de médico requerido');
        }
        
        if (!in_array($nuevoEstado, [1, 2, 3, 4])) {
            return ResponseUtil::badRequest('Estado inválido');
        }
        
        // Obtener datos del médico
        $doctor = DB::table('doctores as d')
            ->join('usuarios as u', 'd.id_usuario', '=', 'u.id_usuario')
            ->where('d.id_doctor', $idDoctor)
            ->first(['d.id_doctor', 'd.id_usuario', 'u.nombres', 'u.apellidos', 'u.id_estado']);
        
        if (!$doctor) {
            return ResponseUtil::notFound('Médico no encontrado');
        }
        
        // Actualizar estado
        DB::table('usuarios')
            ->where('id_usuario', $doctor->id_usuario)
            ->update(['id_estado' => $nuevoEstado]);
        
        // Si se desactiva, desactivar horarios
        if ($nuevoEstado == 2) {
            DB::table('doctor_horarios')
                ->where('id_doctor', $idDoctor)
                ->update(['activo' => 0]);
        }
        
        $estadoTexto = match($nuevoEstado) {
            1 => 'Activo',
            2 => 'Inactivo', 
            3 => 'Pendiente',
            4 => 'Bloqueado'
        };
        
        return ResponseUtil::success([
            'id_doctor' => $idDoctor,
            'nombre_completo' => $doctor->nombres . ' ' . $doctor->apellidos,
            'estado_anterior' => $doctor->id_estado,
            'estado_nuevo' => $nuevoEstado,
            'estado_texto' => $estadoTexto
        ], "Estado del médico cambiado a {$estadoTexto}");
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error cambiando estado: ' . $e->getMessage());
    }
}
    
    private function calcularDisponibilidadDoctor($idDoctor, $idSucursal, $fechaInicio, $fechaFin) {
    // Obtener horarios regulares CON duración
    $horariosQuery = DB::table('doctor_horarios')
        ->select([
            'id_horario',
            'id_sucursal', 
            'dia_semana',
            'hora_inicio',
            'hora_fin',
            'duracion_cita', // 🔥 INCLUIR duración
            'activo'
        ])
        ->where('id_doctor', $idDoctor)
        ->where('activo', 1);
        
    if ($idSucursal > 0) {
        $horariosQuery->where('id_sucursal', $idSucursal);
    }
    
    $horarios = $horariosQuery->get()->toArray();
    
    // Para cada horario, calcular slots disponibles
    foreach ($horarios as &$horario) {
        $inicio = strtotime($horario->hora_inicio);
        $fin = strtotime($horario->hora_fin);
        $duracionMinutos = $horario->duracion_cita;
        
        $slots = [];
        $currentTime = $inicio;
        
        while ($currentTime + ($duracionMinutos * 60) <= $fin) {
            $slots[] = date('H:i', $currentTime);
            $currentTime += ($duracionMinutos * 60);
        }
        
        $horario->slots_disponibles = $slots;
        $horario->total_slots = count($slots);
    }
    
    // Obtener citas existentes
    $citasQuery = DB::table('citas')
        ->select(['fecha_hora', 'estado', 'motivo'])
        ->where('id_doctor', $idDoctor)
        ->whereBetween(DB::raw('DATE(fecha_hora)'), [$fechaInicio, $fechaFin])
        ->whereIn('estado', ['Pendiente', 'Confirmada']);
        
    if ($idSucursal > 0) {
        $citasQuery->where('id_sucursal', $idSucursal);
    }
    
    $citas = $citasQuery->get()->toArray();
    
    // Obtener excepciones
    $excepciones = DB::table('doctor_excepciones')
        ->where('id_doctor', $idDoctor)
        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
        ->where('activo', 1)
        ->get()
        ->toArray();
    
    return [
        'horarios' => $horarios,
        'citas_ocupadas' => $citas,
        'excepciones' => $excepciones,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'configuracion' => [
            'duracion_por_defecto' => 30,
            'duraciones_permitidas' => [15, 20, 30, 45, 60, 90, 120]
        ]
    ];
}
}

// Instanciar y manejar la solicitud si se accede directamente
if (basename($_SERVER['SCRIPT_NAME']) === 'DoctoresApiController.php') {
    $controller = new DoctoresApiController();
    $controller->manejarSolicitud();
}
?>