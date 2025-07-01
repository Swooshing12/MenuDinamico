<?php
class SMSService {
    private $config;
    
    public function __construct() {
        $this->config = [
            // Twilio
            'provider' => 'twilio', // o 'nexmo', 'textbelt', etc.
            'twilio_sid' => 'tu_twilio_sid',
            'twilio_token' => 'tu_twilio_token',
            'twilio_from' => '+1234567890', // Tu número Twilio
            
            // Configuración local (para Ecuador)
            'ecuador_provider' => 'local', // Proveedor local ecuatoriano
            'local_api_key' => 'tu_api_key_local',
            'local_url' => 'https://api.sms-ecuador.com/send'
        ];
    }
    
    /**
     * Enviar SMS de confirmación de cita
     */
    public function enviarConfirmacionCita($cita, $paciente) {
        $fecha = new DateTime($cita['fecha_hora']);
        $fechaFormateada = $fecha->format('d/m/Y');
        $horaFormateada = $fecha->format('H:i');
        
        $tipoTexto = $cita['id_tipo_cita'] == 2 ? 'Virtual' : 'Presencial';
        
        $mensaje = "🏥 MediSys - CITA CONFIRMADA\n";
        $mensaje .= "📅 {$fechaFormateada} a las {$horaFormateada}\n";
        $mensaje .= "👨‍⚕️ Dr. {$cita['doctor_nombres']} {$cita['doctor_apellidos']}\n";
        $mensaje .= "📍 {$cita['nombre_sucursal']}\n";
        $mensaje .= "💼 Tipo: {$tipoTexto}\n";
        
        if ($cita['id_tipo_cita'] == 2 && $cita['enlace_virtual']) {
            $mensaje .= "🔗 Link: {$cita['enlace_virtual']}\n";
        }
        
        $mensaje .= "ID: #{$cita['id_cita']}";
        
        return $this->enviarSMS($paciente['telefono'], $mensaje);
    }
    
    /**
     * Enviar SMS de recordatorio
     */
    public function enviarRecordatorioCita($cita, $paciente) {
        $fecha = new DateTime($cita['fecha_hora']);
        $horaFormateada = $fecha->format('H:i');
        
        $mensaje = "⏰ MediSys - RECORDATORIO\n";
        $mensaje .= "Su cita es MAÑANA a las {$horaFormateada}\n";
        $mensaje .= "👨‍⚕️ Dr. {$cita['doctor_nombres']} {$cita['doctor_apellidos']}\n";
        $mensaje .= "📍 {$cita['nombre_sucursal']}\n";
        
        if ($cita['id_tipo_cita'] == 2 && $cita['enlace_virtual']) {
            $mensaje .= "🔗 {$cita['enlace_virtual']}\n";
        }
        
        $mensaje .= "ID: #{$cita['id_cita']}";
        
        return $this->enviarSMS($paciente['telefono'], $mensaje);
    }
    
    /**
     * Enviar SMS genérico
     */
    private function enviarSMS($telefono, $mensaje) {
        try {
            // Limpiar y formatear número telefónico
            $telefono = $this->formatearTelefono($telefono);
            
            if ($this->config['provider'] === 'twilio') {
                return $this->enviarViaTwilio($telefono, $mensaje);
            } else {
                return $this->enviarViaProveedorLocal($telefono, $mensaje);
            }
            
        } catch (Exception $e) {
            error_log("Error enviando SMS: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar vía Twilio
     */
    private function enviarViaTwilio($telefono, $mensaje) {
        // Implementar integración con Twilio
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->config['twilio_sid']}/Messages.json";
        
        $datos = [
            'From' => $this->config['twilio_from'],
            'To' => $telefono,
            'Body' => $mensaje
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['twilio_sid'] . ':' . $this->config['twilio_token']);
        
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 201) {
            error_log("✅ SMS enviado vía Twilio a: {$telefono}");
            return true;
        } else {
            error_log("❌ Error enviando SMS vía Twilio: {$respuesta}");
            return false;
        }
    }
    
    /**
     * Enviar vía proveedor local (Ecuador)
     */
    private function enviarViaProveedorLocal($telefono, $mensaje) {
        // Implementar integración con proveedor local ecuatoriano
        $datos = [
            'api_key' => $this->config['local_api_key'],
            'to' => $telefono,
            'message' => $mensaje,
            'from' => 'MediSys'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config['local_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['local_api_key']
        ]);
        
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            error_log("✅ SMS enviado vía proveedor local a: {$telefono}");
            return true;
        } else {
            error_log("❌ Error enviando SMS: {$respuesta}");
            return false;
        }
    }
    
    /**
     * Formatear número telefónico para Ecuador
     */
    private function formatearTelefono($telefono) {
        // Limpiar el número
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        
        // Si empieza con 0, quitar el 0 y agregar +593
        if (substr($telefono, 0, 1) === '0') {
            $telefono = '+593' . substr($telefono, 1);
        }
        // Si no tiene código de país, agregar +593
        elseif (substr($telefono, 0, 4) !== '+593') {
            $telefono = '+593' . $telefono;
        }
        
        return $telefono;
    }
}
?>