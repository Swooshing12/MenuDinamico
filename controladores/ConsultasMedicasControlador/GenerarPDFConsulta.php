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

class GeneradorPDFConsulta extends TCPDF {
    private $citaData;
    
    public function __construct($cita_data) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8');
        $this->citaData = $cita_data;
        
        // Configuración del PDF
        $this->SetCreator('MediSys - Sistema Hospitalario');
        $this->SetAuthor('MediSys');
        $this->SetTitle('Consulta Médica #' . $cita_data['id_cita']);
        $this->SetSubject('Consulta Médica Completada');
        $this->SetKeywords('consulta, médica, MediSys');
        
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
        $this->Cell(0, 8, 'CONSULTA MÉDICA COMPLETADA', 0, 1, 'L');
        
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
        $this->Cell(0, 6, 'Estado: ' . ($this->citaData['estado'] ?? 'Completada'), 0, 1, 'R');
    }
    
    // Footer personalizado
    public function Footer() {
        $this->SetY(-20);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Izquierda: Copyright
        $this->Cell(0, 5, '© ' . date('Y') . ' | MediSys - Sistema de Gestión Hospitalaria', 0, 0, 'L');
        
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
        
        // Información de la cita y doctor
        $this->seccionCitaDoctor();
        
        // Triaje (si existe)
        if (!empty($this->citaData['id_triage'])) {
            $this->seccionTriaje();
        }
        
        // Consulta médica
        $this->seccionConsultaMedica();
        
        // Observaciones finales
        $this->seccionObservacionesFinales();
    }
    
    // 🔥 SECCIÓN ESPECÍFICA: Datos del paciente
    private function seccionPaciente() {
        $this->crearTituloSeccion('INFORMACIÓN DEL PACIENTE');
        
        // Crear tabla de información básica
        $this->SetFillColor(227, 242, 253);
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        
        // Headers de la tabla
        $this->Cell(50, 8, 'Campo', 1, 0, 'C', true);
        $this->Cell(120, 8, 'Información', 1, 1, 'C', true);
        
        // Datos del paciente usando los datos correctos de la consulta
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $this->SetFillColor(248, 249, 250);
        
        $nombre_completo = trim(($this->citaData['nombres_paciente'] ?? '') . ' ' . ($this->citaData['apellidos_paciente'] ?? ''));
        $cedula_paciente = $this->citaData['cedula_paciente'] ?? 'No especificada';
        $correo_paciente = $this->citaData['correo_paciente'] ?? 'No especificado';
        $telefono_paciente = $this->citaData['telefono'] ?? 'No especificado';
        $tipo_sangre = $this->citaData['tipo_sangre'] ?? 'No especificado';
        
        $fecha_nacimiento = 'No especificada';
        if (!empty($this->citaData['fecha_nacimiento'])) {
            $fecha_nacimiento = date('d/m/Y', strtotime($this->citaData['fecha_nacimiento']));
        }
        
        $datos_paciente = [
            ['Nombre Completo', $nombre_completo ?: 'No especificado'],
            ['Cédula de Identidad', $cedula_paciente],
            ['Correo Electrónico', $correo_paciente],
            ['Teléfono', $telefono_paciente],
            ['Fecha de Nacimiento', $fecha_nacimiento],
            ['Tipo de Sangre', $tipo_sangre]
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
    
    // Sección: Información de la cita y doctor
    private function seccionCitaDoctor() {
        $this->crearTituloSeccion('INFORMACIÓN DE LA CONSULTA');
        
        $this->SetFillColor(240, 248, 255);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 119, 182);
        
        // Headers
        $this->Cell(50, 7, 'Detalle', 1, 0, 'C', true);
        $this->Cell(120, 7, 'Información', 1, 1, 'C', true);
        
        $fecha_cita = 'No especificada';
        if (!empty($this->citaData['fecha_hora'])) {
            $fecha_cita = date('d/m/Y H:i', strtotime($this->citaData['fecha_hora']));
        }
        
        $doctor_nombre = trim(($this->citaData['nombres_doctor'] ?? '') . ' ' . ($this->citaData['apellidos_doctor'] ?? ''));
        $titulo_profesional = $this->citaData['titulo_profesional'] ?? 'Médico Especialista';
        
        $datos_consulta = [
            ['Fecha y Hora de Cita', $fecha_cita],
            ['Motivo Original', $this->citaData['motivo'] ?? 'No especificado'],
            ['Médico Tratante', $doctor_nombre ?: 'No especificado'],
            ['Título Profesional', $titulo_profesional],
            ['Especialidad', $this->citaData['nombre_especialidad'] ?? 'No especificada'],
            ['Sucursal', $this->citaData['nombre_sucursal'] ?? 'No especificada'],
            ['Fecha de Consulta', date('d/m/Y H:i')]
        ];
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $fill = false;
        
        foreach ($datos_consulta as $dato) {
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
        $this->crearTituloSeccion('TRIAJE Y SIGNOS VITALES');
        
        $this->SetFillColor(255, 248, 220);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(184, 134, 11);
        
        // Headers
        $this->Cell(50, 7, 'Signo Vital', 1, 0, 'C', true);
        $this->Cell(120, 7, 'Valor', 1, 1, 'C', true);
        
        $datos_triaje = [
            ['Temperatura', ($this->citaData['temperatura'] ?? '-') . '°C'],
            ['Presión Arterial', $this->citaData['presion_arterial'] ?? '-'],
            ['Frecuencia Cardíaca', ($this->citaData['frecuencia_cardiaca'] ?? '-') . ' lpm'],
            ['Peso', ($this->citaData['peso'] ?? '-') . ' kg'],
            ['Talla', ($this->citaData['talla'] ?? '-') . ' cm'],
            ['IMC', $this->citaData['imc'] ?? '-'],
            ['Nivel de Urgencia', ($this->citaData['nivel_urgencia'] ?? '-') . '/5']
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
        
        if (!empty($this->citaData['triaje_observaciones'])) {
            $this->Ln(3);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 6, 'Observaciones del Triaje:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell(0, 5, $this->citaData['triaje_observaciones'], 1, 'L');
        }
        
        $this->Ln(8);
    }
    
    // Sección: Consulta médica
    private function seccionConsultaMedica() {
        $this->crearTituloSeccion('CONSULTA MÉDICA');
        
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
        if (!empty($this->citaData['consulta_observaciones'])) {
            $this->crearTituloSeccion('OBSERVACIONES ADICIONALES');
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 5, $this->citaData['consulta_observaciones'], 1, 'L');
            $this->Ln(5);
        }
        
        // Pie de página informativo
        $this->Ln(10);
        $this->SetFont('helvetica', 'I', 9);
        $this->SetTextColor(108, 117, 125);
        $this->MultiCell(0, 4, 'Este documento ha sido generado automáticamente por el Sistema MediSys y contiene información médica confidencial. Debe ser tratado con la debida confidencialidad según las normativas de protección de datos médicos.', 0, 'J');
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
?>