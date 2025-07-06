<?php
/**
 * Controlador para gestión de Triaje - Rol Enfermero
 * Maneja todas las operaciones de triaje y signos vitales
 */

if (!isset($_SESSION)) session_start();

require_once __DIR__ . "/../../modelos/Triaje.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Citas.php";
require_once __DIR__ . "/../../config/database.php";

class EnfermeriaController {
    private $triajeModel;
    private $permisosModel;
    private $citasModel;
    private $debug = true; // Para debugging
    
    public function __construct() {
        $this->triajeModel = new Triaje();
        $this->permisosModel = new Permisos();
        $this->citasModel = new Citas();
    }
    
    // ===== MÉTODO PRINCIPAL =====
    
    public function manejarSolicitud() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
        
        if ($this->debug) {
            error_log("🏥 ENFERMERÍA - Acción: $action");
            error_log("🏥 Usuario ID: " . ($_SESSION['id_usuario'] ?? 'NO SET'));
            error_log("🏥 Rol ID: " . ($_SESSION['id_rol'] ?? 'NO SET'));
        }
        
        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'obtenerCitasPendientes':
                $this->obtenerCitasPendientes();
                break;
            case 'buscarPorCedula': // ⭐ NUEVO
                $this->buscarPorCedula();
                break;
            case 'crearTriaje':
                $this->crearTriaje();
                break;
            case 'obtenerTriajePorCita':
                $this->obtenerTriajePorCita();
                break;
            case 'actualizarTriaje':
                $this->actualizarTriaje();
                break;
            case 'validarSignosVitales':
                $this->validarSignosVitales();
                break;
            case 'obtenerEstadisticas':
                $this->obtenerEstadisticas();
                break;
            case 'obtenerContadoresRapidos': // ⭐ NUEVO
                $this->obtenerContadoresRapidos();
                break;
            default:
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Acción no válida: ' . $action,
                    'debug' => [
                        'action_recibida' => $action,
                        'metodo' => $_SERVER['REQUEST_METHOD'],
                        'get_params' => $_GET,
                        'post_params' => array_keys($_POST)
                    ]
                ]);
                break;
        }
    }
    
    // ===== VISTA PRINCIPAL =====
    
    public function index() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../login.php');
            exit();
        }
        
        // Verificar que sea enfermero (rol ID 73)
        if ($_SESSION['id_rol'] != 73) {
            die("Error: Acceso denegado. Solo enfermeros pueden acceder. Tu rol: " . $_SESSION['id_rol']);
        }
        
        try {
            // ✅ PERMISOS BÁSICOS PARA ENFERMERO
            $permisos = [
                'puede_crear' => 1,
                'puede_editar' => 1,
                'puede_eliminar' => 0
            ];
            
            $id_submenu = 998; // Temporal para triaje
            $id_enfermero = $_SESSION['id_usuario'];
            
            if ($this->debug) {
                error_log("🏥 Variables para vista:");
                error_log("- id_enfermero: " . $id_enfermero);
                error_log("- permisos: " . json_encode($permisos));
            }
            
            // Incluir la vista
            include __DIR__ . '/../../vistas/enfermeria/triaje.php';
            
        } catch (Exception $e) {
            die("Error al cargar la página de triaje: " . $e->getMessage());
        }
    }
    
    // ===== OBTENER CITAS PENDIENTES =====
    
    private function obtenerCitasPendientes() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $buscar_cedula = $_GET['cedula'] ?? null; // ⭐ NUEVO PARÁMETRO
            
            if ($this->debug) {
                error_log("🏥 Obteniendo citas - Fecha: $fecha, Cédula: " . ($buscar_cedula ?: 'ninguna'));
            }
            
            // ✅ USAR BÚSQUEDA POR CÉDULA SI SE PROPORCIONA
            if (!empty($buscar_cedula)) {
                $citas = $this->triajeModel->buscarPorCedula($buscar_cedula, $fecha);
            } else {
                $citas = $this->triajeModel->obtenerCitasPendientesTriaje($fecha);
            }
            
            // ✅ PROCESAR DATOS CON ESTADO_TRIAJE
            foreach ($citas as &$cita) {
                $cita['tiene_triaje'] = !is_null($cita['id_triage']);
                
                // Usar estado_triaje de la base de datos o calcular
                if ($cita['tiene_triaje']) {
                    $cita['estado_triaje_display'] = $cita['estado_triaje'] ?? 'Completado';
                } else {
                    $cita['estado_triaje_display'] = 'Pendiente';
                }
                
                $cita['puede_hacer_triaje'] = !$cita['tiene_triaje'];
                $cita['es_urgente'] = $cita['tiene_triaje'] && ($cita['nivel_urgencia'] >= 3);
            }
            
            if ($this->debug) {
                error_log("🏥 Citas encontradas: " . count($citas));
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $citas,
                'total' => count($citas),
                'fecha' => $fecha,
                'busqueda_cedula' => $buscar_cedula,
                'debug' => [
                    'tiene_busqueda' => !empty($buscar_cedula),
                    'citas_con_triaje' => count(array_filter($citas, fn($c) => $c['tiene_triaje'])),
                    'citas_pendientes' => count(array_filter($citas, fn($c) => !$c['tiene_triaje']))
                ]
            ]);
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("🏥 Error en obtenerCitasPendientes: " . $e->getMessage());
            }
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => [
                    'fecha' => $fecha ?? 'no definida',
                    'cedula' => $buscar_cedula ?? 'no definida'
                ]
            ]);
        }
    }
    
    // ===== ⭐ NUEVO: BUSCAR POR CÉDULA =====
    
    private function buscarPorCedula() {
        try {
            $cedula = $_GET['cedula'] ?? '';
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            if (empty($cedula)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Cédula requerida para búsqueda'
                ]);
                return;
            }
            
            if (strlen($cedula) < 3) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Ingrese al menos 3 dígitos de la cédula'
                ]);
                return;
            }
            
            if ($this->debug) {
                error_log("🔍 Buscando por cédula: $cedula, fecha: $fecha");
            }
            
            $citas = $this->triajeModel->buscarPorCedula($cedula, $fecha);
            
            // Procesar resultados
            foreach ($citas as &$cita) {
                $cita['tiene_triaje'] = !is_null($cita['id_triage']);
                $cita['estado_triaje_display'] = $cita['tiene_triaje'] ? 
                    ($cita['estado_triaje'] ?? 'Completado') : 'Pendiente';
                $cita['puede_hacer_triaje'] = !$cita['tiene_triaje'];
                $cita['es_urgente'] = $cita['tiene_triaje'] && ($cita['nivel_urgencia'] >= 3);
            }
            
            if ($this->debug) {
                error_log("🔍 Resultados búsqueda: " . count($citas));
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $citas,
                'total' => count($citas),
                'cedula_buscada' => $cedula,
                'fecha' => $fecha,
                'message' => count($citas) > 0 ? 
                    "Se encontraron " . count($citas) . " resultado(s)" : 
                    "No se encontraron pacientes con esa cédula"
            ]);
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("🔍 Error en buscarPorCedula: " . $e->getMessage());
            }
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== CREAR TRIAJE =====
    
    private function crearTriaje() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        try {
            // Validar datos requeridos
            $camposRequeridos = ['id_cita', 'nivel_urgencia'];
            foreach ($camposRequeridos as $campo) {
                if (empty($_POST[$campo])) {
                    $this->responderJSON([
                        'success' => false,
                        'message' => "Campo requerido: $campo"
                    ]);
                    return;
                }
            }
            
            $id_cita = (int)$_POST['id_cita'];
            
            if ($this->debug) {
                error_log("🏥 Creando triaje para cita: $id_cita");
                error_log("🏥 Datos POST: " . json_encode($_POST));
            }
            
            // Verificar que la cita no tenga triaje ya
            if ($this->triajeModel->citaTieneTriaje($id_cita)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Esta cita ya tiene triaje realizado'
                ]);
                return;
            }
            
            // Preparar datos del triaje
            $peso = !empty($_POST['peso']) ? (float)$_POST['peso'] : null;
            $talla = !empty($_POST['talla']) ? (int)$_POST['talla'] : null;
            
            // Calcular IMC automáticamente si hay peso y talla
            $imc = null;
            if ($peso && $talla) {
                $imc = $this->triajeModel->calcularIMC($peso, $talla);
            }
            
            $datos_triaje = [
                'id_cita' => $id_cita,
                'id_enfermero' => $_SESSION['id_usuario'],
                'nivel_urgencia' => (int)$_POST['nivel_urgencia'],
                'temperatura' => !empty($_POST['temperatura']) ? (float)$_POST['temperatura'] : null,
                'presion_arterial' => $_POST['presion_arterial'] ?? null,
                'frecuencia_cardiaca' => !empty($_POST['frecuencia_cardiaca']) ? (int)$_POST['frecuencia_cardiaca'] : null,
                'frecuencia_respiratoria' => !empty($_POST['frecuencia_respiratoria']) ? (int)$_POST['frecuencia_respiratoria'] : null,
                'saturacion_oxigeno' => !empty($_POST['saturacion_oxigeno']) ? (int)$_POST['saturacion_oxigeno'] : null,
                'peso' => $peso,
                'talla' => $talla,
                'imc' => $imc,
                'observaciones' => $_POST['observaciones'] ?? null
            ];
            
            // Validar signos vitales
            $alertas = $this->triajeModel->validarSignosVitales($datos_triaje);
            
            // ✅ CREAR EL TRIAJE (que devuelve array con estado_triaje)
            $resultado = $this->triajeModel->crear($datos_triaje);
            
            // ✅ NO CAMBIAR EL ESTADO DE LA CITA
            // La cita mantiene su estado original (Confirmada/Pendiente)
            
            $response = [
                'success' => true,
                'message' => 'Triaje realizado exitosamente',
                'id_triaje' => $resultado['id_triage'],
                'estado_triaje' => $resultado['estado_triaje'],
                'imc' => $imc,
                'categoria_imc' => $imc ? $this->triajeModel->categorizarIMC($imc) : null
            ];
            
            if (!empty($alertas)) {
                $response['alertas'] = $alertas;
                $response['tiene_alertas'] = true;
                $response['message'] .= ' ATENCIÓN: Se detectaron signos vitales fuera del rango normal.';
            }
            
            if ($this->debug) {
                error_log("🏥 Triaje creado exitosamente: " . json_encode($response));
            }
            
            $this->responderJSON($response);
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("🏥 Error creando triaje: " . $e->getMessage());
            }
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== OBTENER TRIAJE POR CITA =====
    
    private function obtenerTriajePorCita() {
        try {
            $id_cita = $_GET['id_cita'] ?? null;
            
            if (!$id_cita) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'ID de cita requerido'
                ]);
                return;
            }
            
            $triaje = $this->triajeModel->obtenerPorCita((int)$id_cita);
            
            if (!$triaje) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No se encontró triaje para esta cita'
                ]);
                return;
            }
            
            // Agregar información adicional
            if ($triaje['imc']) {
                $triaje['categoria_imc'] = $this->triajeModel->categorizarIMC($triaje['imc']);
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $triaje
            ]);
            
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== ACTUALIZAR TRIAJE =====
    
    private function actualizarTriaje() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        try {
            $id_triaje = $_POST['id_triaje'] ?? null;
            
            if (!$id_triaje) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'ID de triaje requerido'
                ]);
                return;
            }
            
            if ($this->debug) {
                error_log("🏥 Actualizando triaje ID: $id_triaje");
            }
            
            // Preparar datos de actualización
            $peso = !empty($_POST['peso']) ? (float)$_POST['peso'] : null;
            $talla = !empty($_POST['talla']) ? (int)$_POST['talla'] : null;
            
            // Recalcular IMC
            $imc = null;
            if ($peso && $talla) {
                $imc = $this->triajeModel->calcularIMC($peso, $talla);
            }
            
            $datos_actualizacion = [
                'nivel_urgencia' => (int)$_POST['nivel_urgencia'],
                'temperatura' => !empty($_POST['temperatura']) ? (float)$_POST['temperatura'] : null,
                'presion_arterial' => $_POST['presion_arterial'] ?? null,
                'frecuencia_cardiaca' => !empty($_POST['frecuencia_cardiaca']) ? (int)$_POST['frecuencia_cardiaca'] : null,
                'frecuencia_respiratoria' => !empty($_POST['frecuencia_respiratoria']) ? (int)$_POST['frecuencia_respiratoria'] : null,
                'saturacion_oxigeno' => !empty($_POST['saturacion_oxigeno']) ? (int)$_POST['saturacion_oxigeno'] : null,
                'peso' => $peso,
                'talla' => $talla,
                'imc' => $imc,
                'observaciones' => $_POST['observaciones'] ?? null
            ];
            
            $resultado = $this->triajeModel->actualizar((int)$id_triaje, $datos_actualizacion);
            
            if ($resultado) {
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Triaje actualizado exitosamente',
                    'imc' => $imc,
                    'categoria_imc' => $imc ? $this->triajeModel->categorizarIMC($imc) : null
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No se pudo actualizar el triaje'
                ]);
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("🏥 Error actualizando triaje: " . $e->getMessage());
            }
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== VALIDAR SIGNOS VITALES =====
    
    private function validarSignosVitales() {
        try {
            $signos = [
                'temperatura' => $_POST['temperatura'] ?? null,
                'frecuencia_cardiaca' => $_POST['frecuencia_cardiaca'] ?? null,
                'saturacion_oxigeno' => $_POST['saturacion_oxigeno'] ?? null,
                'presion_arterial' => $_POST['presion_arterial'] ?? null
            ];
            
            $alertas = $this->triajeModel->validarSignosVitales($signos);
            
            $this->responderJSON([
                'success' => true,
                'alertas' => $alertas,
                'tiene_alertas' => !empty($alertas)
            ]);
            
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== ESTADÍSTICAS =====
    
    private function obtenerEstadisticas() {
        try {
            $fecha_desde = $_GET['fecha_desde'] ?? null;
            $fecha_hasta = $_GET['fecha_hasta'] ?? null;
            
            $estadisticas = $this->triajeModel->obtenerEstadisticas($fecha_desde, $fecha_hasta);
            
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
    
    // ===== ⭐ NUEVO: CONTADORES RÁPIDOS =====
    
    private function obtenerContadoresRapidos() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $contadores = $this->triajeModel->obtenerContadoresRapidos($fecha);
            
            if ($this->debug) {
                error_log("🏥 Contadores rápidos: " . json_encode($contadores));
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $contadores,
                'fecha' => $fecha
            ]);
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("🏥 Error contadores rápidos: " . $e->getMessage());
            }
            
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    private function obtenerIdSubmenu(): ?int {
        try {
            $stmt = $this->permisosModel->conn->prepare(
                "SELECT id_submenu FROM submenus WHERE url_submenu LIKE '%triaje.php%'"
            );
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['id_submenu'] : null;
        } catch (Exception $e) {
            error_log("Error obteniendo ID submenu triaje: " . $e->getMessage());
            return null;
        }
    }
    
    private function redirigir(string $url): void {
        header("Location: $url");
        exit();
    }
    
    private function responderJSON(array $data): void {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        if ($this->debug && isset($data['success']) && !$data['success']) {
            error_log("🏥 RESPUESTA ERROR: " . json_encode($data));
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Instanciar y manejar la solicitud
$controller = new EnfermeriaController();
$controller->manejarSolicitud();
?>