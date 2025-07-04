<?php
/**
 * Controlador para gestión de Triaje - Rol Enfermero
 * Maneja todas las operaciones de triaje y signos vitales
 */

if (!isset($_SESSION)) session_start();

require_once __DIR__ . "/../../modelos/Triaje.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Citas.php";

class EnfermeriaController {
    private $triajeModel;
    private $permisosModel;
    private $citasModel;
    
    public function __construct() {
        $this->triajeModel = new Triaje();
        $this->permisosModel = new Permisos();
        $this->citasModel = new Citas();
    }
    
    // ===== MÉTODO PRINCIPAL =====
    
    public function manejarSolicitud() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
        
        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'obtenerCitasPendientes':
                $this->obtenerCitasPendientes();
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
            default:
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Acción no válida'
                ]);
                break;
        }
    }
    
    // ===== VISTA PRINCIPAL =====
    
    public function index() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../vistas/login.php');
            exit();
        }
        
        // Verificar que sea enfermero
        if ($_SESSION['id_rol'] != 73) { // ID rol enfermero
            $this->redirigir('../../error_permisos.php');
            exit();
        }
        
        $id_rol = $_SESSION['id_rol'];
        $id_submenu = $this->obtenerIdSubmenu();
        
        if (!$id_submenu) {
            die("Error: No se pudo determinar el ID del submenú de triaje");
        }
        
        try {
            $permisos = $this->permisosModel->obtenerPermisos($id_rol, $id_submenu);
            
            if (!$permisos) {
                $this->redirigir('../../error_permisos.php');
                exit();
            }
            
            // Pasar datos a la vista
            extract([
                'permisos' => $permisos,
                'id_submenu' => $id_submenu,
                'id_enfermero' => $_SESSION['id_usuario']
            ]);
            
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
            
            $citas = $this->triajeModel->obtenerCitasPendientesTriaje($fecha);
            
            // Agregar información adicional a cada cita
            foreach ($citas as &$cita) {
                $cita['tiene_triaje'] = !is_null($cita['id_triage']);
                $cita['estado_triaje'] = $cita['tiene_triaje'] ? 'Completado' : 'Pendiente';
                $cita['puede_hacer_triaje'] = !$cita['tiene_triaje'];
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $citas,
                'total' => count($citas),
                'fecha' => $fecha
            ]);
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
            
            // Crear el triaje
            $id_triaje = $this->triajeModel->crear($datos_triaje);
            
            // Actualizar estado de la cita si es necesario
            if ($datos_triaje['nivel_urgencia'] >= 3) {
                // Si es urgencia alta o crítica, marcar la cita como prioritaria
                $this->citasModel->actualizarEstado($id_cita, 'Triaje Urgente');
            } else {
                $this->citasModel->actualizarEstado($id_cita, 'Triaje Completado');
            }
            
            $response = [
                'success' => true,
                'message' => 'Triaje realizado exitosamente',
                'id_triaje' => $id_triaje,
                'imc' => $imc,
                'categoria_imc' => $imc ? $this->triajeModel->categorizarIMC($imc) : null
            ];
            
            if (!empty($alertas)) {
                $response['alertas'] = $alertas;
                $response['message'] .= '. ATENCIÓN: Se detectaron signos vitales fuera del rango normal.';
            }
            
            $this->responderJSON($response);
            
        } catch (Exception $e) {
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
                'saturacion_oxigeno' => $_POST['saturacion_oxigeno'] ?? null
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
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Instanciar y manejar la solicitud
$controller = new EnfermeriaController();
$controller->manejarSolicitud();
?>