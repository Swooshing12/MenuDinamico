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
            'password' => 'afuw rcsw mvxs qwbq',   // Tu contraseña de aplicación
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
        error_log("🔍 DEBUG: Iniciando enviarConfirmacionCita");
        
        $fecha = new DateTime($cita['fecha_hora']);
        $fechaFormateada = $fecha->format('d/m/Y');
        $horaFormateada = $fecha->format('H:i');
        
        $subject = "✅ Confirma tu Cita Médica - MediSys";
        
        $htmlBody = $this->generarPlantillaCita([
            'tipo' => 'confirmacion',
            'paciente_nombre' => $paciente['nombres'] . ' ' . $paciente['apellidos'],
            'fecha' => $fechaFormateada,
            'hora' => $horaFormateada,
            'doctor' => ($cita['doctor_nombres'] ?? '') . ' ' . ($cita['doctor_apellidos'] ?? ''),
            'especialidad' => $cita['nombre_especialidad'] ?? 'No especificada',
            'sucursal' => $cita['nombre_sucursal'] ?? 'No especificada',
            'tipo_cita' => $cita['id_tipo_cita'] == 2 ? 'Virtual' : 'Presencial',
            'enlace_virtual' => $cita['enlace_virtual'] ?? null,
            'sala_virtual' => $cita['sala_virtual'] ?? null,
            'id_cita' => $cita['id_cita']
        ]);
        
        error_log("🔍 DEBUG: Plantilla generada, enviando email...");
        
        $resultado = $this->enviarEmail(
            $paciente['correo'],
            $paciente['nombres'] . ' ' . $paciente['apellidos'],
            $subject,
            $htmlBody
        );
        
        error_log("🔍 DEBUG: Resultado final: " . ($resultado ? 'TRUE' : 'FALSE'));
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("❌ Error en enviarConfirmacionCita: " . $e->getMessage());
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
                        <a href='http://localhost/MenuDinamico/vistas/login.php' class='btn'>
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
        
        Accede al sistema en: http://localhost/MenuDinamico/vistas/login.php
        
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
    /**
 * Generar plantilla HTML para emails de citas
 */
/**
 * Generar plantilla HTML para emails de citas
 */
private function generarPlantillaCita($datos) {
    // Ya no necesitamos generar token ni enlace de confirmación
    
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cita Confirmada - MediSys</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f7fb;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f5f7fb;">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #007bff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">🏥 MediSys</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">Sistema de Gestión Hospitalaria</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #333; margin-top: 0;">¡Hola ' . htmlspecialchars($datos['paciente_nombre']) . '!</h2>
                            
                            <!-- Mensaje de confirmación automática -->
                            <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;">
                                <h3 style="color: #155724; margin-top: 0;">✅ Tu cita está confirmada</h3>
                                <p style="color: #155724; margin-bottom: 0;">Tu cita médica ha sido <strong>registrada y confirmada exitosamente</strong>. No necesitas realizar ninguna acción adicional.</p>
                            </div>
                            
                            <!-- Detalles de la Cita -->
                            <table width="100%" border="0" cellpadding="15" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <h3 style="color: #333; margin-top: 0;">📅 Detalles de tu Cita</h3>
                                        <table width="100%" border="0" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="border-bottom: 1px solid #dee2e6; font-weight: bold; color: #333;">📅 Fecha:</td>
                                                <td style="border-bottom: 1px solid #dee2e6; color: #666;">' . htmlspecialchars($datos['fecha']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="border-bottom: 1px solid #dee2e6; font-weight: bold; color: #333;">🕐 Hora:</td>
                                                <td style="border-bottom: 1px solid #dee2e6; color: #666;">' . htmlspecialchars($datos['hora']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="border-bottom: 1px solid #dee2e6; font-weight: bold; color: #333;">👨‍⚕️ Doctor:</td>
                                                <td style="border-bottom: 1px solid #dee2e6; color: #666;">' . htmlspecialchars($datos['doctor']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="border-bottom: 1px solid #dee2e6; font-weight: bold; color: #333;">🏥 Especialidad:</td>
                                                <td style="border-bottom: 1px solid #dee2e6; color: #666;">' . htmlspecialchars($datos['especialidad']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="border-bottom: 1px solid #dee2e6; font-weight: bold; color: #333;">📍 Sucursal:</td>
                                                <td style="border-bottom: 1px solid #dee2e6; color: #666;">' . htmlspecialchars($datos['sucursal']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #333;">📋 Tipo:</td>
                                                <td style="color: #666;">' . htmlspecialchars($datos['tipo_cita']) . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>';
    
    // Información virtual si aplica
    if (isset($datos['enlace_virtual']) && !empty($datos['enlace_virtual'])) {
        $html .= '
                            <!-- Información Virtual -->
                            <table width="100%" border="0" cellpadding="15" cellspacing="0" style="background-color: #e3f2fd; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <h4 style="color: #1976d2; margin-top: 0;">📹 Información de Cita Virtual</h4>
                                        <p style="color: #333; margin: 10px 0;"><strong>Enlace de la videollamada:</strong></p>
                                        <p style="margin: 10px 0;">
                                            <a href="' . htmlspecialchars($datos['enlace_virtual']) . '" target="_blank" style="color: #007bff; font-weight: bold; text-decoration: none;">' . htmlspecialchars($datos['enlace_virtual']) . '</a>
                                        </p>';
        
        if (isset($datos['sala_virtual'])) {
            $html .= '<p style="color: #333; margin: 10px 0;"><strong>ID de Sala:</strong> ' . htmlspecialchars($datos['sala_virtual']) . '</p>';
        }
        
        $html .= '<p style="color: #666; font-size: 14px; margin: 10px 0;">💡 <em>Guarda este enlace para unirte a tu cita virtual en la fecha programada.</em></p>
                                    </td>
                                </tr>
                            </table>';
    }
    
    $html .= '
                            <!-- Recomendaciones -->
                            <table width="100%" border="0" cellpadding="20" cellspacing="0" style="background-color: #fff3cd; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <h4 style="color: #856404; margin-top: 0;">📝 Recomendaciones antes de tu cita:</h4>
                                        <ul style="color: #856404; margin: 10px 0; padding-left: 20px; line-height: 1.6;">
                                            <li>Llega 15 minutos antes de tu cita</li>
                                            <li>Trae tu cédula de identidad</li>
                                            <li>Lleva el historial médico si tienes</li>';
    
    if (isset($datos['enlace_virtual'])) {
        $html .= '<li>Verifica tu conexión a internet para citas virtuales</li>';
    }
    
    $html .= '
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Información de contacto -->
                            <table width="100%" border="0" cellpadding="15" cellspacing="0" style="background-color: #f0f8ff; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="text-align: center;">
                                        <h4 style="color: #0066cc; margin-top: 0;">📞 ¿Necesitas hacer cambios?</h4>
                                        <p style="color: #333; margin: 10px 0;">Si necesitas reprogramar o cancelar tu cita, contáctanos:</p>
                                        <p style="color: #0066cc; font-weight: bold; margin: 5px 0;">📱 Teléfono: +593-2-XXX-XXXX</p>
                                        <p style="color: #0066cc; font-weight: bold; margin: 5px 0;">📧 Email: info@medisys.com</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666; line-height: 1.6; text-align: center;">Te esperamos en la fecha y hora programada. ¡Que tengas un excelente día! 😊</p>
                            
                            <p style="color: #999; font-size: 12px; text-align: center;">📧 ID de Cita: #' . htmlspecialchars($datos['id_cita']) . '</p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #333; color: white; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px;">
                            <p style="margin: 5px 0; font-weight: bold;">MediSys - Sistema Médico Integral</p>
                            <p style="margin: 5px 0;">📧 info@medisys.com | 📞 +593-2-XXX-XXXX</p>
                            <p style="margin: 5px 0;">🌐 www.medisys.com</p>
                            <p style="margin: 5px 0; font-size: 11px;">Este es un mensaje automático, por favor no responder a este email.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    return $html;
}

/**
 * Generar token seguro para confirmación de cita
 */
/**
 * Generar token seguro para confirmación de cita
 */
private function generarTokenConfirmacion($id_cita) {
    $data = [
        'id_cita' => $id_cita,
        'timestamp' => time(),
        'random' => bin2hex(random_bytes(8))
    ];
    
    // 🔍 DEBUG: Log temporal
    error_log("🔍 DEBUG: Datos para token: " . json_encode($data));
    
    // Crear un hash seguro
    $dataString = json_encode($data);
    $hash = hash_hmac('sha256', $dataString, 'medisys_secret_key_2025');
    
    $token = base64_encode($dataString . '|' . $hash);
    
    // 🔍 DEBUG: Log temporal
    error_log("🔍 DEBUG: Token final generado: " . $token);
    
    return $token;
}
    // ===== MÉTODO AUXILIAR PARA ENVÍO GENÉRICO =====
    
    /**
     * Enviar email genérico
     */
    /**
 * Método genérico para enviar emails
 */
private function enviarEmail($destinatario, $nombreDestinatario, $asunto, $contenidoHTML) {
    try {
        // Limpiar destinatarios previos
        $this->mail->clearAddresses();
        $this->mail->clearAttachments();
        
        // Configurar destinatario
        $this->mail->addAddress($destinatario, $nombreDestinatario);
        
        // Configurar contenido
        $this->mail->isHTML(true);
        $this->mail->Subject = $asunto;
        $this->mail->Body = $contenidoHTML;
        
        // Enviar correo
        $resultado = $this->mail->send();
        
        if ($resultado) {
            error_log("✅ Email enviado exitosamente a: $destinatario");
            return true;
        } else {
            error_log("❌ Error enviando email a: $destinatario");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ Error en enviarEmail: " . $e->getMessage());
        return false;
    }
}
}
?>