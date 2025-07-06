<?php
require_once __DIR__ . "/../../modelos/Especialidades.php";
require_once __DIR__ . "/../../modelos/Sucursales.php";
require_once __DIR__ . "/../../modelos/Permisos.php";

class EspecialidadesController {
    private $especialidadesModel;
    private $sucursalesModel;
    private $permisosModel;
    private $debug = true;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->especialidadesModel = new Especialidades();
        $this->sucursalesModel = new Sucursales();
        $this->permisosModel = new Permisos();
    }
    
    public function manejarSolicitud() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../login.php');
            exit();
        }
        
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        if ($this->debug) {
            error_log("🏥 ESPECIALIDADES - Acción: $action");
        }
        
        switch ($action) {
            // ===== CRUD BÁSICO =====
            case 'crear':
                $this->crear();
                break;
            case 'editar':
                $this->editar();
                break;
            case 'eliminar':
                $this->eliminar();
                break;
                
            // ===== CONSULTAS =====
            case 'obtenerEspecialidadesPaginadas':
                $this->obtenerEspecialidadesPaginadas();
                break;
            case 'obtenerTodas':
                $this->obtenerTodas();
                break;
            case 'obtenerPorId':
                $this->obtenerPorId();
                break;
            case 'obtenerEstadisticas':
                $this->obtenerEstadisticas();
                break;
                
            // ===== VALIDACIONES =====
            case 'verificarNombre':
                $this->verificarNombre();
                break;
                
            // ===== SUCURSALES =====
            case 'obtenerSucursalesEspecialidad':
                $this->obtenerSucursalesEspecialidad();
                break;
                
            default:
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Acción no válida: ' . $action
                ]);
        }
    }
    
    // ===== CRUD PRINCIPAL CON SUCURSALES =====
    
    /**
     * Crear especialidad con sucursales asignadas
     */
    private function crear() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        $this->verificarPermisos('crear');
        
        // Validar campos requeridos
        if (empty($_POST['nombre_especialidad'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre de la especialidad es requerido'
            ]);
            return;
        }
        
        try {
            // Verificar si ya existe
            $existente = $this->especialidadesModel->existePorNombre(trim($_POST['nombre_especialidad']));
            if ($existente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe una especialidad con ese nombre'
                ]);
                return;
            }
            
            // Preparar datos de la especialidad
            $datos = [
                'nombre_especialidad' => trim($_POST['nombre_especialidad']),
                'descripcion' => !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null
            ];
            
            // Obtener sucursales seleccionadas
            $sucursales = isset($_POST['sucursales']) ? $_POST['sucursales'] : [];
            
            if (empty($sucursales)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Debe asignar al menos una sucursal a la especialidad'
                ]);
                return;
            }
            
            // Crear especialidad con sucursales
            $id_especialidad = $this->especialidadesModel->crearConSucursales($datos, $sucursales);
            
            if ($id_especialidad) {
                $mensaje = 'Especialidad creada exitosamente';
                if (!empty($sucursales)) {
                    $mensaje .= ' y asignada a ' . count($sucursales) . ' sucursal(es)';
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => $mensaje,
                    'data' => [
                        'id_especialidad' => $id_especialidad,
                        'nombre_especialidad' => $datos['nombre_especialidad'],
                        'descripcion' => $datos['descripcion'],
                        'total_sucursales' => count($sucursales)
                    ]
                ]);
            } else {
                throw new Exception("No se pudo crear la especialidad");
            }
            
        } catch (Exception $e) {
            $this->logError("Error creando especialidad: " . $e->getMessage(), $_POST);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al crear la especialidad: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Editar especialidad con sucursales
     */
    private function editar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        $this->verificarPermisos('editar');
        
        if (empty($_POST['id_especialidad'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de especialidad requerido'
            ]);
            return;
        }
        
        if (empty($_POST['nombre_especialidad'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'El nombre de la especialidad es requerido'
            ]);
            return;
        }
        
        try {
            $id_especialidad = (int)$_POST['id_especialidad'];
            
            // Verificar que existe
            $especialidadExistente = $this->especialidadesModel->obtenerPorId($id_especialidad);
            if (!$especialidadExistente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Especialidad no encontrada'
                ]);
                return;
            }
            
            // Verificar duplicado (excluyendo la actual)
            $duplicado = $this->especialidadesModel->existePorNombre(trim($_POST['nombre_especialidad']), $id_especialidad);
            if ($duplicado) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ya existe otra especialidad con ese nombre'
                ]);
                return;
            }
            
            // Preparar datos de la especialidad
            $datos = [
                'nombre_especialidad' => trim($_POST['nombre_especialidad']),
                'descripcion' => !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null
            ];
            
            // Obtener sucursales seleccionadas
            $sucursales = isset($_POST['sucursales']) ? $_POST['sucursales'] : [];
            
            if (empty($sucursales)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Debe asignar al menos una sucursal a la especialidad'
                ]);
                return;
            }
            
            // Actualizar especialidad con sucursales
            $resultado = $this->especialidadesModel->actualizarConSucursales($id_especialidad, $datos, $sucursales);
            
            if ($resultado) {
                $mensaje = 'Especialidad actualizada exitosamente';
                if (!empty($sucursales)) {
                    $mensaje .= ' y reasignada a ' . count($sucursales) . ' sucursal(es)';
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => $mensaje,
                    'data' => [
                        'id_especialidad' => $id_especialidad,
                        'total_sucursales' => count($sucursales)
                    ]
                ]);
            } else {
                throw new Exception("No se pudo actualizar la especialidad");
            }
            
        } catch (Exception $e) {
            $this->logError("Error editando especialidad: " . $e->getMessage(), $_POST);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al actualizar la especialidad: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Eliminar especialidad
     */
    private function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        $this->verificarPermisos('eliminar');
        
        if (empty($_POST['id'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de especialidad requerido'
            ]);
            return;
        }
        
        try {
            $id_especialidad = (int)$_POST['id'];
            
            // Verificar que existe
            $especialidad = $this->especialidadesModel->obtenerPorId($id_especialidad);
            if (!$especialidad) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Especialidad no encontrada'
                ]);
                return;
            }
            
            // Verificar si tiene doctores asignados
            if (!$this->especialidadesModel->puedeEliminar($id_especialidad)) {
                $doctoresAsignados = $this->especialidadesModel->contarDoctores($id_especialidad);
                $this->responderJSON([
                    'success' => false,
                    'message' => "No se puede eliminar. La especialidad tiene $doctoresAsignados doctor(es) asignado(s)."
                ]);
                return;
            }
            
            // Eliminar especialidad (esto también eliminará las asignaciones de sucursales)
            $resultado = $this->especialidadesModel->eliminar($id_especialidad);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Especialidad eliminada exitosamente'
                ]);
            } else {
                throw new Exception("No se pudo eliminar la especialidad");
            }
            
        } catch (Exception $e) {
            $this->logError("Error eliminando especialidad: " . $e->getMessage(), $_POST);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al eliminar la especialidad: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== CONSULTAS =====
    
    /**
     * Obtener especialidades paginadas con información de sucursales
     */
    private function obtenerEspecialidadesPaginadas() {
        try {
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $busqueda = $_GET['busqueda'] ?? '';
            
            $inicio = ($pagina - 1) * $limit;
            
            // Obtener especialidades paginadas
            $especialidades = $this->especialidadesModel->obtenerPaginadas($inicio, $limit, $busqueda);
            $totalRegistros = $this->especialidadesModel->contarTotal($busqueda);
            $totalPaginas = ceil($totalRegistros / $limit);
            
            $this->responderJSON([
                'success' => true,
                'data' => $especialidades,
                'totalRegistros' => $totalRegistros,
                'registrosPorPagina' => $limit,
                'mostrando' => count($especialidades),
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda
            ]);
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo especialidades paginadas: " . $e->getMessage(), $_GET);
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener especialidades: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener todas las especialidades
     */
    private function obtenerTodas() {
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
    
    /**
     * Obtener especialidad por ID con sucursales asignadas
     */
    private function obtenerPorId() {
        if (empty($_GET['id'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID requerido'
            ]);
            return;
        }
        
        try {
            $id_especialidad = (int)$_GET['id'];
            $especialidad = $this->especialidadesModel->obtenerPorId($id_especialidad);
            
            if ($especialidad) {
                // Obtener sucursales asignadas
                $especialidad['sucursales'] = $this->especialidadesModel->obtenerSucursales($id_especialidad);
                $especialidad['total_sucursales'] = count($especialidad['sucursales']);
                
                // Contar doctores
                $especialidad['total_doctores'] = $this->especialidadesModel->contarDoctores($id_especialidad);
                
                $this->responderJSON([
                    'success' => true,
                    'data' => $especialidad
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Especialidad no encontrada'
                ]);
            }
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticas() {
        try {
            $estadisticas = $this->especialidadesModel->obtenerEstadisticas();
            
            // Agregar estadísticas de sucursales
            $estadisticas['sucursales_activas'] = $this->sucursalesModel->contarActivas();
            
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
    
    /**
     * Obtener sucursales de una especialidad
     */
    private function obtenerSucursalesEspecialidad() {
        if (empty($_GET['id_especialidad'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de especialidad requerido'
            ]);
            return;
        }
        
        try {
            $id_especialidad = (int)$_GET['id_especialidad'];
            $sucursales = $this->especialidadesModel->obtenerSucursales($id_especialidad);
            
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
    
    // ===== VALIDACIONES =====
    
    /**
     * Verificar si un nombre de especialidad está disponible
     */
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
            
            $existe = $this->especialidadesModel->existePorNombre($nombre, $id_excluir);
            
            $this->responderJSON([
                'success' => true,
                'disponible' => !$existe,
                'existe' => $existe
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== INDEX (VISTA) =====
    
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
            $especialidades = $this->especialidadesModel->obtenerTodas();
            $sucursales = $this->sucursalesModel->obtenerTodas(true); // Solo activas
            
            // Pasar datos a la vista
            extract([
                'especialidades' => $especialidades,
                'sucursales' => $sucursales,
                'permisos' => $permisos,
                'id_submenu' => $id_submenu
            ]);
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/gestion/gestionespecialidades.php';
        } catch (Exception $e) {
            die("Error al cargar la página: " . $e->getMessage());
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
                'message' => 'Sin permisos para acceder a este módulo'
            ]);
            exit();
        }
        
        $campo_permiso = "puede_$accion";
        if (!isset($permisos[$campo_permiso]) || !$permisos[$campo_permiso]) {
            $this->responderJSON([
                'success' => false,
                'message' => "Sin permisos para realizar esta acción: $accion"
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
        
        // Si aún no tenemos ID, usar valor por defecto para gestión de especialidades
        if (!$id_submenu) {
            $script_name = basename($_SERVER['SCRIPT_NAME']);
            if (strpos($script_name, 'gestionespecialidades') !== false || 
                strpos($_SERVER['REQUEST_URI'], 'gestionespecialidades') !== false) {
                $id_submenu = 32; // ID del submenú "Gestión Especialidades" - ajustar según tu BD
            }
        }
        
        return $id_submenu;
    }
    
    private function logError($mensaje, $datos = []) {
        if ($this->debug) {
            error_log("ESPECIALIDADES_DEBUG: $mensaje");
            if (!empty($datos)) {
                error_log("DATOS: " . json_encode($datos, JSON_UNESCAPED_UNICODE));
            }
        }
    }
    
    private function responderJSON($data) {
        if (ob_get_length()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    private function redirigir($url) {
        header("Location: $url");
        exit();
    }
}

// Manejar la solicitud si se accede directamente al controlador
if (basename($_SERVER['SCRIPT_NAME']) === 'EspecialidadesController.php') {
    $controller = new EspecialidadesController();
    $controller->manejarSolicitud();
}
?>