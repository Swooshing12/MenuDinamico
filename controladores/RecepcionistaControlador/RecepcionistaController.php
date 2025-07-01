<?php
require_once __DIR__ . "/../../modelos/Usuario.php";
require_once __DIR__ . "/../../modelos/Roles.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Citas.php";
require_once __DIR__ . "/../../modelos/Pacientes.php";
require_once __DIR__ . "/../../modelos/Especialidades.php";
require_once __DIR__ . "/../../modelos/Sucursales.php";
require_once __DIR__ . "/../../modelos/Doctores.php";

class RecepcionistaController {
    private $usuarioModel;
    private $rolesModel;
    private $permisosModel;
    private $citasModel;
    private $pacientesModel;
    private $especialidadesModel;
    private $sucursalesModel;
    private $doctoresModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->usuarioModel = new Usuario();
        $this->rolesModel = new Roles();
        $this->permisosModel = new Permisos();
        $this->citasModel = new Citas();
        $this->pacientesModel = new Pacientes();
        $this->especialidadesModel = new Especialidades();
        $this->sucursalesModel = new Sucursales();
        $this->doctoresModel = new Doctores();
    }
    
    // ===== MANEJO PRINCIPAL DE SOLICITUDES =====
    public function manejarSolicitud() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../vistas/login.php');
            exit();
        }
        
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
        
        try {
            switch ($action) {
                // === GESTIÓN DE CITAS ===
                case 'registrarCita':
                    $this->registrarCita();
                    break;
                case 'obtenerCitas':
                    $this->obtenerCitas();
                    break;
                case 'editarCita':
                    $this->editarCita();
                    break;
                case 'cancelarCita':
                    $this->cancelarCita();
                    break;
                case 'confirmarCita':
                    $this->confirmarCita();
                    break;
                
                // === TIPOS DE CITA ===
                case 'obtenerTiposCita':
                    $this->obtenerTiposCita();
                    break;
                case 'validarTipoCita':
                    $this->validarTipoCita();
                    break;
                
                // === BÚSQUEDA Y GESTIÓN DE PACIENTES ===
                case 'buscarPacientePorCedula':
                    $this->buscarPacientePorCedula();
                    break;
                case 'registrarPaciente':
                    $this->registrarPaciente();
                    break;
                case 'obtenerDatosCedula':
                    $this->obtenerDatosCedula();
                    break;
                
                // === DATOS PARA FORMULARIOS ===
                case 'obtenerDoctores':
                    $this->obtenerDoctores();
                    break;
                case 'obtenerEspecialidadesPorSucursal':
                    $this->obtenerEspecialidadesPorSucursal();
                    break;
                case 'obtenerDoctoresPorEspecialidad':
                    $this->obtenerDoctoresPorEspecialidad();
                    break;
                case 'obtenerHorariosDisponibles':
                    $this->obtenerHorariosDisponibles();
                    break;
                case 'obtenerSucursales':
                    $this->obtenerSucursales();
                    break;
                case 'verificarDisponibilidad':
                    $this->verificarDisponibilidad();
                    break;
                
                // === TRIAJE ===
                case 'realizarTriaje':
                    $this->realizarTriaje();
                    break;
                
                // === NOTIFICACIONES ===
                case 'enviarNotificacion':
                    $this->enviarNotificacion();
                    break;
                
                // === ESTADÍSTICAS ===
                case 'obtenerEstadisticas':
                    $this->obtenerEstadisticas();
                    break;
                case 'obtenerEstadisticasPorTipo':
                    $this->obtenerEstadisticasPorTipo();
                    break;
                       // === HORARIOS DOCTOR ===
                    case 'obtenerHorariosDoctor':
                    $this->obtenerHorariosDoctor();
                    break;
                case 'index':
                default:
                    $this->index();
                    break;
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== MÉTODOS DE VISTA =====
    public function index() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../vistas/login.php');
            exit();
        }
        
        $id_rol = $_SESSION['id_rol'];
        $id_submenu = $this->obtenerIdSubmenu();
        
        if (!$id_submenu) {
            die("Error: No se pudo determinar el ID del submenú");
        }
        
        try {
            $permisos = $this->permisosModel->obtenerPermisos($id_rol, $id_submenu);
            
            if (!$permisos) {
                $this->redirigir('../../error_permisos.php');
                exit();
            }
            
            // Obtener datos iniciales para la vista
            $sucursales = $this->sucursalesModel->obtenerTodas();
            $especialidades = $this->especialidadesModel->obtenerTodas();
            $tipos_cita = $this->citasModel->obtenerTiposCita(); // NUEVO: Obtener tipos de cita
            
            // Pasar datos a la vista
            extract([
                'sucursales' => $sucursales,
                'especialidades' => $especialidades,
                'tipos_cita' => $tipos_cita, // NUEVO: Incluir tipos de cita
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista correspondiente según el submenú
            $vista = match($id_submenu) {
                28 => '../../vistas/recepcion/registrar_pacientes.php',
                29 => '../../vistas/recepcion/gestionar_citas.php',
                30 => '../../vistas/recepcion/realizar_triaje.php',
                31 => '../../vistas/recepcion/gestion_pagos.php',
                32 => '../../vistas/recepcion/agenda_medicos.php',
                33 => '../../vistas/recepcion/historial_citas.php',
                34 => '../../vistas/recepcion/notificaciones.php',
                default => '../../vistas/recepcion/gestionar_citas.php'
            };
            
            include __DIR__ . '/' . $vista;
        } catch (Exception $e) {
            die("Error al cargar la página: " . $e->getMessage());
        }
    }
    
    // ===== MÉTODOS PARA GESTIÓN DE CITAS =====
    private function registrarCita() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }
    
    $this->verificarPermisos('crear');
    
    // Validar datos requeridos básicos
    $camposRequeridos = ['id_paciente', 'id_doctor', 'id_sucursal', 'id_tipo_cita', 'fecha', 'hora', 'motivo'];
    $camposFaltantes = [];
    
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            $camposFaltantes[] = $campo;
        }
    }
    
    if (!empty($camposFaltantes)) {
        $this->responderJSON([
            'success' => false,
            'message' => "Campos requeridos: " . implode(', ', $camposFaltantes)
        ]);
        return;
    }
    
    // Validar tipo de cita
    if (!$this->citasModel->validarTipoCita($_POST['id_tipo_cita'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Tipo de cita no válido o inactivo'
        ]);
        return;
    }
    
    // Validaciones adicionales para citas virtuales
    if ($_POST['id_tipo_cita'] == 2) { // Cita virtual
        if (empty($_POST['plataforma_virtual'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La plataforma virtual es requerida para citas virtuales'
            ]);
            return;
        }
    }
    
    try {
        // ✅ CONVERTIR FORMATO DE FECHA CORRECTAMENTE
        $fecha_original = $_POST['fecha']; // 03/07/2025
        $hora = $_POST['hora']; // 09:00:00
        
        // Convertir de DD/MM/YYYY a YYYY-MM-DD
        $fecha_partes = explode('/', $fecha_original);
        if (count($fecha_partes) === 3) {
            $fecha_mysql = $fecha_partes[2] . '-' . $fecha_partes[1] . '-' . $fecha_partes[0]; // 2025-07-03
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Formato de fecha inválido. Use DD/MM/YYYY'
            ]);
            return;
        }
        
        // Combinar fecha y hora en formato MySQL
        $fecha_hora = $fecha_mysql . ' ' . $hora; // 2025-07-03 09:00:00
        
        // Verificar disponibilidad del doctor
        $disponible = $this->citasModel->verificarDisponibilidad(
            $_POST['id_doctor'],
            $fecha_hora
        );
        
        if (!$disponible) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El doctor no está disponible en esa fecha y hora'
            ]);
            return;
        }
        
        // Preparar datos de la cita
        $datos_cita = [
            'id_paciente' => (int)$_POST['id_paciente'],
            'id_doctor' => (int)$_POST['id_doctor'],
            'id_sucursal' => (int)$_POST['id_sucursal'],
            'id_tipo_cita' => (int)$_POST['id_tipo_cita'],
            'fecha_hora' => $fecha_hora,
            'motivo' => trim($_POST['motivo']),
            'tipo_cita' => $_POST['id_tipo_cita'] == 1 ? 'presencial' : 'virtual',
            'estado' => 'Pendiente',
            'notas' => trim($_POST['notas'] ?? ''),
            'prioridad' => $_POST['prioridad'] ?? 'normal'
        ];
        
        // Agregar campos para citas virtuales
        if ($_POST['id_tipo_cita'] == 2) {
            $plataforma = $_POST['plataforma_virtual'] ?? 'zoom';
            $enlace_virtual = $this->generarEnlaceVirtual($plataforma);
            
            $datos_cita['enlace_virtual'] = $enlace_virtual;
            $datos_cita['sala_virtual'] = $_POST['sala_virtual'] ?? 'Sala-' . uniqid();
        }
        
        // Registrar la cita
        $id_cita = $this->citasModel->crear($datos_cita);
        
        if ($id_cita) {
            // Enviar notificación si se solicitó
            if (isset($_POST['enviar_notificacion']) && $_POST['enviar_notificacion'] === 'true') {
                // Aquí puedes implementar el envío de notificaciones
                // $this->enviarNotificacionCita($id_cita);
            }
            
            $this->responderJSON([
                'success' => true,
                'message' => 'Cita registrada exitosamente',
                'data' => [
                    'id_cita' => $id_cita,
                    'fecha_hora' => $fecha_hora
                ]
            ]);
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al registrar la cita'
            ]);
        }
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
    
    private function obtenerCitas() {
        try {
            $filtros = [];
            
            // Aplicar filtros si vienen en la petición
            if (!empty($_GET['estado'])) {
                $filtros['estado'] = $_GET['estado'];
            }
            
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            if (!empty($_GET['id_sucursal'])) {
                $filtros['id_sucursal'] = $_GET['id_sucursal'];
            }
            
            if (!empty($_GET['id_tipo_cita'])) { // NUEVO: Filtro por tipo de cita
                $filtros['id_tipo_cita'] = $_GET['id_tipo_cita'];
            }
            
            if (!empty($_GET['cedula_paciente'])) {
                $filtros['cedula_paciente'] = $_GET['cedula_paciente'];
            }
            
            $citas = $this->citasModel->obtenerTodas($filtros);
            
            $this->responderJSON([
                'success' => true,
                'data' => $citas,
                'count' => count($citas)
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function editarCita() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $this->verificarPermisos('editar');
        
        if (empty($_POST['id_cita'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de cita requerido'
            ]);
            return;
        }
        
        $id_cita = (int)$_POST['id_cita'];
        
        // Validar datos requeridos
        $camposRequeridos = ['id_paciente', 'id_doctor', 'id_sucursal', 'id_tipo_cita', 'fecha', 'hora', 'motivo'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($_POST[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            $this->responderJSON([
                'success' => false,
                'message' => "Campos requeridos: " . implode(', ', $camposFaltantes)
            ]);
            return;
        }
        
        // Validar tipo de cita
        if (!$this->citasModel->validarTipoCita($_POST['id_tipo_cita'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Tipo de cita no válido o inactivo'
            ]);
            return;
        }
        
        try {
            // Combinar fecha y hora
            $fecha_hora = $_POST['fecha'] . ' ' . $_POST['hora'];
            
            // Verificar disponibilidad del doctor (solo si cambió doctor o fecha)
            $cita_actual = $this->citasModel->obtenerPorId($id_cita);
            if (!$cita_actual) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ]);
                return;
            }
            
            // Si cambió doctor o fecha, verificar disponibilidad
            if ($cita_actual['id_doctor'] != $_POST['id_doctor'] || 
                $cita_actual['fecha_hora'] != $fecha_hora) {
                
                $disponible = $this->citasModel->verificarDisponibilidad(
                    $_POST['id_doctor'],
                    $fecha_hora
                );
                
                if (!$disponible) {
                    $this->responderJSON([
                        'success' => false,
                        'message' => 'El doctor no está disponible en esa fecha y hora'
                    ]);
                    return;
                }
            }
            
            // Preparar datos para actualizar
            $datos_cita = [
                'id_paciente' => (int)$_POST['id_paciente'],
                'id_doctor' => (int)$_POST['id_doctor'],
                'id_sucursal' => (int)$_POST['id_sucursal'],
                'id_tipo_cita' => (int)$_POST['id_tipo_cita'],
                'fecha_hora' => $fecha_hora,
                'motivo' => trim($_POST['motivo']),
                'tipo_cita' => $_POST['id_tipo_cita'] == 1 ? 'presencial' : 'virtual',
                'estado' => $_POST['estado'] ?? $cita_actual['estado'],
                'notas' => trim($_POST['notas'] ?? ''),
                'enlace_virtual' => $cita_actual['enlace_virtual'],
                'sala_virtual' => $cita_actual['sala_virtual']
            ];
            
            // Actualizar campos virtuales si es cita virtual
            if ($_POST['id_tipo_cita'] == 2) {
                $datos_cita['enlace_virtual'] = !empty($_POST['enlace_virtual']) ? trim($_POST['enlace_virtual']) : $cita_actual['enlace_virtual'];
                $datos_cita['sala_virtual'] = !empty($_POST['sala_virtual']) ? trim($_POST['sala_virtual']) : $cita_actual['sala_virtual'];
            } else {
                // Si cambió de virtual a presencial, limpiar campos virtuales
                $datos_cita['enlace_virtual'] = null;
                $datos_cita['sala_virtual'] = null;
            }
            
            $resultado = $this->citasModel->actualizar($id_cita, $datos_cita);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Cita actualizada exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar la cita'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function cancelarCita() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $this->verificarPermisos('eliminar');
        
        if (empty($_POST['id_cita'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de cita requerido'
            ]);
            return;
        }
        
        try {
            $id_cita = (int)$_POST['id_cita'];
            
            // Cambiar estado a "Cancelada"
            $resultado = $this->citasModel->cambiarEstado($id_cita, 'Cancelada');
            
            if ($resultado) {
                // Enviar notificación de cancelación si está habilitada
                if (!empty($_POST['enviar_notificacion']) && $_POST['enviar_notificacion'] == 'true') {
                    $this->enviarNotificacionCita($id_cita, 'cancelacion');
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Cita cancelada exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al cancelar la cita'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function confirmarCita() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $this->verificarPermisos('editar');
        
        if (empty($_POST['id_cita'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de cita requerido'
            ]);
            return;
        }
        
        try {
            $id_cita = (int)$_POST['id_cita'];
            
            // Cambiar estado a "Confirmada"
            $resultado = $this->citasModel->cambiarEstado($id_cita, 'Confirmada');
            
            if ($resultado) {
                // Enviar notificación de confirmación si está habilitada
                if (!empty($_POST['enviar_notificacion']) && $_POST['enviar_notificacion'] == 'true') {
                    $this->enviarNotificacionCita($id_cita, 'confirmacion');
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Cita confirmada exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al confirmar la cita'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== MÉTODOS PARA TIPOS DE CITA =====
    private function obtenerTiposCita() {
        try {
            $tipos_cita = $this->citasModel->obtenerTiposCita();
            
            $this->responderJSON([
                'success' => true,
                'data' => $tipos_cita
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function validarTipoCita() {
        if (empty($_GET['id_tipo_cita'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de tipo de cita requerido'
            ]);
            return;
        }
        
        try {
            $id_tipo_cita = (int)$_GET['id_tipo_cita'];
            $es_valido = $this->citasModel->validarTipoCita($id_tipo_cita);
            
            $this->responderJSON([
                'success' => true,
                'valido' => $es_valido
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== MÉTODOS PARA BÚSQUEDA DE PACIENTES =====
    private function buscarPacientePorCedula() {
        if (empty($_GET['cedula'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Cédula requerida'
            ]);
            return;
        }
        
        $cedula = trim($_GET['cedula']);
        
        try {
            $paciente = $this->pacientesModel->buscarPorCedula($cedula);
            
            if ($paciente) {
                $this->responderJSON([
                    'success' => true,
                    'encontrado' => true,
                    'data' => $paciente
                ]);
            } else {
                $this->responderJSON([
                    'success' => true,
                    'encontrado' => false,
                    'message' => 'Paciente no encontrado. ¿Desea registrarlo?'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al buscar paciente: ' . $e->getMessage()
            ]);
        }
    }
    
    private function registrarPaciente() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $this->verificarPermisos('crear');
        
        // Validar datos requeridos
        $camposRequeridos = ['cedula', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero', 'telefono', 'direccion'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($_POST[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            $this->responderJSON([
                'success' => false,
                'message' => "Campos requeridos: " . implode(', ', $camposFaltantes)
            ]);
            return;
        }
        
        // Validaciones básicas
        if (!is_numeric($_POST['cedula']) || strlen($_POST['cedula']) < 10) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La cédula debe tener al menos 10 dígitos numéricos'
            ]);
            return;
        }
        
        // Validar correo si se proporciona
        if (!empty($_POST['correo']) && !filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Formato de correo electrónico no válido'
            ]);
            return;
        }
        
        try {
            // Verificar si ya existe un usuario con esa cédula
            $usuarioExistente = $this->usuarioModel->obtenerPorCedula((int)$_POST['cedula']);
            if ($usuarioExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un usuario registrado con esa cédula'
                ]);
                return;
            }
            
            // Verificar si el correo ya existe (si se proporciona)
            if (!empty($_POST['correo'])) {
                $correoExistente = $this->usuarioModel->obtenerPorCorreo(trim($_POST['correo']));
                if ($correoExistente) {
                    $this->responderJSON([
                        'success' => false,
                        'message' => 'El correo electrónico ya está registrado'
                    ]);
                    return;
                }
            }
            
            // PASO 1: Crear el USUARIO
            $username = $this->generarUsername($_POST['nombres'], $_POST['apellidos']);
            $passwordTemporal = $this->generarPasswordTemporal();
            
            // Buscar ID del rol "Paciente"
            $rolPaciente = $this->rolesModel->obtenerPorNombre('Paciente');
            if (!$rolPaciente) {
                throw new Exception("No se encontró el rol 'Paciente' en el sistema");
            }
            
            $resultado = $this->usuarioModel->crearUsuario(
                (int)$_POST['cedula'],
                $username,
                trim($_POST['nombres']),
                trim($_POST['apellidos']),
                $_POST['genero'],
                'Ecuatoriana', // Nacionalidad por defecto
                !empty($_POST['correo']) ? trim($_POST['correo']) : null,
                $passwordTemporal,
                $rolPaciente['id_rol']
            );
            
            if (!$resultado) {
                throw new Exception("Error al crear el usuario");
            }
            
            // Obtener el ID del usuario recién creado
            $usuarioCreado = $this->usuarioModel->obtenerPorUsername($username);
            if (!$usuarioCreado) {
                throw new Exception("Error al obtener el usuario creado");
            }
            
            $id_usuario = $usuarioCreado['id_usuario'];
            
            // PASO 2: Crear el PACIENTE
            // PASO 2: Crear el PACIENTE
                $datos_paciente = [
                    'id_usuario' => $id_usuario,
                    'fecha_nacimiento' => $_POST['fecha_nacimiento'],
                    'telefono' => !empty($_POST['telefono']) ? trim($_POST['telefono']) : null,  // ⭐ NUEVO
                    'tipo_sangre' => !empty($_POST['tipo_sangre']) ? $_POST['tipo_sangre'] : null,
                    'alergias' => !empty($_POST['alergias']) ? trim($_POST['alergias']) : null,
                    'antecedentes_medicos' => !empty($_POST['antecedentes_medicos']) ? trim($_POST['antecedentes_medicos']) : null,
                    'contacto_emergencia' => !empty($_POST['contacto_emergencia']) ? trim($_POST['contacto_emergencia']) : null,
                    'telefono_emergencia' => !empty($_POST['telefono_emergencia']) ? trim($_POST['telefono_emergencia']) : null,
                    'numero_seguro' => !empty($_POST['numero_seguro']) ? trim($_POST['numero_seguro']) : null
                ];
                            
            $id_paciente = $this->pacientesModel->crear($datos_paciente);
            
            if (!$id_paciente) {
                throw new Exception("Error al crear el paciente");
            }
            
            // PASO 3: Enviar correo con credenciales (si tiene correo)
            $envioExitoso = false;
            if (!empty($_POST['correo'])) {
                try {
                    // Usar tu MailService existente
                    require_once __DIR__ . '/../../config/MailService.php';
                    $mailService = new MailService();
                   
                   $nombreCompleto = trim($_POST['nombres']) . ' ' . trim($_POST['apellidos']);
                   $envioExitoso = $mailService->enviarPasswordTemporal(
                       trim($_POST['correo']),
                       $nombreCompleto,
                       $username,
                       $passwordTemporal
                   );
               } catch (Exception $e) {
                   // Si falla el envío de correo, continuar pero marcar como no enviado
                   error_log("Error enviando correo: " . $e->getMessage());
                   $envioExitoso = false;
               }
           }
           
           // Obtener los datos completos del paciente
           $paciente_completo = $this->pacientesModel->obtenerPorId($id_paciente);
           
           // Preparar mensaje de respuesta
           $mensaje = 'Paciente registrado exitosamente';
           if (!empty($_POST['correo'])) {
               if ($envioExitoso) {
                   $mensaje .= '. Se ha enviado un correo con las credenciales de acceso.';
               } else {
                   $mensaje .= ', pero hubo un problema enviando el correo. Credenciales: Usuario: ' . $username . ', Contraseña: ' . $passwordTemporal;
               }
           } else {
               $mensaje .= '. Credenciales: Usuario: ' . $username . ', Contraseña: ' . $passwordTemporal;
           }
           
           $this->responderJSON([
               'success' => true,
               'message' => $mensaje,
               'data' => [
                   'id_paciente' => $id_paciente,
                   'id_usuario' => $id_usuario,
                   'username' => $username,
                   'password_temporal' => $passwordTemporal,
                   'email_enviado' => $envioExitoso,
                   'paciente' => $paciente_completo
               ]
           ]);
           
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function obtenerDatosCedula() {
       if (empty($_GET['cedula'])) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Cédula requerida'
           ]);
           return;
       }
       
       $cedula = trim($_GET['cedula']);
       
       try {
           // Llamar al archivo obtenerDatos.php
           $url = "http://localhost/MenuDinamico/controladores/obtenerDatos.php?cedula=" . urlencode($cedula);
           $response = file_get_contents($url);
           
           if ($response === false) {
               throw new Exception("No se pudo conectar con el servicio de cédulas");
           }
           
           $datos = json_decode($response, true);
           
           if (json_last_error() !== JSON_ERROR_NONE) {
               throw new Exception("Error al procesar respuesta del servicio");
           }
           
           if (isset($datos['estado']) && $datos['estado'] === 'OK') {
               // Procesar y formatear los datos
               $resultado = $datos['resultado'][0] ?? [];
               
               $datos_formateados = [
                   'cedula' => $resultado['cedula'] ?? $cedula,
                   'nombres' => $this->extraerNombres($resultado['nombre'] ?? ''),
                   'apellidos' => $this->extraerApellidos($resultado['apellido'] ?? ''),
                   'fecha_nacimiento' => $this->formatearFecha($resultado['fechaNacimiento'] ?? ''),
                   'lugar_nacimiento' => $resultado['lugarNacimiento'] ?? '',
                   'estado_civil' => $resultado['estadoCivil'] ?? '',
                   'profesion' => $resultado['profesion'] ?? ''
               ];
               
               $this->responderJSON([
                   'success' => true,
                   'data' => $datos_formateados
               ]);
           } else {
               $this->responderJSON([
                   'success' => false,
                   'message' => 'No se encontraron datos para esta cédula'
               ]);
           }
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   // ===== MÉTODOS PARA DATOS DE FORMULARIOS =====
   private function obtenerDoctores() {
       try {
           // Usar el método obtenerTodos con filtro de estado activo
           $filtros = ['estado' => 1]; // Solo doctores activos
           $doctores = $this->doctoresModel->obtenerTodos($filtros);
           
           $this->responderJSON([
               'success' => true,
               'data' => $doctores,
               'count' => count($doctores)
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function obtenerEspecialidadesPorSucursal() {
       if (empty($_GET['id_sucursal'])) {
           $this->responderJSON([
               'success' => false,
               'message' => 'ID de sucursal requerido'
           ]);
           return;
       }
       
       try {
           $especialidades = $this->especialidadesModel->obtenerPorSucursal($_GET['id_sucursal']);
           
           $this->responderJSON([
               'success' => true,
               'data' => $especialidades
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function obtenerDoctoresPorEspecialidad() {
       if (empty($_GET['id_especialidad']) || empty($_GET['id_sucursal'])) {
           $this->responderJSON([
               'success' => false,
               'message' => 'ID de especialidad y sucursal requeridos'
           ]);
           return;
       }
       
       try {
           $doctores = $this->doctoresModel->obtenerPorEspecialidadYSucursal(
               $_GET['id_especialidad'],
               $_GET['id_sucursal']
           );
           
           $this->responderJSON([
               'success' => true,
               'data' => $doctores
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function obtenerHorariosDisponibles() {
       if (empty($_GET['id_doctor']) || empty($_GET['fecha'])) {
           $this->responderJSON([
               'success' => false,
               'message' => 'ID de doctor y fecha requeridos'
           ]);
           return;
       }
       
       try {
           $horarios = $this->doctoresModel->obtenerHorariosDisponibles(
               $_GET['id_doctor'],
               $_GET['fecha']
           );
           
           $this->responderJSON([
               'success' => true,
               'data' => $horarios
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function obtenerSucursales() {
       try {
           $sucursales = $this->sucursalesModel->obtenerTodas();
           
           $this->responderJSON([
               'success' => true,
               'data' => $sucursales
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function verificarDisponibilidad() {
       if (empty($_GET['id_doctor']) || empty($_GET['fecha_hora'])) {
           $this->responderJSON([
               'success' => false,
               'message' => 'ID de doctor y fecha_hora requeridos'
           ]);
           return;
       }
       
       try {
           $id_cita_excluir = !empty($_GET['id_cita']) ? (int)$_GET['id_cita'] : null;
           
           $disponible = $this->citasModel->verificarDisponibilidad(
               $_GET['id_doctor'],
               $_GET['fecha_hora']
           );
           
           $this->responderJSON([
               'success' => true,
               'disponible' => $disponible
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   // ===== MÉTODOS PARA TRIAJE =====
   private function realizarTriaje() {
       if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
           $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
           return;
       }
       
       $this->verificarPermisos('crear');
       
       // Validar datos requeridos
       $camposRequeridos = ['id_cita', 'nivel_urgencia'];
       $camposFaltantes = [];
       
       foreach ($camposRequeridos as $campo) {
           if (empty($_POST[$campo])) {
               $camposFaltantes[] = $campo;
           }
       }
       
       if (!empty($camposFaltantes)) {
           $this->responderJSON([
               'success' => false,
               'message' => "Campos requeridos: " . implode(', ', $camposFaltantes)
           ]);
           return;
       }
       
       try {
           $datos_triaje = [
               'id_cita' => (int)$_POST['id_cita'],
               'id_enfermero' => $_SESSION['id_usuario'],
               'nivel_urgencia' => (int)$_POST['nivel_urgencia'],
               'temperatura' => !empty($_POST['temperatura']) ? (float)$_POST['temperatura'] : null,
               'presion_arterial' => $_POST['presion_arterial'] ?? null,
               'frecuencia_cardiaca' => !empty($_POST['frecuencia_cardiaca']) ? (int)$_POST['frecuencia_cardiaca'] : null,
               'frecuencia_respiratoria' => !empty($_POST['frecuencia_respiratoria']) ? (int)$_POST['frecuencia_respiratoria'] : null,
               'saturacion_oxigeno' => !empty($_POST['saturacion_oxigeno']) ? (int)$_POST['saturacion_oxigeno'] : null,
               'peso' => !empty($_POST['peso']) ? (float)$_POST['peso'] : null,
               'talla' => !empty($_POST['talla']) ? (int)$_POST['talla'] : null,
               'observaciones' => $_POST['observaciones'] ?? null
           ];
           
           // Calcular IMC si se proporcionaron peso y talla
           if ($datos_triaje['peso'] && $datos_triaje['talla']) {
               $altura_metros = $datos_triaje['talla'] / 100;
               $datos_triaje['imc'] = round($datos_triaje['peso'] / ($altura_metros * $altura_metros), 2);
           }
           
           // Nota: Aquí necesitarías un modelo Triaje
           // $id_triaje = $this->triajeModel->crear($datos_triaje);
           
           $this->responderJSON([
               'success' => true,
               'message' => 'Triaje realizado exitosamente'
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   // ===== MÉTODOS PARA NOTIFICACIONES =====
   private function enviarNotificacion() {
       if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
           $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
           return;
       }
       
       if (empty($_POST['id_cita']) || empty($_POST['tipo'])) {
           $this->responderJSON([
               'success' => false,
               'message' => 'ID de cita y tipo de notificación requeridos'
           ]);
           return;
       }
       
       try {
           $id_cita = (int)$_POST['id_cita'];
           $tipo = $_POST['tipo'];
           
           $resultado = $this->enviarNotificacionCita($id_cita, $tipo);
           
           $this->responderJSON($resultado);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function enviarNotificacionCita($id_cita, $tipo) {
    try {
        // Obtener datos completos de la cita
        $cita = $this->citasModel->obtenerPorIdCompleto($id_cita);
        if (!$cita) {
            error_log("❌ Cita no encontrada para ID: $id_cita");
            return [
                'success' => false,
                'message' => 'Cita no encontrada'
            ];
        }
        
        // Verificar que el paciente tenga email
        if (empty($cita['paciente_correo'])) {
            error_log("⚠️ Paciente sin email para cita ID: $id_cita");
            return [
                'success' => false,
                'message' => 'El paciente no tiene email registrado'
            ];
        }
        
        // ✅ USAR EL MAILSERVICE PARA ENVIAR EL EMAIL
        require_once __DIR__ . '/../../config/MailService.php';
        $mailService = new MailService();
        
        // Preparar datos del paciente
        $paciente = [
            'nombres' => $cita['paciente_nombres'],
            'apellidos' => $cita['paciente_apellidos'],
            'correo' => $cita['paciente_correo']
        ];
        
        // Enviar email según el tipo
        $emailEnviado = false;
        switch ($tipo) {
            case 'confirmacion':
                $emailEnviado = $mailService->enviarConfirmacionCita($cita, $paciente);
                break;
                
            case 'recordatorio':
                $emailEnviado = $mailService->enviarRecordatorioCita($cita, $paciente);
                break;
                
            case 'cancelacion':
                $emailEnviado = $mailService->enviarCancelacionCita($cita, $paciente);
                break;
        }
        
        if ($emailEnviado) {
            error_log("✅ Email de $tipo enviado exitosamente para cita ID: $id_cita");
            return [
                'success' => true,
                'message' => "Email de $tipo enviado exitosamente",
                'data' => [
                    'tipo' => $tipo,
                    'destinatario' => $cita['paciente_correo'],
                    'cita_id' => $id_cita
                ]
            ];
        } else {
            error_log("❌ Error enviando email de $tipo para cita ID: $id_cita");
            return [
                'success' => false,
                'message' => "Error enviando email de $tipo"
            ];
        }
        
    } catch (Exception $e) {
        error_log("❌ Excepción en enviarNotificacionCita: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error enviando notificación: ' . $e->getMessage()
        ];
    }
}
   
   // ===== MÉTODOS PARA ESTADÍSTICAS =====
   private function obtenerEstadisticas() {
       try {
           // Obtener estadísticas generales
           $estadisticas_generales = $this->citasModel->obtenerEstadisticas();
           
           // Obtener estadísticas del día
           $fecha_hoy = date('Y-m-d');
           $pacientes_nuevos_hoy = method_exists($this->pacientesModel, 'contarPacientesNuevosPorFecha') 
               ? $this->pacientesModel->contarPacientesNuevosPorFecha($fecha_hoy) 
               : 0;
           
           $estadisticas = [
               'citas_hoy' => (int)$estadisticas_generales['hoy'],
               'citas_pendientes' => (int)$estadisticas_generales['pendientes'],
               'citas_confirmadas' => (int)$estadisticas_generales['confirmadas'],
               'citas_completadas' => (int)$estadisticas_generales['completadas'],
               'citas_canceladas' => (int)$estadisticas_generales['canceladas'],
               'citas_presenciales' => (int)$estadisticas_generales['presenciales'],
               'citas_virtuales' => (int)$estadisticas_generales['virtuales'],
               'total_citas' => (int)$estadisticas_generales['total_citas'],
               'pacientes_nuevos_hoy' => $pacientes_nuevos_hoy
           ];
           
           $this->responderJSON([
               'success' => true,
               'data' => $estadisticas
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   private function obtenerEstadisticasPorTipo() {
       try {
           $estadisticas_por_tipo = $this->citasModel->obtenerEstadisticasPorTipo();
           
           $this->responderJSON([
               'success' => true,
               'data' => $estadisticas_por_tipo
           ]);
       } catch (Exception $e) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Error: ' . $e->getMessage()
           ]);
       }
   }
   
   // ===== MÉTODOS AUXILIARES =====
   private function verificarPermisos($accion) {
       $id_rol = $_SESSION['id_rol'];
       $id_submenu = $this->obtenerIdSubmenu();
       
       $permisos = $this->permisosModel->obtenerPermisos($id_rol, $id_submenu);
       
       $permiso_requerido = match($accion) {
           'crear', 'registrarCita', 'registrarPaciente', 'realizarTriaje' => 'puede_crear',
           'editar', 'editarCita', 'confirmarCita' => 'puede_editar',
           'eliminar', 'cancelarCita' => 'puede_eliminar',
           default => null
       };
       
       if ($permiso_requerido && !$permisos[$permiso_requerido]) {
           $this->responderJSON([
               'success' => false,
               'message' => 'No tienes permisos para realizar esta acción'
           ]);
           exit();
       }
       
       return $permisos;
   }
   
   private function obtenerIdSubmenu() {
       // Intentar obtener de POST primero
       $id_submenu = isset($_POST['submenu_id']) ? (int)$_POST['submenu_id'] : null;
       
       // Intentar obtener de GET si no está en POST
       if (!$id_submenu) {
           $id_submenu = isset($_GET['submenu_id']) ? (int)$_GET['submenu_id'] : null;
       }
       
       // Si aún no tenemos ID, usar valor por defecto según la funcionalidad
       if (!$id_submenu) {
           $script_name = basename($_SERVER['SCRIPT_NAME']);
           $request_uri = $_SERVER['REQUEST_URI'];
           
           if (strpos($script_name, 'gestionar_citas') !== false || 
               strpos($request_uri, 'gestionar_citas') !== false) {
               $id_submenu = 29; // ID del submenú "Gestionar Citas"
           } elseif (strpos($script_name, 'registrar_pacientes') !== false || 
                     strpos($request_uri, 'registrar_pacientes') !== false) {
               $id_submenu = 28; // ID del submenú "Registrar Pacientes"
           } elseif (strpos($script_name, 'realizar_triaje') !== false || 
                     strpos($request_uri, 'realizar_triaje') !== false) {
               $id_submenu = 30; // ID del submenú "Realizar Triaje"
           } else {
               $id_submenu = 29; // Por defecto, usar "Gestionar Citas"
           }
       }
       
       return $id_submenu;
   }
   
/**
 * Generar enlace virtual según la plataforma
 */
private function generarEnlaceVirtual($plataforma) {
    $enlaces = [
        'zoom' => 'https://zoom.us/j/' . rand(100000000, 999999999),
        'meet' => 'https://meet.google.com/' . uniqid(),
        'teams' => 'https://teams.microsoft.com/l/meetup-join/' . uniqid(),
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
   
   private function extraerNombres($nombre_completo) {
       // Lógica para extraer solo los nombres del nombre completo
       $partes = explode(' ', trim($nombre_completo));
       return implode(' ', array_slice($partes, 0, 2)); // Primeros 2 elementos como nombres
   }
   
   private function extraerApellidos($apellido_completo) {
       // Lógica para extraer apellidos
       $partes = explode(' ', trim($apellido_completo));
       return implode(' ', array_slice($partes, -2)); // Últimos 2 elementos como apellidos
   }
   
   private function formatearFecha($fecha_str) {
       // Convertir formato DD/MM/YYYY a YYYY-MM-DD
       if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha_str, $matches)) {
           return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
       }
       return $fecha_str;
   }
   
   private function generarUsername($nombres, $apellidos) {
       $nombre_limpio = strtolower(trim($nombres));
       $apellido_limpio = strtolower(trim($apellidos));
       
       $primera_parte_nombre = explode(' ', $nombre_limpio)[0];
       $primera_parte_apellido = explode(' ', $apellido_limpio)[0];
       
       $username_base = $primera_parte_nombre . '.' . $primera_parte_apellido;
       
       // Verificar si existe y agregar número si es necesario
       $username = $username_base;
       $contador = 1;
       
       while ($this->usuarioModel->existeUsuarioPorUsername($username)) {
           $username = $username_base . $contador;
           $contador++;
       }
       
       return $username;
   }
   
   private function generarPasswordTemporal() {
       return 'Temp' . rand(1000, 9999) . '!';
   }
   

   /**
 * Obtener horarios completos de un doctor para el calendario
 */
private function obtenerHorariosDoctor() {
    if (empty($_GET['id_doctor'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de doctor requerido'
        ]);
        return;
    }
    
    try {
        $id_doctor = (int)$_GET['id_doctor'];
        $semana = $_GET['semana'] ?? date('Y-m-d'); // Fecha de referencia para la semana
        
        // Obtener sucursal del contexto (o usar la primera disponible)
        $id_sucursal = !empty($_GET['id_sucursal']) ? (int)$_GET['id_sucursal'] : null;
        
        if (!$id_sucursal) {
            // Obtener la primera sucursal donde trabaja este doctor
            $sucursales_doctor = $this->doctoresModel->obtenerSucursales($id_doctor);
            if (empty($sucursales_doctor)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Doctor no tiene sucursales asignadas'
                ]);
                return;
            }
            $id_sucursal = $sucursales_doctor[0]['id_sucursal'];
        }
        
        // Calcular inicio de la semana (lunes)
        $fecha_referencia = new DateTime($semana);
        $dia_semana = $fecha_referencia->format('N'); // 1=Lunes, 7=Domingo
        $dias_atras = $dia_semana - 1;
        $fecha_referencia->sub(new DateInterval('P' . $dias_atras . 'D'));
        $fecha_inicio = $fecha_referencia->format('Y-m-d');
        
        // Usar el nuevo modelo de horarios
        require_once __DIR__ . '/../../modelos/DoctorHorarios.php';
        $doctorHorarios = new DoctorHorarios();
        
        $datos_horarios = $doctorHorarios->obtenerHorariosSemanales(
            $id_doctor, 
            $id_sucursal, 
            $fecha_inicio
        );
        
        $this->responderJSON([
            'success' => true,
            'data' => $datos_horarios
        ]);
        
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
   // ===== MÉTODOS DE RESPUESTA =====
   private function responderJSON($data) {
       // Limpiar cualquier salida previa
       if (ob_get_length()) {
           ob_clean();
       }
       
       header('Content-Type: application/json; charset=utf-8');
       echo json_encode($data, JSON_UNESCAPED_UNICODE);
       exit();
   }
   
   private function redirigir($url) {
       header("Location: $url");
       exit();
   }
}

// Manejar la solicitud si se accede directamente al controlador
if (basename($_SERVER['SCRIPT_NAME']) === 'RecepcionistaController.php') {
   $controller = new RecepcionistaController();
   $controller->manejarSolicitud();
}
?>