<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\ResponseUtil;
use App\Validators\CedulaValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class HistorialController
{
    public function buscarPacientePorCedula(Request $request, Response $response, array $args): Response
    {
        $cedula = $args['cedula'];
        
        // Validar cédula
        $erroresCedula = CedulaValidator::validate($cedula);
        if (!empty($erroresCedula)) {
            return ResponseUtil::badRequest('La cédula proporcionada no es válida', $erroresCedula);
        }
        
        try {
            $paciente = DB::table('pacientes')
                ->join('usuarios', 'pacientes.id_usuario', '=', 'usuarios.id_usuario')
                ->leftJoin('historiales_clinicos', 'pacientes.id_paciente', '=', 'historiales_clinicos.id_paciente')
                ->select(
                    'pacientes.*',
                    'usuarios.cedula',
                    'usuarios.nombres',
                    'usuarios.apellidos',
                    'usuarios.correo',
                    'usuarios.sexo',
                    'usuarios.nacionalidad',
                    'usuarios.username',
                    'historiales_clinicos.id_historial',
                    'historiales_clinicos.fecha_creacion as historial_creado',
                    'historiales_clinicos.ultima_actualizacion as historial_actualizado'
                )
                ->where('usuarios.cedula', $cedula)
                ->first();
            
            if (!$paciente) {
                return ResponseUtil::notFound('No se encontró ningún paciente con la cédula proporcionada: ' . $cedula);
            }
            
            // Contar citas del paciente
            $totalCitas = DB::table('citas')
                ->where('id_paciente', $paciente->id_paciente)
                ->count();
            
            $pacienteData = [
                'paciente' => [
                    'id_paciente' => $paciente->id_paciente,
                    'cedula' => $paciente->cedula,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'nombre_completo' => $paciente->nombres . ' ' . $paciente->apellidos,
                    'correo' => $paciente->correo,
                    'sexo' => $paciente->sexo,
                    'nacionalidad' => $paciente->nacionalidad,
                    'username' => $paciente->username,
                    'fecha_nacimiento' => $paciente->fecha_nacimiento,
                    'edad' => $this->calcularEdad($paciente->fecha_nacimiento),
                    'tipo_sangre' => $paciente->tipo_sangre,
                    'alergias' => $paciente->alergias,
                    'antecedentes_medicos' => $paciente->antecedentes_medicos,
                    'contacto_emergencia' => $paciente->contacto_emergencia,
                    'telefono_emergencia' => $paciente->telefono_emergencia,
                    'numero_seguro' => $paciente->numero_seguro,
                    'telefono' => $paciente->telefono
                ],
                'historial_clinico' => [
                    'id_historial' => $paciente->id_historial,
                    'fecha_creacion' => $paciente->historial_creado,
                    'ultima_actualizacion' => $paciente->historial_actualizado,
                    'existe_historial' => !is_null($paciente->id_historial)
                ],
                'estadisticas_rapidas' => [
                    'total_citas' => $totalCitas,
                    'tiene_citas' => $totalCitas > 0
                ]
            ];
            
            return ResponseUtil::success(
                $pacienteData, 
                'Paciente encontrado exitosamente: ' . $paciente->nombres . ' ' . $paciente->apellidos
            );
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error interno del servidor al buscar el paciente: ' . $e->getMessage());
        }
    }

    public function getHistorialByCedula(Request $request, Response $response, array $args): Response
    {
        $cedula = $args['cedula'];
        
        // Validar cédula
        $erroresCedula = CedulaValidator::validate($cedula);
        if (!empty($erroresCedula)) {
            return ResponseUtil::badRequest('La cédula proporcionada no es válida', $erroresCedula);
        }
        
        try {
            // Obtener paciente
            $paciente = DB::table('pacientes')
                ->join('usuarios', 'pacientes.id_usuario', '=', 'usuarios.id_usuario')
                ->leftJoin('historiales_clinicos', 'pacientes.id_paciente', '=', 'historiales_clinicos.id_paciente')
                ->select(
                    'pacientes.*',
                    'usuarios.cedula',
                    'usuarios.nombres',
                    'usuarios.apellidos',
                    'usuarios.correo',
                    'usuarios.sexo',
                    'usuarios.nacionalidad',
                    'usuarios.username',
                    'historiales_clinicos.id_historial',
                    'historiales_clinicos.fecha_creacion as historial_creado',
                    'historiales_clinicos.ultima_actualizacion as historial_actualizado'
                )
                ->where('usuarios.cedula', $cedula)
                ->first();
            
            if (!$paciente) {
                return ResponseUtil::notFound('No se encontró ningún paciente con la cédula proporcionada: ' . $cedula);
            }
            
            // ✅ OBTENER CITAS CON ESTRUCTURA REAL DE LA BD
            $citas = DB::table('citas')
                ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
                ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
                ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
                ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal')
                ->leftJoin('tipos_cita', 'citas.id_tipo_cita', '=', 'tipos_cita.id_tipo_cita')
                ->select(
                    // ✅ CAMPOS REALES DE CITAS
                    'citas.id_cita',
                    'citas.fecha_hora',
                    'citas.motivo',
                    'citas.tipo_cita as modalidad_cita', // presencial/virtual
                    'citas.estado',
                    'citas.notas',
                    'citas.fecha_creacion as cita_creada',
                    
                    // ✅ DATOS DEL DOCTOR
                    'doctores.id_doctor',
                    'u_doctor.nombres as doctor_nombres',
                    'u_doctor.apellidos as doctor_apellidos',
                    'doctores.titulo_profesional',
                    
                    // ✅ DATOS DE ESPECIALIDAD
                    'especialidades.id_especialidad',
                    'especialidades.nombre_especialidad',
                    'especialidades.descripcion as especialidad_descripcion',
                    
                    // ✅ DATOS DE SUCURSAL
                    'sucursales.id_sucursal',
                    'sucursales.nombre_sucursal',
                    'sucursales.direccion as sucursal_direccion',
                    'sucursales.telefono as sucursal_telefono',
                    
                    // ✅ TIPO DE CITA (SI EXISTE)
                    'tipos_cita.nombre_tipo as tipo_cita_nombre'
                )
                ->where('citas.id_paciente', $paciente->id_paciente)
                ->orderBy('citas.fecha_hora', 'desc')
                ->get();
            
            // Calcular estadísticas
            $totalCitas = count($citas);
            $citasCompletadas = collect($citas)->where('estado', 'Completada')->count();
            $citasPendientes = collect($citas)->where('estado', 'Pendiente')->count();
            $citasCanceladas = collect($citas)->where('estado', 'Cancelada')->count();
            
            $historialCompleto = [
                'paciente' => [
                    'id_paciente' => $paciente->id_paciente,
                    'cedula' => $paciente->cedula,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'nombre_completo' => $paciente->nombres . ' ' . $paciente->apellidos,
                    'correo' => $paciente->correo,
                    'sexo' => $paciente->sexo,
                    'nacionalidad' => $paciente->nacionalidad,
                    'username' => $paciente->username,
                    'fecha_nacimiento' => $paciente->fecha_nacimiento,
                    'edad' => $this->calcularEdad($paciente->fecha_nacimiento),
                    'tipo_sangre' => $paciente->tipo_sangre,
                    'alergias' => $paciente->alergias,
                    'antecedentes_medicos' => $paciente->antecedentes_medicos,
                    'contacto_emergencia' => $paciente->contacto_emergencia,
                    'telefono_emergencia' => $paciente->telefono_emergencia,
                    'numero_seguro' => $paciente->numero_seguro,
                    'telefono' => $paciente->telefono
                ],
                'historial_clinico' => [
                    'id_historial' => $paciente->id_historial,
                    'fecha_creacion' => $paciente->historial_creado,
                    'ultima_actualizacion' => $paciente->historial_actualizado,
                    'existe_historial' => !is_null($paciente->id_historial)
                ],
                'citas_medicas' => $citas,
                'estadisticas' => [
                    'total_citas' => $totalCitas,
                    'citas_completadas' => $citasCompletadas,
                    'citas_pendientes' => $citasPendientes,
                    'citas_canceladas' => $citasCanceladas,
                    'primera_cita' => $totalCitas > 0 ? collect($citas)->last()->fecha_hora : null,
                    'ultima_cita' => $totalCitas > 0 ? collect($citas)->first()->fecha_hora : null
                ]
            ];
            
            return ResponseUtil::success(
                $historialCompleto, 
                'Historial clínico encontrado exitosamente para el paciente: ' . $paciente->nombres . ' ' . $paciente->apellidos
            );
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error interno del servidor al buscar el historial clínico: ' . $e->getMessage());
        }
    }

    public function getHistorialCompleto(Request $request, Response $response, array $args): Response
    {
        $cedula = $args['cedula'];
        
        // Obtener filtros del query string
        $filtros = [
            'fecha_desde' => $request->getQueryParams()['fecha_desde'] ?? null,
            'fecha_hasta' => $request->getQueryParams()['fecha_hasta'] ?? null,
            'id_especialidad' => $request->getQueryParams()['id_especialidad'] ?? null,
            'id_doctor' => $request->getQueryParams()['id_doctor'] ?? null,
            'estado' => $request->getQueryParams()['estado'] ?? null,
            'id_sucursal' => $request->getQueryParams()['id_sucursal'] ?? null,
        ];
        
        // Validar cédula
        $erroresCedula = CedulaValidator::validate($cedula);
        if (!empty($erroresCedula)) {
            return ResponseUtil::badRequest('La cédula proporcionada no es válida', $erroresCedula);
        }
        
        try {
            // Obtener paciente
            $paciente = DB::table('pacientes')
                ->join('usuarios', 'pacientes.id_usuario', '=', 'usuarios.id_usuario')
                ->where('usuarios.cedula', $cedula)
                ->first();
            
            if (!$paciente) {
                return ResponseUtil::notFound('Paciente no encontrado');
            }
            
            // ✅ QUERY MEJORADA CON TODOS LOS DETALLES Y CAMPOS REALES
            $query = DB::table('citas')
                ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
                ->join('usuarios as u_doctor', 'doctores.id_usuario', '=', 'u_doctor.id_usuario')
                ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
                ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal')
                ->leftJoin('tipos_cita', 'citas.id_tipo_cita', '=', 'tipos_cita.id_tipo_cita')
                ->leftJoin('consultas_medicas', 'citas.id_cita', '=', 'consultas_medicas.id_cita')
                ->leftJoin('triage', 'citas.id_cita', '=', 'triage.id_cita') // ✅ TABLA TRIAGE (SIN S)
                ->select(
                    // ✅ DATOS DE LA CITA (CAMPOS REALES)
                    'citas.id_cita',
                    'citas.fecha_hora',
                    'citas.motivo',
                    'citas.tipo_cita as modalidad_cita', // presencial/virtual
                    'citas.estado',
                    'citas.notas as cita_notas',
                    'citas.fecha_creacion as cita_creada',
                    'citas.enlace_virtual',
                    
                    // ✅ DATOS DEL DOCTOR
                    'doctores.id_doctor',
                    'u_doctor.nombres as doctor_nombres',
                    'u_doctor.apellidos as doctor_apellidos',
                    'doctores.titulo_profesional',
                    
                    // ✅ DATOS DE ESPECIALIDAD
                    'especialidades.id_especialidad',
                    'especialidades.nombre_especialidad',
                    'especialidades.descripcion as especialidad_descripcion',
                    
                    // ✅ DATOS DE SUCURSAL (CAMPOS REALES)
                    'sucursales.id_sucursal',
                    'sucursales.nombre_sucursal',
                    'sucursales.direccion as sucursal_direccion',
                    'sucursales.telefono as sucursal_telefono',
                    'sucursales.email as sucursal_email',
                    'sucursales.horario_atencion',
                    
                    // ✅ TIPO DE CITA
                    'tipos_cita.nombre_tipo as tipo_cita_nombre',
                    
                    // ✅ DATOS DE CONSULTA MÉDICA (CAMPOS REALES)
                    'consultas_medicas.id_consulta',
                    'consultas_medicas.motivo_consulta',
                    'consultas_medicas.sintomatologia',
                    'consultas_medicas.diagnostico',
                    'consultas_medicas.tratamiento',
                    'consultas_medicas.observaciones as consulta_observaciones',
                    'consultas_medicas.fecha_seguimiento',
                    
                    // ✅ DATOS DE TRIAGE (CAMPOS REALES)
                    'triage.id_triage',
                    'triage.nivel_urgencia',
                    'triage.temperatura',
                    'triage.presion_arterial',
                    'triage.frecuencia_cardiaca',
                    'triage.peso',
                    'triage.talla as altura',
                    'triage.imc',
                    'triage.observaciones as triage_observaciones'
                )
                ->where('citas.id_paciente', $paciente->id_paciente);
            
            // ✅ APLICAR FILTROS
            if (!empty($filtros['fecha_desde'])) {
                $query->where('citas.fecha_hora', '>=', $filtros['fecha_desde'] . ' 00:00:00');
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $query->where('citas.fecha_hora', '<=', $filtros['fecha_hasta'] . ' 23:59:59');
            }
            
            if (!empty($filtros['id_especialidad'])) {
                $query->where('especialidades.id_especialidad', $filtros['id_especialidad']);
            }
            
            if (!empty($filtros['id_doctor'])) {
                $query->where('doctores.id_doctor', $filtros['id_doctor']);
            }
            
            if (!empty($filtros['estado'])) {
                $query->where('citas.estado', $filtros['estado']);
            }
            
            if (!empty($filtros['id_sucursal'])) {
                $query->where('sucursales.id_sucursal', $filtros['id_sucursal']);
            }
            
            $citas = $query->orderBy('citas.fecha_hora', 'desc')->get();
            
            // ✅ PROCESAR CITAS PARA ESTRUCTURAR MEJOR
            $citasProcesadas = collect($citas)->map(function($cita) {
                return [
                    // Datos básicos de la cita
                    'id_cita' => $cita->id_cita,
                    'fecha_hora' => $cita->fecha_hora,
                    'motivo' => $cita->motivo,
                    'estado' => $cita->estado,
                    'modalidad_cita' => $cita->modalidad_cita, // presencial/virtual
                    'notas' => $cita->cita_notas,
                    'fecha_creacion' => $cita->cita_creada,
                    'enlace_virtual' => $cita->enlace_virtual,
                    'tipo_cita' => $cita->tipo_cita_nombre,
                    
                    // Doctor y especialidad
                    'doctor' => [
                        'id_doctor' => $cita->id_doctor,
                        'nombres' => $cita->doctor_nombres,
                        'apellidos' => $cita->doctor_apellidos,
                        'nombre_completo' => $cita->doctor_nombres . ' ' . $cita->doctor_apellidos,
                        'titulo_profesional' => $cita->titulo_profesional
                    ],
                    'especialidad' => [
                        'id_especialidad' => $cita->id_especialidad,
                        'nombre' => $cita->nombre_especialidad,
                        'descripcion' => $cita->especialidad_descripcion
                    ],
                    'sucursal' => [
                        'id_sucursal' => $cita->id_sucursal,
                        'nombre' => $cita->nombre_sucursal,
                        'direccion' => $cita->sucursal_direccion,
                        'telefono' => $cita->sucursal_telefono,
                        'email' => $cita->sucursal_email,
                        'horario_atencion' => $cita->horario_atencion
                    ],
                    
                    // Consulta médica (si existe)
                    'consulta_medica' => $cita->id_consulta ? [
                        'id_consulta' => $cita->id_consulta,
                        'motivo_consulta' => $cita->motivo_consulta,
                        'sintomatologia' => $cita->sintomatologia,
                        'diagnostico' => $cita->diagnostico,
                        'tratamiento' => $cita->tratamiento,
                        'observaciones' => $cita->consulta_observaciones,
                        'fecha_seguimiento' => $cita->fecha_seguimiento
                    ] : null,
                    
                    // Triage (si existe)
                    'triaje' => $cita->id_triage ? [
                        'id_triage' => $cita->id_triage,
                        'nivel_urgencia' => $cita->nivel_urgencia,
                        'signos_vitales' => [
                            'peso' => $cita->peso,
                            'altura' => $cita->altura,
                            'imc' => $cita->imc,
                            'presion_arterial' => $cita->presion_arterial,
                            'temperatura' => $cita->temperatura,
                            'frecuencia_cardiaca' => $cita->frecuencia_cardiaca
                        ],
                        'observaciones' => $cita->triage_observaciones
                    ] : null,
                    
                    // Estados de la cita
                    'tiene_consulta' => !is_null($cita->id_consulta),
                    'tiene_triaje' => !is_null($cita->id_triage),
                    'esta_completada' => $cita->estado === 'Completada'
                ];
            });
            
            // Calcular estadísticas
            $totalCitas = $citasProcesadas->count();
            $citasCompletadas = $citasProcesadas->where('estado', 'Completada')->count();
            $citasPendientes = $citasProcesadas->where('estado', 'Pendiente')->count();
            $citasCanceladas = $citasProcesadas->where('estado', 'Cancelada')->count();
            
            $resultado = [
                'citas' => $citasProcesadas->values(),
                'filtros_aplicados' => array_filter($filtros),
                'estadisticas' => [
                    'total_citas' => $totalCitas,
                    'citas_completadas' => $citasCompletadas,
                    'citas_pendientes' => $citasPendientes,
                    'citas_canceladas' => $citasCanceladas,
                    'primera_cita' => $totalCitas > 0 ? $citasProcesadas->last()['fecha_hora'] : null,
                    'ultima_cita' => $totalCitas > 0 ? $citasProcesadas->first()['fecha_hora'] : null
                ]
            ];
            
            return ResponseUtil::success(
                $resultado, 
                'Historial clínico obtenido exitosamente'
            );
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error obteniendo historial clínico: ' . $e->getMessage());
        }
    }

    public function getEspecialidades(Request $request, Response $response): Response
    {
        try {
            $especialidades = DB::table('especialidades')
                ->select('id_especialidad', 'nombre_especialidad', 'descripcion')
                ->orderBy('nombre_especialidad')
                ->get();
            
            return ResponseUtil::success($especialidades, 'Especialidades obtenidas exitosamente');
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error obteniendo especialidades: ' . $e->getMessage());
        }
    }
    
    public function getDoctoresByEspecialidad(Request $request, Response $response, array $args): Response
    {
        $id_especialidad = $args['id_especialidad'];
        
        try {
            $doctores = DB::table('doctores')
                ->join('usuarios', 'doctores.id_usuario', '=', 'usuarios.id_usuario')
                ->select(
                    'doctores.id_doctor',
                    'usuarios.nombres',
                    'usuarios.apellidos',
                    'doctores.titulo_profesional'
                )
                ->where('doctores.id_especialidad', $id_especialidad)
                ->where('usuarios.id_estado', 1) // Solo usuarios activos
                ->orderBy('usuarios.nombres')
                ->get();
            
            return ResponseUtil::success($doctores, 'Doctores obtenidos exitosamente');
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error obteniendo doctores: ' . $e->getMessage());
        }
    }

    public function getSucursales(Request $request, Response $response): Response
    {
        try {
            $sucursales = DB::table('sucursales')
                ->select('id_sucursal', 'nombre_sucursal', 'direccion', 'telefono', 'email')
                ->where('estado', 1) // Solo sucursales activas
                ->orderBy('nombre_sucursal')
                ->get();
            
            return ResponseUtil::success($sucursales, 'Sucursales obtenidas exitosamente');
            
        } catch (Exception $e) {
            return ResponseUtil::error('Error obteniendo sucursales: ' . $e->getMessage());
        }
    }

    public function getDetalleCita(Request $request, Response $response, array $args): Response
    {
        $id_cita = $args['id_cita'];
        
        try {
            $detalleCita = DB::table('citas')
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
                    // Datos de la cita
                    'citas.*',
                    
                    // Datos del paciente
                    'u_paciente.nombres as paciente_nombres',
                    'u_paciente.apellidos as paciente_apellidos',
                    'u_paciente.cedula as paciente_cedula',
                    'pacientes.fecha_nacimiento',
                    'pacientes.tipo_sangre',
                    'pacientes.alergias',
                    'pacientes.telefono as paciente_telefono',
                    'pacientes.contacto_emergencia',
                    'pacientes.telefono_emergencia',
                    
                    // Datos del doctor
                    'u_doctor.nombres as doctor_nombres',
                    'u_doctor.apellidos as doctor_apellidos',
                    'doctores.titulo_profesional',
                    
                    // Especialidad y sucursal
                    'especialidades.nombre_especialidad',
                    'sucursales.nombre_sucursal',
                    'sucursales.direccion as sucursal_direccion',
                    'sucursales.telefono as sucursal_telefono',
                    'tipos_cita.nombre_tipo as tipo_cita',
                    
                    // Consulta médica completa
                    'consultas_medicas.id_consulta',
                    'consultas_medicas.motivo_consulta',
                    'consultas_medicas.sintomatologia',
                    'consultas_medicas.diagnostico',
                    'consultas_medicas.tratamiento',
                    'consultas_medicas.observaciones as consulta_observaciones',
                    'consultas_medicas.fecha_seguimiento',
                    
                    // Triage completo
                    'triage.id_triage',
                    'triage.nivel_urgencia',
                    'triage.temperatura',
                    'triage.presion_arterial',
                    'triage.frecuencia_cardiaca',
                    'triage.peso',
                    'triage.talla as altura',
                    'triage.imc',
                    'triage.observaciones as triage_observaciones'
                )
                ->where('citas.id_cita', $id_cita)
                ->first();
            
            if (!$detalleCita) {
                return ResponseUtil::notFound('Cita no encontrada');
            }
            
            // Estructurar respuesta completa
            $detalleCompleto = [
                'cita' => [
                    'id_cita' => $detalleCita->id_cita,
                    'fecha_hora' => $detalleCita->fecha_hora,
                    'motivo' => $detalleCita->motivo,
                    'estado' => $detalleCita->estado,
                    'tipo_cita' => $detalleCita->tipo_cita,
                    'modalidad_cita' => $detalleCita->tipo_cita, // presencial/virtual
                    'notas' => $detalleCita->notas,
                    'fecha_creacion' => $detalleCita->fecha_creacion,
                    'enlace_virtual' => $detalleCita->enlace_virtual
                ],
                
                'paciente' => [
                    'nombres' => $detalleCita->paciente_nombres,
                    'apellidos' => $detalleCita->paciente_apellidos,
                    'cedula' => $detalleCita->paciente_cedula,
                    'fecha_nacimiento' => $detalleCita->fecha_nacimiento,
                    'edad' => $this->calcularEdad($detalleCita->fecha_nacimiento),
                    'tipo_sangre' => $detalleCita->tipo_sangre,
                    'alergias' => $detalleCita->alergias,
                    'telefono' => $detalleCita->paciente_telefono,
                    'contacto_emergencia' => $detalleCita->contacto_emergencia,
                    'telefono_emergencia' => $detalleCita->telefono_emergencia
                ],
                
                'doctor' => [
                   'nombres' => $detalleCita->doctor_nombres,
                   'apellidos' => $detalleCita->doctor_apellidos,
                   'titulo_profesional' => $detalleCita->titulo_profesional,
                   'especialidad' => $detalleCita->nombre_especialidad
               ],
               
               'sucursal' => [
                   'nombre' => $detalleCita->nombre_sucursal,
                   'direccion' => $detalleCita->sucursal_direccion,
                   'telefono' => $detalleCita->sucursal_telefono
               ],
               
               'triaje' => $detalleCita->id_triage ? [
                   'id_triage' => $detalleCita->id_triage,
                   'nivel_urgencia' => $detalleCita->nivel_urgencia,
                   'signos_vitales' => [
                       'peso' => $detalleCita->peso,
                       'altura' => $detalleCita->altura,
                       'imc' => $detalleCita->imc,
                       'presion_arterial' => $detalleCita->presion_arterial,
                       'temperatura' => $detalleCita->temperatura,
                       'frecuencia_cardiaca' => $detalleCita->frecuencia_cardiaca
                   ],
                   'observaciones' => $detalleCita->triage_observaciones
               ] : null,
               
               'consulta_medica' => $detalleCita->id_consulta ? [
                   'id_consulta' => $detalleCita->id_consulta,
                   'motivo_consulta' => $detalleCita->motivo_consulta,
                   'sintomatologia' => $detalleCita->sintomatologia,
                   'diagnostico' => $detalleCita->diagnostico,
                   'tratamiento' => $detalleCita->tratamiento,
                   'observaciones' => $detalleCita->consulta_observaciones,
                   'fecha_seguimiento' => $detalleCita->fecha_seguimiento
               ] : null,
               
               'estados' => [
                   'tiene_triaje' => !is_null($detalleCita->id_triage),
                   'tiene_consulta' => !is_null($detalleCita->id_consulta),
                   'esta_completada' => $detalleCita->estado === 'Completada'
               ]
           ];
           
           return ResponseUtil::success($detalleCompleto, 'Detalle de cita obtenido exitosamente');
           
       } catch (Exception $e) {
           return ResponseUtil::error('Error obteniendo detalle de cita: ' . $e->getMessage());
       }
   }
   

   /**
 * 🔍 Consulta general de citas médicas con filtros múltiples
 * Diferente al historial: no requiere cédula específica, busca en TODAS las citas
 */
public function getConsultaGeneralCitas(Request $request, Response $response): Response
{
    try {
        // Obtener filtros del query string
        $filtros = [
            'fecha_desde' => $request->getQueryParams()['fecha_desde'] ?? null,
            'fecha_hasta' => $request->getQueryParams()['fecha_hasta'] ?? null,
            'id_especialidad' => $request->getQueryParams()['id_especialidad'] ?? null,
            'id_doctor' => $request->getQueryParams()['id_doctor'] ?? null,
            'estado' => $request->getQueryParams()['estado'] ?? null,
            'id_sucursal' => $request->getQueryParams()['id_sucursal'] ?? null,
            'cedula_paciente' => $request->getQueryParams()['cedula_paciente'] ?? null,
            'nombre_paciente' => $request->getQueryParams()['nombre_paciente'] ?? null,
            'limit' => (int)($request->getQueryParams()['limit'] ?? 50),
            'offset' => (int)($request->getQueryParams()['offset'] ?? 0)
        ];
        
        // Log para debug
        error_log('🔍 Filtros recibidos en consulta general: ' . json_encode($filtros));
        
        // ✅ QUERY PRINCIPAL CON TODOS LOS FILTROS
        $query = DB::table('citas')
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
                // ✅ DATOS DE LA CITA
                'citas.id_cita',
                'citas.fecha_hora',
                'citas.motivo',
                'citas.tipo_cita as modalidad_cita',
                'citas.estado',
                'citas.notas as cita_notas',
                'citas.fecha_creacion as cita_creada',
                'citas.enlace_virtual',
                
                // ✅ DATOS DEL PACIENTE
                'pacientes.id_paciente',
                'u_paciente.nombres as paciente_nombres',
                'u_paciente.apellidos as paciente_apellidos',
                'u_paciente.cedula as paciente_cedula',
                'pacientes.fecha_nacimiento',
                'pacientes.telefono as paciente_telefono',
                'pacientes.tipo_sangre',
                
                // ✅ DATOS DEL DOCTOR
                'doctores.id_doctor',
                'u_doctor.nombres as doctor_nombres',
                'u_doctor.apellidos as doctor_apellidos',
                'doctores.titulo_profesional',
                
                // ✅ DATOS DE ESPECIALIDAD
                'especialidades.id_especialidad',
                'especialidades.nombre_especialidad',
                
                // ✅ DATOS DE SUCURSAL
                'sucursales.id_sucursal',
                'sucursales.nombre_sucursal',
                'sucursales.direccion as sucursal_direccion',
                
                // ✅ TIPO DE CITA
                'tipos_cita.nombre_tipo as tipo_cita_nombre',
                
                // ✅ ESTADOS DE CONSULTA Y TRIAJE
                'consultas_medicas.id_consulta',
                'triage.id_triage'
            );
        
        // ✅ APLICAR FILTROS
        if (!empty($filtros['fecha_desde'])) {
            $query->where('citas.fecha_hora', '>=', $filtros['fecha_desde'] . ' 00:00:00');
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('citas.fecha_hora', '<=', $filtros['fecha_hasta'] . ' 23:59:59');
        }
        
        if (!empty($filtros['id_especialidad'])) {
            $query->where('especialidades.id_especialidad', $filtros['id_especialidad']);
        }
        
        if (!empty($filtros['id_doctor'])) {
            $query->where('doctores.id_doctor', $filtros['id_doctor']);
        }
        
        if (!empty($filtros['estado'])) {
            $query->where('citas.estado', $filtros['estado']);
        }
        
        if (!empty($filtros['id_sucursal'])) {
            $query->where('sucursales.id_sucursal', $filtros['id_sucursal']);
        }
        
        // ✅ FILTRO POR PACIENTE (CÉDULA)
        if (!empty($filtros['cedula_paciente'])) {
            $query->where('u_paciente.cedula', 'LIKE', '%' . $filtros['cedula_paciente'] . '%');
        }
        
        // ✅ FILTRO POR NOMBRE DE PACIENTE
        if (!empty($filtros['nombre_paciente'])) {
            $query->where(function($q) use ($filtros) {
                $nombre = '%' . $filtros['nombre_paciente'] . '%';
                $q->where('u_paciente.nombres', 'LIKE', $nombre)
                  ->orWhere('u_paciente.apellidos', 'LIKE', $nombre)
                  ->orWhereRaw("CONCAT(u_paciente.nombres, ' ', u_paciente.apellidos) LIKE ?", [$nombre]);
            });
        }
        
        // ✅ OBTENER TOTAL SIN LÍMITE (para estadísticas)
        $totalQuery = clone $query;
        $totalCitas = $totalQuery->count();
        
        // ✅ APLICAR PAGINACIÓN
        $citas = $query->orderBy('citas.fecha_hora', 'desc')
                      ->offset($filtros['offset'])
                      ->limit($filtros['limit'])
                      ->get();
        
        // ✅ PROCESAR CITAS PARA ESTRUCTURAR MEJOR
        $citasProcesadas = collect($citas)->map(function($cita) {
            return [
                // Datos básicos de la cita
                'id_cita' => $cita->id_cita,
                'fecha_hora' => $cita->fecha_hora,
                'motivo' => $cita->motivo,
                'estado' => $cita->estado,
                'modalidad_cita' => $cita->modalidad_cita,
                'notas' => $cita->cita_notas,
                'fecha_creacion' => $cita->cita_creada,
                'enlace_virtual' => $cita->enlace_virtual,
                'tipo_cita' => $cita->tipo_cita_nombre,
                
                // Paciente
                'paciente' => [
                    'id_paciente' => $cita->id_paciente,
                    'nombres' => $cita->paciente_nombres,
                    'apellidos' => $cita->paciente_apellidos,
                    'nombre_completo' => $cita->paciente_nombres . ' ' . $cita->paciente_apellidos,
                    'cedula' => $cita->paciente_cedula,
                    'fecha_nacimiento' => $cita->fecha_nacimiento,
                    'edad' => $this->calcularEdad($cita->fecha_nacimiento),
                    'telefono' => $cita->paciente_telefono,
                    'tipo_sangre' => $cita->tipo_sangre
                ],
                
                // Doctor y especialidad
                'doctor' => [
                    'id_doctor' => $cita->id_doctor,
                    'nombres' => $cita->doctor_nombres,
                    'apellidos' => $cita->doctor_apellidos,
                    'nombre_completo' => $cita->doctor_nombres . ' ' . $cita->doctor_apellidos,
                    'titulo_profesional' => $cita->titulo_profesional
                ],
                'especialidad' => [
                    'id_especialidad' => $cita->id_especialidad,
                    'nombre' => $cita->nombre_especialidad
                ],
                'sucursal' => [
                    'id_sucursal' => $cita->id_sucursal,
                    'nombre' => $cita->nombre_sucursal,
                    'direccion' => $cita->sucursal_direccion
                ],
                
                // Estados de la cita
                'tiene_consulta' => !is_null($cita->id_consulta),
                'tiene_triaje' => !is_null($cita->id_triage),
                'esta_completada' => $cita->estado === 'Completada'
            ];
        });
        
        // ✅ CALCULAR ESTADÍSTICAS
        $estadisticas = $this->calcularEstadisticasCitas($filtros);
        
        $resultado = [
            'citas' => $citasProcesadas->values(),
            'filtros_aplicados' => array_filter($filtros),
            'estadisticas' => $estadisticas,
            'paginacion' => [
                'total' => $totalCitas,
                'limit' => $filtros['limit'],
                'offset' => $filtros['offset'],
                'tiene_siguiente' => ($filtros['offset'] + $filtros['limit']) < $totalCitas,
                'tiene_anterior' => $filtros['offset'] > 0,
                'pagina_actual' => floor($filtros['offset'] / $filtros['limit']) + 1,
                'total_paginas' => ceil($totalCitas / $filtros['limit'])
            ]
        ];
        
        return ResponseUtil::success(
            $resultado, 
            'Consulta general de citas obtenida exitosamente. Encontradas: ' . $totalCitas . ' citas'
        );
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error en consulta general de citas: ' . $e->getMessage());
    }
}

/**
 * 📊 Calcular estadísticas de las citas con los filtros aplicados
 */
private function calcularEstadisticasCitas($filtros): array
{
    try {
        // Query base para estadísticas (sin límite)
        $query = DB::table('citas')
            ->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
            ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id_doctor')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->join('sucursales', 'citas.id_sucursal', '=', 'sucursales.id_sucursal');
        
        // Aplicar los mismos filtros
        if (!empty($filtros['fecha_desde'])) {
            $query->where('citas.fecha_hora', '>=', $filtros['fecha_desde'] . ' 00:00:00');
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('citas.fecha_hora', '<=', $filtros['fecha_hasta'] . ' 23:59:59');
        }
        
        if (!empty($filtros['id_especialidad'])) {
            $query->where('especialidades.id_especialidad', $filtros['id_especialidad']);
        }
        
        if (!empty($filtros['id_doctor'])) {
            $query->where('doctores.id_doctor', $filtros['id_doctor']);
        }
        
        if (!empty($filtros['id_sucursal'])) {
            $query->where('sucursales.id_sucursal', $filtros['id_sucursal']);
        }
        
        if (!empty($filtros['cedula_paciente'])) {
            $query->where('u_paciente.cedula', 'LIKE', '%' . $filtros['cedula_paciente'] . '%');
        }
        
        if (!empty($filtros['nombre_paciente'])) {
            $query->where(function($q) use ($filtros) {
                $nombre = '%' . $filtros['nombre_paciente'] . '%';
                $q->where('u_paciente.nombres', 'LIKE', $nombre)
                  ->orWhere('u_paciente.apellidos', 'LIKE', $nombre)
                  ->orWhereRaw("CONCAT(u_paciente.nombres, ' ', u_paciente.apellidos) LIKE ?", [$nombre]);
            });
        }
        
        // Obtener estadísticas por estado
        $estadisticasPorEstado = $query->select('citas.estado', DB::raw('COUNT(*) as cantidad'))
                                      ->groupBy('citas.estado')
                                      ->get()
                                      ->keyBy('estado');
        
        // Obtener total
        $total = $query->count();
        
        return [
            'total_citas' => $total,
            'pendientes' => $estadisticasPorEstado->get('Pendiente')->cantidad ?? 0,
            'confirmadas' => $estadisticasPorEstado->get('Confirmada')->cantidad ?? 0,
            'completadas' => $estadisticasPorEstado->get('Completada')->cantidad ?? 0,
            'canceladas' => $estadisticasPorEstado->get('Cancelada')->cantidad ?? 0,
            'otros_estados' => $total - (
                ($estadisticasPorEstado->get('Pendiente')->cantidad ?? 0) +
                ($estadisticasPorEstado->get('Confirmada')->cantidad ?? 0) +
                ($estadisticasPorEstado->get('Completada')->cantidad ?? 0) +
                ($estadisticasPorEstado->get('Cancelada')->cantidad ?? 0)
            )
        ];
        
    } catch (Exception $e) {
        error_log('Error calculando estadísticas: ' . $e->getMessage());
        return [
            'total_citas' => 0,
            'pendientes' => 0,
            'confirmadas' => 0,
            'completadas' => 0,
            'canceladas' => 0,
            'otros_estados' => 0
        ];
    }
}

/**
 * 👨‍⚕️ Obtener citas del médico especificado con filtros
 */
public function getMisCitasMedico(Request $request, Response $response): Response
{
    try {
        // ✅ OBTENER ID DOCTOR DESDE LOS PARÁMETROS (enviado desde frontend)
        $filtros = [
            'id_doctor' => $request->getQueryParams()['id_doctor'] ?? null,
            'fecha_desde' => $request->getQueryParams()['fecha_desde'] ?? null,
            'fecha_hasta' => $request->getQueryParams()['fecha_hasta'] ?? null,
            'estado' => $request->getQueryParams()['estado'] ?? null,
            'cedula_paciente' => $request->getQueryParams()['cedula_paciente'] ?? null,
            'nombre_paciente' => $request->getQueryParams()['nombre_paciente'] ?? null,
            'limit' => (int)($request->getQueryParams()['limit'] ?? 20),
            'offset' => (int)($request->getQueryParams()['offset'] ?? 0)
        ];
        
        // Validar que se envíe el ID del doctor
        if (empty($filtros['id_doctor'])) {
            return ResponseUtil::badRequest('ID del doctor es requerido');
        }
        
        error_log('👨‍⚕️ Mis citas médico ID: ' . $filtros['id_doctor'] . ' - Filtros: ' . json_encode($filtros));
        
        // ✅ QUERY ESPECÍFICO PARA EL MÉDICO
        $query = DB::table('citas')
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
                // Datos de la cita
                'citas.id_cita',
                'citas.fecha_hora',
                'citas.motivo',
                'citas.tipo_cita as modalidad_cita',
                'citas.estado',
                'citas.notas as cita_notas',
                'citas.fecha_creacion as cita_creada',
                'citas.enlace_virtual',
                
                // Datos del paciente
                'pacientes.id_paciente',
                'u_paciente.nombres as paciente_nombres',
                'u_paciente.apellidos as paciente_apellidos',
                'u_paciente.cedula as paciente_cedula',
                'pacientes.fecha_nacimiento',
                'pacientes.telefono as paciente_telefono',
                'pacientes.tipo_sangre',
                'pacientes.alergias',
                'pacientes.contacto_emergencia',
                'pacientes.telefono_emergencia',
                
                // Datos del doctor (el especificado)
                'doctores.id_doctor',
                'u_doctor.nombres as doctor_nombres',
                'u_doctor.apellidos as doctor_apellidos',
                'doctores.titulo_profesional',
                
                // Especialidad y sucursal
                'especialidades.id_especialidad',
                'especialidades.nombre_especialidad',
                'sucursales.id_sucursal',
                'sucursales.nombre_sucursal',
                'sucursales.direccion as sucursal_direccion',
                'tipos_cita.nombre_tipo as tipo_cita_nombre',
                
                // Datos de consulta médica
                'consultas_medicas.id_consulta',
                'consultas_medicas.motivo_consulta',
                'consultas_medicas.sintomatologia',
                'consultas_medicas.diagnostico',
                'consultas_medicas.tratamiento',
                'consultas_medicas.observaciones as consulta_observaciones',
                'consultas_medicas.fecha_seguimiento',
                
                // Datos de triaje
                'triage.id_triage',
                'triage.nivel_urgencia',
                'triage.temperatura',
                'triage.presion_arterial',
                'triage.frecuencia_cardiaca',
                'triage.peso',
                'triage.talla as altura',
                'triage.imc',
                'triage.observaciones as triage_observaciones'
            )
            ->where('doctores.id_doctor', $filtros['id_doctor']); // ✅ FILTRO PRINCIPAL: Solo citas del médico
        
        // ✅ APLICAR FILTROS ADICIONALES
        if (!empty($filtros['fecha_desde'])) {
            $query->where('citas.fecha_hora', '>=', $filtros['fecha_desde'] . ' 00:00:00');
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('citas.fecha_hora', '<=', $filtros['fecha_hasta'] . ' 23:59:59');
        }
        
        if (!empty($filtros['estado'])) {
            $query->where('citas.estado', $filtros['estado']);
        }
        
        if (!empty($filtros['cedula_paciente'])) {
            $query->where('u_paciente.cedula', 'LIKE', '%' . $filtros['cedula_paciente'] . '%');
        }
        
        if (!empty($filtros['nombre_paciente'])) {
            $query->where(function($q) use ($filtros) {
                $nombre = '%' . $filtros['nombre_paciente'] . '%';
                $q->where('u_paciente.nombres', 'LIKE', $nombre)
                  ->orWhere('u_paciente.apellidos', 'LIKE', $nombre)
                  ->orWhereRaw("CONCAT(u_paciente.nombres, ' ', u_paciente.apellidos) LIKE ?", [$nombre]);
            });
        }
        
        // ✅ OBTENER TOTAL SIN LÍMITE (para estadísticas)
        $totalQuery = clone $query;
        $totalCitas = $totalQuery->count();
        
        // ✅ APLICAR PAGINACIÓN Y ORDENAR
        $citas = $query->orderBy('citas.fecha_hora', 'desc')
                      ->offset($filtros['offset'])
                      ->limit($filtros['limit'])
                      ->get();
        
        // ✅ PROCESAR CITAS CON TODOS LOS DETALLES
        $citasProcesadas = collect($citas)->map(function($cita) {
            return [
                // Datos básicos de la cita
                'id_cita' => $cita->id_cita,
                'fecha_hora' => $cita->fecha_hora,
                'motivo' => $cita->motivo,
                'estado' => $cita->estado,
                'modalidad_cita' => $cita->modalidad_cita,
                'notas' => $cita->cita_notas,
                'fecha_creacion' => $cita->cita_creada,
                'enlace_virtual' => $cita->enlace_virtual,
                'tipo_cita' => $cita->tipo_cita_nombre,
                
                // Paciente completo
                'paciente' => [
                    'id_paciente' => $cita->id_paciente,
                    'nombres' => $cita->paciente_nombres,
                    'apellidos' => $cita->paciente_apellidos,
                    'nombre_completo' => $cita->paciente_nombres . ' ' . $cita->paciente_apellidos,
                    'cedula' => $cita->paciente_cedula,
                    'fecha_nacimiento' => $cita->fecha_nacimiento,
                    'edad' => $this->calcularEdad($cita->fecha_nacimiento),
                    'telefono' => $cita->paciente_telefono,
                    'tipo_sangre' => $cita->tipo_sangre,
                    'alergias' => $cita->alergias,
                    'contacto_emergencia' => $cita->contacto_emergencia,
                    'telefono_emergencia' => $cita->telefono_emergencia
                ],
                
                // Doctor (el especificado)
                'doctor' => [
                    'id_doctor' => $cita->id_doctor,
                    'nombres' => $cita->doctor_nombres,
                    'apellidos' => $cita->doctor_apellidos,
                    'nombre_completo' => $cita->doctor_nombres . ' ' . $cita->doctor_apellidos,
                    'titulo_profesional' => $cita->titulo_profesional
                ],
                'especialidad' => [
                    'id_especialidad' => $cita->id_especialidad,
                    'nombre' => $cita->nombre_especialidad
                ],
                'sucursal' => [
                    'id_sucursal' => $cita->id_sucursal,
                    'nombre' => $cita->nombre_sucursal,
                    'direccion' => $cita->sucursal_direccion
                ],
                
                // Consulta médica completa (si existe)
                'consulta_medica' => $cita->id_consulta ? [
                    'id_consulta' => $cita->id_consulta,
                    'motivo_consulta' => $cita->motivo_consulta,
                    'sintomatologia' => $cita->sintomatologia,
                    'diagnostico' => $cita->diagnostico,
                    'tratamiento' => $cita->tratamiento,
                    'observaciones' => $cita->consulta_observaciones,
                    'fecha_seguimiento' => $cita->fecha_seguimiento
                ] : null,
                
                // Triaje completo (si existe)
                'triaje' => $cita->id_triage ? [
                    'id_triage' => $cita->id_triage,
                    'nivel_urgencia' => $cita->nivel_urgencia,
                    'signos_vitales' => [
                        'peso' => $cita->peso,
                        'altura' => $cita->altura,
                        'imc' => $cita->imc,
                        'presion_arterial' => $cita->presion_arterial,
                        'temperatura' => $cita->temperatura,
                        'frecuencia_cardiaca' => $cita->frecuencia_cardiaca
                    ],
                    'observaciones' => $cita->triage_observaciones
                ] : null,
                
                // Estados de la cita
                'tiene_consulta' => !is_null($cita->id_consulta),
                'tiene_triaje' => !is_null($cita->id_triage),
                'esta_completada' => $cita->estado === 'Completada'
            ];
        });
        
        // ✅ CALCULAR ESTADÍSTICAS DEL MÉDICO
        $estadisticas = $this->calcularEstadisticasMedico($filtros['id_doctor'], $filtros);
        
        $resultado = [
            'citas' => $citasProcesadas->values(),
            'medico_info' => [
                'id_doctor' => $filtros['id_doctor'],
                'nombre_completo' => $citas->first() ? $citas->first()->doctor_nombres . ' ' . $citas->first()->doctor_apellidos : '',
                'especialidad' => $citas->first() ? $citas->first()->nombre_especialidad : '',
                'titulo_profesional' => $citas->first() ? $citas->first()->titulo_profesional : ''
            ],
            'filtros_aplicados' => array_filter($filtros),
            'estadisticas' => $estadisticas,
            'paginacion' => [
                'total' => $totalCitas,
                'limit' => $filtros['limit'],
                'offset' => $filtros['offset'],
                'tiene_siguiente' => ($filtros['offset'] + $filtros['limit']) < $totalCitas,
                'tiene_anterior' => $filtros['offset'] > 0,
                'pagina_actual' => floor($filtros['offset'] / $filtros['limit']) + 1,
                'total_paginas' => ceil($totalCitas / $filtros['limit'])
            ]
        ];
        
        return ResponseUtil::success(
            $resultado, 
            'Mis citas obtenidas exitosamente. Encontradas: ' . $totalCitas . ' citas'
        );
        
    } catch (Exception $e) {
        return ResponseUtil::error('Error obteniendo mis citas: ' . $e->getMessage());
    }
}

/**
 * 📊 Calcular estadísticas específicas del médico
 */
private function calcularEstadisticasMedico($id_doctor, $filtros): array
{
    try {
        $query = DB::table('citas')
            ->where('id_doctor', $id_doctor);
        
        // Aplicar los mismos filtros de fecha
        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_hora', '>=', $filtros['fecha_desde'] . ' 00:00:00');
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_hora', '<=', $filtros['fecha_hasta'] . ' 23:59:59');
        }
        
        if (!empty($filtros['cedula_paciente']) || !empty($filtros['nombre_paciente'])) {
            $query->join('pacientes', 'citas.id_paciente', '=', 'pacientes.id_paciente')
                  ->join('usuarios as u_paciente', 'pacientes.id_usuario', '=', 'u_paciente.id_usuario');
            
            if (!empty($filtros['cedula_paciente'])) {
                $query->where('u_paciente.cedula', 'LIKE', '%' . $filtros['cedula_paciente'] . '%');
            }
            
            if (!empty($filtros['nombre_paciente'])) {
                $query->where(function($q) use ($filtros) {
                    $nombre = '%' . $filtros['nombre_paciente'] . '%';
                    $q->where('u_paciente.nombres', 'LIKE', $nombre)
                      ->orWhere('u_paciente.apellidos', 'LIKE', $nombre);
                });
            }
        }
        
        $estadisticas = $query->select('citas.estado', DB::raw('COUNT(*) as cantidad'))
                            ->groupBy('citas.estado')
                            ->get()
                            ->keyBy('estado');
        
        $total = $estadisticas->sum('cantidad');
        
        return [
            'total_citas' => $total,
            'pendientes' => $estadisticas->get('Pendiente')->cantidad ?? 0,
            'confirmadas' => $estadisticas->get('Confirmada')->cantidad ?? 0,
            'completadas' => $estadisticas->get('Completada')->cantidad ?? 0,
            'canceladas' => $estadisticas->get('Cancelada')->cantidad ?? 0
        ];
        
    } catch (Exception $e) {
        error_log('Error calculando estadísticas médico: ' . $e->getMessage());
        return [
            'total_citas' => 0,
            'pendientes' => 0,
            'confirmadas' => 0,
            'completadas' => 0,
            'canceladas' => 0
        ];
    }
}



   private function calcularEdad($fechaNacimiento): int
   {
       if (!$fechaNacimiento) return 0;
       
       $hoy = new \DateTime();
       $nacimiento = new \DateTime($fechaNacimiento);
       $edad = $hoy->diff($nacimiento);
       
       return $edad->y;
   }
}
?>