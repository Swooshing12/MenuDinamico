<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\ResponseUtil;
use App\Validators\DateValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;


class CitasController
{
    // PUNTO 5: API para consultar citas por especialidad - MEJORADA
    public function getCitasByEspecialidad(Request $request, Response $response, array $args): Response
    {
        $idEspecialidad = $args['id_especialidad'];
        
        // Validación básica
        if (!is_numeric($idEspecialidad) || $idEspecialidad <= 0) {
            return ResponseUtil::badRequest('El ID de especialidad debe ser un número válido mayor a 0');
        }
        
        try {
            // Verificar que la especialidad existe
            $especialidad = DB::table('especialidades')
                ->where('id_especialidad', $idEspecialidad)
                ->first();
                
            if (!$especialidad) {
                return ResponseUtil::notFound('No se encontró la especialidad con ID: ' . $idEspecialidad);
            }
            
            // Obtener médicos de esta especialidad
            $medicosEspecialidad = DB::table('doctores')
                ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
                ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
                ->where('doctores.id_especialidad', $idEspecialidad)
                ->select(
                    'doctores.id_doctor',
                    'usuarios.nombres',
                    'usuarios.apellidos',
                    'doctores.titulo_profesional'
                )
                ->get();
            
            // Obtener citas agrupadas por médico
            $citasPorMedico = [];
            $totalCitas = 0;
            $estadisticasGlobales = [
                'pendientes' => 0,
                'completadas' => 0,
                'canceladas' => 0,
                'confirmadas' => 0,
                'presenciales' => 0,
                'virtuales' => 0
            ];
            
            foreach ($medicosEspecialidad as $medico) {
                $citas = $this->getCitasQuery()
                    ->where('especialidades.id_especialidad', $idEspecialidad)
                    ->where('doctores.id_doctor', $medico->id_doctor)
                    ->orderBy('citas.fecha_hora', 'desc')
                    ->get();
                
                $estadisticasMedico = [
                    'total_citas' => count($citas),
                    'pendientes' => collect($citas)->where('estado', 'Pendiente')->count(),
                    'completadas' => collect($citas)->where('estado', 'Completada')->count(),
                    'canceladas' => collect($citas)->where('estado', 'Cancelada')->count(),
                    'confirmadas' => collect($citas)->where('estado', 'Confirmada')->count(),
                    'presenciales' => collect($citas)->where('tipo_cita', 'presencial')->count(),
                    'virtuales' => collect($citas)->where('tipo_cita', 'virtual')->count()
                ];
                
                $citasPorMedico[] = [
                    'medico' => [
                        'id_doctor' => $medico->id_doctor,
                        'nombres' => $medico->nombres,
                        'apellidos' => $medico->apellidos,
                        'nombre_completo' => $medico->nombres . ' ' . $medico->apellidos,
                        'titulo_profesional' => $medico->titulo_profesional
                    ],
                    'citas' => $citas,
                    'estadisticas' => $estadisticasMedico
                ];
                
                // Acumular estadísticas globales
                $totalCitas += count($citas);
                $estadisticasGlobales['pendientes'] += $estadisticasMedico['pendientes'];
                $estadisticasGlobales['completadas'] += $estadisticasMedico['completadas'];
                $estadisticasGlobales['canceladas'] += $estadisticasMedico['canceladas'];
                $estadisticasGlobales['confirmadas'] += $estadisticasMedico['confirmadas'];
                $estadisticasGlobales['presenciales'] += $estadisticasMedico['presenciales'];
                $estadisticasGlobales['virtuales'] += $estadisticasMedico['virtuales'];
            }
            
            $responseData = [
                'especialidad' => [
                    'id_especialidad' => $especialidad->id_especialidad,
                    'nombre_especialidad' => $especialidad->nombre_especialidad,
                    'descripcion' => $especialidad->descripcion,
                    'total_medicos' => count($medicosEspecialidad)
                ],
                'resumen_estadisticas' => [
                    'total_citas' => $totalCitas,
                    'total_medicos_con_citas' => count(array_filter($citasPorMedico, function($m) { return $m['estadisticas']['total_citas'] > 0; })),
                    'distribucion_estados' => $estadisticasGlobales,
                    'promedio_citas_por_medico' => count($medicosEspecialidad) > 0 ? round($totalCitas / count($medicosEspecialidad), 2) : 0
                ],
                'citas_por_medico' => $citasPorMedico
            ];
            
            return ResponseUtil::success($responseData, 'Análisis completo de citas para la especialidad: ' . $especialidad->nombre_especialidad);
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error al analizar citas por especialidad: ' . $e->getMessage());
        }
    }
    
    // PUNTO 5 y 8: API para consultar citas por médico - MEJORADA
    public function getCitasByMedico(Request $request, Response $response, array $args): Response
    {
        $idMedico = $args['id_medico'];
        
        // Validación básica
        if (!is_numeric($idMedico) || $idMedico <= 0) {
            return ResponseUtil::badRequest('El ID del médico debe ser un número válido mayor a 0');
        }
        
        try {
            // Información completa del médico
            $medico = DB::table('doctores')
                ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
                ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
                ->select(
                    'doctores.id_doctor',
                    'usuarios.nombres',
                    'usuarios.apellidos',
                    'usuarios.correo',
                    'doctores.titulo_profesional',
                    'especialidades.id_especialidad',
                    'especialidades.nombre_especialidad',
                    'especialidades.descripcion as especialidad_descripcion'
                )
                ->where('doctores.id_doctor', $idMedico)
                ->first();
                
            if (!$medico) {
                return ResponseUtil::notFound('No se encontró el médico con ID: ' . $idMedico);
            }
            
            // Punto 8: Solo citas asignadas a este médico específico
            $todasLasCitas = $this->getCitasQuery()
                ->where('doctores.id_doctor', $idMedico)
                ->orderBy('citas.fecha_hora', 'desc')
                ->get();
            
            // Organizar citas por estado
            $citasPorEstado = [
                'pendientes' => collect($todasLasCitas)->where('estado', 'Pendiente')->values(),
                'confirmadas' => collect($todasLasCitas)->where('estado', 'Confirmada')->values(),
                'completadas' => collect($todasLasCitas)->where('estado', 'Completada')->values(),
                'canceladas' => collect($todasLasCitas)->where('estado', 'Cancelada')->values()
            ];
            
            // Organizar citas por tipo
            $citasPorTipo = [
                'presenciales' => collect($todasLasCitas)->where('tipo_cita', 'presencial')->values(),
                'virtuales' => collect($todasLasCitas)->where('tipo_cita', 'virtual')->values()
            ];
            
            // Citas próximas (siguientes 30 días)
            $fechaLimite = Carbon::now()->addDays(30);
            $citasProximas = collect($todasLasCitas)
                ->filter(function($cita) use ($fechaLimite) {
                    return Carbon::parse($cita->fecha_hora)->between(Carbon::now(), $fechaLimite) 
                           && in_array($cita->estado, ['Pendiente', 'Confirmada']);
                })
                ->sortBy('fecha_hora')
                ->values();
            
            // Análisis temporal
            $citasPorMes = collect($todasLasCitas)
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->format('Y-m');
                })
                ->map(function($citas) {
                    return count($citas);
                });
            
            // Sucursales donde atiende
            $sucursalesAtencion = collect($todasLasCitas)
                ->groupBy('id_sucursal')
                ->map(function($citas) {
                    $primera = $citas->first();
                    return [
                        'id_sucursal' => $primera->id_sucursal,
                        'nombre_sucursal' => $primera->nombre_sucursal,
                        'direccion' => $primera->sucursal_direccion,
                        'total_citas' => count($citas)
                    ];
                })
                ->values();
            
            $responseData = [
                'medico' => [
                    'id_doctor' => $medico->id_doctor,
                    'nombres' => $medico->nombres,
                    'apellidos' => $medico->apellidos,
                    'nombre_completo' => $medico->nombres . ' ' . $medico->apellidos,
                    'correo' => $medico->correo,
                    'titulo_profesional' => $medico->titulo_profesional,
                    'especialidad' => [
                        'id_especialidad' => $medico->id_especialidad,
                        'nombre' => $medico->nombre_especialidad,
                        'descripcion' => $medico->especialidad_descripcion
                    ]
                ],
                'resumen_estadisticas' => [
                    'total_citas' => count($todasLasCitas),
                    'citas_pendientes' => count($citasPorEstado['pendientes']),
                    'citas_confirmadas' => count($citasPorEstado['confirmadas']),
                    'citas_completadas' => count($citasPorEstado['completadas']),
                    'citas_canceladas' => count($citasPorEstado['canceladas']),
                    'citas_presenciales' => count($citasPorTipo['presenciales']),
                    'citas_virtuales' => count($citasPorTipo['virtuales']),
                    'proximas_citas' => count($citasProximas),
                    'sucursales_atencion' => count($sucursalesAtencion)
                ],
                'citas_proximas' => $citasProximas,
                'citas_por_estado' => $citasPorEstado,
                'citas_por_tipo' => $citasPorTipo,
                'analisis_temporal' => $citasPorMes,
                'sucursales_atencion' => $sucursalesAtencion,
                'todas_las_citas' => $todasLasCitas
            ];
            
            return ResponseUtil::success($responseData, 'Análisis completo de citas para Dr. ' . $medico->nombres . ' ' . $medico->apellidos);
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error al analizar citas del médico: ' . $e->getMessage());
        }
    }
    
    // PUNTO 5: API para consultar citas por especialidad Y médico - MEJORADA
    public function getCitasByEspecialidadYMedico(Request $request, Response $response, array $args): Response
    {
        $idEspecialidad = $args['id_especialidad'];
        $idMedico = $args['id_medico'];
        
        // Validaciones básicas
        if (!is_numeric($idEspecialidad) || $idEspecialidad <= 0) {
            return ResponseUtil::badRequest('El ID de especialidad debe ser un número válido mayor a 0');
        }
        
        if (!is_numeric($idMedico) || $idMedico <= 0) {
            return ResponseUtil::badRequest('El ID del médico debe ser un número válido mayor a 0');
        }
        
        try {
            // Verificar relación médico-especialidad
            $medicoEspecialidad = DB::table('doctores')
                ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
                ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
                ->select(
                    'doctores.id_doctor',
                    'usuarios.nombres',
                    'usuarios.apellidos',
                    'doctores.titulo_profesional',
                    'especialidades.id_especialidad',
                    'especialidades.nombre_especialidad',
                    'especialidades.descripcion as especialidad_descripcion'
                )
                ->where('doctores.id_doctor', $idMedico)
                ->where('doctores.id_especialidad', $idEspecialidad)
                ->first();
                
            if (!$medicoEspecialidad) {
                return ResponseUtil::notFound('No se encontró un médico con ID ' . $idMedico . ' en la especialidad con ID ' . $idEspecialidad);
            }
            
            // Obtener citas específicas de este médico en esta especialidad
            $citas = $this->getCitasQuery()
                ->where('especialidades.id_especialidad', $idEspecialidad)
                ->where('doctores.id_doctor', $idMedico)
                ->orderBy('citas.fecha_hora', 'desc')
                ->get();
            
            // Análisis detallado
            $analisisPorMes = collect($citas)
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->format('Y-m');
                })
                ->map(function($citasMes, $mes) {
                    return [
                        'mes' => $mes,
                        'total' => count($citasMes),
                        'pendientes' => collect($citasMes)->where('estado', 'Pendiente')->count(),
                        'completadas' => collect($citasMes)->where('estado', 'Completada')->count(),
                        'canceladas' => collect($citasMes)->where('estado', 'Cancelada')->count()
                    ];
                })
                ->values();
            
            // Pacientes únicos atendidos
            $pacientesUnicos = collect($citas)
                ->groupBy('paciente_cedula')
                ->map(function($citasPaciente) {
                    $primera = $citasPaciente->first();
                    return [
                        'cedula' => $primera->paciente_cedula,
                        'nombres' => $primera->paciente_nombres,
                        'apellidos' => $primera->paciente_apellidos,
                        'total_citas' => count($citasPaciente),
                        'ultima_cita' => collect($citasPaciente)->max('fecha_hora')
                    ];
                })
                ->values();
            
            $responseData = [
                'especialidad' => [
                    'id_especialidad' => $medicoEspecialidad->id_especialidad,
                    'nombre_especialidad' => $medicoEspecialidad->nombre_especialidad,
                    'descripcion' => $medicoEspecialidad->especialidad_descripcion
                ],
                'medico' => [
                    'id_doctor' => $medicoEspecialidad->id_doctor,
                    'nombres' => $medicoEspecialidad->nombres,
                    'apellidos' => $medicoEspecialidad->apellidos,
                    'nombre_completo' => $medicoEspecialidad->nombres . ' ' . $medicoEspecialidad->apellidos,
                    'titulo_profesional' => $medicoEspecialidad->titulo_profesional
                ],
                'resumen_estadisticas' => [
                    'total_citas' => count($citas),
                    'pacientes_unicos' => count($pacientesUnicos),
                    'promedio_citas_por_paciente' => count($pacientesUnicos) > 0 ? round(count($citas) / count($pacientesUnicos), 2) : 0,
                    'distribucion_estados' => [
                        'pendientes' => collect($citas)->where('estado', 'Pendiente')->count(),
                        'confirmadas' => collect($citas)->where('estado', 'Confirmada')->count(),
                        'completadas' => collect($citas)->where('estado', 'Completada')->count(),
                        'canceladas' => collect($citas)->where('estado', 'Cancelada')->count()
                    ],
                    'distribucion_tipos' => [
                        'presenciales' => collect($citas)->where('tipo_cita', 'presencial')->count(),
                        'virtuales' => collect($citas)->where('tipo_cita', 'virtual')->count()
                    ]
                ],
                'analisis_temporal' => $analisisPorMes,
                'pacientes_atendidos' => $pacientesUnicos,
                'citas' => $citas
            ];
            
            return ResponseUtil::success($responseData, 'Análisis específico: ' . $medicoEspecialidad->nombre_especialidad . ' - Dr. ' . $medicoEspecialidad->nombres . ' ' . $medicoEspecialidad->apellidos);
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error al analizar citas específicas: ' . $e->getMessage());
        }
    }
    
    // PUNTO 7: API para consultar citas por rango de fechas - MEJORADA
    public function getCitasByRangoFechas(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validaciones básicas
        if (empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
            return ResponseUtil::badRequest('Los campos fecha_inicio y fecha_fin son requeridos', [
                'fecha_inicio' => empty($data['fecha_inicio']) ? 'La fecha de inicio es requerida' : null,
                'fecha_fin' => empty($data['fecha_fin']) ? 'La fecha de fin es requerida' : null
            ]);
        }
        
        // Validar formato de fechas
        $erroresFechaInicio = DateValidator::validate($data['fecha_inicio']);
        $erroresFechaFin = DateValidator::validate($data['fecha_fin']);
        
        if (!empty($erroresFechaInicio) || !empty($erroresFechaFin)) {
            return ResponseUtil::badRequest('Formato de fechas inválido', [
                'fecha_inicio' => $erroresFechaInicio,
                'fecha_fin' => $erroresFechaFin
            ]);
        }
        
        try {
            // Convertir fechas
            $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
            $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();
            
            // Validaciones de rango
            if ($fechaInicio->gt($fechaFin)) {
                return ResponseUtil::badRequest('La fecha de inicio no puede ser mayor que la fecha de fin');
            }
            
            if ($fechaInicio->diffInDays($fechaFin) > 365) {
                return ResponseUtil::badRequest('El rango de fechas no puede ser mayor a 365 días');
            }
            
            // Query base con filtros
            $query = $this->getCitasQuery()->whereBetween('citas.fecha_hora', [$fechaInicio, $fechaFin]);
            
            // Aplicar filtros opcionales
            if (!empty($data['id_medico'])) {
                if (!is_numeric($data['id_medico'])) {
                    return ResponseUtil::badRequest('El ID del médico debe ser un número válido');
                }
                $query->where('doctores.id_doctor', $data['id_medico']);
            }
            
            if (!empty($data['id_especialidad'])) {
                if (!is_numeric($data['id_especialidad'])) {
                    return ResponseUtil::badRequest('El ID de especialidad debe ser un número válido');
                }
                $query->where('especialidades.id_especialidad', $data['id_especialidad']);
            }
            
            if (!empty($data['estado'])) {
                $query->where('citas.estado', $data['estado']);
            }
            
            $citas = $query->orderBy('citas.fecha_hora', 'asc')->get();
            
            // Análisis avanzado del rango
            $analisisPorDia = collect($citas)
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->format('Y-m-d');
                })
                ->map(function($citasDia, $dia) {
                    return [
                        'fecha' => $dia,
                        'total_citas' => count($citasDia),
                        'completadas' => collect($citasDia)->where('estado', 'Completada')->count(),
                        'pendientes' => collect($citasDia)->where('estado', 'Pendiente')->count(),
                        'canceladas' => collect($citasDia)->where('estado', 'Cancelada')->count()
                    ];
                })
                ->values();
            
            $analisisPorMedico = collect($citas)
                ->groupBy('id_doctor')
                ->map(function($citasMedico) {
                    $primera = $citasMedico->first();
                    return [
                        'id_doctor' => $primera->id_doctor,
                        'medico_nombre' => $primera->medico_nombres . ' ' . $primera->medico_apellidos,
                        'especialidad' => $primera->nombre_especialidad,
                        'total_citas' => count($citasMedico),
                        'distribucion_estados' => [
                            'completadas' => collect($citasMedico)->where('estado', 'Completada')->count(),
                            'pendientes' => collect($citasMedico)->where('estado', 'Pendiente')->count(),
                            'canceladas' => collect($citasMedico)->where('estado', 'Cancelada')->count()
                        ]
                    ];
                })
                ->values();
            
            $estadisticasAvanzadas = [
                'total_citas' => count($citas),
                'fecha_inicio' => $fechaInicio->format('Y-m-d H:i:s'),
                'fecha_fin' => $fechaFin->format('Y-m-d H:i:s'),
                'dias_consultados' => $fechaInicio->diffInDays($fechaFin) + 1,
                'promedio_citas_por_dia' => count($citas) > 0 ? round(count($citas) / ($fechaInicio->diffInDays($fechaFin) + 1), 2) : 0,
                'distribucion_estados' => [
                    'pendientes' => collect($citas)->where('estado', 'Pendiente')->count(),
                    'confirmadas' => collect($citas)->where('estado', 'Confirmada')->count(),
                    'completadas' => collect($citas)->where('estado', 'Completada')->count(),
                    'canceladas' => collect($citas)->where('estado', 'Cancelada')->count()
                ],
                'distribucion_tipos' => [
                    'presenciales' => collect($citas)->where('tipo_cita', 'presencial')->count(),
                    'virtuales' => collect($citas)->where('tipo_cita', 'virtual')->count()
                ],
                'especialidades_involucradas' => collect($citas)->pluck('nombre_especialidad')->unique()->count(),
                'medicos_involucrados' => collect($citas)->pluck('id_doctor')->unique()->count(),
                'sucursales_involucradas' => collect($citas)->pluck('id_sucursal')->unique()->count()
            ];
            
            $responseData = [
                'parametros_busqueda' => [
                    'fecha_inicio' => $data['fecha_inicio'],
                    'fecha_fin' => $data['fecha_fin'],
                    'id_medico' => $data['id_medico'] ?? null,
                    'id_especialidad' => $data['id_especialidad'] ?? null,
                    'estado' => $data['estado'] ?? null
                ],
                'estadisticas_generales' => $estadisticasAvanzadas,
                'analisis_temporal' => $analisisPorDia,
                'analisis_por_medico' => $analisisPorMedico,
                'citas' => $citas
            ];
            
            return ResponseUtil::success($responseData, 'Análisis completo de citas en el rango de fechas especificado');
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error al analizar citas por rango de fechas: ' . $e->getMessage());
        }
    }



    // NUEVO ENDPOINT: Consultar citas por especialidad (ID) y médico (cédula)
public function getCitasByEspecialidadYMedicoCedula(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    
    // Validaciones de campos requeridos
    if (empty($data['id_especialidad'])) {
        return ResponseUtil::badRequest('El campo id_especialidad es requerido');
    }
    
    if (empty($data['cedula_medico'])) {
        return ResponseUtil::badRequest('El campo cedula_medico es requerido');
    }
    
    // Validar ID de especialidad
    if (!is_numeric($data['id_especialidad']) || $data['id_especialidad'] <= 0) {
        return ResponseUtil::badRequest('El ID de especialidad debe ser un número válido mayor a 0');
    }
    
    // Validar cédula del médico
    $erroresCedula = \App\Validators\CedulaValidator::validate($data['cedula_medico']);
    if (!empty($erroresCedula)) {
        return ResponseUtil::badRequest('La cédula del médico no es válida', $erroresCedula);
    }
    
    try {
        // PASO 1: Verificar que la especialidad existe
        $especialidad = DB::table('especialidades')
            ->where('id_especialidad', $data['id_especialidad'])
            ->first();
            
        if (!$especialidad) {
            return ResponseUtil::notFound('No se encontró la especialidad con ID: ' . $data['id_especialidad']);
        }
        
        // PASO 2: Buscar el médico por cédula y verificar que pertenece a la especialidad
        $medico = DB::table('doctores')
            ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->select(
                'doctores.id_doctor',
                'usuarios.id_usuario',
                'usuarios.cedula',
                'usuarios.username',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.correo',
                'usuarios.sexo',
                'usuarios.nacionalidad',
                'doctores.titulo_profesional',
                'especialidades.id_especialidad',
                'especialidades.nombre_especialidad'
            )
            ->where('usuarios.cedula', $data['cedula_medico'])
            ->where('doctores.id_especialidad', $data['id_especialidad'])
            ->first();
            
        if (!$medico) {
            return ResponseUtil::notFound(
                'No se encontró un médico con cédula ' . $data['cedula_medico'] . 
                ' que pertenezca a la especialidad ' . $especialidad->nombre_especialidad
            );
        }
        
        // PASO 3: Obtener todas las citas del médico en esa especialidad
        $todasLasCitas = $this->getCitasQuery()
            ->where('especialidades.id_especialidad', $data['id_especialidad'])
            ->where('doctores.id_doctor', $medico->id_doctor)
            ->orderBy('citas.fecha_hora', 'desc')
            ->get();
        
        // PASO 4: Aplicar filtros opcionales si se proporcionan
        $citasFiltradas = collect($todasLasCitas);
        
        // Filtro por estado
        if (!empty($data['estado'])) {
            $estadosValidos = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada'];
            if (!in_array($data['estado'], $estadosValidos)) {
                return ResponseUtil::badRequest('Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos));
            }
            $citasFiltradas = $citasFiltradas->where('estado', $data['estado']);
        }
        
        // Filtro por tipo de cita
        if (!empty($data['tipo_cita'])) {
            $tiposValidos = ['presencial', 'virtual'];
            if (!in_array($data['tipo_cita'], $tiposValidos)) {
                return ResponseUtil::badRequest('Tipo de cita inválido. Valores permitidos: ' . implode(', ', $tiposValidos));
            }
            $citasFiltradas = $citasFiltradas->where('tipo_cita', $data['tipo_cita']);
        }
        
        // Filtro por rango de fechas
        if (!empty($data['fecha_desde']) && !empty($data['fecha_hasta'])) {
            $erroresFechaDesde = \App\Validators\DateValidator::validate($data['fecha_desde']);
            $erroresFechaHasta = \App\Validators\DateValidator::validate($data['fecha_hasta']);
            
            if (!empty($erroresFechaDesde) || !empty($erroresFechaHasta)) {
                return ResponseUtil::badRequest('Formato de fechas inválido', [
                    'fecha_desde' => $erroresFechaDesde,
                    'fecha_hasta' => $erroresFechaHasta
                ]);
            }
            
            $fechaDesde = Carbon::parse($data['fecha_desde'])->startOfDay();
            $fechaHasta = Carbon::parse($data['fecha_hasta'])->endOfDay();
            
            if ($fechaDesde->gt($fechaHasta)) {
                return ResponseUtil::badRequest('La fecha desde no puede ser mayor que fecha hasta');
            }
            
            $citasFiltradas = $citasFiltradas->filter(function($cita) use ($fechaDesde, $fechaHasta) {
                $fechaCita = Carbon::parse($cita->fecha_hora);
                return $fechaCita->between($fechaDesde, $fechaHasta);
            });
        }
        
        // Filtro por sucursal
        if (!empty($data['id_sucursal'])) {
            if (!is_numeric($data['id_sucursal'])) {
                return ResponseUtil::badRequest('El ID de sucursal debe ser numérico');
            }
            $citasFiltradas = $citasFiltradas->where('id_sucursal', $data['id_sucursal']);
        }
        
        // Filtro por paciente específico
        if (!empty($data['cedula_paciente'])) {
            $erroresCedulaPaciente = \App\Validators\CedulaValidator::validate($data['cedula_paciente']);
            if (!empty($erroresCedulaPaciente)) {
                return ResponseUtil::badRequest('Cédula del paciente inválida', $erroresCedulaPaciente);
            }
            $citasFiltradas = $citasFiltradas->where('paciente_cedula', $data['cedula_paciente']);
        }
        
        // Convertir de vuelta a array
        $citasFiltradas = $citasFiltradas->values()->all();
        
        // PASO 5: Organizar y segmentar la información eficientemente
        
        // 5.1 INFORMACIÓN GENERAL
        $informacionGeneral = [
            'especialidad' => [
                'id_especialidad' => $especialidad->id_especialidad,
                'nombre_especialidad' => $especialidad->nombre_especialidad,
                'descripcion' => $especialidad->descripcion
            ],
            'medico' => [
                'id_doctor' => $medico->id_doctor,
                'cedula' => $medico->cedula,
                'username' => $medico->username,
                'nombres' => $medico->nombres,
                'apellidos' => $medico->apellidos,
                'nombre_completo' => $medico->nombres . ' ' . $medico->apellidos,
                'correo' => $medico->correo,
                'sexo' => $medico->sexo,
                'nacionalidad' => $medico->nacionalidad,
                'titulo_profesional' => $medico->titulo_profesional,
                'especialidad_asignada' => $medico->nombre_especialidad
            ],
            'parametros_consulta' => array_filter([
                'id_especialidad' => $data['id_especialidad'],
                'cedula_medico' => $data['cedula_medico'],
                'estado' => $data['estado'] ?? null,
                'tipo_cita' => $data['tipo_cita'] ?? null,
                'fecha_desde' => $data['fecha_desde'] ?? null,
                'fecha_hasta' => $data['fecha_hasta'] ?? null,
                'id_sucursal' => $data['id_sucursal'] ?? null,
                'cedula_paciente' => $data['cedula_paciente'] ?? null
            ])
        ];
        
        // 5.2 ESTADÍSTICAS COMPLETAS
        $estadisticasCompletas = [
            'totales' => [
                'total_citas_encontradas' => count($citasFiltradas),
                'total_citas_medico_especialidad' => count($todasLasCitas),
                'filtros_aplicados' => count(array_filter($data)) - 2 // -2 porque id_especialidad y cedula_medico son obligatorios
            ],
            'distribucion_por_estado' => [
                'pendientes' => collect($citasFiltradas)->where('estado', 'Pendiente')->count(),
                'confirmadas' => collect($citasFiltradas)->where('estado', 'Confirmada')->count(),
                'completadas' => collect($citasFiltradas)->where('estado', 'Completada')->count(),
                'canceladas' => collect($citasFiltradas)->where('estado', 'Cancelada')->count()
            ],
            'distribucion_por_tipo' => [
                'presenciales' => collect($citasFiltradas)->where('tipo_cita', 'presencial')->count(),
                'virtuales' => collect($citasFiltradas)->where('tipo_cita', 'virtual')->count()
            ],
            'pacientes_atendidos' => [
                'total_pacientes_unicos' => collect($citasFiltradas)->pluck('paciente_cedula')->unique()->count(),
                'promedio_citas_por_paciente' => collect($citasFiltradas)->pluck('paciente_cedula')->unique()->count() > 0 
                    ? round(count($citasFiltradas) / collect($citasFiltradas)->pluck('paciente_cedula')->unique()->count(), 2) 
                    : 0
            ]
        ];
        
        // 5.3 ANÁLISIS TEMPORAL
        $analisisTemporal = [
            'por_mes' => collect($citasFiltradas)
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->format('Y-m');
                })
                ->map(function($citasMes, $mes) {
                    return [
                        'mes' => $mes,
                        'mes_nombre' => Carbon::parse($mes . '-01')->locale('es')->isoFormat('MMMM YYYY'),
                        'total_citas' => count($citasMes),
                        'completadas' => collect($citasMes)->where('estado', 'Completada')->count(),
                        'pendientes' => collect($citasMes)->where('estado', 'Pendiente')->count(),
                        'canceladas' => collect($citasMes)->where('estado', 'Cancelada')->count()
                    ];
                })
                ->values(),
            'proximas_citas' => collect($citasFiltradas)
                ->filter(function($cita) {
                    $fechaCita = Carbon::parse($cita->fecha_hora);
                    return $fechaCita->isFuture() && in_array($cita->estado, ['Pendiente', 'Confirmada']);
                })
                ->sortBy('fecha_hora')
                ->take(10)
                ->values()
        ];
        
        // 5.4 ANÁLISIS POR SUCURSAL
        $analisisPorSucursal = collect($citasFiltradas)
            ->groupBy('id_sucursal')
            ->map(function($citasSucursal) {
                $primera = $citasSucursal->first();
                return [
                    'sucursal' => [
                        'id_sucursal' => $primera->id_sucursal,
                        'nombre_sucursal' => $primera->nombre_sucursal,
                        'direccion' => $primera->sucursal_direccion,
                        'telefono' => $primera->sucursal_telefono,
                        'email' => $primera->sucursal_email
                    ],
                    'estadisticas' => [
                        'total_citas' => count($citasSucursal),
                        'completadas' => collect($citasSucursal)->where('estado', 'Completada')->count(),
                        'pendientes' => collect($citasSucursal)->where('estado', 'Pendiente')->count(),
                        'presenciales' => collect($citasSucursal)->where('tipo_cita', 'presencial')->count(),
                        'virtuales' => collect($citasSucursal)->where('tipo_cita', 'virtual')->count()
                    ]
                ];
            })
            ->values();
        
        // 5.5 TOP PACIENTES (más citas)
        $topPacientes = collect($citasFiltradas)
            ->groupBy('paciente_cedula')
            ->map(function($citasPaciente) {
                $primera = $citasPaciente->first();
                return [
                    'paciente' => [
                        'cedula' => $primera->paciente_cedula,
                        'nombres' => $primera->paciente_nombres,
                        'apellidos' => $primera->paciente_apellidos,
                        'nombre_completo' => $primera->paciente_nombres . ' ' . $primera->paciente_apellidos,
                        'telefono' => $primera->paciente_telefono,
                        'tipo_sangre' => $primera->tipo_sangre
                    ],
                    'resumen_citas' => [
                        'total_citas' => count($citasPaciente),
                        'completadas' => collect($citasPaciente)->where('estado', 'Completada')->count(),
                        'pendientes' => collect($citasPaciente)->where('estado', 'Pendiente')->count(),
                        'canceladas' => collect($citasPaciente)->where('estado', 'Cancelada')->count(),
                        'primera_cita' => collect($citasPaciente)->min('fecha_hora'),
                        'ultima_cita' => collect($citasPaciente)->max('fecha_hora')
                    ]
                ];
            })
            ->sortByDesc('resumen_citas.total_citas')
            ->take(10)
            ->values();
        
        // 5.6 CITAS DETALLADAS ORGANIZADAS
        $citasOrganizadas = collect($citasFiltradas)->map(function($cita) {
            return [
                'cita' => [
                    'id_cita' => $cita->id_cita,
                    'fecha_hora' => $cita->fecha_hora,
                    'fecha_formateada' => Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY [a las] HH:mm'),
                    'motivo' => $cita->motivo,
                    'estado' => $cita->estado,
                    'tipo_cita' => $cita->tipo_cita,
                    'notas' => $cita->notas,
                    'enlace_virtual' => $cita->enlace_virtual,
                    'sala_virtual' => $cita->sala_virtual,
                    'fecha_creacion' => $cita->cita_creada
                ],
                'paciente' => [
                    'cedula' => $cita->paciente_cedula,
                    'nombres' => $cita->paciente_nombres,
                    'apellidos' => $cita->paciente_apellidos,
                    'nombre_completo' => $cita->paciente_nombres . ' ' . $cita->paciente_apellidos,
                    'sexo' => $cita->paciente_sexo,
                    'telefono' => $cita->paciente_telefono,
                    'tipo_sangre' => $cita->tipo_sangre
                ],
                'sucursal' => [
                    'id_sucursal' => $cita->id_sucursal,
                    'nombre' => $cita->nombre_sucursal,
                    'direccion' => $cita->sucursal_direccion,
                    'telefono' => $cita->sucursal_telefono
                ],
                'tipo_cita_detalle' => [
                    'nombre' => $cita->nombre_tipo_cita,
                    'descripcion' => $cita->descripcion_tipo_cita
                ]
            ];
        })->values();
        
        // RESPUESTA FINAL ORGANIZADA
        $responseData = [
            'informacion_general' => $informacionGeneral,
            'estadisticas_completas' => $estadisticasCompletas,
            'analisis_temporal' => $analisisTemporal,
            'analisis_por_sucursal' => $analisisPorSucursal,
            'top_pacientes' => $topPacientes,
            'citas_detalladas' => $citasOrganizadas
        ];
        
        $mensaje = sprintf(
            'Consulta exitosa: %d citas encontradas para %s en %s',
            count($citasFiltradas),
            $medico->nombres . ' ' . $medico->apellidos,
            $especialidad->nombre_especialidad
        );
        
        return ResponseUtil::success($responseData, $mensaje);
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error al consultar citas por especialidad y médico: ' . $e->getMessage());
    }
}
    
// ENDPOINT MEJORADO: Rango de fechas + cédula médico (CON INFORMACIÓN MÉDICA COMPLETA)
public function getCitasByRangoFechasYMedicoCedula(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    
    // Validaciones de campos requeridos
    if (empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
        return ResponseUtil::badRequest('Los campos fecha_inicio y fecha_fin son requeridos', [
            'fecha_inicio' => empty($data['fecha_inicio']) ? 'La fecha de inicio es requerida' : null,
            'fecha_fin' => empty($data['fecha_fin']) ? 'La fecha de fin es requerida' : null
        ]);
    }
    
    if (empty($data['cedula_medico'])) {
        return ResponseUtil::badRequest('El campo cedula_medico es requerido');
    }
    
    // Validar formato de fechas
    $erroresFechaInicio = \App\Validators\DateValidator::validate($data['fecha_inicio']);
    $erroresFechaFin = \App\Validators\DateValidator::validate($data['fecha_fin']);
    
    if (!empty($erroresFechaInicio) || !empty($erroresFechaFin)) {
        return ResponseUtil::badRequest('Formato de fechas inválido', [
            'fecha_inicio' => $erroresFechaInicio,
            'fecha_fin' => $erroresFechaFin
        ]);
    }
    
    // Validar cédula del médico
    $erroresCedula = \App\Validators\CedulaValidator::validate($data['cedula_medico']);
    if (!empty($erroresCedula)) {
        return ResponseUtil::badRequest('La cédula del médico no es válida', $erroresCedula);
    }
    
    try {
        // PASO 1: Verificar fechas
        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();
        
        if ($fechaInicio->gt($fechaFin)) {
            return ResponseUtil::badRequest('La fecha de inicio no puede ser mayor que la fecha de fin');
        }
        
        if ($fechaInicio->diffInDays($fechaFin) > 365) {
            return ResponseUtil::badRequest('El rango de fechas no puede ser mayor a 365 días');
        }
        
        // PASO 2: Obtener información completa del médico
        $medico = DB::table('doctores')
            ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->select(
                'doctores.id_doctor',
                'usuarios.id_usuario',
                'usuarios.cedula',
                'usuarios.username',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.correo',
                'usuarios.sexo',
                'usuarios.nacionalidad',
                'doctores.titulo_profesional',
                'especialidades.id_especialidad',
                'especialidades.nombre_especialidad',
                'especialidades.descripcion as especialidad_descripcion'
            )
            ->where('usuarios.cedula', $data['cedula_medico'])
            ->first();
            
        if (!$medico) {
            return ResponseUtil::notFound('No se encontró un médico con la cédula: ' . $data['cedula_medico']);
        }
        
        // PASO 3: Obtener citas con información médica extendida
        $citasCompletas = $this->getCitasConInformacionMedica($medico->id_doctor, $fechaInicio, $fechaFin);
        
        // PASO 4: Aplicar filtros opcionales
$citasFiltradas = collect($citasCompletas);

if (!empty($data['estado'])) {
    $estadosValidos = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada'];
    if (!in_array($data['estado'], $estadosValidos)) {
        return ResponseUtil::badRequest('Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos));
    }
    $citasFiltradas = $citasFiltradas->where('estado', $data['estado']);
}

if (!empty($data['tipo_cita'])) {
    $tiposValidos = ['presencial', 'virtual'];
    if (!in_array($data['tipo_cita'], $tiposValidos)) {
        return ResponseUtil::badRequest('Tipo de cita inválido. Valores permitidos: ' . implode(', ', $tiposValidos));
    }
    $citasFiltradas = $citasFiltradas->where('tipo_cita', $data['tipo_cita']);
}

if (!empty($data['id_sucursal'])) {
    if (!is_numeric($data['id_sucursal'])) {
        return ResponseUtil::badRequest('El ID de sucursal debe ser numérico');
    }
    $citasFiltradas = $citasFiltradas->where('id_sucursal', $data['id_sucursal']);
}

if (!empty($data['cedula_paciente'])) {
    $erroresCedulaPaciente = \App\Validators\CedulaValidator::validate($data['cedula_paciente']);
    if (!empty($erroresCedulaPaciente)) {
        return ResponseUtil::badRequest('Cédula del paciente inválida', $erroresCedulaPaciente);
    }
    $citasFiltradas = $citasFiltradas->where('paciente_cedula', $data['cedula_paciente']);
}

// MANTENER COMO COLLECTION para los métodos auxiliares
// $citasFiltradas ya es una collection aquí

// PASO 5: ORGANIZAR INFORMACIÓN DE MANERA EFICIENTE Y CLARA

// 5.1 INFORMACIÓN DEL MÉDICO Y CONTEXTO
$informacionMedico = $this->organizarInformacionMedico($medico, $fechaInicio, $fechaFin, $data);

// 5.2 RESUMEN EJECUTIVO
$resumenEjecutivo = $this->generarResumenEjecutivo($citasFiltradas, $fechaInicio, $fechaFin);

// 5.3 ANÁLISIS DETALLADO
$analisisDetallado = $this->generarAnalisisDetallado($citasFiltradas);

// 5.4 CITAS ORGANIZADAS POR ESTADO CON INFORMACIÓN MÉDICA
$citasOrganizadas = $this->organizarCitasPorEstado($citasFiltradas);

// RESPUESTA FINAL SUPER ORGANIZADA
$responseData = [
    'informacion_medico' => $informacionMedico,
    'resumen_ejecutivo' => $resumenEjecutivo,
    'analisis_detallado' => $analisisDetallado,
    'citas_organizadas' => $citasOrganizadas
];

$mensaje = sprintf(
    'Consulta exitosa: %d citas encontradas para Dr. %s entre %s y %s',
    $citasFiltradas->count(), // Usar ->count() en lugar de count()
    $medico->nombres . ' ' . $medico->apellidos,
    $fechaInicio->locale('es')->isoFormat('D [de] MMMM'),
    $fechaFin->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
);
        return ResponseUtil::success($responseData, $mensaje);
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error al consultar citas: ' . $e->getMessage());
    }
}

// MÉTODO AUXILIAR: Obtener citas con información médica completa
private function getCitasConInformacionMedica($idDoctor, $fechaInicio, $fechaFin)
{
    // Query principal con LEFT JOINs para obtener TODA la información médica
    $citas = DB::table('citas')
        ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
        ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario')
        ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
        ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
        ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
        ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal')
        ->leftJoin('tipos_cita', 'citas.id_tipo_cita', '=', 'tipos_cita.id_tipo_cita')
        ->leftJoin('consultas_medicas', 'citas.id_cita', '=', 'consultas_medicas.id_cita')
        ->leftJoin('triage', 'citas.id_cita', '=', 'triage.id_cita')
        ->select(
            // Datos básicos de la cita
            'citas.id_cita',
            'citas.fecha_hora',
            'citas.motivo',
            'citas.tipo_cita',
            'citas.estado',
            'citas.notas',
            'citas.enlace_virtual',
            'citas.sala_virtual',
            'citas.fecha_creacion as cita_creada',
            
            // Datos del paciente
            'u_paciente.cedula as paciente_cedula',
            'u_paciente.nombres as paciente_nombres',
            'u_paciente.apellidos as paciente_apellidos',
            'u_paciente.sexo as paciente_sexo',
            'pacientes.telefono as paciente_telefono',
            'pacientes.tipo_sangre',
            'pacientes.fecha_nacimiento',
            'pacientes.alergias',
            'pacientes.antecedentes_medicos',
            
            // Datos de sucursal
            'sucursales.id_sucursal',
            'sucursales.nombre_sucursal',
            'sucursales.direccion as sucursal_direccion',
            'sucursales.telefono as sucursal_telefono',
            'sucursales.email as sucursal_email',
            'sucursales.horario_atencion',
            
            // Tipo de cita
            'tipos_cita.nombre_tipo as nombre_tipo_cita',
            'tipos_cita.descripcion as descripcion_tipo_cita',
            
            // INFORMACIÓN MÉDICA (consultas_medicas)
            'consultas_medicas.id_consulta',
            'consultas_medicas.fecha_hora as fecha_consulta',
            'consultas_medicas.motivo_consulta',
            'consultas_medicas.sintomatologia',
            'consultas_medicas.diagnostico',
            'consultas_medicas.tratamiento',
            'consultas_medicas.observaciones as observaciones_medicas',
            'consultas_medicas.fecha_seguimiento',
            
            // INFORMACIÓN DE TRIAJE
            'triage.id_triage',
            'triage.nivel_urgencia',
            'triage.estado_triaje',
            'triage.temperatura',
            'triage.presion_arterial',
            'triage.frecuencia_cardiaca',
            'triage.frecuencia_respiratoria',
            'triage.saturacion_oxigeno',
            'triage.peso',
            'triage.talla',
            'triage.imc',
            'triage.observaciones as observaciones_triaje'
        )
        ->where('doctores.id_doctor', $idDoctor)
        ->whereBetween('citas.fecha_hora', [$fechaInicio, $fechaFin])
        ->orderBy('citas.fecha_hora', 'asc')
        ->get();
    
    return $citas;
}

// MÉTODO AUXILIAR: Organizar información del médico
private function organizarInformacionMedico($medico, $fechaInicio, $fechaFin, $parametros)
{
    // Obtener sucursales donde atiende
    $sucursalesMedico = DB::table('doctores_sucursales')
        ->join('sucursales', 'doctores_sucursales.id_sucursal', '=', 'sucursales.id_sucursal')
        ->where('doctores_sucursales.id_doctor', $medico->id_doctor)
        ->select(
            'sucursales.id_sucursal',
            'sucursales.nombre_sucursal',
            'sucursales.direccion',
            'sucursales.telefono',
            'sucursales.email',
            'sucursales.horario_atencion'
        )
        ->get();
    
    return [
        'datos_medico' => [
            'identificacion' => [
                'id_doctor' => $medico->id_doctor,
                'cedula' => $medico->cedula,
                'username' => $medico->username
            ],
            'informacion_personal' => [
                'nombres' => $medico->nombres,
                'apellidos' => $medico->apellidos,
                'nombre_completo' => $medico->nombres . ' ' . $medico->apellidos,
                'sexo' => $medico->sexo,
                'nacionalidad' => $medico->nacionalidad,
                'correo' => $medico->correo
            ],
            'informacion_profesional' => [
                'titulo_profesional' => $medico->titulo_profesional,
                'especialidad' => [
                    'id_especialidad' => $medico->id_especialidad,
                    'nombre' => $medico->nombre_especialidad,
                    'descripcion' => $medico->especialidad_descripcion
                ]
            ]
        ],
        'sucursales_atencion' => $sucursalesMedico,
        'parametros_consulta' => [
            'rango_temporal' => [
                'fecha_inicio' => $parametros['fecha_inicio'],
                'fecha_fin' => $parametros['fecha_fin'],
                'fecha_inicio_procesada' => $fechaInicio->format('Y-m-d H:i:s'),
                'fecha_fin_procesada' => $fechaFin->format('Y-m-d H:i:s'),
                'total_dias' => $fechaInicio->diffInDays($fechaFin) + 1,
                'descripcion_periodo' => $fechaInicio->locale('es')->isoFormat('D [de] MMMM [de] YYYY') . 
                                       ' hasta ' . 
                                       $fechaFin->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
            ],
            'filtros_aplicados' => array_filter([
                'estado' => $parametros['estado'] ?? null,
                'tipo_cita' => $parametros['tipo_cita'] ?? null,
                'id_sucursal' => $parametros['id_sucursal'] ?? null,
                'cedula_paciente' => $parametros['cedula_paciente'] ?? null
            ])
        ]
    ];
}

// MÉTODO AUXILIAR: Generar resumen ejecutivo - FINAL CORREGIDO
private function generarResumenEjecutivo($citasFiltradas, $fechaInicio, $fechaFin)
{
    $totalCitas = $citasFiltradas->count();
    $citasCompletadas = $citasFiltradas->where('estado', 'Completada');
    $citasConConsulta = $citasFiltradas->filter(function($cita) {
        return !is_null($cita->id_consulta);
    });
    
    return [
        'metricas_principales' => [
            'total_citas_periodo' => $totalCitas,
            'citas_completadas' => $citasCompletadas->count(),
            'citas_con_informacion_medica' => $citasConConsulta->count(),
            'promedio_citas_por_dia' => $totalCitas > 0 ? 
                round($totalCitas / ($fechaInicio->diffInDays($fechaFin) + 1), 2) : 0,
            'tasa_completitud' => $totalCitas > 0 ? 
                round(($citasCompletadas->count() / $totalCitas) * 100, 2) : 0,
            'tasa_informacion_medica' => $totalCitas > 0 ? 
                round(($citasConConsulta->count() / $totalCitas) * 100, 2) : 0
        ],
        'distribucion_estados' => [
            'pendientes' => $citasFiltradas->where('estado', 'Pendiente')->count(),
            'confirmadas' => $citasFiltradas->where('estado', 'Confirmada')->count(),
            'completadas' => $citasFiltradas->where('estado', 'Completada')->count(),
            'canceladas' => $citasFiltradas->where('estado', 'Cancelada')->count()
        ],
        'distribucion_tipos' => [
            'presenciales' => $citasFiltradas->where('tipo_cita', 'presencial')->count(),
            'virtuales' => $citasFiltradas->where('tipo_cita', 'virtual')->count()
        ],
        'informacion_pacientes' => [
            'total_pacientes_unicos' => $citasFiltradas->pluck('paciente_cedula')->unique()->count(),
            'promedio_citas_por_paciente' => $citasFiltradas->pluck('paciente_cedula')->unique()->count() > 0 ?
                round($totalCitas / $citasFiltradas->pluck('paciente_cedula')->unique()->count(), 2) : 0
        ]
    ];
}

// MÉTODO AUXILIAR: Generar análisis detallado - FINAL CORREGIDO
private function generarAnalisisDetallado($citasFiltradas)
{
    $totalCitasConConsulta = $citasFiltradas->whereNotNull('id_consulta')->count();
    
    return [
        'analisis_temporal' => [
            'distribucion_por_mes' => $citasFiltradas
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->format('Y-m');
                })
                ->map(function($citasMes, $mes) {
                    return [
                        'mes' => $mes,
                        'mes_nombre' => Carbon::parse($mes . '-01')->locale('es')->isoFormat('MMMM YYYY'),
                        'total_citas' => $citasMes->count(),
                        'completadas' => $citasMes->where('estado', 'Completada')->count(),
                        'con_consulta_medica' => $citasMes->whereNotNull('id_consulta')->count()
                    ];
                })
                ->values(),
            'distribucion_por_dia_semana' => $citasFiltradas
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->dayOfWeek;
                })
                ->map(function($citasDia, $dia) {
                    $nombreDia = Carbon::now()->dayOfWeek($dia)->locale('es')->isoFormat('dddd');
                    return [
                        'dia_numero' => $dia,
                        'dia_nombre' => $nombreDia,
                        'total_citas' => $citasDia->count(),
                        'promedio_por_dia' => round($citasDia->count() / 4, 1)
                    ];
                })
                ->values(),
            'horarios_mas_frecuentes' => $citasFiltradas
                ->groupBy(function($cita) {
                    return Carbon::parse($cita->fecha_hora)->format('H');
                })
                ->map(function($citasHora, $hora) use ($citasFiltradas) {
                    return [
                        'hora' => $hora . ':00',
                        'total_citas' => $citasHora->count(),
                        'porcentaje' => $citasFiltradas->count() > 0 ? 
                            round(($citasHora->count() / $citasFiltradas->count()) * 100, 2) : 0
                    ];
                })
                ->sortByDesc('total_citas')
                ->take(5)
                ->values()
        ],
        'analisis_medico' => [
            'diagnosticos_frecuentes' => $totalCitasConConsulta > 0 ? 
                $citasFiltradas
                    ->whereNotNull('diagnostico')
                    ->where('diagnostico', '!=', '')
                    ->groupBy('diagnostico')
                    ->map(function($citasDiagnostico, $diagnostico) use ($totalCitasConConsulta) {
                        return [
                            'diagnostico' => $diagnostico,
                            'frecuencia' => $citasDiagnostico->count(),
                            'porcentaje' => round(($citasDiagnostico->count() / $totalCitasConConsulta) * 100, 2)
                        ];
                    })
                    ->sortByDesc('frecuencia')
                    ->take(10)
                    ->values() : [],
            'tratamientos_aplicados' => $citasFiltradas
                ->whereNotNull('tratamiento')
                ->where('tratamiento', '!=', '')
                ->pluck('tratamiento')
                ->unique()
                ->take(10)
                ->values(),
            'motivos_consulta_frecuentes' => $citasFiltradas
                ->whereNotNull('motivo_consulta')
                ->where('motivo_consulta', '!=', '')
                ->groupBy('motivo_consulta')
                ->map(function($citasMotivo, $motivo) {
                    return [
                        'motivo' => $motivo,
                        'frecuencia' => $citasMotivo->count()
                    ];
                })
                ->sortByDesc('frecuencia')
                ->take(5)
                ->values(),
            'estadisticas_triaje' => [
                'total_con_triaje' => $citasFiltradas->whereNotNull('id_triage')->count(),
                'niveles_urgencia' => $citasFiltradas
                    ->whereNotNull('nivel_urgencia')
                    ->groupBy('nivel_urgencia')
                    ->map(function($citasNivel, $nivel) {
                        $nombreNivel = match($nivel) {
                            1 => 'No urgente',
                            2 => 'Menos urgente',
                            3 => 'Urgente',
                            4 => 'Muy urgente',
                            5 => 'Crítico',
                            default => 'No especificado'
                        };
                        return [
                            'nivel' => $nivel,
                            'nombre' => $nombreNivel,
                            'cantidad' => $citasNivel->count()
                        ];
                    })
                    ->values()
            ]
        ],
        'analisis_sucursales' => $citasFiltradas
            ->groupBy('id_sucursal')
            ->map(function($citasSucursal) {
                $primera = $citasSucursal->first();
                return [
                    'sucursal' => [
                        'id_sucursal' => $primera->id_sucursal,
                        'nombre' => $primera->nombre_sucursal,
                        'direccion' => $primera->sucursal_direccion
                    ],
                    'estadisticas' => [
                        'total_citas' => $citasSucursal->count(),
                        'completadas' => $citasSucursal->where('estado', 'Completada')->count(),
                        'con_consulta_medica' => $citasSucursal->whereNotNull('id_consulta')->count(),
                        'presenciales' => $citasSucursal->where('tipo_cita', 'presencial')->count(),
                        'virtuales' => $citasSucursal->where('tipo_cita', 'virtual')->count()
                    ]
                ];
            })
            ->sortByDesc('estadisticas.total_citas')
            ->values(),
        'top_pacientes_periodo' => $citasFiltradas
            ->groupBy('paciente_cedula')
            ->map(function($citasPaciente) {
                $primera = $citasPaciente->first();
                return [
                    'paciente' => [
                        'cedula' => $primera->paciente_cedula,
                        'nombre_completo' => $primera->paciente_nombres . ' ' . $primera->paciente_apellidos,
                        'tipo_sangre' => $primera->tipo_sangre
                    ],
                    'estadisticas' => [
                        'total_citas' => $citasPaciente->count(),
                        'completadas' => $citasPaciente->where('estado', 'Completada')->count(),
                        'primera_cita' => $citasPaciente->min('fecha_hora'),
                        'ultima_cita' => $citasPaciente->max('fecha_hora')
                    ]
                ];
            })
            ->sortByDesc('estadisticas.total_citas')
            ->take(10)
            ->values()
    ];
}

// MÉTODO AUXILIAR: Organizar citas por estado - FINAL CORREGIDO
private function organizarCitasPorEstado($citasFiltradas)
{
    $citasPorEstado = $citasFiltradas->groupBy('estado');
    
    $resultado = [];
    
    foreach ($citasPorEstado as $estado => $citasEstado) {
        $resultado[$estado] = [
            'resumen' => [
                'total_citas' => $citasEstado->count(),
                'con_informacion_medica' => $citasEstado->whereNotNull('id_consulta')->count(),
                'con_triaje' => $citasEstado->whereNotNull('id_triage')->count(),
                'porcentaje_del_total' => $citasFiltradas->count() > 0 ? 
                    round(($citasEstado->count() / $citasFiltradas->count()) * 100, 2) : 0
            ],
            'citas' => $citasEstado->map(function($cita) {
                return $this->formatearCitaCompleta($cita);
            })->sortBy('informacion_cita.fecha_hora')->values()->all()
        ];
    }
    
    // Ordenar por cantidad de citas (estado con más citas primero)
    uasort($resultado, function($a, $b) {
        return $b['resumen']['total_citas'] <=> $a['resumen']['total_citas'];
    });
    
    return $resultado;
}

// MÉTODO AUXILIAR: Formatear cita con toda la información médica
private function formatearCitaCompleta($cita)
{
    $fechaCita = Carbon::parse($cita->fecha_hora);
    
    $citaFormateada = [
        'informacion_cita' => [
            'id_cita' => $cita->id_cita,
            'fecha_hora' => $cita->fecha_hora,
            'fecha_formateada' => $fechaCita->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'hora_formateada' => $fechaCita->format('H:i'),
            'motivo_inicial' => $cita->motivo,
            'estado' => $cita->estado,
            'tipo_cita' => $cita->tipo_cita,
            'notas_generales' => $cita->notas,
            'enlace_virtual' => $cita->enlace_virtual,
            'fecha_creacion' => $cita->cita_creada,
            'tiempo_transcurrido' => $fechaCita->diffForHumans()
        ],
        'informacion_paciente' => [
            'identificacion' => [
                'cedula' => $cita->paciente_cedula,
                'nombres' => $cita->paciente_nombres,
                'apellidos' => $cita->paciente_apellidos,
                'nombre_completo' => $cita->paciente_nombres . ' ' . $cita->paciente_apellidos
            ],
            'datos_contacto' => [
                'telefono' => $cita->paciente_telefono
            ],
            'informacion_medica_basica' => [
                'sexo' => $cita->paciente_sexo,
                'fecha_nacimiento' => $cita->fecha_nacimiento,
                'edad' => $cita->fecha_nacimiento ? Carbon::parse($cita->fecha_nacimiento)->age : null,
                'tipo_sangre' => $cita->tipo_sangre,
                'alergias' => $cita->alergias,
                'antecedentes_medicos' => $cita->antecedentes_medicos
            ]
        ],
        'informacion_sucursal' => [
            'id_sucursal' => $cita->id_sucursal,
            'nombre' => $cita->nombre_sucursal,
            'direccion' => $cita->sucursal_direccion,
            'telefono' => $cita->sucursal_telefono,
            'email' => $cita->sucursal_email,
            'horario_atencion' => $cita->horario_atencion
        ]
    ];
    
    // Agregar información de triaje si existe
    if ($cita->id_triage) {
        $citaFormateada['triaje'] = [
            'id_triage' => $cita->id_triage,
            'nivel_urgencia' => $cita->nivel_urgencia,
            'estado_triaje' => $cita->estado_triaje,
            'signos_vitales' => [
                'temperatura' => $cita->temperatura ? $cita->temperatura . ' °C' : null,
                'presion_arterial' => $cita->presion_arterial,
                'frecuencia_cardiaca' => $cita->frecuencia_cardiaca ? $cita->frecuencia_cardiaca . ' bpm' : null,
                'frecuencia_respiratoria' => $cita->frecuencia_respiratoria ? $cita->frecuencia_respiratoria . ' rpm' : null,
                'saturacion_oxigeno' => $cita->saturacion_oxigeno ? $cita->saturacion_oxigeno . '%' : null
            ],
            'medidas_corporales' => [
                'peso' => $cita->peso ? $cita->peso . ' kg' : null,
                'talla' => $cita->talla ? $cita->talla . ' cm' : null,
                'imc' => $cita->imc
            ],
            'observaciones_triaje' => $cita->observaciones_triaje
        ];
    }
    
    // Agregar información médica completa si existe (para citas completadas)
    if ($cita->id_consulta) {
        $citaFormateada['consulta_medica'] = [
            'id_consulta' => $cita->id_consulta,
            'fecha_consulta' => $cita->fecha_consulta,
            'evaluacion_medica' => [
                'motivo_consulta' => $cita->motivo_consulta,
                'sintomatologia' => $cita->sintomatologia,
                'diagnostico' => $cita->diagnostico,
                'tratamiento' => $cita->tratamiento,
                'observaciones_medicas' => $cita->observaciones_medicas
            ],
            'seguimiento' => [
                'fecha_seguimiento' => $cita->fecha_seguimiento,
                'seguimiento_requerido' => !is_null($cita->fecha_seguimiento)
            ]
        ];
    }
    
    return $citaFormateada;
}
    // Método auxiliar para la query base - MEJORADO
    private function getCitasQuery()
    {
        return DB::table('citas')
            ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
            ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
            ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal')
            ->leftJoin('tipos_cita', 'citas.id_tipo_cita', '=', 'tipos_cita.id_tipo_cita')
            ->select(
                // Datos básicos de la cita
                'citas.id_cita',
                'citas.fecha_hora',
                'citas.motivo',
                'citas.tipo_cita',
                'citas.estado',
                'citas.notas',
                'citas.enlace_virtual',
                'citas.sala_virtual',
                'citas.fecha_creacion as cita_creada',
                
                // PACIENTE
                'u_paciente.cedula as paciente_cedula',
                'u_paciente.nombres as paciente_nombres',
                'u_paciente.apellidos as paciente_apellidos',
                'u_paciente.sexo as paciente_sexo',
                'pacientes.telefono as paciente_telefono',
                'pacientes.tipo_sangre',
                
                // MÉDICO ASIGNADO
                'citas.id_doctor',
                'u_doctor.nombres as medico_nombres',
                'u_doctor.apellidos as medico_apellidos',
                'doctores.titulo_profesional as medico_titulo',
                
                // ESPECIALIDAD
                'especialidades.id_especialidad',
                'especialidades.nombre_especialidad',
                'especialidades.descripcion as especialidad_descripcion',
                
                // SUCURSAL
                'sucursales.id_sucursal',
                'sucursales.nombre_sucursal',
                'sucursales.direccion as sucursal_direccion',
                'sucursales.telefono as sucursal_telefono',
                'sucursales.email as sucursal_email',
                
                // TIPO DE CITA
                'tipos_cita.nombre_tipo as nombre_tipo_cita',
                'tipos_cita.descripcion as descripcion_tipo_cita'
            );
    }


    /**
 * ✅ CREAR NUEVO PACIENTE
 */
public function crearPaciente(Request $request, Response $response): Response
{
    try {
        $data = $request->getParsedBody();
        
        // Validar campos requeridos
        $camposRequeridos = ['cedula', 'nombres', 'apellidos', 'correo', 'telefono', 'fecha_nacimiento', 'sexo'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            return ResponseUtil::badRequest('Campos requeridos faltantes: ' . implode(', ', $camposFaltantes));
        }
        
        // Validar formato de cédula
        if (!preg_match('/^[0-9]{10}$/', $data['cedula'])) {
            return ResponseUtil::badRequest('La cédula debe tener exactamente 10 dígitos');
        }
        
        // Validar que no existe ya un paciente con esa cédula
        $pacienteExistente = DB::table('usuarios')->where('cedula', $data['cedula'])->first();
        if ($pacienteExistente) {
            return ResponseUtil::conflict('Ya existe un usuario con esa cédula');
        }
        
        // Generar username único
        $baseUsername = strtolower(trim($data['nombres'])) . '.' . strtolower(trim($data['apellidos']));
        $baseUsername = preg_replace('/[^a-z0-9.]/', '', $baseUsername);
        $username = substr($baseUsername, 0, 20);
        
        // Verificar si el username existe y modificarlo si es necesario
        $contador = 1;
        $usernameOriginal = $username;
        while (DB::table('usuarios')->where('username', $username)->exists()) {
            $username = $usernameOriginal . $contador;
            $contador++;
        }
        
        DB::beginTransaction();
        
        // 1. Crear usuario
        $usuarioId = DB::table('usuarios')->insertGetId([
            'username' => $username,
            'password' => password_hash($data['cedula'], PASSWORD_DEFAULT), // Password temporal = cédula
            'nombres' => trim($data['nombres']),
            'apellidos' => trim($data['apellidos']),
            'correo' => trim($data['correo']),
            'cedula' => $data['cedula'],
            'sexo' => $data['sexo'],
            'nacionalidad' => $data['nacionalidad'] ?? 'Ecuatoriana',
            'id_rol' => 71, // Rol Paciente
            'id_estado' => 1, // Activo
        ]);
        
        // 2. Crear registro en pacientes
        $pacienteId = DB::table('pacientes')->insertGetId([
            'id_usuario' => $usuarioId,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
            'alergias' => $data['alergias'] ?? null,
            'antecedentes_medicos' => $data['antecedentes_medicos'] ?? null,
            'contacto_emergencia' => $data['contacto_emergencia'] ?? null,
            'telefono_emergencia' => $data['telefono_emergencia'] ?? null,
            'telefono' => trim($data['telefono']),
            'numero_seguro' => $data['numero_seguro'] ?? null
        ]);
        
        DB::commit();
        
        // Obtener datos completos del paciente creado
        $pacienteCompleto = DB::table('pacientes')
            ->join('usuarios', 'pacientes.id_usuario', '=', 'usuarios.id_usuario')
            ->select(
                'pacientes.id_paciente',
                'usuarios.id_usuario',
                'usuarios.cedula',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.correo',
                'usuarios.sexo',
                'usuarios.nacionalidad',
                'pacientes.telefono',
                'pacientes.fecha_nacimiento',
                'pacientes.tipo_sangre',
                'pacientes.alergias',
                'pacientes.contacto_emergencia',
                'pacientes.telefono_emergencia'
            )
            ->where('pacientes.id_paciente', $pacienteId)
            ->first();
        
        return ResponseUtil::success([
            'paciente' => $pacienteCompleto,
            'password_temporal' => $data['cedula']
        ], 'Paciente creado exitosamente');
        
    } catch (Exception $e) {
        DB::rollBack();
        return ResponseUtil::error('Error creando paciente: ' . $e->getMessage());
    }
}

public function crearPaciente2(Request $request, Response $response): Response
{
    // Configurar headers para JSON limpio
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $data = $request->getParsedBody();
        
        // Validar datos con mensajes específicos
        $errores = $this->validarDatosPaciente($data);
        if (!empty($errores)) {
            $mensajePrincipal = $this->generarMensajeErrorPaciente($errores);
            
            return ResponseUtil::badRequest($mensajePrincipal, [
                'errors' => $errores,
                'detalles' => $this->formatearErroresParaUsuario($errores)
            ]);
        }
        
        // Convertir cédula a string para BD
        $cedula = (string)$data['cedula'];
        
        DB::beginTransaction();
        
        // 🔑 Generar contraseña temporal
        require_once __DIR__ . '/../../../config/MailService.php';
        $passwordTemporal = \MailService::generarPasswordTemporal();
        
        // 1. Crear usuario
        $usuarioId = DB::table('usuarios')->insertGetId([
            'cedula' => $cedula,
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'correo' => $data['correo'] ?? null,
            'sexo' => $data['sexo'],
            'nacionalidad' => $data['nacionalidad'] ?? 'Ecuatoriana',
            'username' => $cedula,
            'password' => password_hash($passwordTemporal, PASSWORD_DEFAULT),
            'id_rol' => 71, // Rol paciente
            'id_estado' => 1, // Activo
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]);
        
        // 2. Crear paciente
        $pacienteId = DB::table('pacientes')->insertGetId([
            'id_usuario' => $usuarioId,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
            'alergias' => $data['alergias'] ?? null,
            'antecedentes_medicos' => $data['antecedentes_medicos'] ?? null,
            'contacto_emergencia' => $data['contacto_emergencia'] ?? null,
            'telefono_emergencia' => $data['telefono_emergencia'] ?? null,
            'numero_seguro' => $data['numero_seguro'] ?? null,
            'telefono' => $data['telefono'] ?? null
        ]);
        
        // 3. Crear historial clínico
        DB::table('historiales_clinicos')->insert([
            'id_paciente' => $pacienteId,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'ultima_actualizacion' => date('Y-m-d H:i:s')
        ]);
        
        DB::commit();
        
        // 4. Preparar datos del paciente creado
        $pacienteCreado = [
            'id_paciente' => $pacienteId,
            'id_usuario' => $usuarioId,
            'cedula' => (int)$cedula,
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'nombre_completo' => $data['nombres'] . ' ' . $data['apellidos'],
            'correo' => $data['correo'] ?? '',
            'sexo' => $data['sexo'],
            'nacionalidad' => $data['nacionalidad'] ?? 'Ecuatoriana',
            'username' => $cedula,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'edad' => $this->calcularEdad($data['fecha_nacimiento']),
            'tipo_sangre' => $data['tipo_sangre'] ?? '',
            'telefono' => $data['telefono'] ?? '',
            'alergias' => $data['alergias'] ?? '',
            'antecedentes_medicos' => $data['antecedentes_medicos'] ?? '',
            'contacto_emergencia' => $data['contacto_emergencia'] ?? '',
            'telefono_emergencia' => $data['telefono_emergencia'] ?? '',
            'numero_seguro' => $data['numero_seguro'] ?? ''
        ];
        
        // 5. 📧 Enviar credenciales por correo si tiene email
        $mensajeCredenciales = "";
        if (!empty($data['correo'])) {
            try {
                $mailService = new \MailService();
                $envioExitoso = $mailService->enviarPasswordTemporal(
                    $data['correo'],
                    $data['nombres'] . ' ' . $data['apellidos'],
                    $data['correo'], // username
                    $passwordTemporal
                );
                
                if ($envioExitoso) {
                    $mensajeCredenciales = " y credenciales enviadas al correo";
                } else {
                    $mensajeCredenciales = " (no se pudieron enviar las credenciales por correo)";
                }
            } catch (Exception $e) {
                error_log("Error enviando correo a paciente: " . $e->getMessage());
                $mensajeCredenciales = " (error enviando credenciales por correo)";
            }
        }
        
        return ResponseUtil::success(
            $pacienteCreado,
            'Paciente ' . $pacienteCreado['nombre_completo'] . ' creado exitosamente' . $mensajeCredenciales
        );
        
    } catch (Exception $e) {
        DB::rollback();
        error_log("Error creando paciente: " . $e->getMessage());
        
        // Mensaje de error específico para excepciones
        $mensajeError = $this->interpretarErrorBDPaciente($e);
        
        return ResponseUtil::error($mensajeError);
    }
}

// ===== MÉTODOS AUXILIARES PARA VALIDACIÓN DE PACIENTES =====

private function validarDatosPaciente($data) {
    $errores = [];
    
    // ✅ VALIDAR CÉDULA
    if (empty($data['cedula'])) {
        $errores['cedula'] = 'La cédula es obligatoria';
    } else {
        // Validar que sea numérico
        if (!is_numeric($data['cedula'])) {
            $errores['cedula'] = 'La cédula debe contener solo números';
        } else {
            $cedula = (string)$data['cedula'];
            
           
                // Verificar si ya existe
                $usuarioExistente = DB::table('usuarios')->where('cedula', $cedula)->first();
                if ($usuarioExistente) {
                    $errores['cedula'] = "La cédula {$cedula} ya está registrada en el sistema";
                }
            
        }
    }
    
    // ✅ VALIDAR NOMBRES
    if (empty($data['nombres'])) {
        $errores['nombres'] = 'Los nombres son obligatorios';
    } elseif (strlen(trim($data['nombres'])) < 2) {
        $errores['nombres'] = 'Los nombres deben tener al menos 2 caracteres';
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $data['nombres'])) {
        $errores['nombres'] = 'Los nombres solo pueden contener letras y espacios';
    }
    
    // ✅ VALIDAR APELLIDOS
    if (empty($data['apellidos'])) {
        $errores['apellidos'] = 'Los apellidos son obligatorios';
    } elseif (strlen(trim($data['apellidos'])) < 2) {
        $errores['apellidos'] = 'Los apellidos deben tener al menos 2 caracteres';
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $data['apellidos'])) {
        $errores['apellidos'] = 'Los apellidos solo pueden contener letras y espacios';
    }
    
    // ✅ VALIDAR CORREO (opcional pero si está debe ser válido)
    if (!empty($data['correo'])) {
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'El formato del correo electrónico no es válido';
        } else {
            // Verificar si ya existe
            $correoExistente = DB::table('usuarios')->where('correo', $data['correo'])->first();
            if ($correoExistente) {
                $errores['correo'] = "El correo {$data['correo']} ya está registrado en el sistema";
            }
        }
    }
    
    // ✅ VALIDAR SEXO
    if (empty($data['sexo'])) {
        $errores['sexo'] = 'El sexo es obligatorio';
    } elseif (!in_array($data['sexo'], ['M', 'F'])) {
        $errores['sexo'] = 'El sexo debe ser M (Masculino) o F (Femenino)';
    }
    
    // ✅ VALIDAR FECHA DE NACIMIENTO
    if (empty($data['fecha_nacimiento'])) {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento es obligatoria';
    } else {
        $fechaNacimiento = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
        if (!$fechaNacimiento) {
            $errores['fecha_nacimiento'] = 'La fecha de nacimiento debe tener el formato YYYY-MM-DD';
        } else {
            $hoy = new DateTime();
            $edad = $hoy->diff($fechaNacimiento)->y;
            
            if ($fechaNacimiento > $hoy) {
                $errores['fecha_nacimiento'] = 'La fecha de nacimiento no puede ser futura';
            } elseif ($edad > 120) {
                $errores['fecha_nacimiento'] = 'La fecha de nacimiento no es realista (edad mayor a 120 años)';
            }
        }
    }
    
    // ✅ VALIDAR TELÉFONO (opcional pero si está debe ser válido)
    if (!empty($data['telefono'])) {
        if (!preg_match("/^\d{10}$/", $data['telefono'])) {
            $errores['telefono'] = 'El teléfono debe tener exactamente 10 dígitos';
        }
    }
    
    // ✅ VALIDAR TELÉFONO DE EMERGENCIA (opcional)
    if (!empty($data['telefono_emergencia'])) {
        if (!preg_match("/^\d{10}$/", $data['telefono_emergencia'])) {
            $errores['telefono_emergencia'] = 'El teléfono de emergencia debe tener exactamente 10 dígitos';
        }
    }
    
    // ✅ VALIDAR TIPO DE SANGRE (opcional)
    if (!empty($data['tipo_sangre'])) {
        $tiposValidos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($data['tipo_sangre'], $tiposValidos)) {
            $errores['tipo_sangre'] = 'El tipo de sangre no es válido. Debe ser: ' . implode(', ', $tiposValidos);
        }
    }
    
    return $errores;
}

private function generarMensajeErrorPaciente($errores) {
    if (isset($errores['cedula'])) {
        return $errores['cedula'];
    }
    
    if (isset($errores['correo'])) {
        return $errores['correo'];
    }
    
    if (count($errores) == 1) {
        return reset($errores);
    }
    
    return "Se encontraron " . count($errores) . " errores en los datos del paciente";
}

private function formatearErroresParaUsuario($errores) {
    $mensajes = [];
    
    foreach ($errores as $campo => $mensaje) {
        $nombreCampo = $this->traducirNombreCampoPaciente($campo);
        $mensajes[] = "• {$nombreCampo}: {$mensaje}";
    }
    
    return implode("\n", $mensajes);
}

private function traducirNombreCampoPaciente($campo) {
    $traducciones = [
        'cedula' => 'Cédula',
        'nombres' => 'Nombres',
        'apellidos' => 'Apellidos',
        'correo' => 'Correo electrónico',
        'sexo' => 'Sexo',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'telefono' => 'Teléfono',
        'telefono_emergencia' => 'Teléfono de emergencia',
        'tipo_sangre' => 'Tipo de sangre'
    ];
    
    return $traducciones[$campo] ?? ucfirst($campo);
}

private function interpretarErrorBDPaciente($exception) {
    $mensaje = $exception->getMessage();
    
    // Error de cédula duplicada
    if (strpos($mensaje, 'cedula') !== false && strpos($mensaje, 'Duplicate') !== false) {
        return 'Esta cédula ya está registrada como paciente en el sistema';
    }
    
    // Error de correo duplicado
    if (strpos($mensaje, 'correo') !== false && strpos($mensaje, 'Duplicate') !== false) {
        return 'Este correo electrónico ya está registrado en el sistema';
    }
    
    // Error de clave foránea
    if (strpos($mensaje, 'foreign key constraint') !== false) {
        return 'Error de integridad: algunos datos del paciente no son válidos';
    }
    
    // Error de conexión
    if (strpos($mensaje, 'Connection') !== false) {
        return 'Error de conexión con la base de datos. Intente nuevamente.';
    }
    
    return 'Error interno del servidor creando el paciente. Contacte al administrador si persiste.';
}
private function calcularEdad($fechaNacimiento): int
{
    try {
        $nacimiento = new \DateTime($fechaNacimiento);
        $hoy = new \DateTime();
        return $hoy->diff($nacimiento)->y;
    } catch (Exception $e) {
        return 0;
    }
}
/**
 * ✅ OBTENER TIPOS DE CITA
 */
public function getTiposCita(Request $request, Response $response): Response
{
    try {
        $tiposCita = DB::table('tipos_cita')
            ->where('activo', 1)
            ->orderBy('id_tipo_cita')
            ->get();
        
        return ResponseUtil::success($tiposCita->toArray(), 'Tipos de cita obtenidos exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo tipos de cita: ' . $e->getMessage());
    }
}


/**
 * ✅ Buscar paciente por cédula (búsqueda rápida)
 */
public function buscarPacienteRapido(Request $request, Response $response, array $args): Response
{
    $cedula = $args['cedula'];

    try {
        $paciente = DB::table('pacientes')
            ->join('usuarios', 'pacientes.id_usuario', '=', 'usuarios.id_usuario')
            ->select(
                'pacientes.id_paciente',
                'usuarios.cedula',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.correo',
                'usuarios.sexo',
                'pacientes.tipo_sangre',
                'pacientes.alergias',
                'pacientes.antecedentes_medicos',
                'pacientes.contacto_emergencia',
                'pacientes.telefono_emergencia',
                'pacientes.numero_seguro'
            )
            ->where('usuarios.cedula', $cedula)
            ->first();

        if (!$paciente) {
            return ResponseUtil::notFound('No se encontró ningún paciente con la cédula: ' . $cedula);
        }

        return ResponseUtil::success($paciente, 'Paciente encontrado exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error buscando paciente: ' . $e->getMessage());
    }
}

/**
 * ✅ OBTENER ESPECIALIDADES POR SUCURSAL
 */
public function getEspecialidadesPorSucursal(Request $request, Response $response, array $args): Response
{
    try {
        $idSucursal = $args['id_sucursal'];
        
        $especialidades = DB::table('especialidades')
            ->join('especialidades_sucursales', 'especialidades.id_especialidad', '=', 'especialidades_sucursales.id_especialidad')
            ->where('especialidades_sucursales.id_sucursal', $idSucursal)
            ->select(
                'especialidades.id_especialidad',
                'especialidades.nombre_especialidad',
                'especialidades.descripcion'
            )
            ->orderBy('especialidades.nombre_especialidad')
            ->get();
        
        return ResponseUtil::success($especialidades->toArray(), 'Especialidades por sucursal obtenidas exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo especialidades por sucursal: ' . $e->getMessage());
    }
}

/**
 * ✅ OBTENER DOCTORES POR ESPECIALIDAD Y SUCURSAL
 */
public function getDoctoresPorEspecialidadYSucursal(Request $request, Response $response, array $args): Response
{
    try {
        $idEspecialidad = $args['id_especialidad'];
        $idSucursal = $args['id_sucursal'];
        
        $doctores = DB::table('doctores')
            ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
            ->join('doctores_sucursales', 'doctores.id_doctor', '=', 'doctores_sucursales.id_doctor')
            ->where('doctores.id_especialidad', $idEspecialidad)
            ->where('doctores_sucursales.id_sucursal', $idSucursal)
            ->where('usuarios.id_estado', 1) // Solo activos
            ->select(
                'doctores.id_doctor',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.correo',
                'doctores.titulo_profesional'
            )
            ->orderBy('usuarios.nombres')
            ->get();
        
        return ResponseUtil::success($doctores->toArray(), 'Doctores por especialidad y sucursal obtenidos exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo doctores: ' . $e->getMessage());
    }
}

/**
 * ✅ OBTENER HORARIOS DISPONIBLES DE UN DOCTOR
 */
public function getHorariosDisponibles(Request $request, Response $response): Response
{
    try {
        $params = $request->getQueryParams();
        
        $idDoctor = $params['id_doctor'] ?? null;
        $idSucursal = $params['id_sucursal'] ?? null;
        $semana = $params['semana'] ?? date('Y-m-d'); // Formato: 2025-01-20 (lunes de la semana)
        
        if (!$idDoctor) {
            return ResponseUtil::badRequest('ID del doctor es requerido');
        }
        
        if (!$idSucursal) {
            return ResponseUtil::badRequest('ID de sucursal es requerido');
        }
        
        // Calcular fechas de la semana
        $fechaInicio = new DateTime($semana);
        $fechaInicio->modify('monday this week');
        $fechaFin = clone $fechaInicio;
        $fechaFin->modify('+6 days');
        
        // 1. Obtener horarios del doctor
        $horarios = DB::table('doctor_horarios')
            ->where('id_doctor', $idDoctor)
            ->where('id_sucursal', $idSucursal)
            ->where('activo', 1)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get()
            ->toArray();
        
        // 2. Obtener citas ocupadas en el rango de fechas
        $citasOcupadas = DB::table('citas')
            ->where('id_doctor', $idDoctor)
            ->where('id_sucursal', $idSucursal)
            ->whereBetween('fecha_hora', [
                $fechaInicio->format('Y-m-d 00:00:00'),
                $fechaFin->format('Y-m-d 23:59:59')
            ])
            ->whereNotIn('estado', ['Cancelada'])
            ->select(
                DB::raw('DATE(fecha_hora) as fecha'),
                DB::raw('TIME(fecha_hora) as hora'),
                'motivo',
                'estado'
            )
            ->get()
            ->toArray();
        
        // 3. Obtener excepciones (días no laborables, vacaciones, etc.)
        $excepciones = DB::table('doctor_excepciones')
            ->where('id_doctor', $idDoctor)
            ->whereBetween('fecha', [
                $fechaInicio->format('Y-m-d'),
                $fechaFin->format('Y-m-d')
            ])
            ->where('activo', 1)
            ->get()
            ->toArray();
        
        return ResponseUtil::success([
            'horarios' => $horarios,
            'citas_ocupadas' => $citasOcupadas,
            'excepciones' => $excepciones,
            'semana_inicio' => $fechaInicio->format('Y-m-d'),
            'semana_fin' => $fechaFin->format('Y-m-d')
        ], 'Horarios disponibles obtenidos exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo horarios disponibles: ' . $e->getMessage());
    }
}

/**
 * Guardar horarios de un médico
 */
public function guardarHorarios2()
{
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $idDoctor = (int)($data['id_doctor'] ?? 0);
        $horarios = $data['horarios'] ?? [];
        
        if (!$idDoctor || empty($horarios)) {
            return ResponseUtil::badRequest('ID de médico y horarios son requeridos');
        }
        
        // Insertar cada horario
        foreach ($horarios as $horario) {
            DB::table('doctor_horarios')->insert([
                'id_doctor' => $idDoctor,
                'id_sucursal' => $horario['id_sucursal'],
                'dia_semana' => $horario['dia_semana'],
                'hora_inicio' => $horario['hora_inicio'],
                'hora_fin' => $horario['hora_fin'],
                'duracion_cita' => $horario['duracion_cita'],
                'activo' => 1,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ]);
        }
        
        return ResponseUtil::success(null, 'Horarios guardados exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error guardando horarios: ' . $e->getMessage());
    }
}

/**
 * Eliminar horario
 */
public function eliminarHorario()
{
    try {
        $idHorario = $_GET['id'] ?? null;
        
        if (!$idHorario) {
            return ResponseUtil::badRequest('ID de horario requerido');
        }
        
        DB::table('doctor_horarios')
            ->where('id_horario', $idHorario)
            ->delete();
        
        return ResponseUtil::success(null, 'Horario eliminado exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error eliminando horario: ' . $e->getMessage());
    }
}

/**
 * Editar horario existente
 */
public function editarHorario()
{
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $idHorario = (int)($data['id_horario'] ?? 0);
        
        if (!$idHorario) {
            return ResponseUtil::badRequest('ID de horario requerido');
        }
        
        // Verificar que el horario existe
        $horarioExiste = DB::table('doctor_horarios')
            ->where('id_horario', $idHorario)
            ->first();
            
        if (!$horarioExiste) {
            return ResponseUtil::notFound('Horario no encontrado');
        }
        
        // Actualizar solo los campos enviados
        $camposUpdate = [];
        if (isset($data['id_sucursal'])) $camposUpdate['id_sucursal'] = $data['id_sucursal'];
        if (isset($data['dia_semana'])) $camposUpdate['dia_semana'] = $data['dia_semana'];
        if (isset($data['hora_inicio'])) $camposUpdate['hora_inicio'] = $data['hora_inicio'];
        if (isset($data['hora_fin'])) $camposUpdate['hora_fin'] = $data['hora_fin'];
        if (isset($data['duracion_cita'])) $camposUpdate['duracion_cita'] = $data['duracion_cita'];
        
        if (empty($camposUpdate)) {
            return ResponseUtil::badRequest('No hay datos para actualizar');
        }
        
        DB::table('doctor_horarios')
            ->where('id_horario', $idHorario)
            ->update($camposUpdate);
        
        return ResponseUtil::success(null, 'Horario actualizado exitosamente');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error editando horario: ' . $e->getMessage());
    }
}

/**
 * ✅ CREAR NUEVA CITA
 */
/**
 * ✅ CREAR NUEVA CITA - CORREGIDO PARA GUARDAR CORRECTAMENTE
 */
public function crearCita(Request $request, Response $response): Response
{
    try {
        $data = $request->getParsedBody();
        
        // Validar campos requeridos
        $camposRequeridos = ['id_paciente', 'id_doctor', 'id_sucursal', 'fecha_hora', 'motivo'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            return ResponseUtil::badRequest('Campos requeridos faltantes: ' . implode(', ', $camposFaltantes));
        }
        
        // Validar campos específicos para citas virtuales
        if (!empty($data['id_tipo_cita']) && $data['id_tipo_cita'] == 2) {
            if (empty($data['plataforma_virtual'])) {
                return ResponseUtil::badRequest('La plataforma virtual es requerida para citas virtuales');
            }
        }
        
        // Validar formato de fecha
        if (!DateTime::createFromFormat('Y-m-d H:i:s', $data['fecha_hora'])) {
            return ResponseUtil::badRequest('Formato de fecha_hora inválido. Use: YYYY-MM-DD HH:MM:SS');
        }
        
        // Validar que la fecha sea futura
        $fechaCita = new DateTime($data['fecha_hora']);
        $ahora = new DateTime();
        if ($fechaCita <= $ahora) {
            return ResponseUtil::badRequest('La fecha y hora de la cita debe ser futura');
        }
        
        // Verificar que no haya conflicto de horario
        $citaExistente = DB::table('citas')
            ->where('id_doctor', $data['id_doctor'])
            ->where('fecha_hora', $data['fecha_hora'])
            ->whereNotIn('estado', ['Cancelada'])
            ->first();
        
        if ($citaExistente) {
            return ResponseUtil::conflict('Ya existe una cita programada para esa fecha y hora');
        }
        
        DB::beginTransaction();
        
        // ✅ PREPARAR DATOS BASE
        $citaData = [
            'id_paciente' => $data['id_paciente'],
            'id_doctor' => $data['id_doctor'],
            'id_sucursal' => $data['id_sucursal'],
            'id_tipo_cita' => $data['id_tipo_cita'] ?? 1,
            'fecha_hora' => $data['fecha_hora'],
            'motivo' => trim($data['motivo']),
            'tipo_cita' => ($data['id_tipo_cita'] ?? 1) == 2 ? 'Virtual' : 'Presencial',
            'estado' => 'Confirmada',
            'notas' => $data['notas'] ?? null
        ];
        
        // ✅ GENERAR DATOS VIRTUALES - ASEGURAR QUE SE GUARDEN
        $enlaceVirtual = null;
        $salaVirtual = null;
        
        if ($citaData['id_tipo_cita'] == 2) {
            $plataforma = $data['plataforma_virtual'] ?? 'zoom';
            $enlaceVirtual = $this->generarEnlaceVirtual($plataforma);
            
            // USAR sala_virtual PARA GUARDAR PLATAFORMA + ID
            $salaBase = $data['sala_virtual'] ?? ('Sala-' . uniqid());
            $salaVirtual = $plataforma . '|' . $salaBase;
            
            error_log("🔍 DEBUG: Datos virtuales generados:");
            error_log("  - Plataforma: " . $plataforma);
            error_log("  - Enlace: " . $enlaceVirtual);
            error_log("  - Sala: " . $salaVirtual);
        }
        
        // ✅ AGREGAR EXPLÍCITAMENTE LOS CAMPOS VIRTUALES
        $citaData['enlace_virtual'] = $enlaceVirtual;
        $citaData['sala_virtual'] = $salaVirtual;
        
        error_log("🔍 DEBUG: Datos finales para insertar:");
        error_log(json_encode($citaData, JSON_PRETTY_PRINT));
        
        // Crear la cita
        $citaId = DB::table('citas')->insertGetId($citaData);
        
        error_log("🔍 DEBUG: Cita creada con ID: " . $citaId);
        
        DB::commit();
        
        // ✅ VERIFICAR QUE SE GUARDÓ CORRECTAMENTE
        $citaVerificacion = DB::table('citas')
            ->where('id_cita', $citaId)
            ->first();
            
        error_log("🔍 DEBUG: Cita guardada - enlace_virtual: " . ($citaVerificacion->enlace_virtual ?? 'NULL'));
        error_log("🔍 DEBUG: Cita guardada - sala_virtual: " . ($citaVerificacion->sala_virtual ?? 'NULL'));
        
        // Obtener datos completos de la cita creada
        $citaCompleta = DB::table('citas')
            ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
            ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
            ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal')
            ->leftJoin('tipos_cita', 'citas.id_tipo_cita', '=', 'tipos_cita.id_tipo_cita')
            ->select(
                'citas.*',
                'u_paciente.nombres as paciente_nombres',
                'u_paciente.apellidos as paciente_apellidos',
                'u_paciente.cedula as paciente_cedula',
                'u_paciente.correo as paciente_correo',
                'u_doctor.nombres as doctor_nombres',
                'u_doctor.apellidos as doctor_apellidos',
                'doctores.titulo_profesional',
                'especialidades.nombre_especialidad',
                'sucursales.nombre_sucursal',
                'tipos_cita.nombre_tipo as tipo_cita_nombre'
            )
            ->where('citas.id_cita', $citaId)
            ->first();

        // ✅ EXTRAER PLATAFORMA DEL CAMPO sala_virtual
        $plataformaVirtual = null;
        $salaVirtualLimpia = $citaCompleta->sala_virtual;
        
        if ($citaCompleta->tipo_cita == 'virtual' && $citaCompleta->sala_virtual) {
            $partes = explode('|', $citaCompleta->sala_virtual);
            if (count($partes) == 2) {
                $plataformaVirtual = $partes[0];
                $salaVirtualLimpia = $partes[1];
            }
        }

        // ✅ ENVIAR CORREO CON INFO VIRTUAL
        if ($citaCompleta && !empty($citaCompleta->paciente_correo)) {
            try {
                require_once __DIR__ . '/../../../config/MailService.php';
                $mailService = new \MailService();

                // Agregar info de plataforma al array de la cita para el correo
                $citaParaCorreo = (array)$citaCompleta;
                $citaParaCorreo['plataforma_virtual'] = $plataformaVirtual;

                $resultadoCorreo = $mailService->enviarConfirmacionCita(
                    $citaParaCorreo,
                    [
                        'nombres'   => $citaCompleta->paciente_nombres,
                        'apellidos' => $citaCompleta->paciente_apellidos,
                        'correo'    => $citaCompleta->paciente_correo
                    ]
                );
                
                error_log("🔍 DEBUG: Resultado envío correo: " . ($resultadoCorreo ? 'ÉXITO' : 'FALLO'));
            } catch (Exception $e) {
                error_log("❌ ERROR enviando correo: " . $e->getMessage());
            }
        }
        
        return ResponseUtil::success([
            'cita' => $citaCompleta,
            'id_cita' => $citaId,
            'enlace_virtual' => $citaCompleta->enlace_virtual,
            'plataforma_virtual' => $plataformaVirtual,
            'sala_virtual' => $salaVirtualLimpia
        ], 'Cita creada exitosamente' . ($citaCompleta->tipo_cita == 'virtual' ? ' con enlace virtual generado' : ''));
        
    } catch (Exception $e) {
        DB::rollBack();
        error_log("❌ ERROR en crearCita: " . $e->getMessage());
        return ResponseUtil::error('Error creando cita: ' . $e->getMessage());
    }
}
// ✅ MANTENER MÉTODOS DE GENERACIÓN DE ENLACES
private function generarEnlaceVirtual($plataforma) {
    $enlaces = [
        'zoom' => 'https://zoom.us/j/' . rand(100000000, 999999999),
        'meet' => 'https://meet.google.com/' . $this->generarCodigoMeet(),
        'teams' => 'https://teams.microsoft.com/l/meetup-join/' . $this->generarCodigoTeams(),
        'whatsapp' => 'https://wa.me/' . rand(100000000000, 999999999999),
        'jitsi' => 'https://meet.jit.si/' . uniqid()
    ];
    
    return $enlaces[$plataforma] ?? $enlaces['zoom'];
}

private function generarCodigoMeet() {
    $caracteres = 'abcdefghijklmnopqrstuvwxyz';
    $codigo = '';
    for ($i = 0; $i < 3; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        if ($i < 2) $codigo .= '-';
    }
    return $codigo;
}

private function generarCodigoTeams() {
    return bin2hex(random_bytes(16));
}


// En tu CitasController.php - Agregar este método
/**
 * Obtener citas de un paciente por fecha específica
 */
// En CitasController.php - Agregar este método

/**
 * Obtener citas de un paciente por fecha específica
 */
/**
 * Obtener citas de un paciente por fecha específica
 */
public function obtenerCitasPacientePorFecha(Request $request, Response $response, array $args): Response
{
    try {
        $cedula = $args['cedula'] ?? null;
        $fecha = $request->getQueryParams()['fecha'] ?? date('Y-m-d');
        
        if (empty($cedula)) {
            return ResponseUtil::badRequest('Cédula del paciente es requerida');
        }
        
        // Buscar paciente por cédula
        $paciente = DB::table('usuarios')
            ->join('pacientes', 'usuarios.id_usuario', '=', 'pacientes.id_usuario')
            ->where('usuarios.cedula', $cedula)
            ->select('pacientes.id_paciente', 'usuarios.nombres', 'usuarios.apellidos')
            ->first();
            
        if (!$paciente) {
            return ResponseUtil::notFound('Paciente no encontrado');
        }
        
        // CORRIGIENDO NOMBRES DE CAMPOS SEGÚN TU BD
        $citas = DB::table('citas')
            ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
            ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
            ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal')
            ->leftJoin('tipos_cita', 'citas.id_tipo_cita', '=', 'tipos_cita.id_tipo_cita')
            ->leftJoin('triage', 'citas.id_cita', '=', 'triage.id_cita')
            ->where('citas.id_paciente', $paciente->id_paciente)
            ->whereDate('citas.fecha_hora', '=', $fecha)
            ->whereIn('citas.estado', ['Confirmada', 'Pendiente'])
            ->whereNull('triage.id_triage') // Solo citas sin triaje
            ->select(
                'citas.*',
                'u_paciente.nombres as paciente_nombres',
                'u_paciente.apellidos as paciente_apellidos',
                'u_paciente.cedula as paciente_cedula',
                'u_doctor.nombres as doctor_nombres',
                'u_doctor.apellidos as doctor_apellidos',
                'doctores.titulo_profesional',
                'especialidades.nombre_especialidad', // CAMPO CORRECTO
                'sucursales.nombre_sucursal', // CAMPO CORRECTO
                'tipos_cita.nombre_tipo as tipo_cita_nombre',
                'triage.id_triage',
                'triage.estado_triaje'
            )
            ->orderBy('citas.fecha_hora', 'asc')
            ->get();
        
        return ResponseUtil::success($citas->toArray(), 'Citas encontradas para la fecha: ' . $fecha);
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo citas del paciente: ' . $e->getMessage());
    }
}

/**
 * Crear triaje para una cita
 */
public function crearTriaje(Request $request, Response $response): Response
{
    try {
        $data = $request->getParsedBody();
        
        // 🔥 VERIFICAR QUE VENGA EL ID_ENFERMERO EN EL REQUEST
        if (empty($data['id_enfermero'])) {
            return ResponseUtil::badRequest('ID de enfermero es requerido');
        }

        // 🔒 VERIFICAR QUE EL USUARIO EXISTE Y ES ENFERMERO (ID_ROL 73)
        $usuario = DB::table('usuarios')
            ->join('roles', 'usuarios.id_rol', '=', 'roles.id_rol')
            ->where('usuarios.id_usuario', $data['id_enfermero'])
            ->where('usuarios.id_estado', 1) // Usuario activo
            ->select('usuarios.id_usuario', 'usuarios.id_rol', 'roles.nombre_rol', 'usuarios.nombres', 'usuarios.apellidos')
            ->first();

        if (!$usuario) {
            return ResponseUtil::unauthorized('Usuario no encontrado o inactivo');
        }

        // 🔥 VERIFICAR QUE SEA ENFERMERO (ID_ROL 73) O ADMIN
        if ($usuario->id_rol != 73 && strtolower($usuario->nombre_rol) !== 'Administrador') {
            error_log("Access denied - User: {$usuario->nombres} {$usuario->apellidos} - Role: {$usuario->nombre_rol} (ID: {$usuario->id_rol})");
            return ResponseUtil::forbidden('Solo enfermeros pueden registrar triaje');
        }

        // Validar campos requeridos
        if (empty($data['id_cita']) || empty($data['nivel_urgencia'])) {
            return ResponseUtil::badRequest('ID de cita y nivel de urgencia son requeridos');
        }
        
        // Verificar que la cita existe y está confirmada
        $cita = DB::table('citas')->where('id_cita', $data['id_cita'])->first();
        if (!$cita) {
            return ResponseUtil::notFound('Cita no encontrada');
        }
        
        if (!in_array($cita->estado, ['Confirmada', 'Pendiente'])) {
            return ResponseUtil::badRequest('Solo se puede hacer triaje a citas confirmadas o pendientes');
        }
        
        // Verificar que no tenga triaje ya
        $triajeExistente = DB::table('triage')->where('id_cita', $data['id_cita'])->first();
        if ($triajeExistente) {
            return ResponseUtil::conflict('Esta cita ya tiene triaje realizado');
        }
        
        DB::beginTransaction();
        
        // Calcular IMC
        $imc = null;
        if (!empty($data['peso']) && !empty($data['talla'])) {
            $peso = (float)$data['peso'];
            $talla = (int)$data['talla'];
            if ($peso > 0 && $talla > 0) {
                $alturaMetros = $talla / 100;
                $imc = round($peso / ($alturaMetros * $alturaMetros), 2);
            }
        }
        
        // Determinar estado del triaje según urgencia
        $estadoTriaje = match((int)$data['nivel_urgencia']) {
            5 => 'Critico',
            4 => 'Critico', 
            3 => 'Urgente',
            2 => 'Pendiente_Atencion',
            default => 'Completado'
        };
        
        // 🔥 USAR LA FECHA_HORA DEL REQUEST O LA ACTUAL
        $fechaHora = $data['fecha_hora'] ?? date('Y-m-d H:i:s');
        
        // Insertar triaje
        $idTriaje = DB::table('triage')->insertGetId([
            'id_cita' => $data['id_cita'],
            'id_enfermero' => $data['id_enfermero'], // 🔥 USAR EL ID DEL REQUEST
            'nivel_urgencia' => $data['nivel_urgencia'],
            'estado_triaje' => $estadoTriaje,
            'temperatura' => $data['temperatura'] ?? null,
            'presion_arterial' => $data['presion_arterial'] ?? null,
            'frecuencia_cardiaca' => $data['frecuencia_cardiaca'] ?? null,
            'frecuencia_respiratoria' => $data['frecuencia_respiratoria'] ?? null,
            'saturacion_oxigeno' => $data['saturacion_oxigeno'] ?? null,
            'peso' => $data['peso'] ?? null,
            'talla' => $data['talla'] ?? null,
            'imc' => $imc,
            'observaciones' => $data['observaciones'] ?? null,
            'fecha_hora' => $fechaHora
        ]);
        
        DB::commit();
        
        // Validar signos vitales y generar alertas
        $alertas = $this->validarSignosVitales($data);
        
        // Categorizar IMC
        $categoriaImc = null;
        if ($imc) {
            $categoriaImc = match(true) {
                $imc < 18.5 => 'Bajo peso',
                $imc < 25 => 'Peso normal',
                $imc < 30 => 'Sobrepeso',
                default => 'Obesidad'
            };
        }
        
        $responseData = [
            'id_triaje' => $idTriaje,
            'estado_triaje' => $estadoTriaje,
            'imc' => $imc,
            'categoria_imc' => $categoriaImc,
            'alertas' => $alertas,
            'tiene_alertas' => !empty($alertas)
        ];
        
        $mensaje = 'Triaje registrado exitosamente';
        if (!empty($alertas)) {
            $mensaje .= ' - ATENCIÓN: Se detectaron signos vitales que requieren revisión';
        }

        // 🔥 LOG DE ÉXITO
        error_log("Triaje creado exitosamente - ID: {$idTriaje} - Enfermero: {$usuario->nombres} {$usuario->apellidos} - Cita: {$data['id_cita']}");
        
        return ResponseUtil::success($responseData, $mensaje);
        
    } catch (Exception $e) {
        DB::rollBack();
        error_log("Error creando triaje: " . $e->getMessage());
        return ResponseUtil::error('Error creando triaje: ' . $e->getMessage());
    }
}

/**
 * Validar signos vitales
 */
private function validarSignosVitales(array $signos): array 
{
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
            $alertas[] = 'Saturación de oxígeno baja (<95%) - REQUIERE ATENCIÓN INMEDIATA';
        }
    }
    
    return $alertas;
}

// En EnfermeriaController.php
/**
 * Obtener triaje de una cita específica
 */
public function obtenerTriajePorCita(Request $request, Response $response): Response
{
    try {
        $route = $request->getAttribute('route');
        $idCita = $route->getArgument('id_cita');
        
        $triaje = DB::table('triage')
            ->join('usuarios', 'triage.id_enfermero', '=', 'usuarios.id_usuario')
            ->where('triage.id_cita', $idCita)
            ->select(
                'triage.*',
                'usuarios.nombres as enfermero_nombres',
                'usuarios.apellidos as enfermero_apellidos'
            )
            ->first();
            
        if (!$triaje) {
            return ResponseUtil::notFound('No se encontró triaje para esta cita');
        }
        
        return ResponseUtil::success($triaje, 'Triaje encontrado');
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo triaje: ' . $e->getMessage());
    }
}




/**
 * Obtener citas confirmadas/en proceso del doctor para hacer consulta médica
 */
public function obtenerCitasConsultaDoctor(Request $request, Response $response, array $args): Response
{
    try {
        $cedula_doctor = $args['cedula'] ?? null;
        $fecha = $request->getQueryParams()['fecha'] ?? date('Y-m-d');
        $estado = $request->getQueryParams()['estado'] ?? 'Confirmada';
        
        error_log("🔍 Iniciando obtenerCitasConsultaDoctor - Cédula: {$cedula_doctor}, Fecha: {$fecha}, Estado: {$estado}");
        
        if (empty($cedula_doctor)) {
            return ResponseUtil::badRequest('Cédula del doctor es requerida');
        }
        
        // Buscar doctor por cédula
        $doctor = DB::table('usuarios')
            ->join('doctores', 'usuarios.id_usuario', '=', 'doctores.id_usuario')
            ->where('usuarios.cedula', $cedula_doctor)
            ->select('doctores.id_doctor', 'usuarios.nombres', 'usuarios.apellidos')
            ->first();
            
        if (!$doctor) {
            error_log("❌ Doctor no encontrado con cédula: {$cedula_doctor}");
            return ResponseUtil::notFound('Doctor no encontrado con cédula: ' . $cedula_doctor);
        }
        
        error_log("✅ Doctor encontrado - ID: {$doctor->id_doctor}, Nombre: {$doctor->nombres} {$doctor->apellidos}");
        
        // Consulta completa con TODOS los datos necesarios
        $citas = DB::table('citas as c')
            ->join('pacientes as pac', 'c.id_paciente', '=', 'pac.id_paciente')
            ->join('usuarios as u_pac', 'pac.id_usuario', '=', 'u_pac.id_usuario')
            ->join('doctores as d', 'c.id_doctor', '=', 'd.id_doctor')
            ->join('usuarios as u_doc', 'd.id_usuario', '=', 'u_doc.id_usuario')
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->join('sucursales as s', 'c.id_sucursal', '=', 's.id_sucursal')
            ->leftJoin('tipos_cita as tc', 'c.id_tipo_cita', '=', 'tc.id_tipo_cita')
            ->leftJoin('triage as t', 'c.id_cita', '=', 't.id_cita')
            ->leftJoin('consultas_medicas as cm', 'c.id_cita', '=', 'cm.id_cita')
            ->leftJoin('historiales_clinicos as hc', 'pac.id_paciente', '=', 'hc.id_paciente')
            ->where('c.id_doctor', $doctor->id_doctor)
            ->whereDate('c.fecha_hora', '=', $fecha)
            ->when($estado !== 'Todas', function ($query) use ($estado) {
                return $query->where('c.estado', $estado);
            })
            ->select([
                // DATOS DE LA CITA
                'c.id_cita', 'c.fecha_hora', 'c.motivo', 'c.estado', 'c.notas', 
                'c.tipo_cita', 'c.enlace_virtual', 'c.sala_virtual', 'c.fecha_creacion',
                
                // DATOS COMPLETOS DEL PACIENTE  
                'u_pac.nombres as paciente_nombres',
                'u_pac.apellidos as paciente_apellidos', 
                'u_pac.cedula as paciente_cedula',
                'u_pac.correo as paciente_correo',
                'u_pac.sexo as paciente_sexo',
                'u_pac.nacionalidad as paciente_nacionalidad',
                'pac.telefono as paciente_telefono',
                'pac.fecha_nacimiento as paciente_fecha_nacimiento',
                'pac.tipo_sangre as paciente_tipo_sangre',
                'pac.alergias as paciente_alergias',
                'pac.contacto_emergencia as paciente_contacto_emergencia',
                'pac.telefono_emergencia as paciente_telefono_emergencia',
                
                // DATOS DEL DOCTOR
                'u_doc.nombres as doctor_nombres',
                'u_doc.apellidos as doctor_apellidos',
                'd.titulo_profesional as doctor_titulo',
                
                // ESPECIALIDAD Y SUCURSAL
                'e.nombre_especialidad',
                'e.descripcion as especialidad_descripcion',
                's.nombre_sucursal',
                's.direccion as sucursal_direccion',
                's.telefono as sucursal_telefono',
                's.email as sucursal_email',
                's.horario_atencion',
                
                // TIPO DE CITA
                'tc.nombre_tipo as tipo_cita_nombre',
                
                // DATOS COMPLETOS DEL TRIAJE
                't.id_triage',
                't.nivel_urgencia', 
                't.temperatura', 
                't.presion_arterial',
                't.frecuencia_cardiaca', 
                't.frecuencia_respiratoria',
                't.saturacion_oxigeno',
                't.peso', 
                't.talla',
                't.imc',
                't.observaciones as triaje_observaciones',
                't.fecha_hora as triaje_fecha_hora',
                
                // DATOS DE CONSULTA MÉDICA
                'cm.id_consulta',
                'cm.motivo_consulta',
                'cm.sintomatologia',
                'cm.diagnostico',
                'cm.tratamiento',
                'cm.observaciones as consulta_observaciones',
                'cm.fecha_seguimiento',
                'cm.fecha_hora as consulta_fecha_hora',
                
                // HISTORIAL CLÍNICO
                'hc.id_historial'
            ])
            ->orderBy('c.fecha_hora', 'asc')
            ->get();
        
        error_log("📊 Citas encontradas: " . $citas->count());
        
        // Formatear respuesta con estructura correcta
        $citas_formateadas = $citas->map(function($cita) {
            return [
                // DATOS BÁSICOS DE LA CITA
                'id_cita' => (int)$cita->id_cita,
                'fecha_hora' => $cita->fecha_hora,
                'motivo' => $cita->motivo,
                'estado' => $cita->estado,
                'tipo_cita' => $cita->tipo_cita,
                'notas' => $cita->notas,
                'enlace_virtual' => $cita->enlace_virtual,
                'sala_virtual' => $cita->sala_virtual,
                'fecha_creacion' => $cita->fecha_creacion,
                
                // INFORMACIÓN COMPLETA DEL PACIENTE
                'paciente' => [
                    'nombres' => $cita->paciente_nombres,
                    'apellidos' => $cita->paciente_apellidos,
                    'cedula' => $cita->paciente_cedula,
                    'correo' => $cita->paciente_correo,
                    'telefono' => $cita->paciente_telefono,
                    'fecha_nacimiento' => $cita->paciente_fecha_nacimiento,
                    'sexo' => $cita->paciente_sexo,
                    'nacionalidad' => $cita->paciente_nacionalidad,
                    'tipo_sangre' => $cita->paciente_tipo_sangre,
                    'alergias' => $cita->paciente_alergias,
                    'contacto_emergencia' => $cita->paciente_contacto_emergencia,
                    'telefono_emergencia' => $cita->paciente_telefono_emergencia,
                    'nombre_completo' => trim($cita->paciente_nombres . ' ' . $cita->paciente_apellidos)
                ],
                
                // DATOS DEL DOCTOR
                'doctor' => [
                    'nombres' => $cita->doctor_nombres,
                    'apellidos' => $cita->doctor_apellidos,
                    'titulo_profesional' => $cita->doctor_titulo,
                    'especialidad' => $cita->nombre_especialidad,
                    'nombre_completo' => trim($cita->doctor_nombres . ' ' . $cita->doctor_apellidos)
                ],
                
                // DATOS DE LA SUCURSAL
                'sucursal' => [
                    'nombre' => $cita->nombre_sucursal,
                    'direccion' => $cita->sucursal_direccion,
                    'telefono' => $cita->sucursal_telefono,
                    'email' => $cita->sucursal_email,
                    'horario_atencion' => $cita->horario_atencion
                ],
                
                // ESPECIALIDAD
                'especialidad' => [
                    'nombre' => $cita->nombre_especialidad,
                    'descripcion' => $cita->especialidad_descripcion
                ],
                
                // TIPO DE CITA
                'tipo_cita_info' => [
                    'codigo' => $cita->tipo_cita,
                    'nombre' => $cita->tipo_cita_nombre
                ],
                
                // DATOS COMPLETOS DEL TRIAJE
                'triaje' => $cita->id_triage ? [
                    'id_triage' => $cita->id_triage,
                    'signos_vitales' => [
                        'temperatura' => $cita->temperatura,
                        'presion_arterial' => $cita->presion_arterial,
                        'frecuencia_cardiaca' => $cita->frecuencia_cardiaca,
                        'frecuencia_respiratoria' => $cita->frecuencia_respiratoria,
                        'saturacion_oxigeno' => $cita->saturacion_oxigeno,
                        'peso' => $cita->peso,
                        'talla' => $cita->talla,
                        'imc' => $cita->imc
                    ],
                    'evaluacion' => [
                        'nivel_urgencia' => $cita->nivel_urgencia,
                        'observaciones' => $cita->triaje_observaciones,
                        'fecha_triaje' => $cita->triaje_fecha_hora
                    ]
                ] : null,
                
                // CONSULTA MÉDICA (si existe)
                'consulta_medica' => $cita->id_consulta ? [
                    'id_consulta' => $cita->id_consulta,
                    'motivo_consulta' => $cita->motivo_consulta,
                    'sintomatologia' => $cita->sintomatologia,
                    'diagnostico' => $cita->diagnostico,
                    'tratamiento' => $cita->tratamiento,
                    'observaciones' => $cita->consulta_observaciones,
                    'fecha_seguimiento' => $cita->fecha_seguimiento,
                    'fecha_consulta' => $cita->consulta_fecha_hora
                ] : null,
                
                // ESTADOS Y CAPACIDADES
                'tiene_triaje' => !is_null($cita->id_triage),
                'tiene_consulta' => !is_null($cita->id_consulta),
                'tiene_historial' => !is_null($cita->id_historial),
                'puede_consultar' => is_null($cita->id_consulta) && in_array($cita->estado, ['Confirmada', 'En Proceso']),
                'es_urgente' => $cita->nivel_urgencia >= 4
            ];
        });
        
        $mensaje = "Se encontraron " . $citas->count() . " citas para el doctor en fecha: $fecha";
        error_log("✅ " . $mensaje);
        
        return ResponseUtil::success($citas_formateadas->toArray(), $mensaje);
        
    } catch (Exception $e) {
        error_log("❌ Error en obtenerCitasConsultaDoctor: " . $e->getMessage());
        error_log("❌ Stack trace: " . $e->getTraceAsString());
        return ResponseUtil::error('Error obteniendo citas del doctor: ' . $e->getMessage());
    }
}

/**
 * Crear o actualizar consulta médica (diagnóstico + tratamiento + receta)
 */
public function crearActualizarConsultaMedica(Request $request, Response $response, array $args): Response
{
    try {
        $id_cita = $args['id_cita'] ?? null;
        $data = $request->getParsedBody();
        
        if (empty($id_cita)) {
            return ResponseUtil::badRequest('ID de cita es requerido');
        }
        
        // Validar datos requeridos
        $validator = $this->validarDatosConsulta($data);
        if (!$validator['valido']) {
            return ResponseUtil::badRequest($validator['errores']);
        }
        
        // Verificar que la cita existe y está en estado correcto
        $cita = DB::table('citas')
            ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
            ->leftJoin('historiales_clinicos', 'pacientes.id_paciente', '=', 'historiales_clinicos.id_paciente')
            ->where('citas.id_cita', $id_cita)
            ->whereIn('citas.estado', ['Confirmada', 'En Proceso', 'Completada'])
            ->select('citas.*', 'historiales_clinicos.id_historial')
            ->first();
            
        if (!$cita) {
            return ResponseUtil::notFound('Cita no encontrada o no está en estado válido para consulta');
        }
        
        // Verificar si ya existe una consulta médica
        $consultaExistente = DB::table('consultas_medicas')
            ->where('id_cita', $id_cita)
            ->first();
        
        DB::beginTransaction();
        
        try {
            // Crear historial clínico si no existe
            $id_historial = $cita->id_historial;
            if (!$id_historial) {
                $id_historial = DB::table('historiales_clinicos')->insertGetId([
                    'id_paciente' => $cita->id_paciente,
                    'fecha_creacion' => date('Y-m-d H:i:s'),
                    'ultima_actualizacion' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Preparar datos de la consulta
            $datosConsulta = [
                'id_cita' => $id_cita,
                'id_historial' => $id_historial,
                'motivo_consulta' => $data['motivo_consulta'],
                'sintomatologia' => $data['sintomatologia'] ?? null,
                'diagnostico' => $data['diagnostico'],
                'tratamiento' => $data['tratamiento'] ?? null,
                'observaciones' => $data['observaciones'] ?? null, // Aquí van las recetas
                'fecha_seguimiento' => !empty($data['fecha_seguimiento']) ? $data['fecha_seguimiento'] : null,
                'fecha_hora' => date('Y-m-d H:i:s'),
            ];
            
            if ($consultaExistente) {
                // Actualizar consulta existente
                DB::table('consultas_medicas')
                    ->where('id_consulta', $consultaExistente->id_consulta)
                    ->update($datosConsulta);
                    
                $id_consulta = $consultaExistente->id_consulta;
                $accion = 'actualizada';
            } else {
                // Crear nueva consulta
                $id_consulta = DB::table('consultas_medicas')->insertGetId($datosConsulta);
                $accion = 'creada';
            }
            
            // Actualizar estado de la cita
            DB::table('citas')
                ->where('id_cita', $id_cita)
                ->update([
                    'estado' => 'Completada',
                    'notas' => 'Consulta médica completada'
                ]);
                
            // Actualizar historial clínico
            DB::table('historiales_clinicos')
                ->where('id_historial', $id_historial)
                ->update(['ultima_actualizacion' => date('Y-m-d H:i:s')]);
            
            DB::commit();
            
            // 🔥 NUEVO: ENVIAR PDF AUTOMÁTICAMENTE
            try {
                $this->enviarPDFAutomatico($id_cita);
            } catch (Exception $e) {
                error_log("⚠️ Warning: No se pudo enviar PDF automático: " . $e->getMessage());
                // No fallar la operación si el PDF no se puede enviar
            }
            
            // Retornar datos completos de la consulta creada/actualizada
            $consultaCompleta = $this->obtenerConsultaCompleta($id_consulta);
            
            return ResponseUtil::success($consultaCompleta, "Consulta médica $accion exitosamente");
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error procesando consulta médica: ' . $e->getMessage());
    }
}

/**
 * 🔥 NUEVO MÉTODO: Enviar PDF automático después de completar consulta
 */
private function enviarPDFAutomatico($id_cita)
{
    try {
        // Incluir las clases necesarias
        require_once __DIR__ . '/../../../config/Mailer.php';
        require_once __DIR__ . '/../../../controladores/ConsultasMedicasControlador/GenerarPDFConsulta.php';
        
        // Obtener datos completos de la cita para el PDF
        $cita = $this->obtenerDatosCompletosParaPDF($id_cita);
        
        if (!$cita || empty($cita['correo_paciente'])) {
            error_log("❌ No se puede enviar PDF: faltan datos del paciente");
            return false;
        }
        
        // Generar PDF
        $pdf = new \GeneradorPDFConsulta($cita);
        $pdf->generarContenido();
        $pdf_content = $pdf->Output('', 'S'); // Obtener como string
        
        if (empty($pdf_content)) {
            error_log("❌ Error: PDF generado está vacío");
            return false;
        }
        
        // Enviar por correo
        $mailer =  new \Mailer();
        $correo_paciente = $cita['correo_paciente'];
        $nombre_paciente = trim($cita['nombres_paciente'] . ' ' . $cita['apellidos_paciente']);
        
        $enviado = $mailer->enviarPDFCita($correo_paciente, $nombre_paciente, $cita, $pdf_content);
        
        if ($enviado) {
            error_log("✅ PDF enviado automáticamente a: " . $correo_paciente);
            return true;
        } else {
            error_log("❌ Error enviando PDF automático");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ Error en envío automático de PDF: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener datos completos para generar el PDF - VERSIÓN COMPLETA
 */
private function obtenerDatosCompletosParaPDF($id_cita)
{
    $resultado = DB::table('citas as c')
        ->join('pacientes as pac', 'c.id_paciente', '=', 'pac.id_paciente')
        ->join('usuarios as u_pac', 'pac.id_usuario', '=', 'u_pac.id_usuario')
        ->join('doctores as d', 'c.id_doctor', '=', 'd.id_doctor')
        ->join('usuarios as u_doc', 'd.id_usuario', '=', 'u_doc.id_usuario')
        ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
        ->join('sucursales as s', 'c.id_sucursal', '=', 's.id_sucursal')
        ->leftJoin('triage as t', 'c.id_cita', '=', 't.id_cita')
        ->leftJoin('consultas_medicas as cm', 'c.id_cita', '=', 'cm.id_cita')
        ->where('c.id_cita', $id_cita)
        ->select([
            // Datos de la cita
            'c.id_cita', 'c.fecha_hora', 'c.motivo', 'c.estado', 'c.notas', 'c.tipo_cita',
            
            // Datos del paciente
            'u_pac.nombres as nombres_paciente', 'u_pac.apellidos as apellidos_paciente',
            'u_pac.correo as correo_paciente', 'u_pac.cedula as cedula_paciente',
            'pac.fecha_nacimiento', 'pac.tipo_sangre', 'pac.alergias', 'pac.telefono',
            'pac.contacto_emergencia', 'pac.telefono_emergencia',
            
            // Datos del doctor
            'u_doc.nombres as nombres_doctor', 'u_doc.apellidos as apellidos_doctor',
            'u_doc.correo as doctor_correo',
            'd.titulo_profesional',
            
            // Datos de especialidad y sucursal
            'e.nombre_especialidad',
            's.nombre_sucursal', 's.direccion as sucursal_direccion',
            's.telefono as sucursal_telefono', 's.email as sucursal_email',
            's.horario_atencion',
            
            // ✅ DATOS COMPLETOS DEL TRIAJE - ESTOS FALTABAN
            't.id_triage', 
            't.nivel_urgencia', 
            't.temperatura', 
            't.presion_arterial',
            't.frecuencia_cardiaca', 
            't.frecuencia_respiratoria', // ← FALTABA
            't.saturacion_oxigeno',      // ← FALTABA
            't.peso', 
            't.talla',
            't.imc',                     // ← FALTABA
            't.observaciones as triaje_observaciones',
            't.fecha_hora as triaje_fecha_hora', // ← FALTABA
            
            // Datos de la consulta médica
            'cm.motivo_consulta', 'cm.sintomatologia', 'cm.diagnostico',
            'cm.tratamiento', 'cm.observaciones as consulta_observaciones',
            'cm.fecha_seguimiento', 'cm.fecha_hora as consulta_fecha_hora'
        ])
        ->first();
    
    // Convertir objeto a array
    return $resultado ? (array) $resultado : null;
}
/**
 * Validar datos de consulta médica
 */
private function validarDatosConsulta($data): array
{
    $errores = [];
    
    if (empty($data['motivo_consulta'])) {
        $errores[] = 'Motivo de consulta es requerido';
    }
    
    if (empty($data['diagnostico'])) {
        $errores[] = 'Diagnóstico es requerido';
    }
    
    if (!empty($data['fecha_seguimiento'])) {
        if (!DateTime::createFromFormat('Y-m-d', $data['fecha_seguimiento'])) {
            $errores[] = 'Fecha de seguimiento debe tener formato Y-m-d';
        }
    }
    
    return [
        'valido' => empty($errores),
        'errores' => implode(', ', $errores)
    ];
}

/**
 * Obtener consulta médica completa por ID
 */
private function obtenerConsultaCompleta($id_consulta)
{
    return DB::table('consultas_medicas')
        ->join('citas', 'consultas_medicas.id_cita', '=', 'citas.id_cita')
        ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
        ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario')
        ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
        ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
        ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
        ->where('consultas_medicas.id_consulta', $id_consulta)
        ->select(
            'consultas_medicas.*',
            'citas.fecha_hora as fecha_cita',
            'u_paciente.nombres as paciente_nombres',
            'u_paciente.apellidos as paciente_apellidos',
            'u_paciente.cedula as paciente_cedula',
            'u_doctor.nombres as doctor_nombres',
            'u_doctor.apellidos as doctor_apellidos',
            'doctores.titulo_profesional',
            'especialidades.nombre_especialidad'
        )
        ->first();
}

/**
 * Obtener detalle completo de una consulta médica
 */
public function obtenerDetalleConsulta(Request $request, Response $response, array $args): Response
{
    try {
        $id_cita = $args['id_cita'] ?? null;
        
        if (empty($id_cita)) {
            return ResponseUtil::badRequest('ID de cita es requerido');
        }
        
        // Consulta completa con TODOS los datos
        $consulta = DB::table('consultas_medicas as cm')
            ->join('citas as c', 'cm.id_cita', '=', 'c.id_cita')
            ->join('pacientes as pac', 'c.id_paciente', '=', 'pac.id_paciente')
            ->join('usuarios as u_pac', 'pac.id_usuario', '=', 'u_pac.id_usuario')
            ->join('doctores as d', 'c.id_doctor', '=', 'd.id_doctor')
            ->join('usuarios as u_doc', 'd.id_usuario', '=', 'u_doc.id_usuario')
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->join('sucursales as s', 'c.id_sucursal', '=', 's.id_sucursal')
            ->leftJoin('tipos_cita as tc', 'c.id_tipo_cita', '=', 'tc.id_tipo_cita')
            ->leftJoin('triage as t', 'c.id_cita', '=', 't.id_cita')
            ->leftJoin('historiales_clinicos as hc', 'pac.id_paciente', '=', 'hc.id_paciente')
            ->where('cm.id_cita', $id_cita)
            ->select([
                // DATOS DE LA CONSULTA MÉDICA
                'cm.id_consulta', 'cm.fecha_hora as consulta_fecha',
                'cm.motivo_consulta', 'cm.sintomatologia', 'cm.diagnostico',
                'cm.tratamiento', 'cm.observaciones as consulta_observaciones',
                'cm.fecha_seguimiento',
                
                // DATOS DE LA CITA
                'c.id_cita', 'c.fecha_hora as cita_fecha',
                'c.motivo as cita_motivo', 'c.estado as cita_estado',
                'c.tipo_cita', 'c.notas as cita_notas',
                'c.enlace_virtual', 'c.sala_virtual',
                
                // DATOS COMPLETOS DEL PACIENTE
                'u_pac.nombres as paciente_nombres',
                'u_pac.apellidos as paciente_apellidos',
                'u_pac.cedula as paciente_cedula',
                'u_pac.correo as paciente_email',
                'u_pac.sexo as paciente_sexo',
                'u_pac.nacionalidad as paciente_nacionalidad', 
                'pac.telefono as paciente_telefono',
                'pac.fecha_nacimiento as paciente_fecha_nacimiento',
                'pac.tipo_sangre as paciente_tipo_sangre',
                'pac.alergias as paciente_alergias',
                'pac.contacto_emergencia as paciente_contacto_emergencia',
                'pac.telefono_emergencia as paciente_telefono_emergencia',
                
                // DATOS DEL DOCTOR
                'u_doc.nombres as doctor_nombres',
                'u_doc.apellidos as doctor_apellidos',
                'd.titulo_profesional',
                
                // ESPECIALIDAD Y SUCURSAL
                'e.nombre_especialidad',
                'e.descripcion as especialidad_descripcion',
                's.nombre_sucursal',
                's.direccion as sucursal_direccion',
                's.telefono as sucursal_telefono',
                's.email as sucursal_email',
                's.horario_atencion',
                
                // TIPO DE CITA
                'tc.nombre_tipo as tipo_cita_nombre',
                
                // DATOS COMPLETOS DEL TRIAJE
                't.id_triage',
                't.nivel_urgencia', 
                't.temperatura', 
                't.presion_arterial',
                't.frecuencia_cardiaca', 
                't.frecuencia_respiratoria',
                't.saturacion_oxigeno',
                't.peso', 
                't.talla',
                't.imc',
                't.observaciones as triaje_observaciones',
                't.fecha_hora as triaje_fecha',
                
                // HISTORIAL CLÍNICO
                'hc.id_historial'
            ])
            ->first();
            
        if (!$consulta) {
            return ResponseUtil::notFound('Consulta médica no encontrada');
        }
        
        // Formatear respuesta completa
        $resultado = [
            'consulta' => [
                'id_consulta' => $consulta->id_consulta,
                'fecha_hora' => $consulta->consulta_fecha,
                'motivo_consulta' => $consulta->motivo_consulta,
                'sintomatologia' => $consulta->sintomatologia,
                'diagnostico' => $consulta->diagnostico,
                'tratamiento' => $consulta->tratamiento,
                'observaciones' => $consulta->consulta_observaciones,
                'fecha_seguimiento' => $consulta->fecha_seguimiento
            ],
            
            'cita' => [
                'id_cita' => $consulta->id_cita,
                'fecha_hora' => $consulta->cita_fecha,
                'motivo_original' => $consulta->cita_motivo,
                'estado' => $consulta->cita_estado,
                'tipo' => $consulta->tipo_cita,
                'notas' => $consulta->cita_notas,
                'modalidad' => [
                    'tipo' => $consulta->tipo_cita,
                    'nombre' => $consulta->tipo_cita_nombre,
                    'enlace_virtual' => $consulta->enlace_virtual,
                    'sala_virtual' => $consulta->sala_virtual
                ]
            ],
            
            'paciente' => [
                'nombres' => $consulta->paciente_nombres,
                'apellidos' => $consulta->paciente_apellidos,
                'cedula' => $consulta->paciente_cedula,
                'correo' => $consulta->paciente_email,
                'telefono' => $consulta->paciente_telefono,
                'fecha_nacimiento' => $consulta->paciente_fecha_nacimiento,
                'sexo' => $consulta->paciente_sexo,
                'nacionalidad' => $consulta->paciente_nacionalidad,
                'tipo_sangre' => $consulta->paciente_tipo_sangre,
                'alergias' => $consulta->paciente_alergias,
                'contacto_emergencia' => $consulta->paciente_contacto_emergencia,
                'telefono_emergencia' => $consulta->paciente_telefono_emergencia,
                'nombre_completo' => trim($consulta->paciente_nombres . ' ' . $consulta->paciente_apellidos)
            ],
            
            'doctor' => [
                'nombres' => $consulta->doctor_nombres,
                'apellidos' => $consulta->doctor_apellidos,
                'titulo_profesional' => $consulta->titulo_profesional,
                'especialidad' => $consulta->nombre_especialidad,
                'nombre_completo' => trim($consulta->doctor_nombres . ' ' . $consulta->doctor_apellidos)
            ],
            
            'sucursal' => [
                'nombre_sucursal' => $consulta->nombre_sucursal,
                'direccion' => $consulta->sucursal_direccion,
                'telefono' => $consulta->sucursal_telefono,
                'email' => $consulta->sucursal_email,
                'horario_atencion' => $consulta->horario_atencion
            ],
            
            'especialidad' => [
                'nombre_especialidad' => $consulta->nombre_especialidad,
                'descripcion' => $consulta->especialidad_descripcion
            ],
            
            'triaje' => $consulta->id_triage ? [
                'id_triage' => $consulta->id_triage,
                'fecha_triaje' => $consulta->triaje_fecha,
                'signos_vitales' => [
                    'temperatura' => $consulta->temperatura,
                    'presion_arterial' => $consulta->presion_arterial,
                    'frecuencia_cardiaca' => $consulta->frecuencia_cardiaca,
                    'frecuencia_respiratoria' => $consulta->frecuencia_respiratoria,
                    'saturacion_oxigeno' => $consulta->saturacion_oxigeno,
                    'peso' => $consulta->peso,
                    'talla' => $consulta->talla,
                    'imc' => $consulta->imc
                ],
                'evaluacion' => [
                    'nivel_urgencia' => $consulta->nivel_urgencia,
                    'observaciones' => $consulta->triaje_observaciones
                ]
            ] : null,
            
            'historial' => [
                'tiene_historial' => !is_null($consulta->id_historial),
                'id_historial' => $consulta->id_historial
            ]
        ];
        
        return ResponseUtil::success($resultado, 'Detalle de consulta médica obtenido');
        
    } catch (Exception $e) {
        error_log("❌ Error en obtenerDetalleConsulta: " . $e->getMessage());
        return ResponseUtil::error('Error obteniendo detalle de consulta: ' . $e->getMessage());
    }
}
// En CitasController.php - AGREGAR ESTE MÉTODO
public function actualizarEstadoCita(Request $request, Response $response, array $args): Response
{
    try {
        $id_cita = $args['id_cita'] ?? null;
        $data = $request->getParsedBody();
        
        if (empty($id_cita) || empty($data['estado'])) {
            return ResponseUtil::badRequest('ID de cita y estado son requeridos');
        }

        // Log para debug
        error_log("Actualizando estado de cita {$id_cita} a: {$data['estado']}");
        
        $resultado = DB::table('citas')
            ->where('id_cita', $id_cita)
            ->update([
                'estado' => $data['estado']
            ]);
            
        if ($resultado) {
            error_log("✅ Estado actualizado correctamente");
            return ResponseUtil::success(
                ['id_cita' => $id_cita, 'nuevo_estado' => $data['estado']], 
                "Estado de cita actualizado exitosamente"
            );
        } else {
            error_log("❌ No se pudo actualizar - cita no encontrada");
            return ResponseUtil::notFound('Cita no encontrada');
        }
        
    } catch (Exception $e) {
        error_log("❌ Error actualizando estado: " . $e->getMessage());
        return ResponseUtil::error('Error actualizando estado de cita: ' . $e->getMessage());
    }
}


/**
 * Obtener información básica de una cita (paciente, doctor, etc.) 
 * SIN requerir que exista consulta médica
 */
public function obtenerInformacionCita(Request $request, Response $response, array $args): Response
{
    try {
        $id_cita = $args['id_cita'] ?? null;
        
        if (empty($id_cita)) {
            return ResponseUtil::badRequest('ID de cita es requerido');
        }
        
        // Consulta SOLO de la cita y datos relacionados
        $cita = DB::table('citas as c')
            ->join('pacientes as pac', 'c.id_paciente', '=', 'pac.id_paciente')
            ->join('usuarios as u_pac', 'pac.id_usuario', '=', 'u_pac.id_usuario')
            ->join('doctores as d', 'c.id_doctor', '=', 'd.id_doctor')
            ->join('usuarios as u_doc', 'd.id_usuario', '=', 'u_doc.id_usuario')
            ->join('especialidades as e', 'd.id_especialidad', '=', 'e.id_especialidad')
            ->join('sucursales as s', 'c.id_sucursal', '=', 's.id_sucursal')
            ->leftJoin('tipos_cita as tc', 'c.id_tipo_cita', '=', 'tc.id_tipo_cita')
            ->leftJoin('triage as t', 'c.id_cita', '=', 't.id_cita')
            ->leftJoin('consultas_medicas as cm', 'c.id_cita', '=', 'cm.id_cita') // LEFT JOIN para que sea opcional
            ->leftJoin('historiales_clinicos as hc', 'pac.id_paciente', '=', 'hc.id_paciente')
            ->where('c.id_cita', $id_cita)
            ->select([
                // DATOS DE LA CITA
                'c.id_cita', 'c.fecha_hora as cita_fecha',
                'c.motivo as cita_motivo', 'c.estado as cita_estado',
                'c.tipo_cita', 'c.notas as cita_notas',
                'c.enlace_virtual', 'c.sala_virtual',
                
                // DATOS COMPLETOS DEL PACIENTE
                'pac.id_paciente',
                'u_pac.nombres as paciente_nombres',
                'u_pac.apellidos as paciente_apellidos',
                'u_pac.cedula as paciente_cedula',
                'u_pac.correo as paciente_email',
                'u_pac.sexo as paciente_sexo',
                'u_pac.nacionalidad as paciente_nacionalidad',
                'pac.telefono as paciente_telefono',
                'pac.fecha_nacimiento as paciente_fecha_nacimiento',
                'pac.tipo_sangre as paciente_tipo_sangre',
                'pac.alergias as paciente_alergias',
                'pac.contacto_emergencia as paciente_contacto_emergencia',
                'pac.telefono_emergencia as paciente_telefono_emergencia',
                
                // DATOS DEL DOCTOR
                'u_doc.nombres as doctor_nombres',
                'u_doc.apellidos as doctor_apellidos',
                'd.titulo_profesional',
                
                // ESPECIALIDAD Y SUCURSAL
                'e.nombre_especialidad',
                'e.descripcion as especialidad_descripcion',
                's.nombre_sucursal',
                's.direccion as sucursal_direccion',
                's.telefono as sucursal_telefono',
                's.email as sucursal_email',
                's.horario_atencion',
                
                // TIPO DE CITA
                'tc.nombre_tipo as tipo_cita_nombre',
                
                // DATOS DEL TRIAJE (si existe)
                't.id_triage',
                't.nivel_urgencia', 
                't.temperatura', 
                't.presion_arterial',
                't.frecuencia_cardiaca', 
                't.frecuencia_respiratoria',
                't.saturacion_oxigeno',
                't.peso', 
                't.talla',
                't.imc',
                't.observaciones as triaje_observaciones',
                't.fecha_hora as triaje_fecha',
                
                // CONSULTA MÉDICA (si existe)
                'cm.id_consulta',
                'cm.motivo_consulta',
                'cm.sintomatologia',
                'cm.diagnostico',
                'cm.tratamiento',
                'cm.observaciones as consulta_observaciones',
                'cm.fecha_seguimiento',
                
                // HISTORIAL CLÍNICO
                'hc.id_historial'
            ])
            ->first();
            
        if (!$cita) {
            return ResponseUtil::notFound('Cita no encontrada');
        }
        
        // Calcular edad del paciente
        $edad = null;
        if ($cita->paciente_fecha_nacimiento) {
            try {
                $fechaNac = new DateTime($cita->paciente_fecha_nacimiento);
                $hoy = new DateTime();
                $edad = $hoy->diff($fechaNac)->y;
            } catch (Exception $e) {
                error_log("Error calculando edad: " . $e->getMessage());
                $edad = null;
            }
        }
        
        // Formatear respuesta completa
        $resultado = [
            'cita' => [
                'id_cita' => $cita->id_cita,
                'fecha_hora' => $cita->cita_fecha,
                'motivo' => $cita->cita_motivo,
                'estado' => $cita->cita_estado,
                'tipo' => $cita->tipo_cita,
                'notas' => $cita->cita_notas,
                'modalidad' => [
                    'tipo' => $cita->tipo_cita,
                    'nombre' => $cita->tipo_cita_nombre,
                    'enlace_virtual' => $cita->enlace_virtual,
                    'sala_virtual' => $cita->sala_virtual
                ],
                'tiene_consulta' => !is_null($cita->id_consulta)
            ],
            
            'paciente' => [
                'id_paciente' => $cita->id_paciente,
                'nombres' => $cita->paciente_nombres,
                'apellidos' => $cita->paciente_apellidos,
                'cedula' => $cita->paciente_cedula,
                'correo' => $cita->paciente_email,
                'telefono' => $cita->paciente_telefono,
                'fecha_nacimiento' => $cita->paciente_fecha_nacimiento,
                'edad' => $edad,
                'sexo' => $cita->paciente_sexo,
                'nacionalidad' => $cita->paciente_nacionalidad,
                'tipo_sangre' => $cita->paciente_tipo_sangre,
                'alergias' => $cita->paciente_alergias,
                'contacto_emergencia' => $cita->paciente_contacto_emergencia,
                'telefono_emergencia' => $cita->paciente_telefono_emergencia,
                'nombre_completo' => trim($cita->paciente_nombres . ' ' . $cita->paciente_apellidos)
            ],
            
            'doctor' => [
                'nombres' => $cita->doctor_nombres,
                'apellidos' => $cita->doctor_apellidos,
                'titulo_profesional' => $cita->titulo_profesional,
                'especialidad' => $cita->nombre_especialidad,
                'nombre_completo' => trim($cita->doctor_nombres . ' ' . $cita->doctor_apellidos)
            ],
            
            'sucursal' => [
                'nombre_sucursal' => $cita->nombre_sucursal,
                'direccion' => $cita->sucursal_direccion,
                'telefono' => $cita->sucursal_telefono,
                'email' => $cita->sucursal_email,
                'horario_atencion' => $cita->horario_atencion
            ],
            
            'especialidad' => [
                'nombre_especialidad' => $cita->nombre_especialidad,
                'descripcion' => $cita->especialidad_descripcion
            ],
            
            'triaje' => $cita->id_triage ? [
                'id_triage' => $cita->id_triage,
                'fecha_triaje' => $cita->triaje_fecha,
                'completado' => true,
                'signos_vitales' => [
                    'temperatura' => $cita->temperatura,
                    'presion_arterial' => $cita->presion_arterial,
                    'frecuencia_cardiaca' => $cita->frecuencia_cardiaca,
                    'frecuencia_respiratoria' => $cita->frecuencia_respiratoria,
                    'saturacion_oxigeno' => $cita->saturacion_oxigeno,
                    'peso' => $cita->peso,
                    'talla' => $cita->talla,
                    'imc' => $cita->imc
                ],
                'evaluacion' => [
                    'nivel_urgencia' => $cita->nivel_urgencia,
                    'observaciones' => $cita->triaje_observaciones
                ]
            ] : [
                'completado' => false,
                'id_triage' => null
            ],
            
            'consulta_medica' => [
                'existe' => !is_null($cita->id_consulta),
                'id_consulta' => $cita->id_consulta,
                'motivo_consulta' => $cita->motivo_consulta,
                'diagnostico' => $cita->diagnostico,
                'sintomatologia' => $cita->sintomatologia,
                'tratamiento' => $cita->tratamiento,
                'observaciones' => $cita->consulta_observaciones,
                'fecha_seguimiento' => $cita->fecha_seguimiento
            ],
            
            'historial' => [
                'tiene_historial' => !is_null($cita->id_historial),
                'id_historial' => $cita->id_historial
            ]
        ];
        
        return ResponseUtil::success($resultado, 'Información de cita obtenida exitosamente');
        
    } catch (Exception $e) {
        error_log("❌ Error en obtenerInformacionCita: " . $e->getMessage());
        return ResponseUtil::error('Error obteniendo información de cita: ' . $e->getMessage());
    }
}
    
}
?>