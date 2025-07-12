<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../modelos/PacienteCitas.php";

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


class GeneradorPDFCita extends TCPDF {
    private $pacienteCitas;
    private $citaData;
    
    public function __construct($cita_data) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8');
        $this->pacienteCitas = new PacienteCitas();
        $this->citaData = $cita_data;
        
        // Configuración del PDF
        $this->SetCreator('MediSys - Sistema Hospitalario');
        $this->SetAuthor('MediSys');
        $this->SetTitle('Detalle de Cita Médica #' . $cita_data['id_cita']);
        $this->SetSubject('Detalle de Cita Médica');
        $this->SetKeywords('cita, médica, detalle, MediSys');
        
        // Configurar márgenes (izq, arriba, der)
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
        
        // Logo/Icono médico (simulado con texto)
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
        $this->Cell(0, 8, 'DETALLE DE CITA MÉDICA', 0, 1, 'L');
        
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
        $this->Cell(0, 6, 'Estado: ' . $this->citaData['estado'], 0, 1, 'R');
        
        $this->Ln(15);
    }
    
    // Footer personalizado
    public function Footer() {
        $this->SetY(-20);
        
        // Línea decorativa
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(0, 119, 182);
        $this->Line(20, $this->GetY(), 190, $this->GetY());
        
        $this->Ln(3);
        
        // Información del footer
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(108, 117, 125);
        
        // Izquierda: Información del sistema
        $this->SetX(20);
        $this->Cell(0, 5, 'MediSys © ' . date('Y') . ' | Sistema de Gestión Hospitalaria', 0, 0, 'L');
        
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
        
        // Información del paciente y datos básicos
        $this->seccionDatosBasicos();
        
        // Información de la cita
        $this->seccionInformacionCita();
        
        // Información del médico y sucursal
        $this->seccionMedicoSucursal();
        
        // Triaje (si existe)
        if (!empty($this->citaData['id_triage'])) {
            $this->seccionTriaje();
        }
        
        // Consultas médicas (si existen)
        if (!empty($this->citaData['consultas'])) {
            $this->seccionConsultas();
        } else {
            $this->seccionSinConsultas();
        }
        
        // Observaciones finales
        $this->seccionObservacionesFinales();
    }
    
    // Sección: Datos básicos del paciente
    private function seccionDatosBasicos() {
        $this->crearTituloSeccion('INFORMACIÓN DEL PACIENTE');
        
        // Crear tabla de información básica
        $this->SetFillColor(227, 242, 253); // Azul claro
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
            ['Nombre del Paciente', $_SESSION['username']],
            ['Fecha de Generación', date('d/m/Y H:i:s')],
            ['ID de Cita', '#' . $this->citaData['id_cita']],
            ['Fecha de Creación', $this->formatearFecha($this->citaData['fecha_creacion'])]
        ];
        
        $fill = false;
        foreach ($datos_paciente as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(120, 7, $dato[1], 1, 1, 'L', $fill);
            $fill = !$fill;
        }
        
        $this->Ln(10);
    }
    
    // Sección: Información de la cita - CORREGIDA
    private function seccionInformacionCita() {
        $this->crearTituloSeccion('DETALLES DE LA CITA MÉDICA');
        
        // Información básica de la cita en una tabla completa
        $this->SetFillColor(248, 249, 250);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 119, 182);
        
        // Headers
        $this->Cell(50, 8, 'Información', 1, 0, 'C', true);
        $this->Cell(120, 8, 'Detalles', 1, 1, 'C', true);
        
        // Datos de la cita
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        
        $datos_cita = [
            ['Fecha de la Cita', $this->formatearFecha($this->citaData['fecha_hora'])],
            ['Hora de la Cita', $this->formatearHora($this->citaData['fecha_hora'])],
            ['Tipo de Cita', ucfirst($this->citaData['tipo_cita'])],
            ['Estado Actual', $this->citaData['estado']],
            ['Motivo de Consulta', $this->citaData['motivo']],
            ['Tipo de Atención', $this->citaData['nombre_tipo'] ?: 'Consulta General']
        ];
        
        $fill = false;
        foreach ($datos_cita as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            
            // Para textos largos, usar MultiCell
            $x = $this->GetX();
            $y = $this->GetY();
            $this->MultiCell(120, 7, $dato[1], 1, 'L', $fill);
            
            // Asegurar que la siguiente fila esté en la posición correcta
            $newY = $this->GetY();
            if ($newY == $y + 7) {
                // Si no hubo salto de línea, continuar normal
            } else {
                // Si hubo salto de línea, ajustar
                $this->SetXY(20, $newY);
            }
            
            $fill = !$fill;
        }
        
        // Notas adicionales (si existen)
        if (!empty($this->citaData['notas'])) {
            $this->Ln(5);
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 6, 'Notas Adicionales:', 0, 1, 'L');
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->SetFillColor(255, 248, 220);
            $this->MultiCell(170, 6, $this->citaData['notas'], 1, 'L', true);
        }
        
        $this->Ln(10);
    }
    
    // Sección: Médico y sucursal - CORREGIDA
    private function seccionMedicoSucursal() {
        $this->crearTituloSeccion('EQUIPO MÉDICO Y UBICACIÓN');
        
        // MÉDICO - Tabla completa
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 8, 'MÉDICO TRATANTE', 0, 1, 'L');
        
        $this->SetFillColor(227, 242, 253);
        $this->SetFont('helvetica', 'B', 10);
        
        // Headers médico
        $this->Cell(50, 7, 'Información', 1, 0, 'C', true);
        $this->Cell(120, 7, 'Detalles', 1, 1, 'C', true);
        
        $datos_medico = [
            ['Nombre Completo', $this->citaData['doctor_nombre']],
            ['Título Profesional', $this->citaData['titulo_profesional'] ?: 'Médico Especialista'],
            ['Especialidad', $this->citaData['nombre_especialidad']],
            ['Correo Electrónico', $this->citaData['doctor_correo'] ?: 'No disponible']
        ];
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $fill = false;
        
        foreach ($datos_medico as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(120, 7, $dato[1], 1, 1, 'L', $fill);
            $fill = !$fill;
        }
        
        $this->Ln(8);
        
        // SUCURSAL - Tabla completa
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 8, 'CENTRO MÉDICO', 0, 1, 'L');
        
        $this->SetFillColor(240, 248, 255);
        $this->SetFont('helvetica', 'B', 10);
        
        // Headers sucursal
        $this->Cell(50, 7, 'Información', 1, 0, 'C', true);
        $this->Cell(120, 7, 'Detalles', 1, 1, 'C', true);
        
        $datos_sucursal = [
            ['Nombre', $this->citaData['nombre_sucursal']],
            ['Dirección', $this->citaData['sucursal_direccion']],
            ['Teléfono', $this->citaData['sucursal_telefono'] ?: 'No disponible'],
            ['Email', $this->citaData['sucursal_email'] ?: 'No disponible'],
            ['Horario de Atención', $this->citaData['horario_atencion'] ?: 'Consultar con el centro médico']
        ];
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $fill = false;
        
        foreach ($datos_sucursal as $dato) {
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(50, 7, $dato[0], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 10);
            
            // Para direcciones largas, usar MultiCell
            if ($dato[0] === 'Dirección' && strlen($dato[1]) > 50) {
                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell(120, 7, $dato[1], 1, 'L', $fill);
                $newY = $this->GetY();
                if ($newY > $y + 7) {
                    $this->SetXY(20, $newY);
                }
            } else {
                $this->Cell(120, 7, $dato[1], 1, 1, 'L', $fill);
            }
            
            $fill = !$fill;
        }
        
        $this->Ln(10);
    }
    
    // Sección: Triaje - SIMPLIFICADA
    private function seccionTriaje() {
        $this->crearTituloSeccion('INFORMACIÓN DE TRIAJE');
        
        // Información general del triaje
        $this->SetFillColor(255, 243, 224);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 119, 182);
        
        // Headers
        $this->Cell(50, 8, 'Información', 1, 0, 'C', true);
        $this->Cell(120, 8, 'Detalles', 1, 1, 'C', true);
        
        $fecha_triaje = $this->formatearFecha($this->citaData['triaje_fecha']);
        $nivel_urgencia = $this->obtenerNivelUrgencia($this->citaData['nivel_urgencia']);
        
        $datos_triaje = [
            ['Fecha de Triaje', $fecha_triaje],
            ['Estado del Triaje', $this->citaData['estado_triaje']],
            ['Nivel de Urgencia', $nivel_urgencia],
            ['Realizado por', $this->citaData['enfermero_nombre'] ?: 'Personal de Enfermería']
        ];
        
        // Agregar signos vitales si existen
        if ($this->citaData['temperatura']) $datos_triaje[] = ['Temperatura', $this->citaData['temperatura'] . '°C'];
        if ($this->citaData['presion_arterial']) $datos_triaje[] = ['Presión Arterial', $this->citaData['presion_arterial'] . ' mmHg'];
        if ($this->citaData['frecuencia_cardiaca']) $datos_triaje[] = ['Frecuencia Cardíaca', $this->citaData['frecuencia_cardiaca'] . ' bpm'];
        if ($this->citaData['frecuencia_respiratoria']) $datos_triaje[] = ['Frecuencia Respiratoria', $this->citaData['frecuencia_respiratoria'] . ' rpm'];
        if ($this->citaData['saturacion_oxigeno']) $datos_triaje[] = ['Saturación O₂', $this->citaData['saturacion_oxigeno'] . '%'];
        if ($this->citaData['peso']) $datos_triaje[] = ['Peso', $this->citaData['peso'] . ' kg'];
        if ($this->citaData['talla']) $datos_triaje[] = ['Talla', $this->citaData['talla'] . ' cm'];
        if ($this->citaData['imc']) $datos_triaje[] = ['IMC', $this->citaData['imc']];
        
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
        
        // Observaciones del triaje
        if (!empty($this->citaData['triaje_observaciones'])) {
            $this->Ln(5);
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 6, 'Observaciones del Triaje:', 0, 1, 'L');
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->SetFillColor(255, 243, 224);
            $this->MultiCell(170, 6, $this->citaData['triaje_observaciones'], 1, 'L', true);
        }
        
        $this->Ln(10);
    }
    
    // Resto de métodos igual que antes...
    private function seccionConsultas() {
        $this->crearTituloSeccion('CONSULTAS MÉDICAS REALIZADAS');
        
        foreach ($this->citaData['consultas'] as $index => $consulta) {
            $numero_consulta = $index + 1;
            $fecha_consulta = $this->formatearFecha($consulta['fecha_consulta']);
            
            // Título de la consulta
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(0, 119, 182);
            $this->Cell(0, 8, "Consulta #{$numero_consulta} - {$fecha_consulta}", 0, 1, 'L');
            
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(108, 117, 125);
            $this->Cell(0, 5, 'Médico: Dr. ' . $consulta['medico_nombre'], 0, 1, 'L');
            $this->Ln(3);
            
            // Tabla de detalles
            $this->SetFillColor(248, 249, 250);
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(0, 119, 182);
            
            $this->Cell(50, 7, 'Aspecto', 1, 0, 'C', true);
            $this->Cell(120, 7, 'Descripción', 1, 1, 'C', true);
            
            $detalles = [];
            if ($consulta['motivo_consulta']) $detalles[] = ['Motivo de Consulta', $consulta['motivo_consulta']];
            if ($consulta['sintomatologia']) $detalles[] = ['Sintomatología', $consulta['sintomatologia']];
            if ($consulta['diagnostico']) $detalles[] = ['Diagnóstico', $consulta['diagnostico']];
            if ($consulta['tratamiento']) $detalles[] = ['Tratamiento', $consulta['tratamiento']];
            if ($consulta['observaciones']) $detalles[] = ['Observaciones', $consulta['observaciones']];
            if ($consulta['fecha_seguimiento']) $detalles[] = ['Fecha de Seguimiento', $this->formatearFecha($consulta['fecha_seguimiento'])];
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $fill = false;
            
            foreach ($detalles as $detalle) {
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell(50, 7, $detalle[0], 1, 0, 'L', $fill);
                $this->SetFont('helvetica', '', 10);
                
                // Usar MultiCell para textos largos
                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell(120, 7, $detalle[1], 1, 'L', $fill);
                $newY = $this->GetY();
                if ($newY > $y + 7) {
                    $this->SetXY(20, $newY);
                }
                
                $fill = !$fill;
            }
            
            $this->Ln(8);
        }
    }
    
    private function seccionSinConsultas() {
        $this->crearTituloSeccion('ESTADO DE LA CONSULTA');
        
        $estado_info = $this->obtenerInfoEstado($this->citaData['estado']);
        
        $this->SetFillColor(248, 249, 250);
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        
        $this->MultiCell(170, 6, "Estado: " . $this->citaData['estado'], 1, 'L', true);
        $this->MultiCell(170, 6, "Descripción: " . $estado_info['descripcion'], 1, 'L', true);
        $this->MultiCell(170, 6, "Información: " . $estado_info['info_adicional'], 1, 'L', true);
        
        $this->Ln(8);
    }
    
    private function seccionObservacionesFinales() {
        $this->crearTituloSeccion('INFORMACIÓN ADICIONAL');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(108, 117, 125);
        
        $notas_finales = [
            "• Este documento es una copia del registro médico oficial.",
            "• Para consultas adicionales, contacte al centro médico correspondiente.",
            "• Mantenga este documento para sus registros personales.",
            "• En caso de emergencia, presente este documento al personal médico."
        ];
        
        foreach ($notas_finales as $nota) {
            $this->Cell(0, 5, $nota, 0, 1, 'L');
        }
        
        $this->Ln(5);
        
        // Información de contacto
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 6, 'Información de Contacto:', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(51, 51, 51);
        $this->Cell(0, 5, 'Centro Médico: ' . $this->citaData['nombre_sucursal'], 0, 1, 'L');
        if ($this->citaData['sucursal_telefono']) {
            $this->Cell(0, 5, 'Teléfono: ' . $this->citaData['sucursal_telefono'], 0, 1, 'L');
        }
        if ($this->citaData['sucursal_email']) {
            $this->Cell(0, 5, 'Email: ' . $this->citaData['sucursal_email'], 0, 1, 'L');
        }
    }
    
    // Método auxiliar para título de sección
    private function crearTituloSeccion($titulo) {
        // Fondo de la sección
        $this->SetFillColor(0, 119, 182);
        $this->Rect($this->GetX(), $this->GetY(), 170, 10, 'F');
        
        // Texto del título
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(170, 10, $titulo, 0, 1, 'C', false);
        $this->Ln(3);
    }
    
    // Métodos auxiliares para formateo (mantener iguales)
    private function formatearFecha($fecha) {
        if (empty($fecha)) return 'No disponible';
        
        try {
            $fecha_obj = new DateTime($fecha);
            $dias_semana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            
            $dia_semana = $dias_semana[$fecha_obj->format('w')];
            $dia = $fecha_obj->format('d');
            $mes = $meses[$fecha_obj->format('n')];
            $año = $fecha_obj->format('Y');
            
            return "{$dia_semana}, {$dia}/{$mes}/{$año}";
        } catch (Exception $e) {
            return 'Fecha inválida';
        }
    }
    
    private function formatearHora($fecha) {
        if (empty($fecha)) return '--:--';
        
        try {
            $fecha_obj = new DateTime($fecha);
            return $fecha_obj->format('H:i');
        } catch (Exception $e) {
            return '--:--';
        }
    }
    
    private function obtenerNivelUrgencia($nivel) {
        $niveles = [
            1 => 'Baja (1)',
            2 => 'Normal (2)', 
            3 => 'Media (3)',
            4 => 'Alta (4)',
            5 => 'Crítica (5)'
        ];
        
        return $niveles[$nivel] ?? 'Sin clasificar';
    }
    
    private function obtenerInfoEstado($estado) {
        $estados = [
            'Pendiente' => [
                'descripcion' => 'La cita está programada pero pendiente de confirmación',
                'info_adicional' => 'Espere la confirmación del centro médico'
            ],
            'Confirmada' => [
                'descripcion' => 'La cita ha sido confirmada y está programada',
                'info_adicional' => 'Asista puntualmente a su cita'
            ],
            'Completada' => [
                'descripcion' => 'La consulta médica ha sido realizada exitosamente',
                'info_adicional' => 'Consulta finalizada con éxito'
            ],
            'Cancelada' => [
                'descripcion' => 'La cita fue cancelada',
                'info_adicional' => 'Puede reprogramar una nueva cita si lo desea'
            ],
            'No Asistio' => [
                'descripcion' => 'El paciente no asistió a la cita programada',
                'info_adicional' => 'Puede reprogramar una nueva cita'
            ]
        ];
        
        return $estados[$estado] ?? [
            'descripcion' => 'Estado no definido',
            'info_adicional' => 'Consulte con el centro médico' 
            ];
   }
}

// Procesar solicitud de PDF
if (isset($_GET['id_cita']) && isset($_GET['accion']) && $_GET['accion'] === 'generar_pdf') {
   try {
       // Obtener ID del paciente
       $conn = Database::getConnection();
       $query = "SELECT id_paciente FROM pacientes WHERE id_usuario = :id_usuario";
       $stmt = $conn->prepare($query);
       $stmt->execute([':id_usuario' => $_SESSION['id_usuario']]);
       $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
       
       if (!$paciente) {
           throw new Exception('Paciente no encontrado');
       }
       
       // Obtener detalles de la cita
       $pacienteCitas = new PacienteCitas();
       $cita = $pacienteCitas->obtenerDetalleCita($_GET['id_cita'], $paciente['id_paciente']);
       
       if (!$cita) {
           throw new Exception('Cita no encontrada');
       }
       
       // Generar PDF
       $pdf = new GeneradorPDFCita($cita);
       $pdf->generarContenido();
       
       // Nombre del archivo
       $nombre_archivo = "Cita_Medica_" . $cita['id_cita'] . "_" . date('Y-m-d') . ".pdf";
       
       // Enviar PDF al navegador
       $pdf->Output($nombre_archivo, 'D');
       
   } catch (Exception $e) {
       error_log("Error generando PDF: " . $e->getMessage());
       echo "<script>alert('Error al generar el PDF: " . $e->getMessage() . "'); history.back();</script>";
   }
}
?>