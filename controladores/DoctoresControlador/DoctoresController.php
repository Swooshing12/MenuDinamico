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
        
        // Validar datos requeridos
        $camposRequeridos = ['cedula', 'username', 'nombres', 'apellidos', 'sexo', 'nacionalidad', 'correo', 'id_especialidad'];
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
                'nacionalidad' => trim($_POST['nacionalidad']),
                'correo' => trim($_POST['correo']),
                'password' => password_hash($passwordGenerada, PASSWORD_DEFAULT),
                'id_estado' => isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : 1
            ];
            
            // Preparar datos del doctor
            $datosDoctor = [
                'id_especialidad' => (int)$_POST['id_especialidad'],
                'titulo_profesional' => !empty($_POST['titulo_profesional']) ? trim($_POST['titulo_profesional']) : null
            ];
            
            // Obtener sucursales seleccionadas
            $sucursales = isset($_POST['sucursales']) ? $_POST['sucursales'] : [];
            
            // Crear doctor
            $id_doctor = $this->doctoresModel->crear($datosUsuario, $datosDoctor, $sucursales);
            
            // En el método crear(), reemplaza esta parte:

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
                    
                    $mensaje = 'Doctor creado exitosamente';
                    if (!empty($sucursales)) {
                        $mensaje .= ' y asignado a ' . count($sucursales) . ' sucursal(es)';
                    }
                    $mensaje .= '. Credenciales enviadas por correo.';
                    
                } catch (Exception $e) {
                    if ($this->debug) {
                        error_log("Error enviando correo: " . $e->getMessage());
                    }
                    
                    // Si falla el correo, aún es éxito pero informar
                    $mensaje = 'Doctor creado exitosamente';
                    if (!empty($sucursales)) {
                        $mensaje .= ' y asignado a ' . count($sucursales) . ' sucursal(es)';
                    }
                    $mensaje .= ', pero hubo un problema enviando el correo. Credenciales: Usuario: ' . $datosUsuario['username'] . ', Contraseña: ' . $passwordGenerada;
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => $mensaje,
                    'id' => $id_doctor
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
        
        // Validar datos requeridos
        $camposRequeridos = ['cedula', 'username', 'nombres', 'apellidos', 'sexo', 'nacionalidad', 'correo', 'id_especialidad'];
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
            if ($this->doctoresModel->existeUsuarioPorCedula($_POST['cedula'], $doctorActual['id_usuario'])) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe otro usuario con esa cédula'
                ]);
                return;
            }
            
            if ($this->doctoresModel->existeUsuarioPorUsername($_POST['username'], $doctorActual['id_usuario'])) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe otro usuario con ese nombre de usuario'
                ]);
                return;
            }
            
            if ($this->doctoresModel->existeUsuarioPorCorreo($_POST['correo'], $doctorActual['id_usuario'])) {
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
            
            // Obtener sucursales seleccionadas
            $sucursales = isset($_POST['sucursales']) ? $_POST['sucursales'] : [];
            
            // Actualizar doctor
            $resultado = $this->doctoresModel->actualizar($id_doctor, $datosUsuario, $datosDoctor, $sucursales);
            
            if ($resultado) {
                $mensaje = 'Doctor actualizado exitosamente';
                if (!empty($sucursales)) {
                    $mensaje .= ' con ' . count($sucursales) . ' sucursal(es) asignada(s)';
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => $mensaje
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el doctor'
                ]);
            }
            
        } catch (Exception $e) {
            $this->logError("Error editando doctor: " . $e->getMessage(), $_POST);
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
    
    private function obtenerPorId() {
        if (empty($_GET['id'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID requerido'
            ]);
            return;
        }
        
        try {
            $id_doctor = (int)$_GET['id'];
            $doctor = $this->doctoresModel->obtenerPorId($id_doctor);
            
            if ($doctor) {
                $this->responderJSON([
                    'success' => true,
                    'data' => $doctor
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Doctor no encontrado'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
            $id_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
            
            $existe = $this->doctoresModel->existeUsuarioPorCedula($cedula, $id_excluir);
            
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
        if (empty($_GET['username'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Username requerido'
            ]);
            return;
        }
        
        try {
            $username = trim($_GET['username']);
            $id_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
            
            $existe = $this->doctoresModel->existeUsuarioPorUsername($username, $id_excluir);
            
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
            $id_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
            
            $existe = $this->doctoresModel->existeUsuarioPorCorreo($correo, $id_excluir);
            
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