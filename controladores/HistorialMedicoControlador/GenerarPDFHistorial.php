<?php
require_once __DIR__ . "/../../config/database.php";

// Verificar instalación de TCPDF
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';
} else {
    die('TCPDF no está instalado. Por favor instala TCPDF primero.');
}

// Iniciar sesión
if (!isset($_SESSION)) {
    session_start();
}

class GeneradorPDFHistorial extends TCPDF {
    private $citaData;
    
    public function __construct($cita_data) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8');
        $this->citaData = $cita_data;
        
        // Configuración del PDF
        $this->SetCreator('MediSys - Sistema Hospitalario');
        $this->SetAuthor('MediSys');
        $this->SetTitle('Historial Médico - Cita #' . $cita_data['id_cita']);
        $this->SetSubject('Detalle de Cita Médica desde Historial');
        $this->SetKeywords('historial, médico, cita, MediSys');
        
        // Configurar márgenes
        $this->SetMargins(20, 45, 20);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(15);
        
        // Auto page breaks
        $this->SetAutoPageBreak(TRUE, 30);
        
        // Fuente por defecto
        $this->SetFont('helvetica', '', 11);
    }
    
    // Header personalizado
    public function Header() {
        // Fondo del header
        $this->SetFillColor(0, 119, 182);
        $this->Rect(0, 0, 210, 40, 'F');
        
        // Logo/Icono médico
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(20, 12);
        $this->Cell(15, 15, '🏥', 0, 0, 'C');
        
        // Título principal
        $this->SetFont('helvetica', 'B', 22);
        $this->SetXY(40, 8);
        $this->Cell(0, 10, 'MEDISYS', 0, 1, 'L');
        
        // Subtítulo
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(40, 18);
        $this->Cell(0, 8, 'Sistema de Gestión Hospitalaria', 0, 1, 'L');
        
        // Título del documento
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(40, 26);
        $this->Cell(0, 8, 'HISTORIAL MÉDICO - DETALLE DE CITA', 0, 1, 'L');
        
        // Información de la cita en el header
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(140, 12);
        $this->Cell(0, 8, 'CITA #' . $this->citaData['id_cita'], 0, 1, 'R');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(140, 20);
        $fecha_actual = date('d/m/Y H:i');
        $this->Cell(0, 6, 'Generado: ' . $fecha_actual, 0, 1, 'R');
        
        // Estado de la cita
        $this->SetFont('helvetica', 'B', 11);
        $this->SetXY(140, 28);
        $this->Cell(0, 6, 'Estado: ' . ($this->citaData['estado'] ?? 'N/A'), 0, 1, 'R');
    }
    
    // Footer personalizado
    public function Footer() {
        $this->SetY(-20);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Izquierda: Copyright
        $this->Cell(0, 5, '© ' . date('Y') . ' | MediSys - Historial Médico Digital', 0, 0, 'L');
        
        // Derecha: Paginación
        $this->Cell(0, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 1, 'R');
        
        // Línea de confidencialidad
        $this->SetFont('helvetica', 'I', 8);
        $this->SetX(20);
        $this->Cell(0, 4, 'Documento confidencial - Solo para uso médico autorizado', 0, 1, 'C');
    }
    
    // Generar contenido del PDF
    public function generarContenido() {
        $this->AddPage();
        
        // Información del paciente
        $this->seccionPaciente();
        
        // Información de la cita
        $this->seccionCita();
        
        // Triaje (si existe)
        if (!empty($this->citaData['id_triage'])) {
            $this->seccionTriaje();
        }
        
        // Consulta médica (si existe)
        if (!empty($this->citaData['id_consulta'])) {
            $this->seccionConsultaMedica();
        }
        
        // Observaciones finales
        $this->seccionObservacionesFinales();
    }
    
    // Sección: Información del paciente
    private function seccionPaciente() {
        $this->crearTituloSeccion('INFORMACIÓN DEL PACIENTE');
        
        $this->SetFillColor(227, 242, 253);
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        
        // Headers de la tabla
        $this->Cell(50, 8, 'Campo', 1, 0, 'C', true);
        $this->Cell(120, 8, 'Información', 1, 1, 'C', true);
        
        // Datos del paciente
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $this->SetFillColor(248, 249, 250);
        
        $datos_paciente = [
            ['Nombre Completo', $this->citaData['paciente_nombre'] ?? 'No especificado'],
            ['Cédula de Identidad', $this->citaData['paciente_cedula'] ?? 'No especificada'],
            ['Edad', ($this->citaData['edad'] ?? 'No especificada') . ' años'],
            ['Tipo de Sangre', $this->citaData['tipo_sangre'] ?? 'No especificado'],
            ['Alergias Conocidas', $this->citaData['alergias'] ?? 'Ninguna registrada'],
            ['Teléfono', $this->citaData['telefono'] ?? 'No registrado']
        ];
        
        $fill = false;
        foreach ($datos_paciente as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(120, 7, $dato[1], 1, 1, 'L', $fill);
            $fill = !$fill;
        }
        
        $this->Ln(8);
    }
    
    // Sección: Información de la cita
    private function seccionCita() {
        $this->crearTituloSeccion('INFORMACIÓN DE LA CITA MÉDICA');
        
        $this->SetFillColor(240, 248, 255);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 119, 182);
        
        // Headers
        $this->Cell(50, 7, 'Detalle', 1, 0, 'C', true);
        $this->Cell(120, 7, 'Información', 1, 1, 'C', true);
        
        $fecha_cita = 'No especificada';
        if (!empty($this->citaData['fecha_hora_cita'])) {
            $fecha_cita = date('d/m/Y H:i', strtotime($this->citaData['fecha_hora_cita']));
        }
        
        $datos_cita = [
            ['Fecha y Hora', $fecha_cita],
            ['Especialidad', $this->citaData['nombre_especialidad'] ?? 'No especificada'],
            ['Médico Tratante', $this->citaData['doctor_nombre'] ?? 'No especificado'],
            ['Sucursal', $this->citaData['nombre_sucursal'] ?? 'No especificada'],
            ['Motivo de la Cita', $this->citaData['motivo'] ?? 'No especificado'],
            ['Estado de la Cita', $this->citaData['estado'] ?? 'No especificado']
        ];
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $fill = false;
        
        foreach ($datos_cita as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(120, 7, $dato[1], 1, 1, 'L', $fill);
            $fill = !$fill;
        }
        
        $this->Ln(8);
    }
    
    // Sección: Triaje
    private function seccionTriaje() {
        $this->crearTituloSeccion('INFORMACIÓN DEL TRIAJE');
        
        $this->SetFillColor(255, 248, 220);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(184, 134, 11);
        
        // Headers
        $this->Cell(50, 7, 'Parámetro', 1, 0, 'C', true);
        $this->Cell(120, 7, 'Valor', 1, 1, 'C', true);
        
        $fecha_triaje = 'No registrada';
        if (!empty($this->citaData['fecha_hora_triaje'])) {
            $fecha_triaje = date('d/m/Y H:i', strtotime($this->citaData['fecha_hora_triaje']));
        }
        
        $datos_triaje = [
            ['Fecha del Triaje', $fecha_triaje],
            ['Enfermero', $this->citaData['enfermero_nombre'] ?? 'No registrado'],
            ['Nivel de Urgencia', ($this->citaData['nivel_urgencia'] ?? '-') . '/5'],
            ['Estado del Triaje', $this->citaData['estado_triaje'] ?? 'Completado'],
            ['Temperatura', ($this->citaData['temperatura'] ?? '-') . '°C'],
            ['Presión Arterial', $this->citaData['presion_arterial'] ?? '-'],
            ['Frecuencia Cardíaca', ($this->citaData['frecuencia_cardiaca'] ?? '-') . ' lpm'],
            ['Peso', ($this->citaData['peso'] ?? '-') . ' kg'],
            ['Talla', ($this->citaData['talla'] ?? '-') . ' cm'],
            ['IMC', $this->citaData['imc'] ?? '-']
        ];
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $fill = false;
        
        foreach ($datos_triaje as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(120, 7, $dato[1], 1, 1, 'L', $fill);
            $fill = !$fill;
        }
        
        if (!empty($this->citaData['observaciones_triaje'])) {
            $this->Ln(3);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 6, 'Observaciones del Triaje:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell(0, 5, $this->citaData['observaciones_triaje'], 1, 'L');
        }
        
        $this->Ln(8);
    }
    
    // Sección: Consulta médica
    private function seccionConsultaMedica() {
        $this->crearTituloSeccion('CONSULTA MÉDICA');
        
        $fecha_consulta = 'No registrada';
        if (!empty($this->citaData['fecha_hora_consulta'])) {
            $fecha_consulta = date('d/m/Y H:i', strtotime($this->citaData['fecha_hora_consulta']));
        }
        
        // Información básica de la consulta
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 7, 'Fecha de la Consulta: ' . $fecha_consulta, 0, 1, 'L');
        $this->Ln(3);
        
        // Motivo de consulta
        if (!empty($this->citaData['motivo_consulta'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 7, 'Motivo de Consulta:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 5, $this->citaData['motivo_consulta'], 1, 'L');
            $this->Ln(3);
        }
        
        // Sintomatología
        if (!empty($this->citaData['sintomatologia'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 7, 'Sintomatología:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 5, $this->citaData['sintomatologia'], 1, 'L');
            $this->Ln(3);
        }
        
        // Diagnóstico
        if (!empty($this->citaData['diagnostico'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(220, 38, 127);
            $this->Cell(0, 7, 'Diagnóstico:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 5, $this->citaData['diagnostico'], 1, 'L');
            $this->Ln(3);
        }
        
        // Tratamiento
        if (!empty($this->citaData['tratamiento'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(25, 135, 84);
            $this->Cell(0, 7, 'Tratamiento:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 5, $this->citaData['tratamiento'], 1, 'L');
            $this->Ln(3);
        }
        
        // Observaciones de la consulta
        if (!empty($this->citaData['observaciones_consulta'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 7, 'Observaciones Adicionales:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 5, $this->citaData['observaciones_consulta'], 1, 'L');
            $this->Ln(3);
        }
        
        // Fecha de seguimiento
        if (!empty($this->citaData['fecha_seguimiento'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 7, 'Próxima Cita de Seguimiento:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $fecha_seguimiento = date('d/m/Y', strtotime($this->citaData['fecha_seguimiento']));
            $this->Cell(0, 7, $fecha_seguimiento, 1, 1, 'L');
            $this->Ln(3);
        }
    }
    
    // Sección: Observaciones finales
    private function seccionObservacionesFinales() {
        $this->Ln(10);
        $this->SetFont('helvetica', 'I', 9);
        $this->SetTextColor(108, 117, 125);
        $this->MultiCell(0, 4, 'Este documento del historial médico ha sido generado automáticamente por el Sistema MediSys. Contiene información médica confidencial que debe ser tratada según las normativas de protección de datos médicos. Para consultas adicionales, contacte con el centro médico.', 0, 'J');
    }
    
    // Método auxiliar para crear títulos de sección
    private function crearTituloSeccion($titulo) {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 119, 182);
        $this->SetFillColor(240, 248, 255);
        $this->Cell(0, 10, $titulo, 0, 1, 'L', true);
        $this->Ln(5);
    }
}

// Procesar solicitud de PDF
if (isset($_GET['id_cita']) && isset($_GET['accion']) && $_GET['accion'] === 'generar_pdf_historial') {
    try {
        $id_cita = (int)$_GET['id_cita'];
        
        if (!$id_cita) {
            throw new Exception('ID de cita no válido');
        }
        
        // Obtener datos completos de la cita desde la base de datos
        $conn = Database::getConnection();
        $query = "SELECT 
                    c.id_cita,
                    c.fecha_hora as fecha_hora_cita,
                    c.motivo,
                    c.estado,
                    c.notas,
                    
                    -- Datos del paciente
                    CONCAT(u_pac.nombres, ' ', u_pac.apellidos) as paciente_nombre,
                    u_pac.cedula as paciente_cedula,
                    TIMESTAMPDIFF(YEAR, pac.fecha_nacimiento, CURDATE()) as edad,
                    pac.tipo_sangre,
                    pac.alergias,
                    pac.telefono,
                    
                    -- Datos del doctor y especialidad
                    CONCAT(u_doc.nombres, ' ', u_doc.apellidos) as doctor_nombre,
                    e.nombre_especialidad,
                    
                    -- Datos de la sucursal
                    s.nombre_sucursal,
                    
                    -- Datos del triaje
                    t.id_triage,
                    t.fecha_hora as fecha_hora_triaje,
                    t.nivel_urgencia,
                    t.estado_triaje,
                    t.temperatura,
                    t.presion_arterial,
                    t.frecuencia_cardiaca,
                    t.frecuencia_respiratoria,
                    t.saturacion_oxigeno,
                    t.peso,
                    t.talla,
                    t.imc,
                    t.observaciones as observaciones_triaje,
                    CONCAT(u_enf.nombres, ' ', u_enf.apellidos) as enfermero_nombre,
                    
                    -- Datos de la consulta médica
                    cm.id_consulta,
                    cm.fecha_hora as fecha_hora_consulta,
                    cm.motivo_consulta,
                    cm.sintomatologia,
                    cm.diagnostico,
                    cm.tratamiento,
                    cm.observaciones as observaciones_consulta,
                    cm.fecha_seguimiento
                    
                  FROM citas c
                  INNER JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                  INNER JOIN usuarios u_pac ON pac.id_usuario = u_pac.id_usuario
                  INNER JOIN doctores d ON c.id_doctor = d.id_doctor
                  INNER JOIN usuarios u_doc ON d.id_usuario = u_doc.id_usuario
                  INNER JOIN especialidades e ON d.id_especialidad = e.id_especialidad
                  INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                  LEFT JOIN triage t ON c.id_cita = t.id_cita
                  LEFT JOIN usuarios u_enf ON t.id_enfermero = u_enf.id_usuario
                  LEFT JOIN consultas_medicas cm ON c.id_cita = cm.id_cita
                  WHERE c.id_cita = :id_cita";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':id_cita' => $id_cita]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cita) {
            throw new Exception('Cita no encontrada en el historial');
        }
        
        // Generar PDF
        $pdf = new GeneradorPDFHistorial($cita);
        $pdf->generarContenido();
        
        // Nombre del archivo
        $nombre_archivo = "Historial_Cita_" . $id_cita . "_" . date('Y-m-d_H-i') . ".pdf";
        
        // Enviar PDF al navegador
        $pdf->Output($nombre_archivo, 'D');
        
    } catch (Exception $e) {
        error_log("Error generando PDF historial: " . $e->getMessage());
        die("Error al generar el PDF: " . $e->getMessage());
    }
}
?>