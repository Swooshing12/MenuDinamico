<?php
require_once __DIR__ . "/../../modelos/Usuario.php";
require_once __DIR__ . "/../../modelos/Roles.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Submenus.php";

class UsuariosController {
    private $usuarioModel;
    private $rolesModel;
    private $permisosModel;
    private $submenusModel;
    private $debug = false; // Desactivar debug en producción
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->usuarioModel = new Usuario();
        $this->rolesModel = new Roles();
        $this->permisosModel = new Permisos();
        $this->submenusModel = new Submenus();
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
                case 'obtenerTodos':
                    $this->obtenerTodos();
                    break;
                case 'obtenerUsuariosPaginados':
                    $this->obtenerUsuariosPaginados();
                    break;
                case 'buscarPorCedula':
                    $this->buscarPorCedula();
                    break;
                case 'verificarUsername':
                    $this->verificarUsername();
                    break;
                case 'verificarCorreo':
                    $this->verificarCorreo();
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
            throw new Exception("No se pudo determinar el submenú para verificar permisos");
        }
        
        $permisos = $this->permisosModel->obtenerPermisos($id_rol, $id_submenu);
        
        if (!$permisos) {
            $this->responderJSON([
                'success' => false,
                'message' => 'No tienes acceso a este módulo'
            ]);
            exit();
        }
        
        $puede = match ($accion) {
            'crear' => $permisos['puede_crear'] ?? false,
            'editar' => $permisos['puede_editar'] ?? false,
            'eliminar' => $permisos['puede_eliminar'] ?? false,
            default => true, // Ver siempre permitido
        };
        
        if (!$puede) {
            $this->responderJSON([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ]);
            exit();
        }
        
        return $permisos;
    }
    
    private function obtenerIdSubmenu() {
        // Intentar obtener de POST primero (prioridad)
        $id_submenu = isset($_POST['submenu_id']) ? (int)$_POST['submenu_id'] : null;
        
        // Intentar obtener de GET si no está en POST
        if (!$id_submenu) {
            $id_submenu = isset($_GET['submenu_id']) ? (int)$_GET['submenu_id'] : null;
        }
        
        // Si aún no tenemos ID, usar valor por defecto para gestionusuarios
        if (!$id_submenu) {
            $script_name = basename($_SERVER['SCRIPT_NAME']);
            if (strpos($script_name, 'gestionusuarios') !== false || 
                strpos($_SERVER['REQUEST_URI'], 'gestionusuarios') !== false) {
                $id_submenu = 18; // ID del submenú "Gestión Usuarios"
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
            
            // Obtener filtro
            $filtro = $_GET['filtro'] ?? 'todos';
            $estadoF = match ($filtro) {
                'activos'    => 1,
                'inactivos'  => 4,
                'bloqueados' => 2,
                'pendientes' => 3,
                default      => null,
            };
            
            // Obtener datos para la vista
            $usuarios = $this->usuarioModel->obtenerTodos($estadoF);
            $roles = $this->rolesModel->obtenerTodos();
            
            // Crear instancia de rolesModel para la vista
            $rolesModel = $this->rolesModel;
            
            // Pasar datos a la vista
            extract([
                'usuarios' => $usuarios,
                'roles' => $roles,
                'rolesModel' => $rolesModel,
                'permisos' => $permisos,
                'filtro' => $filtro,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestionusuarios.php';
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
        $camposRequeridos = ['cedula', 'username', 'nombres', 'apellidos', 'sexo', 'nacionalidad', 'correo', 'password', 'rol'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($_POST[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            $this->responderJSON([
                'success' => false,
                'message' => "Campos requeridos: " . implode(', ', $camposFaltantes),
                'campos_faltantes' => $camposFaltantes
            ]);
            return;
        }
        
        // Validaciones básicas
        if (!is_numeric($_POST['cedula']) || strlen($_POST['cedula']) < 10) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La cédula debe tener al menos 10 dígitos numéricos',
                'campo_error' => 'cedula'
            ]);
            return;
        }
        
        if (!filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Formato de correo electrónico no válido',
                'campo_error' => 'correo'
            ]);
            return;
        }
        
        if (strlen($_POST['password']) < 6) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres',
                'campo_error' => 'password'
            ]);
            return;
        }
        
        try {
            // Verificar si el username ya existe
            $usuarioExistente = $this->usuarioModel->obtenerPorUsername(trim($_POST['username']));
            if ($usuarioExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El nombre de usuario ya está en uso',
                    'campo_error' => 'username'
                ]);
                return;
            }
            
            // Verificar si el correo ya existe
            $correoExistente = $this->usuarioModel->obtenerPorCorreo(trim($_POST['correo']));
            if ($correoExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado',
                    'campo_error' => 'correo'
                ]);
                return;
            }
            
            // Crear usuario
            $resultado = $this->usuarioModel->crearUsuario(
                (int)$_POST['cedula'],
                trim($_POST['username']),
                trim($_POST['nombres']),
                trim($_POST['apellidos']),
                $_POST['sexo'],
                trim($_POST['nacionalidad']),
                trim($_POST['correo']),
                $_POST['password'],
                (int)$_POST['rol']
            );
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al crear el usuario'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
        
        // Validar datos requeridos
        $camposRequeridos = ['id_usuario', 'cedula', 'username', 'nombres', 'apellidos', 'sexo', 'nacionalidad', 'correo', 'rol', 'estado'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (!isset($_POST[$campo]) || $_POST[$campo] === '') {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            $this->responderJSON([
                'success' => false,
                'message' => "Campos requeridos: " . implode(', ', $camposFaltantes),
                'campos_faltantes' => $camposFaltantes
            ]);
            return;
        }
        
        // Validaciones básicas
        if (!is_numeric($_POST['cedula']) || strlen($_POST['cedula']) < 10) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La cédula debe tener al menos 10 dígitos numéricos',
                'campo_error' => 'cedula'
            ]);
            return;
        }
        
        if (!filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Formato de correo electrónico no válido',
                'campo_error' => 'correo'
            ]);
            return;
        }
        
        try {
            $id_usuario = (int)$_POST['id_usuario'];
            
            // Verificar si el usuario existe
            $usuarioActual = $this->usuarioModel->obtenerPorId($id_usuario);
            if (!$usuarioActual) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El usuario que intenta editar no existe'
                ]);
                return;
            }
            
            // Verificar si el username ya existe en otro usuario
            $usuarioExistente = $this->usuarioModel->obtenerPorUsername(trim($_POST['username']));
            if ($usuarioExistente && $usuarioExistente['id_usuario'] != $id_usuario) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El nombre de usuario ya está en uso por otro usuario',
                    'campo_error' => 'username'
                ]);
                return;
            }
            
            // Verificar si el correo ya existe en otro usuario
            $correoExistente = $this->usuarioModel->obtenerPorCorreo(trim($_POST['correo']));
            if ($correoExistente && $correoExistente['id_usuario'] != $id_usuario) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado por otro usuario',
                    'campo_error' => 'correo'
                ]);
                return;
            }
            
            // Editar usuario
            $resultado = $this->usuarioModel->editarUsuario(
                $id_usuario,
                (int)$_POST['cedula'],
                trim($_POST['username']),
                trim($_POST['nombres']),
                trim($_POST['apellidos']),
                $_POST['sexo'],
                trim($_POST['nacionalidad']),
                trim($_POST['correo']),
                (int)$_POST['rol'],
                (int)$_POST['estado']
            );
            
            // Cambiar contraseña si se proporcionó
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 6) {
                    $this->responderJSON([
                        'success' => false,
                        'message' => 'La nueva contraseña debe tener al menos 6 caracteres',
                        'campo_error' => 'password'
                    ]);
                    return;
                }
                
                $this->usuarioModel->cambiarPassword($id_usuario, $_POST['password']);
            }
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Usuario actualizado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el usuario'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
        
        if (empty($_POST['id_usuario'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de usuario requerido'
            ]);
            return;
        }
        
        try {
            $id_usuario = (int)$_POST['id_usuario'];
            
            // Verificar que el usuario exista
            $usuario = $this->usuarioModel->obtenerPorId($id_usuario);
            if (!$usuario) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El usuario no existe'
                ]);
                return;
            }
            
            // Verificar que no sea el mismo usuario logueado
            if (isset($_SESSION['id_usuario']) && $id_usuario == $_SESSION['id_usuario']) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No puedes desactivar tu propia cuenta'
                ]);
                return;
            }
            
            $resultado = $this->usuarioModel->desactivarUsuario($id_usuario);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Usuario desactivado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al desactivar el usuario'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerTodos() {
        // Obtener parámetros
        $filtro = $_GET['filtro'] ?? 'todos';
        $estadoF = match ($filtro) {
            'activos'    => 1,
            'inactivos'  => 4,
            'bloqueados' => 2,
            'pendientes' => 3,
            default      => null,
        };
        
        try {
            $usuarios = $this->usuarioModel->obtenerTodos($estadoF);
            
            // Agregar nombre del rol a cada usuario
            foreach ($usuarios as &$usuario) {
                $rol = $this->rolesModel->obtenerPorId($usuario['id_rol']);
                $usuario['nombre_rol'] = $rol ? $rol['nombre_rol'] : 'Sin rol';
            }
            
            $this->responderJSON([
                'success' => true, 
                'data' => $usuarios,
                'count' => count($usuarios),
                'filtro' => $filtro
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerUsuariosPaginados() {
    // Obtener parámetros de paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $filtro = $_GET['filtro'] ?? 'todos';
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : ''; // ⭐ NUEVO PARÁMETRO
    
    // Debug de parámetros recibidos
    if ($this->debug) {
        error_log("DEBUG obtenerUsuariosPaginados: pagina=$pagina, limit=$limit, filtro=$filtro, busqueda='$busqueda'");
    }
    
    // Determinar estado según filtro
    $estadoF = match ($filtro) {
        'activos'    => 1,
        'inactivos'  => 4,
        'bloqueados' => 2,
        'pendientes' => 3,
        default      => null,
    };
    
    // Calcular offset
    $inicio = ($pagina - 1) * $limit;
    
    try {
        // ⭐ PASAR BÚSQUEDA A LOS MÉTODOS DEL MODELO
        // Contar total de registros según filtro Y búsqueda
        $totalRegistros = $this->usuarioModel->contarUsuarios($estadoF, $busqueda);
        
        // Calcular total de páginas (mínimo 1)
        $totalPaginas = $totalRegistros > 0 ? ceil($totalRegistros / $limit) : 1;
        
        // Asegurar que la página actual sea válida
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
            $inicio = ($pagina - 1) * $limit;
        }
        
        // ⭐ OBTENER usuarios paginados CON búsqueda
        $usuarios = $this->usuarioModel->obtenerUsuariosPaginados($estadoF, $inicio, $limit, $busqueda);
        
        // Agregar nombre del rol a cada usuario para facilitar renderizado
        foreach ($usuarios as &$usuario) {
            $rol = $this->rolesModel->obtenerPorId($usuario['id_rol']);
            $usuario['nombre_rol'] = $rol ? $rol['nombre_rol'] : 'Sin rol';
        }
        
        // Debug de resultados
        if ($this->debug) {
            error_log("DEBUG resultados: totalRegistros=$totalRegistros, usuariosEncontrados=" . count($usuarios) . ", busqueda='$busqueda'");
        }
        
        $this->responderJSON([
            'success' => true,
            'data' => $usuarios,
            'totalRegistros' => $totalRegistros,
            'mostrando' => count($usuarios),
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas,
            'filtro' => $filtro,
            'busqueda' => $busqueda // ⭐ INCLUIR EN LA RESPUESTA
        ]);
    } catch (Exception $e) {
        $this->logError("Error obteniendo usuarios paginados: " . $e->getMessage(), [
            'pagina' => $pagina, 
            'limit' => $limit,
            'filtro' => $filtro,
            'busqueda' => $busqueda // ⭐ INCLUIR EN LOG DE ERROR
        ]);
        
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al obtener usuarios: ' . $e->getMessage()
        ]);
    }
}
    
    private function buscarPorCedula() {
        $cedula = $_GET['cedula'] ?? '';
        
        if (empty($cedula)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Cédula requerida'
            ]);
            return;
        }
        
        if (!is_numeric($cedula)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'La cédula debe contener solo números'
            ]);
            return;
        }
        
        try {
            // Aquí puedes integrar con tu API de cédulas
            // Este es un ejemplo simulado de respuesta exitosa
            $this->responderJSON([
                'estado' => 'OK',
                'resultado' => [
                    [
                        'cedula' => $cedula,
                        'nombre' => 'APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2',
                        'condicionCiudadano' => 'CIUDADANO'
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'estado' => 'ERROR'
            ]);
        }
    }
    
    private function verificarUsername() {
        $username = $_GET['username'] ?? '';
        $id_usuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;
        
        if (empty($username)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Nombre de usuario requerido'
            ]);
            return;
        }
        
        try {
            $usuario = $this->usuarioModel->obtenerPorUsername($username);
            $existe = $usuario && $usuario['id_usuario'] != $id_usuario;
            
            $this->responderJSON([
                'success' => true,
                'existe' => $existe,
                'message' => $existe ? 'Nombre de usuario en uso' : 'Nombre de usuario disponible',
                'disponible' => !$existe
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function verificarCorreo() {
        $correo = $_GET['correo'] ?? '';
        $id_usuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;
        
        if (empty($correo)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Correo electrónico requerido'
            ]);
            return;
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Formato de correo no válido',
                'valido' => false
            ]);
            return;
        }
        
        try {
            $usuario = $this->usuarioModel->obtenerPorCorreo($correo);
            $existe = $usuario && $usuario['id_usuario'] != $id_usuario;
            
            $this->responderJSON([
                'success' => true,
                'existe' => $existe,
                'message' => $existe ? 'Correo en uso' : 'Correo disponible',
                'disponible' => !$existe,
                'valido' => true
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function responderJSON($data) {
        header('Content-Type: application/json; charset=utf-8');
        if (ob_get_length()) ob_clean();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    private function redirigir($url) {
        if (ob_get_length()) ob_clean();
        header("Location: {$url}");
        exit();
    }
    
    private function logError($mensaje, $contexto = []) {
        error_log(date('Y-m-d H:i:s') . " [ERROR] [UsuariosController] {$mensaje}");
    }
    
    private function logDebug($mensaje, $contexto = []) {
        if ($this->debug) {
            error_log(date('Y-m-d H:i:s') . " [DEBUG] [UsuariosController] {$mensaje}");
        }
    }
}

// Ejecutar controlador si se accede directamente
if (basename($_SERVER['PHP_SELF']) === 'UsuariosController.php') {
    try {
        $controller = new UsuariosController();
        $controller->manejarSolicitud();
    } catch (Throwable $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
}
?>