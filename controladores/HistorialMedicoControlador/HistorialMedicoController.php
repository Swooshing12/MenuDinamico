<?php
require_once __DIR__ . "/../../modelos/HistorialMedico.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../config/database.php";

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class HistorialMedicoController {
    private $historialModel;
    private $permisosModel;
    
    public function __construct() {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            exit;
        }
        
        $this->historialModel = new HistorialMedico();
        $this->permisosModel = new Permisos();
    }
    
    /**
     * 🎯 Manejar todas las solicitudes del controlador
     */
    public function manejarSolicitud() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
        
        try {
            switch ($action) {
                case 'buscar_paciente':
                    $this->buscarPaciente();
                    break;
                    
                case 'obtener_historial':
                    $this->obtenerHistorial();
                    break;
                    
                case 'obtener_detalle_cita':
                    $this->obtenerDetalleCita();
                    break;
                    
                case 'obtener_especialidades':
                    $this->obtenerEspecialidades();
                    break;
                    
                case 'obtener_sucursales':
                    $this->obtenerSucursales();
                    break;
                    
                case 'obtener_doctores_paciente':
                    $this->obtenerDoctoresPaciente();
                    break;
                    
                case 'buscar_en_historial':
                    $this->buscarEnHistorial();
                    break;
                    
                case 'obtener_estadisticas':
                    $this->obtenerEstadisticas();
                    break;
                    
                case 'exportar_historial':
                    $this->exportarHistorial();
                    break;
                    
                case 'index':
                default:
                    $this->index();
                    break;
            }
            
        } catch (Exception $e) {
            error_log("Error en HistorialMedicoController: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 🏠 Mostrar la vista principal
     */
    public function index() {
        try {
            // Verificar permisos (puedes ajustar el submenu_id según corresponda)
            $id_rol = $_SESSION['id_rol'];
            $submenu_id = $_GET['submenu_id'] ?? null;
            
            if ($submenu_id) {
                $permisos = $this->permisosModel->obtenerPermisos($id_rol, $submenu_id);
                if (!$permisos) {
                    header("Location: ../../error_permisos.php");
                    exit();
                }
            }
            
            // Cargar datos iniciales para la vista
            $especialidades = $this->historialModel->obtenerEspecialidades();
            $sucursales = $this->historialModel->obtenerSucursales();
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/historial_medico/historial_medico.php';
            
        } catch (Exception $e) {
            die("Error al cargar la página: " . $e->getMessage());
        }
    }
    
    /**
     * 🔍 Buscar paciente por cédula
     */
    private function buscarPaciente() {
        try {
            $cedula = $_POST['cedula'] ?? '';
            
            if (empty($cedula)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'La cédula es requerida'
                ]);
                return;
            }
            
            // Limpiar cédula
            $cedula = trim($cedula);
            
            $paciente = $this->historialModel->buscarPacientePorCedula($cedula);
            
            if ($paciente) {
                // Obtener estadísticas del paciente
                $estadisticas = $this->historialModel->obtenerEstadisticasHistorial($paciente['id_paciente']);
                
                $this->responderJSON([
                    'success' => true,
                    'data' => [
                        'paciente' => $paciente,
                        'estadisticas' => $estadisticas
                    ]
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'No se encontró ningún paciente con esa cédula'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error buscando paciente: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al buscar el paciente'
            ]);
        }
    }
    
    /**
     * 📋 Obtener historial médico completo con filtros
     */
    private function obtenerHistorial() {
        try {
            $id_paciente = $_POST['id_paciente'] ?? $_GET['id_paciente'] ?? '';
            
            if (empty($id_paciente)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID del paciente es requerido'
                ]);
                return;
            }
            
            // Obtener filtros
            $filtros = [
                'fecha_desde' => $_POST['fecha_desde'] ?? $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_POST['fecha_hasta'] ?? $_GET['fecha_hasta'] ?? '',
                'id_especialidad' => $_POST['id_especialidad'] ?? $_GET['id_especialidad'] ?? '',
                'id_doctor' => $_POST['id_doctor'] ?? $_GET['id_doctor'] ?? '',
                'estado' => $_POST['estado'] ?? $_GET['estado'] ?? '',
                'id_sucursal' => $_POST['id_sucursal'] ?? $_GET['id_sucursal'] ?? ''
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return !empty($value);
            });
            
            $historial = $this->historialModel->obtenerHistorialCompleto($id_paciente, $filtros);
            
            $this->responderJSON([
                'success' => true,
                'data' => [
                    'historial' => $historial,
                    'total_registros' => count($historial),
                    'filtros_aplicados' => $filtros
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener el historial médico'
            ]);
        }
    }
    
    /**
     * 📄 Obtener detalle completo de una cita
     */
    private function obtenerDetalleCita() {
        try {
            $id_cita = $_POST['id_cita'] ?? $_GET['id_cita'] ?? '';
            
            if (empty($id_cita)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID de la cita es requerido'
                ]);
                return;
            }
            
            $detalle = $this->historialModel->obtenerDetalleCita($id_cita);
            
            if ($detalle) {
                $this->responderJSON([
                    'success' => true,
                    'data' => $detalle
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'No se encontró la cita especificada'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error obteniendo detalle de cita: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener el detalle de la cita'
            ]);
        }
    }
    
    /**
     * 🏥 Obtener especialidades para filtros
     */
    private function obtenerEspecialidades() {
        try {
            $especialidades = $this->historialModel->obtenerEspecialidades();
            
            $this->responderJSON([
                'success' => true,
                'data' => $especialidades
            ]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo especialidades: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener las especialidades'
            ]);
        }
    }
    
    /**
     * 🏨 Obtener sucursales para filtros
     */
    private function obtenerSucursales() {
        try {
            $sucursales = $this->historialModel->obtenerSucursales();
            
            $this->responderJSON([
                'success' => true,
                'data' => $sucursales
            ]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo sucursales: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener las sucursales'
            ]);
        }
    }
    
    /**
     * 👨‍⚕️ Obtener doctores que han atendido al paciente
     */
    private function obtenerDoctoresPaciente() {
        try {
            $id_paciente = $_POST['id_paciente'] ?? $_GET['id_paciente'] ?? '';
            
            if (empty($id_paciente)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID del paciente es requerido'
                ]);
                return;
            }
            
            $doctores = $this->historialModel->obtenerDoctoresPaciente($id_paciente);
            
            $this->responderJSON([
                'success' => true,
                'data' => $doctores
            ]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo doctores del paciente: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener los doctores'
            ]);
        }
    }
    
    /**
     * 🔍 Buscar término en el historial
     */
    private function buscarEnHistorial() {
        try {
            $id_paciente = $_POST['id_paciente'] ?? '';
            $termino = $_POST['termino'] ?? '';
            
            if (empty($id_paciente) || empty($termino)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID del paciente y término de búsqueda son requeridos'
                ]);
                return;
            }
            
            $resultados = $this->historialModel->buscarEnHistorial($id_paciente, $termino);
            
            $this->responderJSON([
                'success' => true,
                'data' => [
                    'resultados' => $resultados,
                    'total_encontrados' => count($resultados),
                    'termino_busqueda' => $termino
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error buscando en historial: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al buscar en el historial'
            ]);
        }
    }
    
    /**
     * 📊 Obtener estadísticas del historial
     */
    private function obtenerEstadisticas() {
        try {
            $id_paciente = $_POST['id_paciente'] ?? $_GET['id_paciente'] ?? '';
            
            if (empty($id_paciente)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID del paciente es requerido'
                ]);
                return;
            }
            
            $estadisticas = $this->historialModel->obtenerEstadisticasHistorial($id_paciente);
            
            $this->responderJSON([
                'success' => true,
                'data' => $estadisticas
            ]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al obtener las estadísticas'
            ]);
        }
    }
    
    /**
     * 📥 Exportar historial a PDF/Excel (placeholder)
     */
    private function exportarHistorial() {
        try {
            $id_paciente = $_POST['id_paciente'] ?? '';
            $formato = $_POST['formato'] ?? 'pdf'; // pdf o excel
            
            if (empty($id_paciente)) {
                $this->responderJSON([
                    'success' => false,
                    'error' => 'ID del paciente es requerido'
                ]);
                return;
            }
            
            // TODO: Implementar exportación real
            $this->responderJSON([
                'success' => true,
                'message' => 'Funcionalidad de exportación en desarrollo',
                'data' => [
                    'id_paciente' => $id_paciente,
                    'formato' => $formato
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error exportando historial: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'error' => 'Error al exportar el historial'
            ]);
        }
    }
    
    /**
     * 🛡️ Verificar permisos específicos
     */
    private function verificarPermisos($accion, $submenu_id = null) {
        if (!$submenu_id) {
            $submenu_id = $_GET['submenu_id'] ?? $_POST['submenu_id'] ?? null;
        }
        
        if (!$submenu_id) {
            throw new Exception('ID de submenú no especificado');
        }
        
        $permisos = $this->permisosModel->obtenerPermisos($_SESSION['id_rol'], $submenu_id);
        
        if (!$permisos) {
            throw new Exception('Sin permisos para acceder a este módulo');
        }
        
        $campo_permiso = '';
        switch ($accion) {
            case 'leer':
                $campo_permiso = 'leer';
                break;
            case 'crear':
                $campo_permiso = 'crear';
                break;
            case 'editar':
                $campo_permiso = 'editar';
                break;
            case 'eliminar':
                $campo_permiso = 'eliminar';
                break;
            default:
                $campo_permiso = 'leer';
        }
        
        if (!$permisos[$campo_permiso]) {
            throw new Exception("Sin permisos para {$accion} en este módulo");
        }
        
        return true;
    }
    
    /**
     * 📝 Registrar actividad del usuario
     */
    private function registrarActividad($accion, $detalles = '') {
        try {
            $log = date('Y-m-d H:i:s') . " - Historial Médico - " . 
                   "Usuario: " . $_SESSION['username'] . 
                   " - Acción: $accion - Detalles: $detalles" . PHP_EOL;
            
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            error_log($log, 3, $logDir . '/historial_medico_' . date('Y-m') . '.log');
        } catch (Exception $e) {
            // No hacer nada si falla el log
        }
    }
    
    /**
     * 📤 Responder con JSON
     */
    private function responderJSON($data) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * ↩️ Redireccionar
     */
    private function redirigir($url) {
        header("Location: $url");
        exit();
    }
}

// ===== EJECUCIÓN DEL CONTROLADOR =====
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new HistorialMedicoController();
        $controller->manejarSolicitud();
    } else {
        throw new Exception('Método HTTP no permitido');
    }
} catch (Exception $e) {
    error_log("Error fatal en HistorialMedicoController: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>