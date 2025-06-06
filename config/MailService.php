<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    private function configurarSMTP() {
        try {
            // Configuración del servidor SMTP (usando Gmail como ejemplo)
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'swooshing14@gmail.com'; // 🔥 CAMBIAR POR TU CORREO
            $this->mail->Password   = 'voqz tczc fpmu fbfo';    // 🔥 USAR CONTRASEÑA DE APLICACIÓN
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            // Configuración del remitente
            $this->mail->setFrom('swooshing14@gmail.com', 'MediSys - Sistema Hospitalario');
            
            // Configuración de charset
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Encoding = 'base64';
            
        } catch (Exception $e) {
            error_log("Error configurando SMTP: " . $e->getMessage());
            throw new Exception("Error configurando el servicio de correo");
        }
    }
    
    /**
     * Enviar correo con contraseña temporal
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
            $htmlBody = $this->generarPlantillaHTML($nombreCompleto, $username, $passwordTemporal);
            $this->mail->Body = $htmlBody;
            
            // Versión en texto plano
            $this->mail->AltBody = $this->generarTextoPlano($nombreCompleto, $username, $passwordTemporal);
            
            // Enviar correo
            $resultado = $this->mail->send();
            
            if ($resultado) {
                error_log("✅ Correo enviado exitosamente a: $destinatario");
                return true;
            } else {
                error_log("❌ Error enviando correo a: $destinatario");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en enviarPasswordTemporal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar contraseña temporal aleatoria
     */
    public static function generarPasswordTemporal($longitud = 12) {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*';
        $password = '';
        
        for ($i = 0; $i < $longitud; $i++) {
            $password .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Plantilla HTML para el correo
     */
    private function generarPlantillaHTML($nombreCompleto, $username, $passwordTemporal) {
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
     * Versión en texto plano del correo
     */
    private function generarTextoPlano($nombreCompleto, $username, $passwordTemporal) {
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
}
?>