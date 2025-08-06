<?php
require_once __DIR__ . "/../../modelos/Doctores.php";
require_once __DIR__ . "/../../modelos/Usuario.php";
require_once __DIR__ . "/../../modelos/Especialidades.php";
require_once __DIR__ . "/../../modelos/Sucursales.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Submenus.php";
require_once __DIR__ . "/../../config/MailService.php";

class DoctoresController {
    private $doctoresModel;
    private $usuarioModel;
    private $especialidadesModel;
    private $sucursalesModel;
    private $permisosModel;
    private $submenusModel;
    private $mailService;
    private $debug = false;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->doctoresModel = new Doctores();
        $this->usuarioModel = new Usuario();
        $this->especialidadesModel = new Especialidades();
        $this->sucursalesModel = new Sucursales();
        $this->permisosModel = new Permisos();
        $this->submenusModel = new Submenus();
        $this->mailService = new MailService();
    }
    
    public function manejarSolicitud() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../login.php');
            exit();
        }
        
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
        
        try {
            switch ($action) {
                case 'crear':
                    $this->crear();
                    break;
                case 'editar':
                    $this->editar();
                    break;
                case 'eliminar':
                    $this->eliminar();
                    break;
                case 'cambiarEstado':
                    $this->cambiarEstado();
                    break;
                case 'obtenerTodos':
                    $this->obtenerTodos();
                    break;
                case 'obtenerDoctoresPaginados':
                    $this->obtenerDoctoresPaginados();
                    break;
                case 'obtenerPorId':
                    $this->obtenerPorId();
                    break;
                case 'verificarCedula':
                    $this->verificarCedula();
                    break;
                case 'verificarUsername':
                    $this->verificarUsername();
                    break;
                case 'verificarCorreo':
                    $this->verificarCorreo();
                    break;
                case 'obtenerEstadisticas':
                    $this->obtenerEstadisticas();
                    break;
                case 'obtenerEspecialidades':
                    $this->obtenerEspecialidades();
                    break;
                case 'obtenerSucursales':
                    $this->obtenerSucursales();
                    break;
                case 'obtenerSucursalesDoctor':
                    $this->obtenerSucursalesDoctor();
                    break;
                case 'generarPassword':
                    $this->generarPassword();
                    break;
                case 'obtenerHorarios':
                    $this->obtenerHorarios();
                    break;
                 case 'actualizar':
                    $this->actualizar();
                        break;
                case 'obtenerEspecialidadesPorSucursal':
                    $this->obtenerEspecialidadesPorSucursal();
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
    
    // ===== MÉTODOS PRINCIPALES =====
    
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
            
            // Obtener datos para la vista
            $doctores = $this->doctoresModel->obtenerTodos();
            $especialidades = $this->especialidadesModel->obtenerTodas();
            $sucursales = $this->sucursalesModel->obtenerActivas();
            
            // Pasar datos a la vista
            extract([
                'doctores' => $doctores,
                'especialidades' => $especialidades,
                'sucursales' => $sucursales,
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestiondoctores.php';
        } catch (Exception $e) {
            die("Error al cargar la página: " . $e->getMessage());
        }
    }
    
    private function obtenerEspecialidadesPorSucursal() {
        error_log("🔍 === DEBUGGING obtenerEspecialidadesPorSucursal ===");
        
        try {
            $id_sucursal = $_POST['id_sucursal'] ?? '';
            $submenu_id = $_POST['submenu_id'] ?? '';
            
            error_log("🔍 ID Sucursal recibido: " . $id_sucursal);
            error_log("🔍 Submenu ID recibido: " . $submenu_id);
            
            if (empty($id_sucursal)) {
                error_log("❌ ID de sucursal vacío");
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID de sucursal requerido'
                ]);
                return;
            }
            
            // Verificar que el modelo existe
            if (!$this->especialidadesModel) {
                error_log("❌ Modelo de especialidades no inicializado");
                $this->responderJSON([
                    'success' => false,
                    'error' => 'Error de inicialización del modelo'
                ]);
                return;
            }
            
            error_log("✅ Llamando al modelo...");
            $especialidades = $this->especialidadesModel->obtenerEspecialidadesPorSucursal($id_sucursal);
            
            error_log("✅ Especialidades obtenidas: " . count($especialidades));
            error_log("📊 Datos: " . print_r($especialidades, true));
            
            $this->responderJSON([
                'success' => true,
                'data' => $especialidades,
                'total' => count($especialidades),
                'sucursal_id' => $id_sucursal,
                'debug' => [
                    'submenu_id' => $submenu_id,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("❌ Error en obtenerEspecialidadesPorSucursal: " . $e->getMessage());
            error_log("❌ Stack trace: " . $e->getTraceAsString());
            
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener especialidades: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    /**
 * Actualizar doctor con horarios
 */
private function actualizar() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->responderJSON([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    // Verificar permisos
    $this->verificarPermisos('editar');
    
    $id_doctor = isset($_POST['id_doctor']) ? (int)$_POST['id_doctor'] : 0;
    
    if (!$id_doctor) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de doctor requerido'
        ]);
        return;
    }
    
    try {
        // Por ahora, respuesta básica para probar
        $this->responderJSON([
            'success' => true,
            'message' => 'Método actualizar funcionando correctamente',
            'data' => [
                'id_doctor' => $id_doctor,
                'action' => 'actualizar'
            ]
        ]);
        
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
    // Y que exista este método:
/**
 * ✅ OBTENER HORARIOS DE DOCTOR
 */
private function obtenerHorarios() {
    if (!isset($_GET['id_doctor'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de doctor requerido'
        ]);
        return;
    }
    
    try {
        $id_doctor = (int)$_GET['id_doctor'];
        $id_sucursal = isset($_GET['id_sucursal']) ? (int)$_GET['id_sucursal'] : null;
        
        $horarios = $this->doctoresModel->obtenerHorarios($id_doctor, $id_sucursal);
        
        $this->responderJSON([
            'success' => true,
            'data' => $horarios,
            'message' => 'Horarios obtenidos exitosamente'
        ]);
        
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al obtener horarios: ' . $e->getMessage()
        ]);
    }
}
   private function crear() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->responderJSON([
            'success' => false, 
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    // Verificar permisos
    $this->verificarPermisos('crear');
    
    // ✅ AGREGAR id_sucursal a los campos requeridos
    $camposRequeridos = ['cedula', 'username', 'nombres', 'apellidos', 'sexo', 'correo', 'id_especialidad', 'id_sucursal'];
    $camposFaltantes = [];
    
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            $camposFaltantes[] = $campo;
        }
    }
    
    // 🔥 VALIDACIÓN ESPECIAL PARA NACIONALIDAD (select normal o hidden)
    $nacionalidad = !empty($_POST['nacionalidad']) ? $_POST['nacionalidad'] : 
                    (!empty($_POST['nacionalidad_hidden']) ? $_POST['nacionalidad_hidden'] : '');
    
    if (empty($nacionalidad)) {
        $camposFaltantes[] = 'nacionalidad';
    }
    
    if (!empty($camposFaltantes)) {
        $this->responderJSON([
            'success' => false,
            'message' => "Campos requeridos: " . implode(', ', $camposFaltantes)
        ]);
        return;
    }
    
    try {
        // Validar duplicados
        if ($this->doctoresModel->existeUsuarioPorCedula($_POST['cedula'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe un usuario con esa cédula'
            ]);
            return;
        }
        
        if ($this->doctoresModel->existeUsuarioPorUsername($_POST['username'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe un usuario con ese nombre de usuario'
            ]);
            return;
        }
        
        if ($this->doctoresModel->existeUsuarioPorCorreo($_POST['correo'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe un usuario con ese correo electrónico'
            ]);
            return;
        }
        
        // Preparar datos del usuario
        $passwordGenerada = $this->generarPasswordAleatoria();
        $datosUsuario = [
            'cedula' => trim($_POST['cedula']),
            'username' => trim($_POST['username']),
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'sexo' => $_POST['sexo'],
            'nacionalidad' => trim($nacionalidad), // 🔥 USAR LA VARIABLE VALIDADA
            'correo' => trim($_POST['correo']),
            'password' => password_hash($passwordGenerada, PASSWORD_DEFAULT),
            'id_estado' => isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : 1
        ];
        
        // Preparar datos del doctor
        $datosDoctor = [
            'id_especialidad' => (int)$_POST['id_especialidad'],
            'titulo_profesional' => !empty($_POST['titulo_profesional']) ? trim($_POST['titulo_profesional']) : null
        ];
        
        // ✅ CAMBIO PRINCIPAL: Obtener UNA sola sucursal
        $idSucursal = (int)$_POST['id_sucursal'];
        $sucursales = [$idSucursal]; // Convertir a array para compatibilidad con el modelo
        
        // 🕒 OBTENER HORARIOS
        $horariosJson = isset($_POST['horarios']) ? $_POST['horarios'] : '[]';
        $horarios = json_decode($horariosJson, true);
        
        if ($horarios === null) {
            $horarios = [];
        }
        
        // DEBUG
        error_log("📦 Horarios recibidos: " . $horariosJson);
        error_log("📋 Horarios procesados: " . print_r($horarios, true));
        error_log("🏥 Sucursal seleccionada: " . $idSucursal);
        
        // ✅ VALIDACIÓN CORREGIDA: Validar que tenga sucursal
        if (empty($idSucursal) || $idSucursal <= 0) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Debe seleccionar una sucursal válida'
            ]);
            return;
        }
        
        // Crear doctor con horarios
        $id_doctor = $this->doctoresModel->crearConHorarios($datosUsuario, $datosDoctor, $sucursales, $horarios);
        
        if ($id_doctor) {
            // Enviar credenciales por correo
            try {
                $nombreCompleto = trim($datosUsuario['nombres']) . ' ' . trim($datosUsuario['apellidos']);
                $this->enviarCredencialesPorCorreo(
                    $datosUsuario['correo'], 
                    $nombreCompleto, 
                    $datosUsuario['username'], 
                    $passwordGenerada
                );
                
                // ✅ MENSAJE CORREGIDO para una sola sucursal
                $mensaje = 'Doctor creado exitosamente y asignado a la sucursal seleccionada';
                if (!empty($horarios)) {
                    $mensaje .= ' con ' . count($horarios) . ' horario(s) configurado(s)';
                }
                $mensaje .= '. Credenciales enviadas por correo.';
                
            } catch (Exception $e) {
                if ($this->debug) {
                    error_log("Error enviando correo: " . $e->getMessage());
                }
                
                // Si falla el correo, aún es éxito pero informar
                $mensaje = 'Doctor creado exitosamente y asignado a la sucursal seleccionada';
                if (!empty($horarios)) {
                    $mensaje .= ' con ' . count($horarios) . ' horario(s) configurado(s)';
                }
                $mensaje .= ', pero hubo un problema enviando el correo. Credenciales: Usuario: ' . $datosUsuario['username'] . ', Contraseña: ' . $passwordGenerada;
            }
            
            $this->responderJSON([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'id_doctor' => $id_doctor,
                    'id_sucursal' => $idSucursal,
                    'total_horarios' => count($horarios),
                    'username' => $datosUsuario['username'],
                    'password_temporal' => $passwordGenerada
                ]
            ]);
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al crear el doctor'
            ]);
        }
        
    } catch (Exception $e) {
        $this->logError("Error creando doctor: " . $e->getMessage(), $_POST);
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al crear el doctor: ' . $e->getMessage()
        ]);
    }
}
   /**
 * ✅ EDITAR DOCTOR - MÉTODO CORREGIDO PARA SUCURSAL ÚNICA
 */
private function editar() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->responderJSON([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    // Verificar permisos
    $this->verificarPermisos('editar');
    
    // Validar ID
    if (empty($_POST['id_doctor'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de doctor requerido'
        ]);
        return;
    }
    
    $id_doctor = (int)$_POST['id_doctor'];
    
    // ✅ AGREGAR id_sucursal a los campos requeridos
    $camposRequeridos = ['cedula', 'username', 'nombres', 'apellidos', 'sexo', 'nacionalidad', 'correo', 'id_especialidad', 'id_sucursal'];
    $camposFaltantes = [];
    
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            $camposFaltantes[] = $campo;
        }
    }
    
    if (!empty($camposFaltantes)) {
        $this->responderJSON([
            'success' => false,
            'message' => "Campos requeridos faltantes: " . implode(', ', $camposFaltantes)
        ]);
        return;
    }
    
    try {
        // Obtener datos actuales del doctor
        $doctorActual = $this->doctoresModel->obtenerPorId($id_doctor);
        if (!$doctorActual) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Doctor no encontrado'
            ]);
            return;
        }
        
        // Validar duplicados (excluyendo el usuario actual)
        if ($this->usuarioModel->existeUsuarioPorUsername($_POST['username'], $doctorActual['id_usuario'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe otro usuario con ese nombre de usuario'
            ]);
            return;
        }
        
        if ($this->usuarioModel->existeUsuarioPorCorreo($_POST['correo'], $doctorActual['id_usuario'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe otro usuario con ese correo electrónico'
            ]);
            return;
        }
        
        // Preparar datos del usuario
        $datosUsuario = [
            'cedula' => trim($_POST['cedula']),
            'username' => trim($_POST['username']),
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'sexo' => $_POST['sexo'],
            'nacionalidad' => trim($_POST['nacionalidad']),
            'correo' => trim($_POST['correo']),
            'id_estado' => isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : 1
        ];
        
        // Preparar datos del doctor
        $datosDoctor = [
            'id_especialidad' => (int)$_POST['id_especialidad'],
            'titulo_profesional' => !empty($_POST['titulo_profesional']) ? trim($_POST['titulo_profesional']) : null
        ];
        
        // ✅ CAMBIO PRINCIPAL: Obtener UNA sola sucursal
        $idSucursal = (int)$_POST['id_sucursal'];
        $sucursales = [$idSucursal]; // Convertir a array para compatibilidad con el modelo
        
        // ✅ PROCESAR HORARIOS - FORMATO JSON (igual que en crear)
        $horarios = [];
        if (isset($_POST['horarios'])) {
            $horariosJson = $_POST['horarios'];
            $horariosDecodificados = json_decode($horariosJson, true);
            
            if ($horariosDecodificados !== null && is_array($horariosDecodificados)) {
                $horarios = $horariosDecodificados;
            }
        }
        
        // ✅ VALIDACIÓN CORREGIDA: Validar que tenga sucursal
        if (empty($idSucursal) || $idSucursal <= 0) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Debe seleccionar una sucursal válida'
            ]);
            return;
        }
        
        if ($this->debug) {
            error_log("🔄 Actualizando doctor ID: $id_doctor");
            error_log("Datos usuario: " . json_encode($datosUsuario));
            error_log("Datos doctor: " . json_encode($datosDoctor));
            error_log("🏥 Sucursal seleccionada: " . $idSucursal);
            error_log("🕒 Horarios recibidos: " . ($horariosJson ?? 'ninguno'));
            error_log("📋 Horarios procesados: " . json_encode($horarios));
        }
        
        // ✅ ACTUALIZAR DOCTOR CON HORARIOS
        $resultado = $this->doctoresModel->actualizarConHorarios($id_doctor, $datosUsuario, $datosDoctor, $sucursales, $horarios);
        
        if ($resultado) {
            // ✅ MENSAJE CORREGIDO para una sola sucursal
            $mensaje = 'Doctor actualizado exitosamente y asignado a la sucursal seleccionada';
            if (!empty($horarios)) {
                $mensaje .= ' con ' . count($horarios) . ' horario(s) configurado(s)';
            }
            
            $this->responderJSON([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'id_doctor' => $id_doctor,
                    'id_sucursal' => $idSucursal,
                    'total_horarios' => count($horarios)
                ]
            ]);
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al actualizar el doctor'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("❌ Error editando doctor: " . $e->getMessage());
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al actualizar el doctor: ' . $e->getMessage()
        ]);
    }
}
    
    private function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        // Verificar permisos
        $this->verificarPermisos('eliminar');
        
        // Validar ID
        if (empty($_POST['id'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de doctor requerido'
            ]);
            return;
        }
        
        try {
            $id_doctor = (int)$_POST['id'];
            
            // Verificar si puede ser eliminado
            if (!$this->doctoresModel->puedeEliminar($id_doctor)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar el doctor porque tiene citas programadas o historial médico activo'
                ]);
                return;
            }
            
            // Eliminar (soft delete)
            $resultado = $this->doctoresModel->eliminar($id_doctor);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Doctor eliminado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al eliminar el doctor'
                ]);
            }
            
        } catch (Exception $e) {
            $this->logError("Error eliminando doctor: " . $e->getMessage(), $_POST);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al eliminar el doctor: ' . $e->getMessage()
            ]);
        }
    }
    
    private function cambiarEstado() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        // Verificar permisos
        $this->verificarPermisos('editar');
        
        // Validar datos
        if (empty($_POST['id']) || !isset($_POST['estado'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID y estado requeridos'
            ]);
            return;
        }
        
        try {
            $id_doctor = (int)$_POST['id'];
            $nuevo_estado = (int)$_POST['estado'];
            
            $resultado = $this->doctoresModel->cambiarEstado($id_doctor, $nuevo_estado);
            
            if ($resultado) {
                $estado_texto = match($nuevo_estado) {
                    1 => 'activado',
                    2 => 'bloqueado',
                    3 => 'puesto en estado pendiente',
                    4 => 'desactivado',
                    default => 'actualizado'
                };
                
                $this->responderJSON([
                    'success' => true,
                    'message' => "Doctor {$estado_texto} exitosamente"
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al cambiar el estado'
                ]);
            }
            
        } catch (Exception $e) {
            $this->logError("Error cambiando estado: " . $e->getMessage(), $_POST);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== MÉTODOS DE CONSULTA =====
    
    private function obtenerTodos() {
        try {
            $filtros = [];
            
            if (isset($_GET['estado']) && $_GET['estado'] !== '') {
                $filtros['estado'] = $_GET['estado'];
            }
            
            if (isset($_GET['especialidad']) && $_GET['especialidad'] !== '') {
                $filtros['id_especialidad'] = $_GET['especialidad'];
            }
            
            if (isset($_GET['sucursal']) && $_GET['sucursal'] !== '') {
                $filtros['id_sucursal'] = $_GET['sucursal'];
            }
            
            $doctores = $this->doctoresModel->obtenerTodos($filtros);
            
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

    
    
    
    private function obtenerDoctoresPaginados() {
        try {
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $busqueda = $_GET['busqueda'] ?? '';
            
            // Filtros
            $filtros = [];
            if (isset($_GET['estado']) && $_GET['estado'] !== '') {
                $filtros['estado'] = (int)$_GET['estado'];
            }
            if (isset($_GET['especialidad']) && $_GET['especialidad'] !== '') {
                $filtros['id_especialidad'] = (int)$_GET['especialidad'];
            }
            if (isset($_GET['sucursal']) && $_GET['sucursal'] !== '') {
                $filtros['id_sucursal'] = (int)$_GET['sucursal'];
            }
            
            $inicio = ($pagina - 1) * $limit;
            
            // Obtener doctores paginados
            $doctores = $this->doctoresModel->obtenerPaginados($inicio, $limit, $busqueda, $filtros);
            $totalRegistros = $this->doctoresModel->contar($busqueda, $filtros);
            $totalPaginas = ceil($totalRegistros / $limit);
            
            // Debug
            if ($this->debug) {
                error_log("DEBUG: totalRegistros=$totalRegistros, doctoresEncontrados=" . count($doctores) . ", busqueda='$busqueda'");
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $doctores,
                'totalRegistros' => $totalRegistros,
                'mostrando' => count($doctores),
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda
            ]);
        } catch (Exception $e) {
            $this->logError("Error obteniendo doctores paginados: " . $e->getMessage(), [
                'pagina' => $pagina ?? 0, 
                'limit' => $limit ?? 0,
                'busqueda' => $busqueda ?? ''
            ]);
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener doctores: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
 * ✅ OBTENER DOCTOR POR ID - MÉTODO CORREGIDO
 */
private function obtenerPorId() {
    if (!isset($_GET['id'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de doctor requerido'
        ]);
        return;
    }
    
    try {
        $id_doctor = (int)$_GET['id'];
        
        if ($this->debug) {
            error_log("🔍 Obteniendo doctor por ID: $id_doctor");
        }
        
        $doctor = $this->doctoresModel->obtenerPorId($id_doctor);
        
        if ($doctor) {
            // Obtener horarios del doctor
            try {
                $horarios = $this->doctoresModel->obtenerHorarios($id_doctor);
                $doctor['horarios'] = $horarios;
            } catch (Exception $e) {
                error_log("Warning: No se pudieron cargar horarios para doctor $id_doctor: " . $e->getMessage());
                $doctor['horarios'] = [];
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $doctor,
                'message' => 'Doctor encontrado'
            ]);
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Doctor no encontrado'
            ]);
        }
        
    } catch (Exception $e) {
        if ($this->debug) {
            error_log("❌ Error en obtenerPorId: " . $e->getMessage());
        }
        
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al obtener el doctor: ' . $e->getMessage()
        ]);
    }
}
    
    // ===== MÉTODOS DE VALIDACIÓN =====
    
    private function verificarCedula() {
    if (empty($_GET['cedula'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Cédula requerida'
        ]);
        return;
    }
    
    try {
        $cedula = trim($_GET['cedula']);
        $id_doctor_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
        
        $existe = $this->doctoresModel->existeCedulaExcluyendoDoctor($cedula, $id_doctor_excluir);
        
        $this->responderJSON([
            'success' => true,
            'existe' => $existe
        ]);
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

private function verificarUsername() {
    $username = $_GET['username'] ?? '';
    $id_doctor_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
    
    if (empty($username)) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Username requerido'
        ]);
        return;
    }
    
    try {
        $existe = $this->doctoresModel->existeUsernameExcluyendoDoctor($username, $id_doctor_excluir);
        
        $this->responderJSON([
            'success' => true,
            'disponible' => !$existe,
            'existe' => $existe,
            'username' => $username
        ]);
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

private function verificarCorreo() {
    if (empty($_GET['correo'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Correo requerido'
        ]);
        return;
    }
    
    try {
        $correo = trim($_GET['correo']);
        $id_doctor_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
        
        $existe = $this->doctoresModel->existeCorreoExcluyendoDoctor($correo, $id_doctor_excluir);
        
        $this->responderJSON([
            'success' => true,
            'existe' => $existe
        ]);
    } catch (Exception $e) {
        $this->responderJSON([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
    
    // ===== MÉTODOS DE DATOS =====
    
    private function obtenerEstadisticas() {
        try {
            $estadisticas = $this->doctoresModel->obtenerEstadisticas();
            
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
    
    private function obtenerEspecialidades() {
        try {
            $especialidades = $this->especialidadesModel->obtenerTodas();
            
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
    
    private function obtenerSucursales() {
        try {
            $sucursales = $this->sucursalesModel->obtenerActivas();
            
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
    
    private function obtenerSucursalesDoctor() {
        if (empty($_GET['id_doctor'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de doctor requerido'
            ]);
            return;
        }
        
        try {
            $id_doctor = (int)$_GET['id_doctor'];
            $sucursales = $this->doctoresModel->obtenerSucursales($id_doctor);
            
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
    
    // ===== MÉTODOS AUXILIARES CORREGIDOS =====

private function generarPassword() {
    // Usar el método estático de MailService
    $password = MailService::generarPasswordTemporal();
    
    $this->responderJSON([
        'success' => true,
        'password' => $password
    ]);
}

private function generarPasswordAleatoria($longitud = 12) {
    // Usar el método del MailService
    return MailService::generarPasswordTemporal($longitud);
}

private function enviarCredencialesPorCorreo($correo, $nombres, $username, $password) {
    try {
        // Usar tu MailService existente con el método correcto
        $nombreCompleto = $nombres; // Ya viene completo desde el controlador
        
        $resultado = $this->mailService->enviarPasswordTemporal(
            $correo,
            $nombreCompleto,
            $username,
            $password
        );
        
        if ($resultado) {
            if ($this->debug) {
                error_log("✅ Credenciales enviadas exitosamente a: $correo");
            }
            return true;
        } else {
            if ($this->debug) {
                error_log("❌ Error enviando credenciales a: $correo");
            }
            return false;
        }
        
    } catch (Exception $e) {
        if ($this->debug) {
            error_log("❌ Excepción enviando credenciales: " . $e->getMessage());
        }
        return false;
    }
}

    
    private function verificarPermisos($accion) {
        if (!isset($_SESSION['id_rol'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Sesión no válida'
            ]);
            exit();
        }
        
        $id_rol = $_SESSION['id_rol'];
        $id_submenu = $this->obtenerIdSubmenu();
        
        if (!$id_submenu) {
            $this->responderJSON([
                'success' => false,
                'message' => 'No se pudo determinar el submenú' 
                ]);
           exit();
       }
       
       $permisos = $this->permisosModel->obtenerPermisos($id_rol, $id_submenu);
       
       if (!$permisos) {
           $this->responderJSON([
               'success' => false,
               'message' => 'Sin permisos para acceder'
           ]);
           exit();
       }
       
       $campo_permiso = "puede_$accion";
       if (!isset($permisos[$campo_permiso]) || !$permisos[$campo_permiso]) {
           $this->responderJSON([
               'success' => false,
               'message' => "Sin permisos para $accion"
           ]);
           exit();
       }
   }
   
   private function obtenerIdSubmenu() {
       // Intentar obtener de POST primero
       $id_submenu = isset($_POST['submenu_id']) ? (int)$_POST['submenu_id'] : null;
       
       // Intentar obtener de GET si no está en POST
       if (!$id_submenu) {
           $id_submenu = isset($_GET['submenu_id']) ? (int)$_GET['submenu_id'] : null;
       }
       
       // Si aún no tenemos ID, usar valor por defecto para gestión de doctores
       if (!$id_submenu) {
           $script_name = basename($_SERVER['SCRIPT_NAME']);
           if (strpos($script_name, 'gestiondoctores') !== false || 
               strpos($_SERVER['REQUEST_URI'], 'gestiondoctores') !== false) {
               // Necesitarás crear este submenú en tu BD
               $id_submenu = 31; // ID del submenú "Gestión Doctores" - ajustar según tu BD
           }
       }
       
       return $id_submenu;
   }

   
   
   private function logError($mensaje, $datos = []) {
       if ($this->debug) {
           error_log("DOCTORES_ERROR: $mensaje");
           if (!empty($datos)) {
               error_log("DATOS: " . json_encode($datos));
           }
       }
   }
   
   private function responderJSON($data) {
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
if (basename($_SERVER['SCRIPT_NAME']) === 'DoctoresController.php') {
   $controller = new DoctoresController();
   $controller->manejarSolicitud();
}
?>