<?php
require_once __DIR__ . "/../../modelos/Menus.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Submenus.php";

class MenusController {
    private $menuModel;
    private $permisosModel;
    private $submenusModel;
    private $debug = false; // Activar solo para debugging
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->menuModel = new Menus();
        $this->permisosModel = new Permisos();
        $this->submenusModel = new Submenus();
    }
    
    private function obtenerEstadisticas() {
    try {
        $estadisticas = $this->menuModel->obtenerEstadisticas();
        
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
                case 'obtenerMenusPaginados':
                    $this->obtenerMenusPaginados();
                    break;
                case 'obtenerEstadisticas':  // ⭐ NUEVO
                    $this->obtenerEstadisticas();
                    break;
                case 'verificarNombreMenu':
                    $this->verificarNombreMenu();
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
        
        // Si aún no tenemos ID, usar valor por defecto para gestión de menús
        if (!$id_submenu) {
            $script_name = basename($_SERVER['SCRIPT_NAME']);
            if (strpos($script_name, 'gestionmenus') !== false || 
                strpos($_SERVER['REQUEST_URI'], 'gestionmenus') !== false) {
                $id_submenu = 16; // ID del submenú "Gestión Menús" - ajustar según tu BD
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
            $menus = $this->menuModel->obtenerTodos();
            
            // Pasar datos a la vista
            extract([
                'menus' => $menus,
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestionmenus.php';
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
        if (empty($_POST['nombre_menu'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del menú es requerido',
                'campo_error' => 'nombre_menu'
            ]);
            return;
        }
        
        $nombre_menu = trim($_POST['nombre_menu']);
        
        // Validaciones
        if (strlen($nombre_menu) < 3) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del menú debe tener al menos 3 caracteres',
                'campo_error' => 'nombre_menu'
            ]);
            return;
        }
        
        if (strlen($nombre_menu) > 50) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del menú no puede tener más de 50 caracteres',
                'campo_error' => 'nombre_menu'
            ]);
            return;
        }
        
        try {
            // Verificar si el nombre ya existe
            $menuExistente = $this->menuModel->obtenerPorNombre($nombre_menu);
            if ($menuExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un menú con ese nombre',
                    'campo_error' => 'nombre_menu'
                ]);
                return;
            }
            
            // Crear menú
            $resultado = $this->menuModel->crearMenu($nombre_menu);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Menú creado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al crear el menú'
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
        if (empty($_POST['id_menu']) || empty($_POST['nombre_menu'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Datos incompletos para la edición',
                'campos_faltantes' => ['id_menu', 'nombre_menu']
            ]);
            return;
        }
        
        $id_menu = (int)$_POST['id_menu'];
        $nombre_menu = trim($_POST['nombre_menu']);
        
        // Validaciones
        if (strlen($nombre_menu) < 3) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del menú debe tener al menos 3 caracteres',
                'campo_error' => 'nombre_menu'
            ]);
            return;
        }
        
        if (strlen($nombre_menu) > 50) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del menú no puede tener más de 50 caracteres',
                'campo_error' => 'nombre_menu'
            ]);
            return;
        }
        
        try {
            // Verificar si el menú existe
            $menuActual = $this->menuModel->obtenerPorId($id_menu);
            if (!$menuActual) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El menú que intenta editar no existe'
                ]);
                return;
            }
            
            // Verificar si el nombre ya existe en otro menú
            $menuExistente = $this->menuModel->obtenerPorNombre($nombre_menu);
            if ($menuExistente && $menuExistente['id_menu'] != $id_menu) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe otro menú con ese nombre',
                    'campo_error' => 'nombre_menu'
                ]);
                return;
            }
            
            // Editar menú
            $resultado = $this->menuModel->editarMenu($id_menu, $nombre_menu);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Menú actualizado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el menú'
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
        
        if (empty($_POST['id_menu'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de menú requerido'
            ]);
            return;
        }
        
        try {
            $id_menu = (int)$_POST['id_menu'];
            
            // Verificar que el menú exista
            $menu = $this->menuModel->obtenerPorId($id_menu);
            if (!$menu) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El menú no existe'
                ]);
                return;
            }
            
            // Verificar si el menú tiene submenús asociados
            $tieneSubmenus = $this->menuModel->tieneSubmenus($id_menu);
            if ($tieneSubmenus) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar el menú porque tiene submenús asociados. Elimine primero los submenús.'
                ]);
                return;
            }
            
            $resultado = $this->menuModel->eliminarMenu($id_menu);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Menú eliminado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al eliminar el menú'
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
            $menus = $this->menuModel->obtenerTodos();
            
            $this->responderJSON([
                'success' => true, 
                'data' => $menus,
                'count' => count($menus)
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerMenusPaginados() {
        // Obtener parámetros de paginación
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        
        // Debug de parámetros recibidos
        if ($this->debug) {
            error_log("DEBUG obtenerMenusPaginados: pagina=$pagina, limit=$limit, busqueda='$busqueda'");
        }
        
        // Calcular offset
        $inicio = ($pagina - 1) * $limit;
        
        try {
            // Contar total de registros según búsqueda
            $totalRegistros = $this->menuModel->contarMenus($busqueda);
            
            // Calcular total de páginas (mínimo 1)
            $totalPaginas = $totalRegistros > 0 ? ceil($totalRegistros / $limit) : 1;
            
            // Asegurar que la página actual sea válida
            if ($pagina > $totalPaginas) {
                $pagina = $totalPaginas;
                $inicio = ($pagina - 1) * $limit;
            }
            
            // Obtener menús paginados con búsqueda
            $menus = $this->menuModel->obtenerMenusPaginados($inicio, $limit, $busqueda);
            
            // Debug de resultados
            if ($this->debug) {
                error_log("DEBUG resultados: totalRegistros=$totalRegistros, menusEncontrados=" . count($menus) . ", busqueda='$busqueda'");
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $menus,
                'totalRegistros' => $totalRegistros,
                'mostrando' => count($menus),
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda
            ]);
        } catch (Exception $e) {
            $this->logError("Error obteniendo menús paginados: " . $e->getMessage(), [
                'pagina' => $pagina, 
                'limit' => $limit,
                'busqueda' => $busqueda
            ]);
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener menús: ' . $e->getMessage()
            ]);
        }
    }
    

    
    private function verificarNombreMenu() {
        $nombre_menu = $_GET['nombre_menu'] ?? '';
        $id_menu = isset($_GET['id_menu']) ? (int)$_GET['id_menu'] : 0;
        
        if (empty($nombre_menu)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Nombre de menú requerido'
            ]);
            return;
        }
        
        try {
            $menu = $this->menuModel->obtenerPorNombre($nombre_menu);
            $existe = $menu && $menu['id_menu'] != $id_menu;
            
            $this->responderJSON([
                'success' => true,
                'existe' => $existe,
                'message' => $existe ? 'Nombre de menú en uso' : 'Nombre de menú disponible',
                'disponible' => !$existe
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
        error_log(date('Y-m-d H:i:s') . " [ERROR] [MenusController] {$mensaje}");
        if (!empty($contexto)) {
            error_log(date('Y-m-d H:i:s') . " [CONTEXT] " . json_encode($contexto));
        }
    }
    
    private function logDebug($mensaje, $contexto = []) {
        if ($this->debug) {
            error_log(date('Y-m-d H:i:s') . " [DEBUG] [MenusController] {$mensaje}");
            if (!empty($contexto)) {
                error_log(date('Y-m-d H:i:s') . " [DEBUG_CONTEXT] " . json_encode($contexto));
            }
        }
    }
}

// Ejecutar controlador si se accede directamente
if (basename($_SERVER['PHP_SELF']) === 'MenusController.php') {
    try {
        $controller = new MenusController();
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