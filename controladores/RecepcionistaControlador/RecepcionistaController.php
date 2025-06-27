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
    private $debug = false; // Activar solo para debugging
    
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
    
    public function manejarSolicitud() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../login.php');
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
                $id_submenu = 28; // Por defecto, usar "Registrar Pacientes"
            }
        }
        
        return $id_submenu;
    }
    
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
            
            // Pasar datos a la vista
            extract([
                'sucursales' => $sucursales,
                'especialidades' => $especialidades,
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
        
        // Validar datos requeridos
        $camposRequeridos = ['id_paciente', 'id_doctor', 'id_sucursal', 'fecha_hora', 'motivo'];
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
            // Verificar disponibilidad del doctor
            $disponible = $this->doctoresModel->verificarDisponibilidad(
                $_POST['id_doctor'],
                $_POST['fecha_hora']
            );
            
            if (!$disponible) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El doctor no está disponible en esa fecha y hora'
                ]);
                return;
            }
            
            // Crear la cita
            $datos_cita = [
                'id_paciente' => (int)$_POST['id_paciente'],
                'id_doctor' => (int)$_POST['id_doctor'],
                'id_sucursal' => (int)$_POST['id_sucursal'],
                'fecha_hora' => $_POST['fecha_hora'],
                'motivo' => trim($_POST['motivo']),
                'notas' => trim($_POST['notas'] ?? ''),
                'estado' => 'Pendiente'
            ];
            
            $id_cita = $this->citasModel->crear($datos_cita);
            
            if ($id_cita) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Cita registrada exitosamente',
                    'data' => ['id_cita' => $id_cita]
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
            $url = "http://localhost/MenuDinamico/obtenerDatos.php?cedula=" . urlencode($cedula);
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
                'message' => 'Error al obtener datos de cédula: ' . $e->getMessage()
            ]);
        }
    }
    
    private function registrarPaciente() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $this->verificarPermisos('crear');
        
        // Validar datos requeridos del usuario
        $camposUsuario = ['cedula', 'nombres', 'apellidos', 'correo', 'sexo', 'nacionalidad'];
        $camposFaltantes = [];
        
        foreach ($camposUsuario as $campo) {
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
            // Verificar si ya existe un usuario con esa cédula
            if ($this->usuarioModel->existeUsuarioPorCedula($_POST['cedula'])) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un usuario registrado con esa cédula'
                ]);
                return;
            }
            
            // Verificar si ya existe un usuario con ese correo
            if ($this->usuarioModel->existeUsuarioPorCorreo($_POST['correo'])) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un usuario registrado con ese correo'
                ]);
                return;
            }
            
            // Generar username único basado en nombres y apellidos
            $username = $this->generarUsername($_POST['nombres'], $_POST['apellidos']);
            
            // Generar password temporal
            $password_temporal = $this->generarPasswordTemporal();
            
            // Buscar ID del rol "Paciente" (asumiendo que existe)
            $rol_paciente = $this->rolesModel->obtenerPorNombre('Paciente');
            
            if (!$rol_paciente) {
                throw new Exception("No se encontró el rol 'Paciente' en el sistema");
            }
            
            // Crear usuario
            $datos_usuario = [
                'cedula' => (int)$_POST['cedula'],
                'username' => $username,
                'nombres' => strtoupper(trim($_POST['nombres'])),
                'apellidos' => strtoupper(trim($_POST['apellidos'])),
                'sexo' => $_POST['sexo'],
                'nacionalidad' => $_POST['nacionalidad'],
                'correo' => trim($_POST['correo']),
                'password' => password_hash($password_temporal, PASSWORD_DEFAULT),
                'id_rol' => $rol_paciente['id_rol'],
                'id_estado' => 1 // Activo
            ];
            
            $id_usuario = $this->usuarioModel->crear($datos_usuario);
            
            if (!$id_usuario) {
                throw new Exception("Error al crear el usuario");
            }
            
            // Crear paciente
            $datos_paciente = [
                'id_usuario' => $id_usuario,
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                'tipo_sangre' => $_POST['tipo_sangre'] ?? null,
                'alergias' => $_POST['alergias'] ?? null,
                'antecedentes_medicos' => $_POST['antecedentes_medicos'] ?? null,
                'contacto_emergencia' => $_POST['contacto_emergencia'] ?? null,
                'telefono_emergencia' => $_POST['telefono_emergencia'] ?? null,
                'numero_seguro' => $_POST['numero_seguro'] ?? null
            ];
            
            $id_paciente = $this->pacientesModel->crear($datos_paciente);
            
            if (!$id_paciente) {
                // Si falla crear paciente, eliminar usuario creado
                $this->usuarioModel->eliminarUsuario($id_usuario);
                throw new Exception("Error al crear el perfil de paciente");
            }
            
            // Crear historial clínico
            $this->pacientesModel->crearHistorialClinico($id_paciente);
            
            $this->responderJSON([
                'success' => true,
                'message' => 'Paciente registrado exitosamente',
                'data' => [
                    'id_usuario' => $id_usuario,
                    'id_paciente' => $id_paciente,
                    'username' => $username,
                    'password_temporal' => $password_temporal
                ]
            ]);
            
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al registrar paciente: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== MÉTODOS PARA OBTENER DATOS RELACIONADOS =====
    
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
    
    // ===== MÉTODOS AUXILIARES =====
    
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


    // ===== AGREGAR ESTOS MÉTODOS AL RecepcionistaController.php =====

// ===== MÉTODOS ADICIONALES PARA GESTIÓN DE CITAS =====

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
    $camposRequeridos = ['id_paciente', 'id_doctor', 'id_sucursal', 'fecha_hora', 'motivo'];
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
            $cita_actual['fecha_hora'] != $_POST['fecha_hora']) {
            
            $disponible = $this->doctoresModel->verificarDisponibilidad(
                $_POST['id_doctor'],
                $_POST['fecha_hora']
            );
            
            if (!$disponible) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El doctor no está disponible en esa fecha y hora'
                ]);
                return;
            }
        }
        
        // Actualizar la cita
        $datos_cita = [
            'id_paciente' => (int)$_POST['id_paciente'],
            'id_doctor' => (int)$_POST['id_doctor'],
            'id_sucursal' => (int)$_POST['id_sucursal'],
            'fecha_hora' => $_POST['fecha_hora'],
            'motivo' => trim($_POST['motivo']),
            'estado' => $_POST['estado'] ?? $cita_actual['estado'],
            'notas' => trim($_POST['notas'] ?? '')
        ];
        
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
        $motivo_cancelacion = trim($_POST['motivo_cancelacion'] ?? 'Cancelada por recepcionista');
        
        // Cambiar estado a "Cancelada"
        $resultado = $this->citasModel->cambiarEstado($id_cita, 'Cancelada');
        
        if ($resultado) {
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
        // Nota: Necesitarás crear el modelo Triage si no existe
        $datos_triaje = [
            'id_cita' => (int)$_POST['id_cita'],
            'id_enfermero' => $_SESSION['id_usuario'], // Usuario actual como enfermero
            'nivel_urgencia' => (int)$_POST['nivel_urgencia'],
            'temperatura' => !empty($_POST['temperatura']) ? (float)$_POST['temperatura'] : null,
            'presion_arterial' => $_POST['presion_arterial'] ?? null,
            'frecuencia_cardiaca' => !empty($_POST['frecuencia_cardiaca']) ? (int)$_POST['frecuencia_cardiaca'] : null,
            'frecuencia_respiratoria' => !empty($_POST['frecuencia_respiratoria']) ? (int)$_POST['frecuencia_respiratoria'] : null,
            'saturacion_oxigeno' => !empty($_POST['saturacion_oxigeno']) ? (int)$_POST['saturacion_oxigeno'] : null,
            'peso' => !empty($_POST['peso']) ? (float)$_POST['peso'] : null,
            'talla' => !empty($_POST['talla']) ? (int)$_POST['talla'] : null,
            'imc' => !empty($_POST['imc']) ? (float)$_POST['imc'] : null,
            'observaciones' => $_POST['observaciones'] ?? null
        ];
        
        // Por ahora, simularemos que se guardó correctamente
        // Cuando tengas el modelo Triage, usar: $this->triageModel->crear($datos_triaje);
        
        $this->responderJSON([
            'success' => true,
            'message' => 'Triaje registrado exitosamente',
            'data' => $datos_triaje
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
    
    $this->verificarPermisos('crear');
    
    if (empty($_POST['id_cita']) || empty($_POST['tipo_notificacion'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de cita y tipo de notificación requeridos'
        ]);
        return;
    }
    
    try {
        $id_cita = (int)$_POST['id_cita'];
        $tipo = $_POST['tipo_notificacion']; // 'recordatorio', 'confirmacion', 'cancelacion'
        
        // Obtener datos de la cita
        $cita = $this->citasModel->obtenerPorId($id_cita);
        if (!$cita) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Cita no encontrada'
            ]);
            return;
        }
        
        // Simular envío de notificación (aquí integrarías con tu sistema de emails/SMS)
        $mensaje = match($tipo) {
            'recordatorio' => "Recordatorio: Tiene una cita médica el " . date('d/m/Y H:i', strtotime($cita['fecha_hora'])),
            'confirmacion' => "Su cita médica ha sido confirmada para el " . date('d/m/Y H:i', strtotime($cita['fecha_hora'])),
            'cancelacion' => "Su cita médica del " . date('d/m/Y H:i', strtotime($cita['fecha_hora'])) . " ha sido cancelada",
            default => "Notificación sobre su cita médica"
        };
        
        $this->responderJSON([
            'success' => true,
            'message' => 'Notificación enviada exitosamente',
            'data' => [
                'tipo' => $tipo,
                'destinatario' => $cita['paciente_correo'],
                'mensaje' => $mensaje
            ]
        ]);
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}


    
    private function obtenerEstadisticas() {
        try {
            // Obtener estadísticas del día
            $fecha_hoy = date('Y-m-d');
            
            $estadisticas = [
                'citas_hoy' => $this->citasModel->contarCitasPorFecha($fecha_hoy),
                'pacientes_nuevos_hoy' => $this->pacientesModel->contarPacientesNuevosPorFecha($fecha_hoy),
                'citas_pendientes' => $this->citasModel->contarCitasPorEstado('Pendiente'),
                'citas_confirmadas' => $this->citasModel->contarCitasPorEstado('Confirmada'),
                'total_pacientes' => $this->pacientesModel->contarTotal()
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
    
    // ===== MÉTODOS AUXILIARES HEREDADOS =====
    
    private function responderJSON($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
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