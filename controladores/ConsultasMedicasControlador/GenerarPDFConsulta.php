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
    // Sección: Triaje - VERSIÓN COMPLETA Y MEJORADA
private function seccionTriaje() {
    $this->crearTituloSeccion('TRIAJE Y SIGNOS VITALES');
    
    // Solo mostrar si hay datos de triaje
    if (empty($this->citaData['nivel_urgencia']) && empty($this->citaData['temperatura'])) {
        $this->SetFont('helvetica', 'I', 10);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 8, 'No se realizó triaje para esta cita', 0, 1, 'C');
        $this->Ln(8);
        return;
    }
    
    // TABLA DE SIGNOS VITALES
    $this->SetFillColor(255, 248, 220); // Fondo amarillo claro
    $this->SetFont('helvetica', 'B', 10);
    $this->SetTextColor(184, 134, 11); // Texto dorado oscuro
    // Headers de la tabla
    $this->Cell(60, 8, 'Signo Vital / Parámetro', 1, 0, 'C', true);
    $this->Cell(50, 8, 'Valor', 1, 0, 'C', true);
    $this->Cell(60, 8, 'Referencia Normal', 1, 1, 'C', true);
    
    // Preparar datos de triaje
    $signos_vitales = [
        [
            'signo' => 'Temperatura Corporal',
            'valor' => $this->citaData['temperatura'] ? $this->citaData['temperatura'] . '°C' : 'No registrada',
            'referencia' => '36.0 - 37.5°C'
        ],
        [
            'signo' => 'Presión Arterial',
            'valor' => $this->citaData['presion_arterial'] ?? 'No registrada',
            'referencia' => '120/80 mmHg'
        ],
        [
            'signo' => 'Frecuencia Cardíaca',
            'valor' => $this->citaData['frecuencia_cardiaca'] ? $this->citaData['frecuencia_cardiaca'] . ' lpm' : 'No registrada',
            'referencia' => '60 - 100 lpm'
        ],
        [
            'signo' => 'Saturación de Oxígeno',
            'valor' => isset($this->citaData['saturacion_oxigeno']) ? $this->citaData['saturacion_oxigeno'] . '%' : 'No registrada',
            'referencia' => '≥ 95%'
        ],
        [
            'signo' => 'Frecuencia Respiratoria',
            'valor' => isset($this->citaData['frecuencia_respiratoria']) ? $this->citaData['frecuencia_respiratoria'] . ' rpm' : 'No registrada',
            'referencia' => '12 - 20 rpm'
        ]
    ];
    
    // Mostrar signos vitales
    $this->SetFont('helvetica', '', 9);
    $this->SetTextColor(51, 51, 51);
    $fill = false;
    
    foreach ($signos_vitales as $signo) {
        // Determinar color según si el valor está fuera de rango
        $esAnormal = $this->evaluarSignoVital($signo['signo'], $signo['valor']);
        
        if ($esAnormal && $signo['valor'] !== 'No registrada') {
            $this->SetFillColor(255, 235, 235); // Fondo rojo claro para valores anormales
            $this->SetTextColor(220, 38, 38); // Texto rojo
        } else {
            // ✅ CORRECCIÓN: Colores alternados correctos
            if ($fill) {
                $this->SetFillColor(248, 249, 250); // Fondo gris claro
            } else {
                $this->SetFillColor(255, 255, 255); // Fondo blanco
            }
            $this->SetTextColor(51, 51, 51); // Texto negro normal
        }
        
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(60, 7, $signo['signo'], 1, 0, 'L', true);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(50, 7, $signo['valor'], 1, 0, 'C', true);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(107, 114, 128); // Color gris para referencia
        $this->Cell(60, 7, $signo['referencia'], 1, 1, 'C', true);
        
        // ✅ Resetear color del texto para la siguiente fila
        $this->SetTextColor(51, 51, 51);
        
        $fill = !$fill;
    }
    $this->Ln(5);
    
    // MEDIDAS ANTROPOMÉTRICAS
    if ($this->citaData['peso'] || $this->citaData['talla']) {
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 8, 'MEDIDAS ANTROPOMÉTRICAS', 0, 1, 'L');
        
        $this->SetFillColor(240, 249, 255); // Fondo azul muy claro
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(30, 64, 175);
        
        $this->Cell(60, 8, 'Medida', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Valor', 1, 0, 'C', true);
        $this->Cell(60, 8, 'Categoría/Estado', 1, 1, 'C', true);
        
        $medidas = [
            [
                'medida' => 'Peso Corporal',
                'valor' => $this->citaData['peso'] ? $this->citaData['peso'] . ' kg' : 'No registrado',
                'categoria' => '-'
            ],
            [
                'medida' => 'Talla/Estatura',
                'valor' => $this->citaData['talla'] ? $this->citaData['talla'] . ' cm' : 'No registrada',
                'categoria' => '-'
            ]
        ];
        
        // Calcular IMC si tenemos peso y talla
        if ($this->citaData['peso'] && $this->citaData['talla']) {
            $imc = round($this->citaData['peso'] / pow($this->citaData['talla'] / 100, 2), 1);
            $categoria_imc = $this->categorizarIMC($imc);
            
            $medidas[] = [
                'medida' => 'Índice de Masa Corporal (IMC)',
                'valor' => $imc . ' kg/m²',
                'categoria' => $categoria_imc
            ];
        }
        
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(51, 51, 51);
        $fill = false;
        
        foreach ($medidas as $medida) {
            // ✅ CORRECCIÓN: Colores alternados correctos para medidas
            if ($fill) {
                $this->SetFillColor(248, 249, 250); // Fondo gris claro
            } else {
                $this->SetFillColor(255, 255, 255); // Fondo blanco
            }
            
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(60, 7, $medida['medida'], 1, 0, 'L', $fill);
            $this->SetFont('helvetica', '', 9);
            $this->Cell(50, 7, $medida['valor'], 1, 0, 'C', $fill);
            $this->Cell(60, 7, $medida['categoria'], 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        $this->Ln(5);
    }
    
    
    // NIVEL DE URGENCIA
    if ($this->citaData['nivel_urgencia']) {
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 8, 'CLASIFICACIÓN DE URGENCIA', 0, 1, 'L');
        
        $nivel = (int)$this->citaData['nivel_urgencia'];
        $info_urgencia = $this->obtenerInfoUrgencia($nivel);
        
        // Frame colorido según nivel de urgencia
        $this->SetFillColor($info_urgencia['color_r'], $info_urgencia['color_g'], $info_urgencia['color_b']);
        $this->Rect($this->GetX(), $this->GetY(), 170, 15, 'F');
        
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(170, 15, 
            $info_urgencia['icono'] . ' NIVEL ' . $nivel . ' - ' . strtoupper($info_urgencia['nombre']), 
            1, 1, 'C', false);
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $this->Cell(170, 8, $info_urgencia['descripcion'], 1, 1, 'C');
        
        $this->Ln(5);
    }
    
    // OBSERVACIONES DEL TRIAJE
    if (!empty($this->citaData['triaje_observaciones'])) {
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 119, 182);
        $this->Cell(0, 8, 'OBSERVACIONES DEL TRIAJE', 0, 1, 'L');
        
        $this->SetFillColor(254, 249, 195); // Fondo amarillo muy claro
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        $this->MultiCell(0, 6, $this->citaData['triaje_observaciones'], 1, 'L', true);
        $this->Ln(5);
    }
    
    $this->Ln(8);
}

// Método auxiliar: Evaluar si un signo vital está fuera del rango normal
private function evaluarSignoVital($signo, $valor) {
    if ($valor === 'No registrada' || $valor === 'No registrado') {
        return false;
    }
    
    $numerico = (float)preg_replace('/[^0-9.]/', '', $valor);
    
    switch ($signo) {
        case 'Temperatura Corporal':
            return $numerico < 36.0 || $numerico > 37.5;
        case 'Frecuencia Cardíaca':
            return $numerico < 60 || $numerico > 100;
        case 'Saturación de Oxígeno':
            return $numerico < 95;
        case 'Frecuencia Respiratoria':
            return $numerico < 12 || $numerico > 20;
        default:
            return false;
    }
}

// Método auxiliar: Categorizar IMC
private function categorizarIMC($imc) {
    if ($imc < 18.5) return 'Bajo peso';
    if ($imc < 25) return 'Peso normal';
    if ($imc < 30) return 'Sobrepeso';
    return 'Obesidad';
}

// Método auxiliar: Obtener información del nivel de urgencia
private function obtenerInfoUrgencia($nivel) {
    return match($nivel) {
        1 => [
            'nombre' => 'Baja',
            'descripcion' => 'Puede esperar - Atención programada',
            'icono' => '🟢',
            'color_r' => 34, 'color_g' => 197, 'color_b' => 94
        ],
        2 => [
            'nombre' => 'Media',
            'descripcion' => 'Atención en 30-60 minutos',
            'icono' => '🟡',
            'color_r' => 245, 'color_g' => 158, 'color_b' => 11
        ],
        3 => [
            'nombre' => 'Alta',
            'descripcion' => 'Atención en 15-30 minutos',
            'icono' => '🟠',
            'color_r' => 249, 'color_g' => 115, 'color_b' => 22
        ],
        4 => [
            'nombre' => 'Crítica',
            'descripcion' => 'Atención inmediata requerida',
            'icono' => '🔴',
            'color_r' => 239, 'color_g' => 68, 'color_b' => 68
        ],
        5 => [
            'nombre' => 'Emergencia',
            'descripcion' => 'Riesgo de vida - Atención INMEDIATA',
            'icono' => '🚨',
            'color_r' => 147, 'color_g' => 51, 'color_b' => 234
        ],
        default => [
            'nombre' => 'No especificado',
            'descripcion' => 'Nivel de urgencia no definido',
            'icono' => '⚪',
            'color_r' => 107, 'color_g' => 114, 'color_b' => 128
        ]
    };
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
            $this->crearTituloSeccion('RECETA MÉDICA Y OBSERVACIONES FINALES');
            
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