<?php
require_once __DIR__ . "/../../modelos/PacienteCitas.php";
require_once __DIR__ . "/../../config/database.php";

// Asegurar que la sesión esté iniciada
if (!isset($_SESSION)) {
    session_start();
}

class PacientesController {
    private $pacienteCitas;
    
    public function __construct() {
        // Verificar que el usuario esté logueado y sea paciente
        if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 71) { // Asumiendo que rol 4 = Paciente
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
            exit;
        }
        
        $this->pacienteCitas = new PacienteCitas();
    }
    
    /**
     * Manejar las peticiones AJAX
     */
    public function manejarPeticion() {
        // Capturar la acción desde POST o GET
        $accion = '';
        if (isset($_POST['accion'])) {
            $accion = $_POST['accion'];
        } elseif (isset($_GET['accion'])) {
            $accion = $_GET['accion'];
        }
        
        // Debug - Eliminar en producción
        error_log("Acción recibida: " . $accion);
        error_log("POST data: " . print_r($_POST, true));
        error_log("GET data: " . print_r($_GET, true));
        
        try {
            switch ($accion) {
                case 'obtener_historial':
                    $this->obtenerHistorial();
                    break;
                    
                case 'obtener_detalle_cita':
                    $this->obtenerDetalleCita();
                    break;
                    
                case 'buscar_por_fechas':
                    $this->buscarPorFechas();
                    break;
                    
                case 'obtener_proximas_citas':
                    $this->obtenerProximasCitas();
                    break;
                    
                case 'obtener_estadisticas':
                    $this->obtenerEstadisticas();
                    break;
                    
                case 'obtener_especialidades':
                    $this->obtenerEspecialidades();
                    break;
                    
                case 'buscar_citas':
                    $this->buscarCitas();
                    break;
                    
                default:
                    throw new Exception("Acción no válida: " . $accion);
            }
            
        } catch (Exception $e) {
            error_log("Error en PacientesController: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener historial completo de citas del paciente
     */
    private function obtenerHistorial() {
        try {
            $id_paciente = $this->obtenerIdPaciente();
            
            // Obtener filtros de la petición
            $filtros = $this->obtenerFiltros();
            
            // Paginación
            $pagina = (int)($_POST['pagina'] ?? $_GET['pagina'] ?? 1);
            $por_pagina = (int)($_POST['por_pagina'] ?? $_GET['por_pagina'] ?? 10);
            
            // Obtener citas
            $citas = $this->pacienteCitas->obtenerHistorialCitas($id_paciente, $filtros);
            
            // Aplicar paginación
            $total_citas = count($citas);
            $total_paginas = ceil($total_citas / $por_pagina);
            $inicio = ($pagina - 1) * $por_pagina;
            $citas_paginadas = array_slice($citas, $inicio, $por_pagina);
            
            // Preparar respuesta
            $respuesta = [
                'success' => true,
                'data' => [
                    'citas' => $citas_paginadas,
                    'paginacion' => [
                        'pagina_actual' => $pagina,
                        'total_paginas' => $total_paginas,
                        'total_registros' => $total_citas,
                        'por_pagina' => $por_pagina,
                        'tiene_anterior' => $pagina > 1,
                        'tiene_siguiente' => $pagina < $total_paginas
                    ],
                    'resumen' => [
                        'total_citas' => $total_citas,
                        'filtros_aplicados' => !empty($filtros)
                    ]
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($respuesta);
            
        } catch (Exception $e) {
            throw new Exception('Error al obtener historial: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener detalle específico de una cita
     */
    private function obtenerDetalleCita() {
        try {
            // Obtener ID de cita desde POST o GET
            $id_cita = 0;
            if (isset($_POST['id_cita'])) {
                $id_cita = (int)$_POST['id_cita'];
            } elseif (isset($_GET['id_cita'])) {
                $id_cita = (int)$_GET['id_cita'];
            }
            
            $id_paciente = $this->obtenerIdPaciente();
            
            // Debug
            error_log("ID Cita recibido: " . $id_cita);
            error_log("ID Paciente: " . $id_paciente);
            
            if ($id_cita <= 0) {
                throw new Exception('ID de cita no válido');
            }
            
            $cita = $this->pacienteCitas->obtenerDetalleCita($id_cita, $id_paciente);
            
            if (!$cita) {
                throw new Exception('Cita no encontrada o no autorizada');
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $cita
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Error al obtener detalle: ' . $e->getMessage());
        }
    }
    
    /**
     * Buscar citas por rango de fechas
     */
    private function buscarPorFechas() {
        try {
            $id_paciente = $this->obtenerIdPaciente();
            
            $fecha_inicio = $_POST['fecha_inicio'] ?? $_GET['fecha_inicio'] ?? '';
            $fecha_fin = $_POST['fecha_fin'] ?? $_GET['fecha_fin'] ?? '';
            
            if (empty($fecha_inicio) || empty($fecha_fin)) {
                throw new Exception('Fechas de búsqueda requeridas');
            }
            
            // Validar formato de fechas
            if (!$this->validarFecha($fecha_inicio) || !$this->validarFecha($fecha_fin)) {
                throw new Exception('Formato de fecha no válido');
            }
            
            // Verificar que fecha_inicio <= fecha_fin
            if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
                throw new Exception('La fecha de inicio debe ser menor o igual a la fecha de fin');
            }
            
            $citas = $this->pacienteCitas->buscarPorRangoFechas($id_paciente, $fecha_inicio, $fecha_fin);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'citas' => $citas,
                    'rango' => [
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'total_encontradas' => count($citas)
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Error en búsqueda por fechas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener próximas citas del paciente
     */
    private function obtenerProximasCitas() {
        try {
            $id_paciente = $this->obtenerIdPaciente();
            $limite = (int)($_POST['limite'] ?? $_GET['limite'] ?? 5);
            
            $proximas_citas = $this->pacienteCitas->obtenerProximasCitas($id_paciente, $limite);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'proximas_citas' => $proximas_citas,
                    'total' => count($proximas_citas)
                ]
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Error al obtener próximas citas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas del paciente
     */
    private function obtenerEstadisticas() {
        try {
            $id_paciente = $this->obtenerIdPaciente();
            
            $estadisticas = $this->pacienteCitas->obtenerEstadisticasPaciente($id_paciente);
            $especialidades = $this->pacienteCitas->obtenerEspecialidadesVisitadas($id_paciente);
            
            // Calcular porcentajes
            $total = $estadisticas['total_citas'];
            if ($total > 0) {
                $estadisticas['porcentaje_completadas'] = round(($estadisticas['citas_completadas'] / $total) * 100, 1);
                $estadisticas['porcentaje_pendientes'] = round(($estadisticas['citas_pendientes'] / $total) * 100, 1);
                $estadisticas['porcentaje_canceladas'] = round(($estadisticas['citas_canceladas'] / $total) * 100, 1);
                $estadisticas['porcentaje_virtuales'] = round(($estadisticas['citas_virtuales'] / $total) * 100, 1);
            } else {
                $estadisticas['porcentaje_completadas'] = 0;
                $estadisticas['porcentaje_pendientes'] = 0;
                $estadisticas['porcentaje_canceladas'] = 0;
                $estadisticas['porcentaje_virtuales'] = 0;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas,
                    'especialidades_visitadas' => $especialidades
                ]
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener especialidades visitadas por el paciente
     */
    private function obtenerEspecialidades() {
        try {
            $id_paciente = $this->obtenerIdPaciente();
            
            $especialidades = $this->pacienteCitas->obtenerEspecialidadesVisitadas($id_paciente);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $especialidades
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Error al obtener especialidades: ' . $e->getMessage());
        }
    }
    
    /**
     * Búsqueda general de citas con texto libre
     */
    private function buscarCitas() {
        try {
            $id_paciente = $this->obtenerIdPaciente();
            $termino_busqueda = trim($_POST['busqueda'] ?? $_GET['busqueda'] ?? '');
            
            if (empty($termino_busqueda)) {
                throw new Exception('Término de búsqueda requerido');
            }
            
            if (strlen($termino_busqueda) < 3) {
                throw new Exception('El término de búsqueda debe tener al menos 3 caracteres');
            }
            
            $filtros = ['busqueda' => $termino_busqueda];
            $citas = $this->pacienteCitas->obtenerHistorialCitas($id_paciente, $filtros);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'citas' => $citas,
                    'termino_busqueda' => $termino_busqueda,
                    'total_encontradas' => count($citas)
                ]
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Error en búsqueda: ' . $e->getMessage());
        }
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    /**
     * Obtener el ID del paciente basado en el usuario logueado
     */
    private function obtenerIdPaciente() {
        try {
            if (!isset($_SESSION['id_usuario'])) {
                throw new Exception('Usuario no autenticado');
            }
            
            // Buscar el ID del paciente basado en el ID del usuario
            $conn = Database::getConnection();
            $query = "SELECT id_paciente FROM pacientes WHERE id_usuario = :id_usuario";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id_usuario' => $_SESSION['id_usuario']]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resultado) {
                throw new Exception('Paciente no encontrado para el usuario: ' . $_SESSION['id_usuario']);
            }
            
            return $resultado['id_paciente'];
            
        } catch (Exception $e) {
            throw new Exception('Error al obtener ID del paciente: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener filtros de la petición
     */
    private function obtenerFiltros() {
        $filtros = [];
        
        // Filtro por fecha desde
        if (!empty($_POST['fecha_desde']) || !empty($_GET['fecha_desde'])) {
            $fecha_desde = $_POST['fecha_desde'] ?? $_GET['fecha_desde'];
            if ($this->validarFecha($fecha_desde)) {
                $filtros['fecha_desde'] = $fecha_desde;
            }
        }
        
        // Filtro por fecha hasta
        if (!empty($_POST['fecha_hasta']) || !empty($_GET['fecha_hasta'])) {
            $fecha_hasta = $_POST['fecha_hasta'] ?? $_GET['fecha_hasta'];
            if ($this->validarFecha($fecha_hasta)) {
                $filtros['fecha_hasta'] = $fecha_hasta;
            }
        }
        
        // Filtro por estado
        if (!empty($_POST['estado']) || !empty($_GET['estado'])) {
            $estado = $_POST['estado'] ?? $_GET['estado'];
            if (in_array($estado, ['Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'No Asistio'])) {
                $filtros['estado'] = $estado;
            }
        }
        
        // Filtro por tipo de cita
        if (!empty($_POST['tipo_cita']) || !empty($_GET['tipo_cita'])) {
            $tipo_cita = $_POST['tipo_cita'] ?? $_GET['tipo_cita'];
            if (in_array($tipo_cita, ['presencial', 'virtual'])) {
                $filtros['tipo_cita'] = $tipo_cita;
            }
        }
        
        // Filtro por especialidad
        if (!empty($_POST['especialidad']) || !empty($_GET['especialidad'])) {
            $especialidad = (int)($_POST['especialidad'] ?? $_GET['especialidad']);
            if ($especialidad > 0) {
                $filtros['especialidad'] = $especialidad;
            }
        }
        
        // Filtro por búsqueda de texto
        if (!empty($_POST['busqueda']) || !empty($_GET['busqueda'])) {
            $busqueda = trim($_POST['busqueda'] ?? $_GET['busqueda']);
            if (strlen($busqueda) >= 3) {
                $filtros['busqueda'] = $busqueda;
            }
        }
        
        return $filtros;
    }
    
    /**
     * Validar formato de fecha
     */
    private function validarFecha($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
    
    /**
     * Registrar log de actividad (opcional)
     */
    private function registrarLog($accion, $detalles = '') {
        try {
            $log = date('Y-m-d H:i:s') . " - Usuario: " . $_SESSION['username'] . 
                   " - Acción: $accion - Detalles: $detalles" . PHP_EOL;
            
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            error_log($log, 3, $logDir . '/pacientes_' . date('Y-m') . '.log');
        } catch (Exception $e) {
            // No hacer nada si falla el log
        }
    }
}

// ===== EJECUCIÓN DEL CONTROLADOR =====
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new PacientesController();
        $controller->manejarPeticion();
    } else {
        throw new Exception('Método HTTP no permitido');
    }
} catch (Exception $e) {
    error_log("Error fatal en PacientesController: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>