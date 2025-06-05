<?php
require_once __DIR__ . "/../../modelos/Submenus.php";
require_once __DIR__ . "/../../modelos/Menus.php";
require_once __DIR__ . "/../../modelos/Permisos.php";

class SubMenusController {
    private $submenuModel;
    private $menuModel;
    private $permisosModel;
    private $submenusModel;
    private $debug = false; // Activar solo para debugging
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->submenuModel = new Submenus();
        $this->menuModel = new Menu();
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
                case 'obtenerSubmenusPaginados':
                    $this->obtenerSubmenusPaginados();
                    break;
                case 'obtenerEstadisticas':
                    $this->obtenerEstadisticas();
                    break;
                case 'verificarNombreSubmenu':
                    $this->verificarNombreSubmenu();
                    break;
                case 'verificarUrlSubmenu':
                    $this->verificarUrlSubmenu();
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
        
        // Si aún no tenemos ID, usar valor por defecto para gestión de submenús
        if (!$id_submenu) {
            $script_name = basename($_SERVER['SCRIPT_NAME']);
            if (strpos($script_name, 'gestionsubmenus') !== false || 
                strpos($_SERVER['REQUEST_URI'], 'gestionsubmenus') !== false) {
                $id_submenu = 17; // ID del submenú "Gestión Submenús" - ajustar según tu BD
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
            $submenus = $this->submenuModel->obtenerTodos();
            $menus = $this->menuModel->obtenerTodos();
            
            // Pasar datos a la vista
            extract([
                'submenus' => $submenus,
                'menus' => $menus,
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestionsubmenus.php';
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
        $camposRequeridos = ['nombre_submenu', 'url_submenu', 'id_menu'];
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
        
        $nombre_submenu = trim($_POST['nombre_submenu']);
        $url_submenu = trim($_POST['url_submenu']);
        $id_menu = (int)$_POST['id_menu'];
        
        // Validaciones específicas
        if (strlen($nombre_submenu) < 3) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del submenú debe tener al menos 3 caracteres',
                'campo_error' => 'nombre_submenu'
            ]);
            return;
        }
        
        if (strlen($nombre_submenu) > 50) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del submenú no puede tener más de 50 caracteres',
                'campo_error' => 'nombre_submenu'
            ]);
            return;
        }
        
        if (strlen($url_submenu) < 5) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La URL del submenú debe tener al menos 5 caracteres',
                'campo_error' => 'url_submenu'
            ]);
            return;
        }
        
        try {
            // Verificar si el menú padre existe
            $menuPadre = $this->menuModel->obtenerPorId($id_menu);
            if (!$menuPadre) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El menú padre seleccionado no existe',
                    'campo_error' => 'id_menu'
                ]);
                return;
            }
            
            // Verificar si el nombre ya existe
            $submenuExistente = $this->submenuModel->obtenerPorNombre($nombre_submenu);
            if ($submenuExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un submenú con ese nombre',
                    'campo_error' => 'nombre_submenu'
                ]);
                return;
            }
            
            // Verificar si la URL ya existe
            $urlExistente = $this->submenuModel->obtenerPorUrl($url_submenu);
            if ($urlExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe un submenú con esa URL',
                    'campo_error' => 'url_submenu'
                ]);
                return;
            }
            
            // Crear submenú
            $resultado = $this->submenuModel->crearSubmenu($nombre_submenu, $url_submenu, $id_menu);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Submenú creado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al crear el submenú'
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
        $camposRequeridos = ['id_submenu', 'nombre_submenu', 'url_submenu', 'id_menu'];
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
        
        $id_submenu = (int)$_POST['id_submenu'];
        $nombre_submenu = trim($_POST['nombre_submenu']);
        $url_submenu = trim($_POST['url_submenu']);
        $id_menu = (int)$_POST['id_menu'];
        
        // Validaciones específicas
        if (strlen($nombre_submenu) < 3) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre del submenú debe tener al menos 3 caracteres',
                'campo_error' => 'nombre_submenu'
            ]);
            return;
        }
        
        if (strlen($url_submenu) < 5) {
            $this->responderJSON([
                'success' => false,
                'message' => 'La URL del submenú debe tener al menos 5 caracteres',
                'campo_error' => 'url_submenu'
            ]);
            return;
        }
        
        try {
            // Verificar si el submenú existe
            $submenuActual = $this->submenuModel->obtenerPorId($id_submenu);
            if (!$submenuActual) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El submenú que intenta editar no existe'
                ]);
                return;
            }
            
            // Verificar si el menú padre existe
            $menuPadre = $this->menuModel->obtenerPorId($id_menu);
            if (!$menuPadre) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El menú padre seleccionado no existe',
                    'campo_error' => 'id_menu'
                ]);
                return;
            }
            
            // Verificar si el nombre ya existe en otro submenú
            $submenuExistente = $this->submenuModel->obtenerPorNombre($nombre_submenu);
            if ($submenuExistente && $submenuExistente['id_submenu'] != $id_submenu) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe otro submenú con ese nombre',
                    'campo_error' => 'nombre_submenu'
                ]);
                return;
            }
            
            // Verificar si la URL ya existe en otro submenú
            $urlExistente = $this->submenuModel->obtenerPorUrl($url_submenu);
            if ($urlExistente && $urlExistente['id_submenu'] != $id_submenu) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe otro submenú con esa URL',
                    'campo_error' => 'url_submenu'
                ]);
                return;
            }
            
            // Editar submenú
            $resultado = $this->submenuModel->editarSubmenu($id_submenu, $nombre_submenu, $url_submenu, $id_menu);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Submenú actualizado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el submenú'
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
        
        if (empty($_POST['id_submenu'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de submenú requerido'
            ]);
            return;
        }
        
        try {
            $id_submenu = (int)$_POST['id_submenu'];
            
            // Verificar que el submenú exista
            $submenu = $this->submenuModel->obtenerPorId($id_submenu);
            if (!$submenu) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'El submenú no existe'
                ]);
                return;
            }
            
            // Verificar si el submenú tiene permisos asociados
            $tienePermisos = $this->submenuModel->tienePermisos($id_submenu);
            if ($tienePermisos) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar el submenú porque tiene permisos asociados. Elimine primero los permisos relacionados.'
                ]);
                return;
            }
            
            $resultado = $this->submenuModel->eliminarSubmenu($id_submenu);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Submenú eliminado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al eliminar el submenú'
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
            $submenus = $this->submenuModel->obtenerTodos();
            
            // Agregar nombre del menú padre a cada submenú
            foreach ($submenus as &$submenu) {
                $menu = $this->menuModel->obtenerPorId($submenu['id_menu']);
                $submenu['nombre_menu'] = $menu ? $menu['nombre_menu'] : 'Sin menú';
            }
            
            $this->responderJSON([
                'success' => true, 
                'data' => $submenus,
                'count' => count($submenus)
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerSubmenusPaginados() {
        // Obtener parámetros de paginación
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        
        // Debug de parámetros recibidos
        if ($this->debug) {
            error_log("DEBUG obtenerSubmenusPaginados: pagina=$pagina, limit=$limit, busqueda='$busqueda'");
        }
        
        // Calcular offset
        $inicio = ($pagina - 1) * $limit;
        
        try {
            // Contar total de registros según búsqueda
            $totalRegistros = $this->submenuModel->contarSubmenus($busqueda);
            
            // Calcular total de páginas (mínimo 1)
            $totalPaginas = $totalRegistros > 0 ? ceil($totalRegistros / $limit) : 1;
            
            // Asegurar que la página actual sea válida
            if ($pagina > $totalPaginas) {
                $pagina = $totalPaginas;
                $inicio = ($pagina - 1) * $limit;
            }
            
            // Obtener submenús paginados con búsqueda
            $submenus = $this->submenuModel->obtenerSubmenusPaginados($inicio, $limit, $busqueda);
            
            // Agregar nombre del menú padre a cada submenú
            foreach ($submenus as &$submenu) {
                $menu = $this->menuModel->obtenerPorId($submenu['id_menu']);
                $submenu['nombre_menu'] = $menu ? $menu['nombre_menu'] : 'Sin menú';
            }
            
            // Debug de resultados
            if ($this->debug) {
                error_log("DEBUG resultados: totalRegistros=$totalRegistros, submenusEncontrados=" . count($submenus) . ", busqueda='$busqueda'");
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $submenus,
                'totalRegistros' => $totalRegistros,
                'mostrando' => count($submenus),
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda
            ]);
        } catch (Exception $e) {
            $this->logError("Error obteniendo submenús paginados: " . $e->getMessage(), [
                'pagina' => $pagina, 
                'limit' => $limit,
                'busqueda' => $busqueda
            ]);
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener submenús: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerEstadisticas() {
        try {
            $estadisticas = $this->submenuModel->obtenerEstadisticas();
            
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
    
    private function verificarNombreSubmenu() {
        $nombre_submenu = $_GET['nombre_submenu'] ?? '';
        $id_submenu = isset($_GET['id_submenu']) ? (int)$_GET['id_submenu'] : 0;
        
        if (empty($nombre_submenu)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Nombre de submenú requerido'
            ]);
            return;
        }
        
        try {
            $submenu = $this->submenuModel->obtenerPorNombre($nombre_submenu);
            $existe = $submenu && $submenu['id_submenu'] != $id_submenu;
            
            $this->responderJSON([
                'success' => true,
                'existe' => $existe,
                'message' => $existe ? 'Nombre de submenú en uso' : 'Nombre de submenú disponible',
                'disponible' => !$existe
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function verificarUrlSubmenu() {
        $url_submenu = $_GET['url_submenu'] ?? '';
        $id_submenu = isset($_GET['id_submenu']) ? (int)$_GET['id_submenu'] : 0;
        
        if (empty($url_submenu)) {
            $this->responderJSON([
                'success' => false, 
                'message' => 'URL de submenú requerida'
            ]);
            return;
        }
        
        try {
            $submenu = $this->submenuModel->obtenerPorUrl($url_submenu);
            $existe = $submenu && $submenu['id_submenu'] != $id_submenu;
            
            $this->responderJSON([
                'success' => true,
                'existe' => $existe,
                'message' => $existe ? 'URL de submenú en uso' : 'URL de submenú disponible',
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
        error_log(date('Y-m-d H:i:s') . " [ERROR] [SubMenusController] {$mensaje}");
        if (!empty($contexto)) {
            error_log(date('Y-m-d H:i:s') . " [CONTEXT] " . json_encode($contexto));
        }
    }
    
    private function logDebug($mensaje, $contexto = []) {
        if ($this->debug) {
            error_log(date('Y-m-d H:i:s') . " [DEBUG] [SubMenusController] {$mensaje}");
            if (!empty($contexto)) {
                error_log(date('Y-m-d H:i:s') . " [DEBUG_CONTEXT] " . json_encode($contexto));
            }
        }
    }
}

// Ejecutar controlador si se accede directamente
if (basename($_SERVER['PHP_SELF']) === 'SubMenusController.php') {
    try {
        $controller = new SubMenusController();
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