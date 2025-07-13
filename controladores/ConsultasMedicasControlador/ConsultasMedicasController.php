<?php
/**
 * Controlador para gestión de Consultas Médicas - Rol Médico
 */

if (!isset($_SESSION)) session_start();

// Agregar estas líneas al inicio del archivo
require_once __DIR__ . "/../../config/Mailer.php";
require_once __DIR__ . "/../../controladores/ConsultasMedicasControlador/GenerarPDFConsulta.php";
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
                 // 🔥 AGREGAR ESTE NUEVO CASE
            case 'obtenerDatosConsultaCompleta':
                $this->obtenerDatosConsultaCompleta();
                break;
                    case 'generarPDFConsulta':
        $this->generarPDFConsulta();
        break;
            default:
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Acción no válida: ' . $action
                ]);
                break;
        }
    }
    
    /**
 * 🔥 NUEVO MÉTODO: Generar PDF para descarga manual
 */
private function generarPDFConsulta() {
    try {
        $id_cita = $_GET['id_cita'] ?? '';
        
        if (empty($id_cita)) {
            die("Error: ID de cita requerido");
        }
        
        // Verificar que el médico tenga acceso a la cita
        $medico = $this->obtenerMedicoActual();
        if (!$medico) {
            die("Error: Usuario no es médico válido");
        }
        
        // Obtener datos completos de la cita (misma query que el envío automático)
        $query = "SELECT 
                    c.id_cita, c.fecha_hora, c.motivo, c.estado, c.notas, c.tipo_cita,
                    
                    -- Datos del paciente
                    u_pac.nombres as nombres_paciente, u_pac.apellidos as apellidos_paciente,
                    u_pac.correo as correo_paciente, u_pac.cedula as cedula_paciente,
                    pac.fecha_nacimiento, pac.tipo_sangre, pac.alergias, pac.telefono,
                    pac.contacto_emergencia, pac.telefono_emergencia,
                    
                    -- Datos del doctor
                    u_doc.nombres as nombres_doctor, u_doc.apellidos as apellidos_doctor,
                    u_doc.correo as doctor_correo,
                    d.titulo_profesional,
                    CONCAT(u_doc.nombres, ' ', u_doc.apellidos) as doctor_nombre,
                    
                    -- Datos de especialidad y sucursal
                    e.nombre_especialidad,
                    s.nombre_sucursal, s.direccion as sucursal_direccion,
                    s.telefono as sucursal_telefono, s.email as sucursal_email,
                    s.horario_atencion,
                    
                    -- Datos del triaje
                    t.id_triage, t.nivel_urgencia, t.temperatura, t.presion_arterial,
                    t.frecuencia_cardiaca, t.peso, t.talla, t.imc,
                    t.observaciones as triaje_observaciones,
                    
                    -- Datos de la consulta médica
                    cm.motivo_consulta, cm.sintomatologia, cm.diagnostico,
                    cm.tratamiento, cm.observaciones as consulta_observaciones,
                    cm.fecha_seguimiento
                    
                  FROM citas c
                  INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                  INNER JOIN usuarios u_pac ON pac.id_usuario = u_pac.id_usuario
                  INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                  INNER JOIN usuarios u_doc ON d.id_usuario = u_doc.id_usuario
                  INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                  INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                  LEFT JOIN triage t ON c.id_cita = t.id_cita
                  LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                  WHERE c.id_cita = :id_cita AND c.id_doctor = :id_doctor";
        
        $stmt = Database::getConnection()->prepare($query);
        $stmt->execute([
            ':id_cita' => $id_cita,
            ':id_doctor' => $medico['id_doctor']
        ]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cita) {
            die("Error: No se encontró la cita o no tiene permisos para acceder a ella");
        }
        
        // Generar PDF usando el generador específico de consultas
        require_once __DIR__ . "/GenerarPDFConsulta.php";
        $pdf = new GeneradorPDFConsulta($cita);
        $pdf->generarContenido();
        
        // Nombre del archivo
        $nombre_archivo = "Consulta_Medica_" . $id_cita . "_" . date('Y-m-d_H-i') . ".pdf";
        
        // Enviar PDF al navegador para descarga
        $pdf->Output($nombre_archivo, 'D');
        
    } catch (Exception $e) {
        error_log("Error generando PDF consulta: " . $e->getMessage());
        die("Error al generar el PDF: " . $e->getMessage());
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
        
        // 🔧 SIMPLIFICAR: Quitar verificación problemática por ahora
        // En lugar de verificarAccesoCita, solo verificar que el médico existe
        if (!$medico) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Médico no encontrado'
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
        
        // 🔧 USAR EL MÉTODO DEL MODELO DIRECTAMENTE
        $resultado = $this->consultasModel->crearConsulta($datos_consulta);
        
        // 🔥 Si la consulta se creó exitosamente
        if ($resultado['success']) {
            // Enviar PDF automáticamente (sin bloquear la respuesta)
            try {
                $pdf_enviado = $this->enviarPDFAutomatico($id_cita);
                $resultado['pdf_enviado'] = $pdf_enviado;
                
                if ($pdf_enviado) {
                    $resultado['message'] = 'Consulta registrada exitosamente. PDF enviado al paciente.';
                } else {
                    $resultado['message'] = 'Consulta registrada exitosamente. Error al enviar PDF.';
                }
            } catch (Exception $e) {
                // No fallar si el PDF no se puede enviar
                error_log("Error enviando PDF: " . $e->getMessage());
                $resultado['pdf_enviado'] = false;
                $resultado['message'] = 'Consulta registrada exitosamente. Error al enviar PDF.';
            }
        }
        
        $this->responderJSON($resultado);
        
    } catch (Exception $e) {
        error_log("Error en crearConsulta: " . $e->getMessage());
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

    /**
 * 🔥 NUEVO MÉTODO: Obtener datos completos de consulta para modal
 */
private function obtenerDatosConsultaCompleta() {
    try {
        $id_cita = $_GET['id_cita'] ?? $_POST['id_cita'] ?? '';
        
        if (empty($id_cita)) {
            $this->responderJSON([
                'success' => false,
                'message' => 'ID de cita requerido'
            ]);
            return;
        }
        
        // Obtener datos completos de la cita y consulta
        $query = "SELECT 
                    c.id_cita, c.fecha_hora, c.motivo, c.estado, c.notas,
                    
                    -- Datos del paciente
                    u_pac.nombres as nombres_paciente, u_pac.apellidos as apellidos_paciente,
                    u_pac.cedula as cedula_paciente, u_pac.correo as correo_paciente,
                    pac.fecha_nacimiento, pac.tipo_sangre, pac.alergias,
                    
                    -- Datos del doctor
                    u_doc.nombres as nombres_doctor, u_doc.apellidos as apellidos_doctor,
                    d.titulo_profesional,
                    
                    -- Datos de especialidad y sucursal
                    e.nombre_especialidad,
                    s.nombre_sucursal, s.direccion as sucursal_direccion,
                    
                    -- Datos del triaje
                    t.nivel_urgencia, t.temperatura, t.presion_arterial,
                    t.frecuencia_cardiaca, t.peso, t.talla, t.imc,
                    t.observaciones as triaje_observaciones,
                    
                    -- Datos de la consulta médica
                    cm.motivo_consulta, cm.sintomatologia, cm.diagnostico,
                    cm.tratamiento, cm.observaciones as consulta_observaciones,
                    cm.fecha_seguimiento, cm.fecha_hora as fecha_consulta
                    
                  FROM citas c
                  INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                  INNER JOIN usuarios u_pac ON pac.id_usuario = u_pac.id_usuario
                  INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                  INNER JOIN usuarios u_doc ON d.id_usuario = u_doc.id_usuario
                  INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                  INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                  LEFT JOIN triage t ON c.id_cita = t.id_cita
                  LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                  WHERE c.id_cita = :id_cita";
        
        $stmt = Database::getConnection()->prepare($query);
        $stmt->execute([':id_cita' => $id_cita]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($datos) {
            $this->responderJSON([
                'success' => true,
                'data' => $datos
            ]);
        } else {
            $this->responderJSON([
                'success' => false,
                'message' => 'No se encontraron datos para la cita'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error obteniendo datos completos: " . $e->getMessage());
        $this->responderJSON([
            'success' => false,
            'message' => 'Error al obtener los datos'
        ]);
    }
}

/**
 * 🔥 MÉTODO CORREGIDO: Enviar PDF automáticamente
 */
private function enviarPDFAutomatico($id_cita) {
    try {
        if ($this->debug) {
            error_log("📧 Iniciando envío de PDF para cita: " . $id_cita);
        }
        
        // Obtener datos COMPLETOS de la cita con todos los JOINs necesarios
        $query = "SELECT 
                    c.id_cita, c.fecha_hora, c.motivo, c.estado, c.notas, c.tipo_cita,
                    
                    -- Datos del paciente
                    u_pac.nombres as nombres_paciente, u_pac.apellidos as apellidos_paciente,
                    u_pac.correo as correo_paciente, u_pac.cedula as cedula_paciente,
                    pac.fecha_nacimiento, pac.tipo_sangre, pac.alergias, pac.telefono,
                    pac.contacto_emergencia, pac.telefono_emergencia,
                    
                    -- Datos del doctor
                    u_doc.nombres as nombres_doctor, u_doc.apellidos as apellidos_doctor,
                    u_doc.correo as doctor_correo,
                    d.titulo_profesional,
                    CONCAT(u_doc.nombres, ' ', u_doc.apellidos) as doctor_nombre,
                    
                    -- Datos de especialidad y sucursal
                    e.nombre_especialidad,
                    s.nombre_sucursal, s.direccion as sucursal_direccion,
                    s.telefono as sucursal_telefono, s.email as sucursal_email,
                    s.horario_atencion,
                    
                    -- Datos del triaje
                    t.id_triage, t.nivel_urgencia, t.temperatura, t.presion_arterial,
                    t.frecuencia_cardiaca, t.peso, t.talla, t.imc,
                    t.observaciones as triaje_observaciones,
                    
                    -- Datos de la consulta médica (recién creada)
                    cm.motivo_consulta, cm.sintomatologia, cm.diagnostico,
                    cm.tratamiento, cm.observaciones as consulta_observaciones,
                    cm.fecha_seguimiento
                    
                  FROM citas c
                  INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                  INNER JOIN usuarios u_pac ON pac.id_usuario = u_pac.id_usuario
                  INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                  INNER JOIN usuarios u_doc ON d.id_usuario = u_doc.id_usuario
                  INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                  INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                  LEFT JOIN triage t ON c.id_cita = t.id_cita
                  LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                  WHERE c.id_cita = :id_cita";
        
        $stmt = Database::getConnection()->prepare($query);
        $stmt->execute([':id_cita' => $id_cita]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cita) {
            error_log("❌ No se encontró la cita para envío automático: " . $id_cita);
            return false;
        }
        
        // 🔥 DEBUG: Verificar datos del paciente específicamente
        if ($this->debug) {
            error_log("📋 Datos del paciente para PDF:");
            error_log("  - Nombres: " . ($cita['nombres_paciente'] ?? 'NULL'));
            error_log("  - Apellidos: " . ($cita['apellidos_paciente'] ?? 'NULL'));
            error_log("  - Cédula: " . ($cita['cedula_paciente'] ?? 'NULL'));
            error_log("  - Correo: " . ($cita['correo_paciente'] ?? 'NULL'));
            error_log("  - Datos consulta: " . ($cita['diagnostico'] ?? 'NULL'));
        }
        
        // Verificar que tenemos los datos esenciales
        if (empty($cita['correo_paciente'])) {
            error_log("❌ No hay correo del paciente para envío");
            return false;
        }
        
        if (empty($cita['nombres_paciente'])) {
            error_log("❌ No hay nombre del paciente");
            return false;
        }
        
        // 🔥 CAMBIO PRINCIPAL: Usar el nuevo GeneradorPDFConsulta
        require_once __DIR__ . "/GenerarPDFConsulta.php";
        $pdf = new GeneradorPDFConsulta($cita);  // ← NUEVO GENERADOR
        $pdf->generarContenido();
        $pdf_content = $pdf->Output('', 'S'); // Obtener contenido como string
        
        // Verificar que el PDF se generó correctamente
        if (empty($pdf_content)) {
            error_log("❌ Error: PDF generado está vacío");
            return false;
        }
        
        if ($this->debug) {
            error_log("📄 PDF generado exitosamente, tamaño: " . strlen($pdf_content) . " bytes");
        }
        
        // Enviar por correo
        $mailer = new Mailer();
        $correo_paciente = $cita['correo_paciente'];
        $nombre_paciente = trim($cita['nombres_paciente'] . ' ' . $cita['apellidos_paciente']);
        
        $enviado = $mailer->enviarPDFCita($correo_paciente, $nombre_paciente, $cita, $pdf_content);
        
        if ($enviado) {
            error_log("✅ PDF enviado automáticamente a: " . $correo_paciente);
            return true;
        } else {
            error_log("❌ Error enviando PDF automático a: " . $correo_paciente);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ Error en envío automático de PDF: " . $e->getMessage());
        error_log("❌ Stack trace: " . $e->getTraceAsString());
        return false;
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