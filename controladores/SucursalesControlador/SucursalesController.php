<?php
require_once __DIR__ . "/../../modelos/Sucursales.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Submenus.php";
require_once __DIR__ . "/../../modelos/Especialidades.php";


class SucursalesController {
    private $sucursalesModel;
    private $especialidadesModel; // ⭐ NUEVO
    private $permisosModel;
    private $submenusModel;
    private $debug = false; // Desactivar debug en producción
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->sucursalesModel = new Sucursales();
        $this->especialidadesModel = new Especialidades(); // ⭐ NUEVO
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
                case 'cambiarEstado':
                    $this->cambiarEstado();
                    break;
                case 'obtenerTodas':
                    $this->obtenerTodas();
                    break;
                case 'obtenerSucursalesPaginadas':
                    $this->obtenerSucursalesPaginadas();
                    break;
                case 'obtenerPorId':
                    $this->obtenerPorId();
                    break;
                case 'verificarNombre':
                    $this->verificarNombre();
                    break;
                case 'obtenerEstadisticas':
                    $this->obtenerEstadisticas();
                    break;
                case 'obtenerResumenActividad':
                    $this->obtenerResumenActividad();
                    break;
                case 'obtenerEspecialidades': // ⭐ NUEVO
                    $this->obtenerEspecialidades();
                    break;
                case 'obtenerEspecialidadesSucursal': // ⭐ NUEVO
                    $this->obtenerEspecialidadesSucursal();
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
    
    // ⭐ MODIFICAR EL MÉTODO INDEX para incluir especialidades
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
            $sucursales = $this->sucursalesModel->obtenerTodas(true);
            $especialidades = $this->especialidadesModel->obtenerTodas(); // ⭐ NUEVO
            
            // Pasar datos a la vista
            extract([
                'sucursales' => $sucursales,
                'especialidades' => $especialidades, // ⭐ NUEVO
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestionsucursales.php';
        } catch (Exception $e) {
            die("Error al cargar la página: " . $e->getMessage());
        }
    }

    // ⭐ MÉTODO CREAR MEJORADO
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
    $camposRequeridos = ['nombre_sucursal', 'direccion', 'telefono'];
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
        // Verificar si ya existe
        if ($this->sucursalesModel->existePorNombre($_POST['nombre_sucursal'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe una sucursal con ese nombre'
            ]);
            return;
        }
        
        // Preparar datos de la sucursal
        $datos = [
            'nombre_sucursal' => trim($_POST['nombre_sucursal']),
            'direccion' => trim($_POST['direccion']),
            'telefono' => trim($_POST['telefono']),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'horario_atencion' => !empty($_POST['horario_atencion']) ? trim($_POST['horario_atencion']) : null,
            'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1
        ];
        
        // Crear sucursal
        $id_sucursal = $this->sucursalesModel->crear($datos);
        
        if ($id_sucursal) {
            // ⭐ ASIGNAR ESPECIALIDADES - MEJORADO
            $especialidades = isset($_POST['especialidades']) ? $_POST['especialidades'] : [];
            $especialidadesAsignadas = 0;
            $especialidadesError = 0;
            
            if (!empty($especialidades) && is_array($especialidades)) {
                foreach ($especialidades as $id_especialidad) {
                    try {
                        // ⭐ VERIFICAR SI YA EXISTE ANTES DE ASIGNAR
                        if (!$this->especialidadesModel->existeEnSucursal($id_especialidad, $id_sucursal)) {
                            $this->especialidadesModel->asignarASucursal($id_especialidad, $id_sucursal);
                            $especialidadesAsignadas++;
                        }
                    } catch (Exception $e) {
                        $especialidadesError++;
                        if ($this->debug) {
                            error_log("Error asignando especialidad $id_especialidad: " . $e->getMessage());
                        }
                    }
                }
            }
            
            // Construir mensaje de respuesta
            $mensaje = 'Sucursal creada exitosamente';
            if ($especialidadesAsignadas > 0) {
                $mensaje .= " con $especialidadesAsignadas especialidades asignadas";
            }
            
            $this->responderJSON([
                'success' => true,
                'message' => $mensaje,
                'id' => $id_sucursal
            ]);
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al crear la sucursal'
            ]);
        }
        
    } catch (Exception $e) {
        $this->logError("Error creando sucursal: " . $e->getMessage(), $_POST);
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al crear la sucursal: ' . $e->getMessage()
        ]);
    }
}

// ⭐ MÉTODO EDITAR MEJORADO
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
    if (empty($_POST['id_sucursal'])) {
        $this->responderJSON([
            'success' => false,
            'message' => 'ID de sucursal requerido'
        ]);
        return;
    }
    
    $id_sucursal = (int)$_POST['id_sucursal'];
    
    // Validar datos requeridos
    $camposRequeridos = ['nombre_sucursal', 'direccion', 'telefono'];
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
        // Verificar si existe otra sucursal con el mismo nombre
        if ($this->sucursalesModel->existePorNombre($_POST['nombre_sucursal'], $id_sucursal)) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Ya existe otra sucursal con ese nombre'
            ]);
            return;
        }
        
        // Preparar datos
        $datos = [
            'nombre_sucursal' => trim($_POST['nombre_sucursal']),
            'direccion' => trim($_POST['direccion']),
            'telefono' => trim($_POST['telefono']),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'horario_atencion' => !empty($_POST['horario_atencion']) ? trim($_POST['horario_atencion']) : null,
            'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1
        ];
        
        // Actualizar sucursal
        $resultado = $this->sucursalesModel->actualizar($id_sucursal, $datos);
        
        if ($resultado) {
            // ⭐ ACTUALIZAR ESPECIALIDADES - MEJORADO
            try {
                // Primero eliminar todas las asignaciones actuales
                $especialidadesActuales = $this->sucursalesModel->obtenerEspecialidades($id_sucursal);
                foreach ($especialidadesActuales as $esp) {
                    $this->especialidadesModel->desasignarDeSucursal($esp['id_especialidad'], $id_sucursal);
                }
                
                // Luego asignar las nuevas especialidades
                $especialidades = isset($_POST['especialidades']) ? $_POST['especialidades'] : [];
                $especialidadesAsignadas = 0;
                
                if (!empty($especialidades) && is_array($especialidades)) {
                    foreach ($especialidades as $id_especialidad) {
                        try {
                            $this->especialidadesModel->asignarASucursal($id_especialidad, $id_sucursal);
                            $especialidadesAsignadas++;
                        } catch (Exception $e) {
                            // Si ya existe, continuar sin error
                            if ($this->debug) {
                                error_log("Especialidad $id_especialidad ya asignada o error: " . $e->getMessage());
                            }
                        }
                    }
                }
                
                // Construir mensaje de respuesta
                $mensaje = 'Sucursal actualizada exitosamente';
                if ($especialidadesAsignadas > 0) {
                    $mensaje .= " con $especialidadesAsignadas especialidades";
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => $mensaje
                ]);
                
            } catch (Exception $e) {
                // Si hay error con especialidades, pero la sucursal se actualizó
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Sucursal actualizada exitosamente (con advertencias en especialidades)'
                ]);
            }
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al actualizar la sucursal'
            ]);
        }
        
    } catch (Exception $e) {
        $this->logError("Error editando sucursal: " . $e->getMessage(), $_POST);
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al actualizar la sucursal: ' . $e->getMessage()
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
                'message' => 'Error al obtener especialidades: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener especialidades asignadas a una sucursal
     */
    private function obtenerEspecialidadesSucursal() {
        if (empty($_GET['id_sucursal'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de sucursal requerido'
            ]);
            return;
        }
        
        try {
            $id_sucursal = (int)$_GET['id_sucursal'];
            $especialidades = $this->sucursalesModel->obtenerEspecialidades($id_sucursal);
            
            $this->responderJSON([
                'success' => true,
                'data' => $especialidades
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener especialidades: ' . $e->getMessage()
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
                'message' => 'ID de sucursal requerido'
            ]);
            return;
        }
        
        try {
            $id_sucursal = (int)$_POST['id'];
            
            // Verificar si puede ser eliminada
            if (!$this->sucursalesModel->puedeEliminar($id_sucursal)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar la sucursal porque tiene citas programadas o doctores asignados'
                ]);
                return;
            }
            
            // Eliminar (desactivar)
            $resultado = $this->sucursalesModel->eliminar($id_sucursal);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Sucursal eliminada exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al eliminar la sucursal'
                ]);
            }
            
        } catch (Exception $e) {
            $this->logError("Error eliminando sucursal: " . $e->getMessage(), $_POST);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al eliminar la sucursal: ' . $e->getMessage()
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
            $id_sucursal = (int)$_POST['id'];
            $nuevo_estado = (int)$_POST['estado'];
            
            $resultado = $this->sucursalesModel->cambiarEstado($id_sucursal, $nuevo_estado);
            
            if ($resultado) {
                $estado_texto = $nuevo_estado ? 'activada' : 'desactivada';
                $this->responderJSON([
                    'success' => true,
                    'message' => "Sucursal {$estado_texto} exitosamente"
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
    
    private function obtenerTodas() {
        try {
            $incluir_inactivas = isset($_GET['incluir_inactivas']) ? 
                                filter_var($_GET['incluir_inactivas'], FILTER_VALIDATE_BOOLEAN) : false;
            
            $sucursales = $this->sucursalesModel->obtenerTodas($incluir_inactivas);
            
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
    
    private function obtenerSucursalesPaginadas() {
        try {
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $busqueda = $_GET['busqueda'] ?? '';
            
            // Filtros
            $filtros = [];
            if (isset($_GET['estado']) && $_GET['estado'] !== '') {
                $filtros['estado'] = (int)$_GET['estado'];
            }
            
            $inicio = ($pagina - 1) * $limit;
            
            // Obtener sucursales paginadas
            $sucursales = $this->sucursalesModel->obtenerPaginadas($inicio, $limit, $busqueda, $filtros);
            $totalRegistros = $this->sucursalesModel->contar($busqueda, $filtros);
            $totalPaginas = ceil($totalRegistros / $limit);
            
            // Debug
            if ($this->debug) {
                error_log("DEBUG: totalRegistros=$totalRegistros, sucursalesEncontradas=" . count($sucursales) . ", busqueda='$busqueda'");
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $sucursales,
                'totalRegistros' => $totalRegistros,
                'mostrando' => count($sucursales),
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda
            ]);
        } catch (Exception $e) {
            $this->logError("Error obteniendo sucursales paginadas: " . $e->getMessage(), [
                'pagina' => $pagina ?? 0, 
                'limit' => $limit ?? 0,
                'busqueda' => $busqueda ?? ''
            ]);
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener sucursales: ' . $e->getMessage()
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
            $id_sucursal = (int)$_GET['id'];
            $sucursal = $this->sucursalesModel->obtenerPorId($id_sucursal);
            
            if ($sucursal) {
                $this->responderJSON([
                    'success' => true,
                    'data' => $sucursal
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Sucursal no encontrada'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function verificarNombre() {
        if (empty($_GET['nombre'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Nombre requerido'
            ]);
            return;
        }
        
        try {
            $nombre = trim($_GET['nombre']);
            $id_excluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : null;
            
            $existe = $this->sucursalesModel->existePorNombre($nombre, $id_excluir);
            
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
    
    private function obtenerEstadisticas() {
        try {
            $estadisticas = $this->sucursalesModel->obtenerEstadisticas();
            
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
    
    private function obtenerResumenActividad() {
        try {
            $resumen = $this->sucursalesModel->obtenerResumenActividad();
            
            $this->responderJSON([
                'success' => true,
                'data' => $resumen
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
        
        // Si aún no tenemos ID, usar valor por defecto para gestión de sucursales
        if (!$id_submenu) {
            $script_name = basename($_SERVER['SCRIPT_NAME']);
            if (strpos($script_name, 'gestionsucursales') !== false || 
                strpos($_SERVER['REQUEST_URI'], 'gestionsucursales') !== false) {
                // Necesitarás crear este submenú en tu BD
                $id_submenu = 30; // ID del submenú "Gestión Sucursales" - ajustar según tu BD
            }
        }
        
        return $id_submenu;
    }
    
    private function logError($mensaje, $datos = []) {
        if ($this->debug) {
            error_log("SUCURSALES_ERROR: $mensaje");
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
if (basename($_SERVER['SCRIPT_NAME']) === 'SucursalesController.php') {
    $controller = new SucursalesController();
    $controller->manejarSolicitud();
}
?>