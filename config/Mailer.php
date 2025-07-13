<?php
/**
 * Configuración de envío de correos para MediSys
 */

// Verifica si PHPMailer está instalado
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Si no está instalado vía Composer, usar la versión manual
    require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;
    private $debug = true;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    private function configurarSMTP() {
        try {
            // Configuración del servidor SMTP (ajusta según tu proveedor)
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com'; // o tu servidor SMTP
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'swooshing14@gmail.com'; // Tu email
            $this->mail->Password   = 'afuw rcsw mvxs qwbq'; // Tu contraseña de aplicación
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            // Configuración general
            $this->mail->setFrom('noreply@medisys.com', 'MediSys - Sistema Hospitalario');
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            
            if ($this->debug) {
                error_log("✅ Mailer configurado correctamente");
            }
            
        } catch (Exception $e) {
            error_log("❌ Error configurando mailer: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar PDF de cita al paciente
     */
    public function enviarPDFCita($correo_paciente, $nombre_paciente, $cita_data, $pdf_content) {
        try {
            // Limpiar destinatarios previos
            $this->mail->clearAddresses();
            
            // Configurar destinatario
            $this->mail->addAddress($correo_paciente, $nombre_paciente);
            
            // Asunto
            $this->mail->Subject = "🏥 Detalle de su Cita Médica #" . $cita_data['id_cita'] . " - MediSys";
            
            // Contenido del correo
            $contenido_html = $this->generarContenidoCorreo($cita_data);
            $this->mail->Body = $contenido_html;
            
            // Adjuntar PDF
            $nombre_archivo = "Cita_Medica_" . $cita_data['id_cita'] . "_" . date('Y-m-d') . ".pdf";
            $this->mail->addStringAttachment($pdf_content, $nombre_archivo, 'base64', 'application/pdf');
            
            // Enviar correo
            $enviado = $this->mail->send();
            
            if ($enviado && $this->debug) {
                error_log("✅ PDF enviado exitosamente a: " . $correo_paciente);
            }
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("❌ Error enviando PDF: " . $e->getMessage());
            return false;
        }
    }
    
   /**
 * Generar contenido HTML del correo
 */
private function generarContenidoCorreo($cita_data) {
    $fecha_cita = date('d/m/Y H:i', strtotime($cita_data['fecha_hora']));
    
    // Obtener datos adicionales para mostrar más información
    $nombre_paciente = trim(($cita_data['nombres_paciente'] ?? '') . ' ' . ($cita_data['apellidos_paciente'] ?? ''));
    $doctor_nombre = trim(($cita_data['nombres_doctor'] ?? '') . ' ' . ($cita_data['apellidos_doctor'] ?? ''));
    $especialidad = $cita_data['nombre_especialidad'] ?? 'Medicina General';
    $sucursal = $cita_data['nombre_sucursal'] ?? 'Centro Médico';
    $estado = $cita_data['estado'] ?? 'Completada';
    
    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>MediSys - Consulta Médica</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f7fa;
                line-height: 1.6;
            }
            
            .container {
                max-width: 650px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            
            .header {
                background: linear-gradient(135deg, #0d6efd, #0dcaf0);
                color: #070707ff;
                padding: 30px 25px;
                text-align: center;
                position: relative;
            }
            
            .header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><circle cx=\"20\" cy=\"20\" r=\"2\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"80\" cy=\"30\" r=\"1.5\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"40\" cy=\"70\" r=\"1\" fill=\"white\" opacity=\"0.1\"/></svg>');
            }
            
            .header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                position: relative;
                z-index: 1;
            }
            
            .header h2 {
                margin: 10px 0 0 0;
                font-size: 18px;
                font-weight: 400;
                opacity: 0.95;
                position: relative;
                z-index: 1;
            }
            
            .content {
                padding: 30px 25px;
                color: #2c3e50;
            }
            
            .greeting {
                font-size: 16px;
                color: #34495e;
                margin-bottom: 20px;
            }
            
            .intro {
                font-size: 15px;
                color: #5a6c7d;
                margin-bottom: 25px;
                background-color: #e8f4f8;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #0dcaf0;
            }
            
            .cita-info {
                background: linear-gradient(145deg, #ffffff, #f8f9fb);
                border: 1px solid #e9ecef;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            }
            
            .cita-info h3 {
                margin: 0 0 20px 0;
                color: #0d6efd;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .info-grid {
                display: grid;
                gap: 12px;
            }
            
            .info-item {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            
            .info-label {
                font-weight: 600;
                color: #495057;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .info-value {
                color: #2c3e50;
                font-size: 16px;
                font-weight: 500;
                padding: 8px 12px;
                background-color: #f8f9fa;
                border-radius: 6px;
                border-left: 3px solid #0d6efd;
            }
            
            .estado-badge {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .estado-completada {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .highlight-box {
                background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
                border: 1px solid #bbdefb;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
            }
            
            .pdf-notice {
                color: #1565c0;
                font-weight: 600;
                font-size: 15px;
            }
            
            .contact-info {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                color: #856404;
                text-align: center;
            }
            
            .footer {
                background: linear-gradient(135deg, #2c3e50, #34495e);
                color: #ecf0f1;
                padding: 25px;
                text-align: center;
            }
            
            .footer p {
                margin: 8px 0;
                font-size: 13px;
            }
            
            .footer .company-name {
                font-weight: 700;
                font-size: 15px;
                margin-bottom: 5px;
            }
            
            .footer .disclaimer {
                opacity: 0.8;
                font-style: italic;
            }
            
            @media only screen and (max-width: 600px) {
                .container {
                    margin: 10px;
                    border-radius: 8px;
                }
                
                .header, .content, .footer {
                    padding: 20px 15px;
                }
                
                .header h1 {
                    font-size: 26px;
                }
                
                .cita-info {
                    padding: 20px 15px;
                }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏥 MediSys</h1>
                <h2>Consulta Médica Completada</h2>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Estimado/a <strong>" . ($nombre_paciente ?: 'Paciente') . "</strong>,
                </div>
                
                <div class='intro'>
                    📋 Su consulta médica ha sido completada exitosamente. Adjunto encontrará el reporte detallado con toda la información de su atención médica.
                </div>
                
                <div class='cita-info'>
                    <h3>📅 Resumen de su Consulta</h3>
                    
                    <div class='info-grid'>
                        <div class='info-item'>
                            <div class='info-label'>Número de Cita</div>
                            <div class='info-value'>#" . $cita_data['id_cita'] . "</div>
                        </div>
                        
                        <div class='info-item'>
                            <div class='info-label'>Fecha y Hora</div>
                            <div class='info-value'>📅 " . $fecha_cita . "</div>
                        </div>
                        
                        <div class='info-item'>
                            <div class='info-label'>Médico Tratante</div>
                            <div class='info-value'>👨‍⚕️ " . ($doctor_nombre ?: 'Dr. No especificado') . "</div>
                        </div>
                        
                        <div class='info-item'>
                            <div class='info-label'>Especialidad</div>
                            <div class='info-value'>🩺 " . $especialidad . "</div>
                        </div>
                        
                        <div class='info-item'>
                            <div class='info-label'>Centro Médico</div>
                            <div class='info-value'>🏥 " . $sucursal . "</div>
                        </div>
                        
                        <div class='info-item'>
                            <div class='info-label'>Estado</div>
                            <div class='info-value'>
                                <span class='estado-badge estado-completada'>✅ " . $estado . "</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class='highlight-box'>
                    <div class='pdf-notice'>
                        📄 <strong>Reporte Médico Adjunto</strong><br>
                        El documento PDF contiene el detalle completo de su consulta, incluyendo diagnóstico, tratamiento y recomendaciones.
                    </div>
                </div>
                
                <div class='contact-info'>
                    💬 <strong>¿Tiene alguna consulta?</strong><br>
                    No dude en contactarnos para cualquier información adicional sobre su atención médica.
                </div>
                
                <p style='color: #5a6c7d; margin-top: 25px;'>
                    Saludos cordiales,<br>
                    <strong style='color: #0d6efd;'>Equipo MediSys</strong><br>
                    <em>Cuidando su salud con tecnología</em>
                </p>
            </div>
            
            <div class='footer'>
                <p class='company-name'>MediSys - Sistema de Gestión Hospitalaria</p>
                <p>© " . date('Y') . " Todos los derechos reservados</p>
                <p class='disclaimer'>
                    Este es un correo automático generado por nuestro sistema.<br>
                    Por favor, no responda directamente a este mensaje.
                </p>
            </div>
        </div>
    </body>
    </html>";
}
}
?>