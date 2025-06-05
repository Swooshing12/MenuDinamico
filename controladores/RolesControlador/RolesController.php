<?php
require_once __DIR__ . "/../../modelos/Roles.php";
require_once __DIR__ . "/../../modelos/Menus.php";
require_once __DIR__ . "/../../modelos/Submenus.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Usuario.php";

class RolesController {
    private $rolesModel;
    private $menusModel;
    private $submenusModel;
    private $permisosModel;
    private $usuarioModel;
    private $debug = false; // Activar solo para debugging
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->rolesModel = new Roles();
        $this->menusModel = new Menus();
        $this->submenusModel = new Submenus();
        $this->permisosModel = new Permisos();
        $this->usuarioModel = new Usuario();
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
                case 'obtenerRolesPaginados':
                    $this->obtenerRolesPaginados();
                    break;
                case 'obtenerEstadisticas':
                    $this->obtenerEstadisticas();
                    break;
                case 'obtenerEstructuraPermisos':
                    $this->obtenerEstructuraPermisos();
                    break;
                case 'obtenerPermisosPorRol':
                    $this->obtenerPermisosPorRol();
                    break;
                case 'verificarNombreRol':
                    $this->verificarNombreRol();
                    break;
                case 'verificarUsuariosAsignados':
                    $this->verificarUsuariosAsignados();
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
        // Intentar obtener de POST primero
        $id_submenu = isset($_POST['submenu_id']) ? (int)$_POST['submenu_id'] : null;
        
        // Intentar obtener de GET si no está en POST
        if (!$id_submenu) {
            $id_submenu = isset($_GET['submenu_id']) ? (int)$_GET['submenu_id'] : null;
        }
        
        // Si aún no tenemos ID, usar valor por defecto para gestión de roles
        if (!$id_submenu) {
            $script_name = basename($_SERVER['SCRIPT_NAME']);
            if (strpos($script_name, 'gestionroles') !== false || 
                strpos($_SERVER['REQUEST_URI'], 'gestionroles') !== false) {
                $id_submenu = 16; // ID del submenú "Gestión Roles" - ajustar según tu BD
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
            
            // Obtener datos para la vista
            $roles = $this->rolesModel->obtenerTodos();
            $menus = $this->menusModel->obtenerTodos();
            $submenus = $this->submenusModel->obtenerTodos();
            
            // Pasar datos a la vista
            extract([
                'roles' => $roles,
                'menus' => $menus,
                'submenus' => $submenus,
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestionroles.php';
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
        if (empty($_POST['nombre_rol'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del rol es requerido',
                'campo_error' => 'nombre_rol'
            ]);
            return;
        }
        
        $nombre_rol = trim($_POST['nombre_rol']);
        
        // Validaciones
        if (strlen($nombre_rol) < 3) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del rol debe tener al menos 3 caracteres',
                'campo_error' => 'nombre_rol'
            ]);
            return;
        }
        
        if (strlen($nombre_rol) > 50) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del rol no puede tener más de 50 caracteres',
                'campo_error' => 'nombre_rol'
            ]);
            return;
        }
        
        try {
            // Verificar si el nombre ya existe
            if ($this->rolesModel->existeRolPorNombre($nombre_rol)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un rol con ese nombre',
                    'campo_error' => 'nombre_rol'
                ]);
                return;
            }
            
            // Crear rol
            $id_rol_nuevo = $this->rolesModel->crearRol($nombre_rol);
            
            if ($id_rol_nuevo) {
                // Procesar permisos si se enviaron
                if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
                    foreach ($_POST['permisos'] as $id_submenu => $acciones) {
                        if (!empty($acciones)) {
                            // Asociar submenú al rol
                            $this->rolesModel->asociarSubmenu($id_rol_nuevo, $id_submenu);
                            
                            // Obtener ID de la relación
                            $id_roles_submenus = $this->rolesModel->obtenerIdRolesSubmenus($id_rol_nuevo, $id_submenu);
                            
                            if ($id_roles_submenus) {
                                // Crear permisos específicos
                                $this->rolesModel->crearPermisos($id_roles_submenus, [
                                    'puede_crear' => in_array('crear', $acciones),
                                    'puede_editar' => in_array('editar', $acciones),
                                    'puede_eliminar' => in_array('eliminar', $acciones),
                                ]);
                            }
                        }
                    }
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Rol creado exitosamente',
                    'id_rol' => $id_rol_nuevo
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al crear el rol'
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
        if (empty($_POST['id_rol']) || empty($_POST['nombre_rol'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Datos incompletos para la edición',
                'campos_faltantes' => ['id_rol', 'nombre_rol']
            ]);
            return;
        }
        
        $id_rol = (int)$_POST['id_rol'];
        $nombre_rol = trim($_POST['nombre_rol']);
        
        // Validaciones
        if (strlen($nombre_rol) < 3) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del rol debe tener al menos 3 caracteres',
                'campo_error' => 'nombre_rol'
            ]);
            return;
        }
        
        try {
            // Verificar si el rol existe
            $rolActual = $this->rolesModel->obtenerPorId($id_rol);
            if (!$rolActual) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El rol que intenta editar no existe'
                ]);
                return;
            }
            
            // Verificar si el nombre ya existe en otro rol
            if ($this->rolesModel->existeRolPorNombre($nombre_rol)) {
                $rolExistente = $this->rolesModel->obtenerPorNombre($nombre_rol);
                if ($rolExistente && $rolExistente['id_rol'] != $id_rol) {
                    $this->responderJSON([
                        'success' => false,
                        'message' => 'Ya existe otro rol con ese nombre',
                        'campo_error' => 'nombre_rol'
                    ]);
                    return;
                }
            }
            
            // Editar rol
            $resultado = $this->rolesModel->editarRol($id_rol, $nombre_rol);
            
            if ($resultado) {
                // Actualizar permisos si se enviaron
                if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
                    $this->rolesModel->actualizarPermisosRol($id_rol, $_POST['permisos']);
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Rol actualizado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el rol'
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
        
        if (empty($_POST['id_rol'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de rol requerido'
            ]);
            return;
        }
        
        try {
            $id_rol = (int)$_POST['id_rol'];
            
            // Verificar que el rol exista
            $rol = $this->rolesModel->obtenerPorId($id_rol);
            if (!$rol) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El rol no existe'
                ]);
                return;
            }
            
            // Verificar que no sea el rol del usuario actual
            if ($id_rol == $_SESSION['id_rol']) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio rol'
                ]);
                return;
            }
            
            // Verificar si hay usuarios asignados a este rol
            $usuariosConRol = $this->usuarioModel->contarUsuariosPorRol($id_rol);
            if ($usuariosConRol > 0) {
                $this->responderJSON([
                    'success' => false,
                    'message' => "No se puede eliminar el rol porque tiene {$usuariosConRol} usuario(s) asignado(s). Reasigne los usuarios antes de eliminar.",
                    'usuarios_asignados' => $usuariosConRol
                ]);
                return;
            }
            
            $resultado = $this->rolesModel->eliminarRol($id_rol);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Rol eliminado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al eliminar el rol'
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
        try {
            $roles = $this->rolesModel->obtenerTodos();
            
            // Agregar información adicional a cada rol
            foreach ($roles as &$rol) {
                $rol['usuarios_asignados'] = $this->usuarioModel->contarUsuariosPorRol($rol['id_rol']);
                $rol['permisos_count'] = $this->rolesModel->contarPermisosPorRol($rol['id_rol']);
            }
            
            $this->responderJSON([
                'success' => true, 
                'data' => $roles,
                'count' => count($roles)
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerRolesPaginados() {
        // Obtener parámetros de paginación
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        
        // Debug de parámetros recibidos
        if ($this->debug) {
            error_log("DEBUG obtenerRolesPaginados: pagina=$pagina, limit=$limit, busqueda='$busqueda'");
        }
        
        // Calcular offset
        $inicio = ($pagina - 1) * $limit;
        
        try {
            // Contar total de registros según búsqueda
            $totalRegistros = $this->rolesModel->contarRoles($busqueda);
            
            // Calcular total de páginas (mínimo 1)
            $totalPaginas = $totalRegistros > 0 ? ceil($totalRegistros / $limit) : 1;
            
            // Asegurar que la página actual sea válida
            if ($pagina > $totalPaginas) {
                $pagina = $totalPaginas;
                $inicio = ($pagina - 1) * $limit;
            }
            
            // Obtener roles paginados con búsqueda
            $roles = $this->rolesModel->obtenerRolesPaginados($inicio, $limit, $busqueda);
            
            // Agregar información adicional a cada rol
            foreach ($roles as &$rol) {
                $rol['usuarios_asignados'] = $this->usuarioModel->contarUsuariosPorRol($rol['id_rol']);
                $rol['permisos_count'] = $this->rolesModel->contarPermisosPorRol($rol['id_rol']);
                $rol['fecha_creacion_formatted'] = date('d/m/Y H:i', strtotime($rol['fecha_creacion'] ?? 'now'));
            }
            
            // Debug de resultados
            if ($this->debug) {
                error_log("DEBUG resultados: totalRegistros=$totalRegistros, rolesEncontrados=" . count($roles) . ", busqueda='$busqueda'");
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $roles,
                'totalRegistros' => $totalRegistros,
                'mostrando' => count($roles),
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda
            ]);
        } catch (Exception $e) {
            $this->logError("Error obteniendo roles paginados: " . $e->getMessage(), [
                'pagina' => $pagina, 
                'limit' => $limit,
                'busqueda' => $busqueda
            ]);
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerEstadisticas() {
        try {
            $estadisticas = [
                'total_roles' => $this->rolesModel->contarRoles(),
                'roles_con_usuarios' => $this->rolesModel->contarRolesConUsuarios(),
                'permisos_asignados' => $this->rolesModel->contarPermisosAsignados()
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
    
    private function obtenerEstructuraPermisos() {
        try {
            $menus = $this->menusModel->obtenerTodos();
            $estructura = [];
            
            foreach ($menus as $menu) {
                $submenus = $this->submenusModel->obtenerPorMenu($menu['id_menu']);
                $estructura[] = [
                    'id_menu' => $menu['id_menu'],
                    'nombre_menu' => $menu['nombre_menu'],
                    'submenus' => $submenus
                ];
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $estructura
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerPermisosPorRol() {
        $id_rol = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : 0;
        
        if (!$id_rol) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de rol requerido'
            ]);
            return;
        }
        
        try {
            $permisos = $this->rolesModel->obtenerPermisosPorRol($id_rol);
            
            $this->responderJSON([
                'success' => true,
                'data' => $permisos
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function verificarNombreRol() {
        $nombre_rol = $_GET['nombre_rol'] ?? '';
        $id_rol = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : 0;
        
        if (empty($nombre_rol)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Nombre de rol requerido'
            ]);
            return;
        }
        
        try {
            $existe = $this->rolesModel->existeRolPorNombre($nombre_rol);
            
            if ($existe && $id_rol > 0) {
                $rolExistente = $this->rolesModel->obtenerPorNombre($nombre_rol);
                $existe = $rolExistente && $rolExistente['id_rol'] != $id_rol;
            }
            
            $this->responderJSON([
                'success' => true,
                'existe' => $existe,
                'message' => $existe ? 'Nombre de rol en uso' : 'Nombre de rol disponible',
                'disponible' => !$existe
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function verificarUsuariosAsignados() {
        $id_rol = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : 0;
        
        if (!$id_rol) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de rol requerido'
            ]);
            return;
        }
        
        try {
            $cantidad = $this->usuarioModel->contarUsuariosPorRol($id_rol);
            
            $this->responderJSON([
                'success' => true,
                'cantidad' => $cantidad,
                'tiene_usuarios' => $cantidad > 0
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
        error_log(date('Y-m-d H:i:s') . " [ERROR] [RolesController] {$mensaje}");
        if (!empty($contexto)) {
            error_log(date('Y-m-d H:i:s') . " [CONTEXT] " . json_encode($contexto));
        }
    }
    
    private function logDebug($mensaje, $contexto = []) {
        if ($this->debug) {
            error_log(date('Y-m-d H:i:s') . " [DEBUG] [RolesController] {$mensaje}");
            if (!empty($contexto)) {
                error_log(date('Y-m-d H:i:s') . " [DEBUG_CONTEXT] " . json_encode($contexto));
            }
        }
    }
}

// Ejecutar controlador si se accede directamente
if (basename($_SERVER['PHP_SELF']) === 'RolesController.php') {
    try {
        $controller = new RolesController();
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