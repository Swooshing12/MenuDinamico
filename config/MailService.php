<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService {
    private $mail;
    private $config;
    
    public function __construct() {
        $this->config = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'swooshing14@gmail.com', // Tu correo
            'password' => 'XD',   // Tu contraseña de aplicación
            'from_email' => 'swooshing14@gmail.com',
            'from_name' => 'MediSys - Sistema Hospitalario'
        ];
        
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    private function configurarSMTP() {
        try {
            // Configuración del servidor SMTP
            $this->mail->isSMTP();
            $this->mail->Host       = $this->config['host'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $this->config['username'];
            $this->mail->Password   = $this->config['password'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $this->config['port'];
            
            // Configuración del remitente
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Configuración de charset
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Encoding = 'base64';
            
        } catch (Exception $e) {
            error_log("Error configurando SMTP: " . $e->getMessage());
            throw new Exception("Error configurando el servicio de correo");
        }
    }
    
    // ===== MÉTODOS PARA CREDENCIALES DE USUARIO =====
    
    /**
     * Enviar correo con contraseña temporal (MÉTODO ORIGINAL)
     */
    public function enviarPasswordTemporal($destinatario, $nombreCompleto, $username, $passwordTemporal) {
        try {
            // Limpiar destinatarios previos
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Configurar destinatario
            $this->mail->addAddress($destinatario, $nombreCompleto);
            
            // Configurar contenido
            $this->mail->isHTML(true);
            $this->mail->Subject = '🔐 Credenciales de Acceso - MediSys';
            
            // Plantilla HTML del correo
            $htmlBody = $this->generarPlantillaCredencialesHTML($nombreCompleto, $username, $passwordTemporal);
            $this->mail->Body = $htmlBody;
            
            // Versión en texto plano
            $this->mail->AltBody = $this->generarCredencialesTextoPlano($nombreCompleto, $username, $passwordTemporal);
            
            // Enviar correo
            $resultado = $this->mail->send();
            
            if ($resultado) {
                error_log("✅ Correo de credenciales enviado exitosamente a: $destinatario");
                return true;
            } else {
                error_log("❌ Error enviando correo de credenciales a: $destinatario");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en enviarPasswordTemporal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar contraseña temporal aleatoria (MÉTODO ORIGINAL)
     */
    public static function generarPasswordTemporal($longitud = 12) {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*';
        $password = '';
        
        for ($i = 0; $i < $longitud; $i++) {
            $password .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        
        return $password;
    }
    
    // ===== MÉTODOS PARA NOTIFICACIONES DE CITAS =====
    
    /**
     * Enviar confirmación de cita
     */
    public function enviarConfirmacionCita($cita, $paciente) {
        try {
            $fecha = new DateTime($cita['fecha_hora']);
            $fechaFormateada = $fecha->format('l, d \d\e F \d\e Y');
            $horaFormateada = $fecha->format('H:i');
            
            $tipoTexto = $cita['id_tipo_cita'] == 2 ? 'Virtual' : 'Presencial';
            $iconoTipo = $cita['id_tipo_cita'] == 2 ? '📹' : '🏥';
            
            $subject = "✅ Cita Médica Confirmada - MediSys";
            
            $htmlBody = $this->generarPlantillaCita([
                'tipo' => 'confirmacion',
                'paciente_nombre' => $paciente['nombres'] . ' ' . $paciente['apellidos'],
                'fecha' => $fechaFormateada,
                'hora' => $horaFormateada,
                'doctor' => $cita['doctor_nombres'] . ' ' . $cita['doctor_apellidos'],
                'especialidad' => $cita['nombre_especialidad'],
                'sucursal' => $cita['nombre_sucursal'],
                'direccion' => $cita['sucursal_direccion'] ?? '',
                'tipo_cita' => $tipoTexto,
                'icono_tipo' => $iconoTipo,
                'motivo' => $cita['motivo'],
                'enlace_virtual' => $cita['enlace_virtual'] ?? null,
                'sala_virtual' => $cita['sala_virtual'] ?? null,
                'id_cita' => $cita['id_cita']
            ]);
            
            return $this->enviarEmail(
                $paciente['correo'],
                $paciente['nombres'] . ' ' . $paciente['apellidos'],
                $subject,
                $htmlBody
            );
            
        } catch (Exception $e) {
            error_log("Error enviando confirmación de cita: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar recordatorio de cita
     */
    public function enviarRecordatorioCita($cita, $paciente) {
        try {
            $fecha = new DateTime($cita['fecha_hora']);
            $fechaFormateada = $fecha->format('l, d \d\e F \d\e Y');
            $horaFormateada = $fecha->format('H:i');
            
            $subject = "⏰ Recordatorio: Cita Médica Mañana - MediSys";
            
            $htmlBody = $this->generarPlantillaCita([
                'tipo' => 'recordatorio',
                'paciente_nombre' => $paciente['nombres'] . ' ' . $paciente['apellidos'],
                'fecha' => $fechaFormateada,
                'hora' => $horaFormateada,
                'doctor' => $cita['doctor_nombres'] . ' ' . $cita['doctor_apellidos'],
                'especialidad' => $cita['nombre_especialidad'],
                'sucursal' => $cita['nombre_sucursal'],
                'direccion' => $cita['sucursal_direccion'] ?? '',
                'tipo_cita' => $cita['id_tipo_cita'] == 2 ? 'Virtual' : 'Presencial',
                'enlace_virtual' => $cita['enlace_virtual'] ?? null,
                'sala_virtual' => $cita['sala_virtual'] ?? null,
                'id_cita' => $cita['id_cita']
            ]);
            
            return $this->enviarEmail(
                $paciente['correo'],
                $paciente['nombres'] . ' ' . $paciente['apellidos'],
                $subject,
                $htmlBody
            );
            
        } catch (Exception $e) {
            error_log("Error enviando recordatorio: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar cancelación de cita
     */
    public function enviarCancelacionCita($cita, $paciente) {
        try {
            $fecha = new DateTime($cita['fecha_hora']);
            $fechaFormateada = $fecha->format('l, d \d\e F \d\e Y');
            $horaFormateada = $fecha->format('H:i');
            
            $subject = "❌ Cita Médica Cancelada - MediSys";
            
            $htmlBody = $this->generarPlantillaCita([
                'tipo' => 'cancelacion',
                'paciente_nombre' => $paciente['nombres'] . ' ' . $paciente['apellidos'],
                'fecha' => $fechaFormateada,
                'hora' => $horaFormateada,
                'doctor' => $cita['doctor_nombres'] . ' ' . $cita['doctor_apellidos'],
                'especialidad' => $cita['nombre_especialidad'],
                'sucursal' => $cita['nombre_sucursal'],
                'id_cita' => $cita['id_cita']
            ]);
            
            return $this->enviarEmail(
                $paciente['correo'],
                $paciente['nombres'] . ' ' . $paciente['apellidos'],
                $subject,
                $htmlBody
            );
            
        } catch (Exception $e) {
            error_log("Error enviando cancelación: " . $e->getMessage());
            return false;
        }
    }
    
    // ===== PLANTILLAS HTML =====
    
    /**
     * Plantilla HTML para credenciales de usuario (PLANTILLA ORIGINAL)
     */
    private function generarPlantillaCredencialesHTML($nombreCompleto, $username, $passwordTemporal) {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Credenciales de Acceso - MediSys</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f5f7fb; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
                .header { background: linear-gradient(135deg, #2e7d32, #1976d2); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .header p { margin: 10px 0 0 0; opacity: 0.9; }
                .content { padding: 40px 30px; }
                .welcome { font-size: 18px; color: #333; margin-bottom: 20px; }
                .credentials-box { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 25px; margin: 25px 0; }
                .credential-item { margin: 15px 0; }
                .credential-label { font-weight: bold; color: #495057; }
                .credential-value { background: #fff; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6; font-family: monospace; font-size: 16px; color: #2e7d32; }
                .warning-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 20px; margin: 25px 0; }
                .warning-box h3 { color: #856404; margin-top: 0; }
                .warning-box ul { color: #856404; margin-bottom: 0; }
                .btn { display: inline-block; background: linear-gradient(135deg, #2e7d32, #1976d2); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                .footer p { margin: 5px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 MediSys</h1>
                    <p>Sistema de Gestión Hospitalaria</p>
                </div>
                
                <div class='content'>
                    <p class='welcome'>¡Hola <strong>$nombreCompleto</strong>!</p>
                    
                    <p>Te damos la bienvenida al sistema MediSys. Tu cuenta ha sido creada exitosamente y estas son tus credenciales de acceso:</p>
                    
                    <div class='credentials-box'>
                        <div class='credential-item'>
                            <div class='credential-label'>👤 Usuario:</div>
                            <div class='credential-value'>$username</div>
                        </div>
                        <div class='credential-item'>
                            <div class='credential-label'>🔐 Contraseña Temporal:</div>
                            <div class='credential-value'>$passwordTemporal</div>
                        </div>
                    </div>
                    
                    <div class='warning-box'>
                        <h3>⚠️ Importante - Primer Inicio de Sesión</h3>
                        <ul>
                            <li>Esta es una <strong>contraseña temporal</strong></li>
                            <li>Debes cambiarla en tu primer inicio de sesión</li>
                            <li>Tu cuenta está en estado <strong>\"Pendiente\"</strong> hasta que cambies la contraseña</li>
                            <li>Guarda estas credenciales en un lugar seguro</li>
                        </ul>
                    </div>
                    
                    <center>
                        <a href='http://localhost:8080/MenuDinamico/vistas/login.php' class='btn'>
                            🚀 Iniciar Sesión Ahora
                        </a>
                    </center>
                    
                    <p><strong>Nota:</strong> Si tienes problemas para acceder, contacta al administrador del sistema.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>MediSys - Sistema de Gestión Hospitalaria</strong></p>
                    <p>📧 Este correo fue generado automáticamente, no respondas a este mensaje.</p>
                    <p>🔒 Mantén tus credenciales seguras y no las compartas con nadie.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Versión en texto plano para credenciales (MÉTODO ORIGINAL)
     */
    private function generarCredencialesTextoPlano($nombreCompleto, $username, $passwordTemporal) {
        return "
        ================================
        MEDISYS - CREDENCIALES DE ACCESO
        ================================
        
        ¡Hola $nombreCompleto!
        
        Te damos la bienvenida al sistema MediSys. Tu cuenta ha sido creada exitosamente.
        
        TUS CREDENCIALES:
        Usuario: $username
        Contraseña Temporal: $passwordTemporal
        
        IMPORTANTE:
        - Esta es una contraseña temporal
        - Debes cambiarla en tu primer inicio de sesión
        - Tu cuenta está en estado 'Pendiente' hasta que cambies la contraseña
        
        Accede al sistema en: http://localhost:8080/MenuDinamico/vistas/login.php
        
        Si tienes problemas, contacta al administrador.
        
        ================================
        MediSys - Sistema de Gestión Hospitalaria
        Este correo fue generado automáticamente.
        ================================
        ";
    }
    
    /**
     * Generar plantilla HTML para emails de citas
     */
    private function generarPlantillaCita($datos) {
        $estilos = "
        <style>
            .email-container { max-width: 600px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f8f9fa; }
            .cita-card { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .info-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; }
            .virtual-info { background: #e3f2fd; padding: 15px; border-radius: 6px; margin: 15px 0; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
            .btn-primary { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 5px; }
            .warning-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 15px 0; }
        </style>";
        
        $tipoTexto = [
            'confirmacion' => 'Confirmación de Cita',
            'recordatorio' => 'Recordatorio de Cita',
            'cancelacion' => 'Cancelación de Cita'
        ];
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$tipoTexto[$datos['tipo']]}</title>
            {$estilos}
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>{$datos['icono_tipo']} MediSys</h1>
                    <h2>{$tipoTexto[$datos['tipo']]}</h2>
                </div>
                
                <div class='content'>
                    <p>Estimado/a <strong>{$datos['paciente_nombre']}</strong>,</p>";
        
        if ($datos['tipo'] == 'confirmacion') {
            $html .= "<p>Su cita médica ha sido <strong>confirmada exitosamente</strong> con los siguientes detalles:</p>";
        } elseif ($datos['tipo'] == 'recordatorio') {
            $html .= "<p>Le recordamos que tiene una cita médica programada para <strong>mañana</strong>:</p>";
        } elseif ($datos['tipo'] == 'cancelacion') {
            $html .= "<p>Lamentamos informarle que su cita médica ha sido <strong>cancelada</strong>:</p>";
        }
        
        $html .= "
                    <div class='cita-card'>
                        <h3>📋 Detalles de la Cita</h3>
                        
                        <div class='info-row'>
                            <span class='label'>📅 Fecha:</span>
                            <span class='value'>{$datos['fecha']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>🕐 Hora:</span>
                            <span class='value'>{$datos['hora']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>👨‍⚕️ Doctor:</span>
                            <span class='value'>Dr. {$datos['doctor']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>🏥 Especialidad:</span>
                            <span class='value'>{$datos['especialidad']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>📍 Sucursal:</span>
                            <span class='value'>{$datos['sucursal']}</span>
                        </div>";
        
        if ($datos['direccion']) {
            $html .= "
                        <div class='info-row'>
                            <span class='label'>🗺️ Dirección:</span>
                            <span class='value'>{$datos['direccion']}</span>
                        </div>";
        }
        
        $html .= "
                        <div class='info-row'>
                            <span class='label'>💼 Tipo de Cita:</span>
                            <span class='value'>{$datos['tipo_cita']}</span>
                        </div>";
        
        if (isset($datos['motivo'])) {
            $html .= "
                        <div class='info-row'>
                            <span class='label'>📝 Motivo:</span>
                            <span class='value'>{$datos['motivo']}</span>
                        </div>";
        }
        
        $html .= "</div>";
        
        // Información adicional para citas virtuales
        if ($datos['tipo_cita'] == 'Virtual' && ($datos['enlace_virtual'] || $datos['sala_virtual'])) {
            $html .= "
                    <div class='virtual-info'>
                        <h4>📹 Información de la Cita Virtual</h4>";
            
            if ($datos['enlace_virtual']) {
                $html .= "<p><strong>🔗 Enlace de acceso:</strong><br>
                         <a href='{$datos['enlace_virtual']}' target='_blank'>{$datos['enlace_virtual']}</a></p>";
            }
            
            if ($datos['sala_virtual']) {
                $html .= "<p><strong>🆔 ID de Sala:</strong> {$datos['sala_virtual']}</p>";
            }
            
            $html .= "
                        <div class='warning-box'>
                            <p><strong>💡 Consejos para su cita virtual:</strong></p>
                            <ul>
                                <li>Úsese 5 minutos antes de la hora programada</li>
                                <li>Asegúrese de tener buena conexión a internet</li>
                                <li>Busque un lugar tranquilo y bien iluminado</li>
                                <li>Tenga sus documentos médicos a la mano</li>
                            </ul>
                        </div>
                    </div>";
        }
        
        // Mensaje específico según el tipo
        if ($datos['tipo'] == 'confirmacion') {
            $html .= "
                    <p>Si necesita reprogramar o cancelar su cita, por favor contáctenos con al menos 24 horas de anticipación.</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='tel:+593-2-XXX-XXXX' class='btn-primary'>📞 Llamar al Centro</a>
                        <a href='mailto:citas@medisys.com' class='btn-primary'>✉️ Enviar Email</a>
                    </div>";
        } elseif ($datos['tipo'] == 'recordatorio') {
            $html .= "
                    <div class='warning-box'>
                        <p><strong>⚠️ Importante:</strong> Si no puede asistir, por favor cancele su cita para permitir que otros pacientes puedan agendar.</p>
                    </div>";
        } elseif ($datos['tipo'] == 'cancelacion') {
            $html .= "
                    <p>Para reagendar su cita, puede contactarnos o usar nuestro sistema en línea.</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='https://medisys.com/agendar' class='btn-primary'>📅 Reagendar Cita</a>
                    </div>";
        }
        
        $html .= "
                    <p>Gracias por confiar en MediSys para su atención médica.</p>
                    
                    <p><small>📧 ID de Cita: #{$datos['id_cita']}</small></p>
                </div>
                
                <div class='footer'>
                    <p><strong>MediSys - Sistema Médico Integral</strong></p>
                    <p>📧 info@medisys.com | 📞 +593-2-XXX-XXXX</p>
                    <p>🌐 www.medisys.com</p>
                    <p><small>Este es un mensaje automático, por favor no responder a este email.</small></p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    // ===== MÉTODO AUXILIAR PARA ENVÍO GENÉRICO =====
    
    /**
     * Enviar email genérico
     */
    private function enviarEmail($destinatario, $nombreDestinatario, $asunto, $contenidoHtml) {
        try {
            // Limpiar destinatarios anteriores
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Configurar destinatario
            $this->mail->addAddress($destinatario, $nombreDestinatario);
            
            // Contenido del email
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $contenidoHtml;
            $this->mail->AltBody = strip_tags($contenidoHtml); // Versión texto plano
            
            $this->mail->send();
            
            error_log("✅ Email enviado exitosamente a: {$destinatario}");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error enviando email: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
?>