<?php
require_once __DIR__ . '/../modelos/Citas.php';
require_once __DIR__ . '/../config/Database.php';

class ConfirmadorCita {
    private $citasModel;
    
    public function __construct() {
        $this->citasModel = new Citas();
    }
    
    public function procesarConfirmacion($token) {
        try {
            // Decodificar y validar token
            $tokenData = $this->validarToken($token);
            
            if (!$tokenData) {
                return [
                    'success' => false,
                    'message' => 'Token inválido o corrupto',
                    'icon' => 'error'
                ];
            }
            
            $id_cita = $tokenData['id_cita'];
            $timestamp = $tokenData['timestamp'];
            
            // Verificar que el token no haya expirado (48 horas = 172800 segundos)
            if (time() - $timestamp > 172800) {
                return [
                    'success' => false,
                    'message' => 'El enlace de confirmación ha expirado',
                    'submessage' => 'Por favor contacta a la clínica para confirmar tu cita',
                    'icon' => 'warning'
                ];
            }
            
            // Obtener información de la cita
            $cita = $this->citasModel->obtenerPorIdCompleto($id_cita);
            
            if (!$cita) {
                return [
                    'success' => false,
                    'message' => 'Cita no encontrada',
                    'icon' => 'error'
                ];
            }
            
            // Verificar estado actual
            if ($cita['estado'] === 'Confirmada') {
                return [
                    'success' => true,
                    'message' => 'Tu cita ya estaba confirmada',
                    'submessage' => 'No necesitas hacer nada más',
                    'icon' => 'info',
                    'cita' => $cita
                ];
            }
            
            if ($cita['estado'] === 'Cancelada') {
                return [
                    'success' => false,
                    'message' => 'Esta cita ha sido cancelada',
                    'submessage' => 'Contacta a la clínica si necesitas reagendar',
                    'icon' => 'error'
                ];
            }
            
            if ($cita['estado'] === 'Completada') {
                return [
                    'success' => false,
                    'message' => 'Esta cita ya fue completada',
                    'icon' => 'info'
                ];
            }
            
            // Confirmar la cita
            $resultado = $this->citasModel->cambiarEstado($id_cita, 'Confirmada');
            
            if ($resultado) {
                // Log de confirmación
                error_log("✅ Cita ID {$id_cita} confirmada por el paciente vía enlace de correo");
                
                return [
                    'success' => true,
                    'message' => '¡Cita confirmada exitosamente!',
                    'submessage' => 'Recibirás un recordatorio antes de tu cita',
                    'icon' => 'success',
                    'cita' => $cita
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al confirmar la cita',
                    'submessage' => 'Por favor intenta nuevamente o contacta a la clínica',
                    'icon' => 'error'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en confirmación de cita: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del sistema',
                'submessage' => 'Por favor contacta a la clínica',
                'icon' => 'error'
            ];
        }
    }
    
    private function validarToken($token) {
    try {
        error_log("🔍 DEBUG: Token recibido: " . $token);
        
        $decoded = base64_decode($token);
        error_log("🔍 DEBUG: Token decodificado: " . $decoded);
        
        if (!$decoded) {
            error_log("❌ DEBUG: Error decodificando base64");
            return false;
        }
        
        $parts = explode('|', $decoded);
        error_log("🔍 DEBUG: Partes del token: " . count($parts));
        
        if (count($parts) !== 2) {
            error_log("❌ DEBUG: Token no tiene 2 partes");
            return false;
        }
        
        $dataString = $parts[0];
        $providedHash = $parts[1];
        
        error_log("🔍 DEBUG: Data string: " . $dataString);
        error_log("🔍 DEBUG: Hash proporcionado: " . $providedHash);
        
        // Verificar el hash
        $expectedHash = hash_hmac('sha256', $dataString, 'medisys_secret_key_2025');
        error_log("🔍 DEBUG: Hash esperado: " . $expectedHash);
        
        if (!hash_equals($expectedHash, $providedHash)) {
            error_log("❌ DEBUG: Hashes no coinciden");
            return false;
        }
        
        // Decodificar datos
        $data = json_decode($dataString, true);
        error_log("🔍 DEBUG: Datos decodificados: " . json_encode($data));
        
        if (!$data || !isset($data['id_cita'], $data['timestamp'])) {
            error_log("❌ DEBUG: Datos inválidos");
            return false;
        }
        
        error_log("✅ DEBUG: Token válido");
        return $data;
        
    } catch (Exception $e) {
        error_log("❌ Error validando token: " . $e->getMessage());
        return false;
    }
}
}

// Procesar la solicitud
$resultado = null;
if (isset($_GET['token'])) {
    $confirmador = new ConfirmadorCita();
    $resultado = $confirmador->procesarConfirmacion($_GET['token']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Cita - MediSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #007bff, #0056b3);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .confirmation-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .confirmation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .card-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .card-header.error {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .card-header.warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }
        .card-header.info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        .card-body {
            padding: 40px;
        }
        .cita-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .btn-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <?php if ($resultado): ?>
                <div class="card-header <?= $resultado['icon'] ?>">
                    <h1>
                        <?php if ($resultado['icon'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill display-4"></i>
                        <?php elseif ($resultado['icon'] === 'error'): ?>
                            <i class="bi bi-x-circle-fill display-4"></i>
                        <?php elseif ($resultado['icon'] === 'warning'): ?>
                            <i class="bi bi-exclamation-triangle-fill display-4"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill display-4"></i>
                        <?php endif; ?>
                    </h1>
                    <h2 class="mt-3">🏥 MediSys</h2>
                    <p class="mb-0">Sistema de Gestión Hospitalaria</p>
                </div>
                
                <div class="card-body text-center">
                    <h3 class="mb-3"><?= htmlspecialchars($resultado['message']) ?></h3>
                    
                    <?php if (isset($resultado['submessage'])): ?>
                        <p class="text-muted mb-4"><?= htmlspecialchars($resultado['submessage']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($resultado['cita']) && $resultado['success']): ?>
                        <div class="cita-details">
                            <h4><i class="bi bi-calendar-check me-2"></i>Detalles de tu Cita</h4>
                            <div class="detail-row">
                                <strong>Paciente:</strong>
                                <span><?= htmlspecialchars($resultado['cita']['paciente_nombres'] . ' ' . $resultado['cita']['paciente_apellidos']) ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Doctor:</strong>
                                <span><?= htmlspecialchars($resultado['cita']['doctor_nombres'] . ' ' . $resultado['cita']['doctor_apellidos']) ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Fecha y Hora:</strong>
                                <span><?= date('d/m/Y H:i', strtotime($resultado['cita']['fecha_hora'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Especialidad:</strong>
                                <span><?= htmlspecialchars($resultado['cita']['nombre_especialidad'] ?? 'No especificada') ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Sucursal:</strong>
                                <span><?= htmlspecialchars($resultado['cita']['nombre_sucursal'] ?? 'No especificada') ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Estado:</strong>
                                <span class="badge bg-success">Confirmada</span>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Recordatorio:</strong> Llega 15 minutos antes de tu cita y trae tu cédula de identidad.
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="tel:+593-2-XXX-XXXX" class="btn btn-outline-primary me-3">
                            <i class="bi bi-telephone me-1"></i>Llamar a la Clínica
                        </a>
                        <a href="mailto:info@medisys.com" class="btn btn-outline-secondary">
                            <i class="bi bi-envelope me-1"></i>Enviar Email
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-header error">
                    <h1><i class="bi bi-x-circle-fill display-4"></i></h1>
                    <h2 class="mt-3">🏥 MediSys</h2>
                </div>
                
                <div class="card-body text-center">
                    <h3 class="mb-3">Enlace Inválido</h3>
                    <p class="text-muted mb-4">El enlace de confirmación no es válido o está mal formado.</p>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Por favor contacta a la clínica para confirmar tu cita manualmente.
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card-footer text-center bg-light">
                <small class="text-muted">
                    © 2025 MediSys - Sistema de Gestión Hospitalaria
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>