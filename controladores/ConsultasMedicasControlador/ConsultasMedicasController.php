<?php
/**
 * Controlador para gestión de Consultas Médicas - Rol Médico
 */

if (!isset($_SESSION)) session_start();

require_once __DIR__ . "/../../modelos/ConsultasMedicas.php";
require_once __DIR__ . "/../../modelos/Permisos.php";
require_once __DIR__ . "/../../modelos/Doctores.php";
require_once __DIR__ . "/../../config/database.php";

class ConsultasMedicasController {
    private $consultasModel;
    private $permisosModel;
    private $doctoresModel;
    private $debug = true;
    
    public function __construct() {
        $this->consultasModel = new ConsultasMedicas();
        $this->permisosModel = new Permisos();
        $this->doctoresModel = new Doctores();
    }
    
    public function manejarSolicitud() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
        
        if ($this->debug) {
            error_log("🩺 CONSULTAS MÉDICAS - Acción: $action");
            error_log("🩺 Usuario ID: " . ($_SESSION['id_usuario'] ?? 'NO SET'));
            error_log("🩺 Rol ID: " . ($_SESSION['id_rol'] ?? 'NO SET'));
        }
        
        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'obtenerCitasConTriaje':
                $this->obtenerCitasConTriaje();
                break;
            case 'crearConsulta':
                $this->crearConsulta();
                break;
            case 'obtenerHistorialPaciente':
                $this->obtenerHistorialPaciente();
                break;
            case 'obtenerEstadisticasMedico':
                $this->obtenerEstadisticasMedico();
                break;
            default:
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Acción no válida: ' . $action
                ]);
                break;
        }
    }
    
    public function index() {
        if (!isset($_SESSION['id_rol'])) {
            $this->redirigir('../../login.php');
            exit();
        }
        
        // Verificar que sea médico (rol ID 70)
        if ($_SESSION['id_rol'] != 70) {
            die("Error: Acceso denegado. Solo médicos pueden acceder. Tu rol: " . $_SESSION['id_rol']);
        }
        
        try {
            // ✅ OBTENER INFORMACIÓN DEL MÉDICO
            $medico = $this->obtenerMedicoActual();
            
            if (!$medico) {
                die("Error: Usuario no está registrado como médico. ID Usuario: " . $_SESSION['id_usuario']);
            }
            
            if ($this->debug) {
                error_log("🩺 Médico encontrado: " . json_encode($medico));
            }
            
            // ✅ DEFINIR VARIABLES PARA LA VISTA
            $permisos = [
                'puede_crear' => 1,
                'puede_editar' => 1,
                'puede_eliminar' => 0
            ];
            
            $id_submenu = 34; // Temporal
            $id_medico = $medico['id_doctor'];
            $nombre_medico = $medico['nombres'] . ' ' . $medico['apellidos'];
            $especialidad = $medico['nombre_especialidad'];
            $titulo_profesional = $medico['titulo_profesional'] ?? '';
            
            // ✅ INCLUIR LA VISTA CON VARIABLES DEFINIDAS
            include __DIR__ . '/../../vistas/consultas_medicas/index.php';
            
        } catch (Exception $e) {
            die("Error al cargar consultas médicas: " . $e->getMessage());
        }
    }
    
    /**
     * ✅ MÉTODO PARA OBTENER MÉDICO ACTUAL
     */
    private function obtenerMedicoActual() {
        try {
            $query = "SELECT d.id_doctor, u.nombres, u.apellidos, u.correo,
                             e.nombre_especialidad, d.titulo_profesional
                      FROM doctores d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                      WHERE u.id_usuario = :id_usuario AND u.id_estado = 1";
            
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute([':id_usuario' => $_SESSION['id_usuario']]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo médico: " . $e->getMessage());
            return null;
        }
    }
    
    private function obtenerCitasConTriaje() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $medico = $this->obtenerMedicoActual();
            
            if (!$medico) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Usuario no es médico válido'
                ]);
                return;
            }
            
            if ($this->debug) {
                error_log("🩺 Obteniendo citas para médico ID: " . $medico['id_doctor'] . " fecha: $fecha");
            }
            
            $citas = $this->consultasModel->obtenerCitasConTriaje($medico['id_doctor'], $fecha);
            
            // Agregar información adicional
            foreach ($citas as &$cita) {
                $cita['edad_paciente'] = $this->calcularEdad($cita['fecha_nacimiento'] ?? '1990-01-01');
                $cita['puede_consultar'] = $cita['tiene_consulta'] == 0;
            }
            
            if ($this->debug) {
                error_log("🩺 Citas encontradas: " . count($citas));
            }
            
            $this->responderJSON([
                'success' => true,
                'data' => $citas,
                'total' => count($citas),
                'fecha' => $fecha,
                'medico_id' => $medico['id_doctor']
            ]);
            
        } catch (Exception $e) {
            error_log("Error en obtenerCitasConTriaje: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function crearConsulta() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        try {
            // Validar datos requeridos
            $camposRequeridos = ['id_cita', 'motivo_consulta', 'diagnostico'];
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
            $medico = $this->obtenerMedicoActual();
            
            // Verificar acceso
            if (!$this->consultasModel->verificarAccesoCita($id_cita, $medico['id_doctor'])) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'No tiene permisos para esta cita'
                ]);
                return;
            }
            
            // Obtener o crear historial clínico
            $id_historial = $_POST['id_historial'] ?? null;
            if (empty($id_historial)) {
                $query = "SELECT p.id_paciente FROM citas c 
                         INNER JOIN pacientes p ON c.id_paciente = p.id_paciente 
                         WHERE c.id_cita = :id_cita";
                $stmt = Database::getConnection()->prepare($query);
                $stmt->execute([':id_cita' => $id_cita]);
                $cita = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($cita) {
                    $id_historial = $this->consultasModel->crearHistorialClinico($cita['id_paciente']);
                }
            }
            
            $datos_consulta = [
                'id_cita' => $id_cita,
                'id_historial' => $id_historial,
                'motivo_consulta' => $_POST['motivo_consulta'],
                'sintomatologia' => $_POST['sintomatologia'] ?? '',
                'diagnostico' => $_POST['diagnostico'],
                'tratamiento' => $_POST['tratamiento'] ?? '',
                'observaciones' => $_POST['observaciones'] ?? '',
                'fecha_seguimiento' => !empty($_POST['fecha_seguimiento']) ? $_POST['fecha_seguimiento'] : null
            ];
            
            $resultado = $this->consultasModel->crearConsulta($datos_consulta);
            $this->responderJSON($resultado);
            
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerHistorialPaciente() {
        try {
            $id_paciente = $_GET['id_paciente'] ?? null;
            
            if (!$id_paciente) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'ID de paciente requerido'
                ]);
                return;
            }
            
            $historial = $this->consultasModel->obtenerHistorialPaciente($id_paciente);
            
            $this->responderJSON([
                'success' => true,
                'data' => $historial
            ]);
            
        } catch (Exception $e) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function obtenerEstadisticasMedico() {
        try {
            $medico = $this->obtenerMedicoActual();
            
            if (!$medico) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Médico no encontrado'
                ]);
                return;
            }
            
            $estadisticas = $this->consultasModel->obtenerEstadisticasMedico($medico['id_doctor']);
            
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
    
    private function calcularEdad($fechaNacimiento) {
        if (empty($fechaNacimiento)) return 0;
        $hoy = new DateTime();
        $nacimiento = new DateTime($fechaNacimiento);
        return $hoy->diff($nacimiento)->y;
    }
    
    private function redirigir(string $url): void {
        header("Location: $url");
        exit();
    }
    
    private function responderJSON(array $data): void {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Instanciar y manejar la solicitud
$controller = new ConsultasMedicasController();
$controller->manejarSolicitud();
?>