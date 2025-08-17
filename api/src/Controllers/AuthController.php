<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\ResponseUtil;
use App\Validators\EmailValidator;        // ✅ ESTE
use App\Validators\PasswordValidator;     // ✅ ESTE
use Illuminate\Database\Capsule\Manager as DB;
use Exception;


class AuthController
{
    // PUNTO 1 y 3: Login mejorado con sistema de 3 intentos (SIN CAMPOS NUEVOS)
public function login(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    
    // Validaciones básicas
    if (empty($data['correo']) || empty($data['password'])) {
        return ResponseUtil::badRequest('Datos incompletos', [
            'correo' => empty($data['correo']) ? 'El correo es requerido' : null,
            'password' => empty($data['password']) ? 'La contraseña es requerida' : null
        ]);
    }
    
    // Validar formato de email
    $erroresEmail = EmailValidator::validate($data['correo']);
    if (!empty($erroresEmail)) {
        return ResponseUtil::badRequest('Formato de correo inválido', $erroresEmail);
    }
    
    try {
        // Obtener IP del cliente para logs de seguridad
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // 🔒 INICIAR SESIÓN PARA TRACKING DE INTENTOS
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Buscar usuario por correo
        $usuario = DB::table('usuarios')
            ->join('roles', 'usuarios.id_rol', '=', 'roles.id_rol')
            ->leftJoin('doctores', 'usuarios.id_usuario', '=', 'doctores.id_usuario')
            ->leftJoin('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id_especialidad')
            ->leftJoin('pacientes', 'usuarios.id_usuario', '=', 'pacientes.id_usuario')
            ->select(
                'usuarios.id_usuario',
                'usuarios.cedula',
                'usuarios.username',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.sexo',
                'usuarios.nacionalidad',
                'usuarios.correo',
                'usuarios.password',
                'usuarios.id_estado',
                'usuarios.fecha_creacion',
                'roles.nombre_rol',
                'doctores.id_doctor',
                'especialidades.nombre_especialidad',
                'pacientes.id_paciente'
            )
            ->where('usuarios.correo', $data['correo'])
            ->first();
        
        // 🔒 MENSAJE SEGURO: No revelar si el usuario existe o no
        if (!$usuario) {
            $this->incrementarIntentosFallidos($data['correo'], $clientIp);
            error_log("Login failed - Email not found: {$data['correo']} from IP: $clientIp");
            return ResponseUtil::unauthorized('Credenciales incorrectas');
        }
        
        // 🔒 VERIFICAR ESTADO DEL USUARIO
        if ($usuario->id_estado == 2) {
            error_log("Login blocked - Account blocked: {$data['correo']} from IP: $clientIp");
            return ResponseUtil::unauthorized('Cuenta bloqueada por múltiples intentos fallidos. Contacte al administrador');
        }
        
        
        if ($usuario->id_estado == 4) {
            error_log("Login blocked - Account disabled: {$data['correo']} from IP: $clientIp");
            return ResponseUtil::unauthorized('Cuenta deshabilitada. Contacte al administrador');
        }
        
        if ($usuario->id_estado != 1 && $usuario->id_estado != 3) {
            error_log("Login blocked - Invalid status {$usuario->id_estado}: {$data['correo']} from IP: $clientIp");
            return ResponseUtil::unauthorized('Estado de cuenta inválido. Contacte al administrador');
        }
        
        // 🔒 VERIFICAR CONTRASEÑA
        if (!password_verify($data['password'], $usuario->password)) {
            // Incrementar contador de intentos fallidos
            $intentosActuales = $this->incrementarIntentosFallidos($data['correo'], $clientIp);
            
            // Si llegó a 3 intentos, bloquear usuario
            if ($intentosActuales >= 3) {
                $this->bloquearUsuario($usuario->id_usuario, $data['correo'], $clientIp);
                return ResponseUtil::unauthorized('Cuenta bloqueada por múltiples intentos fallidos. Contacte al administrador');
            }
            
            error_log("Login failed - Wrong password: {$data['correo']} (attempt $intentosActuales/3) from IP: $clientIp");
            return ResponseUtil::unauthorized('Credenciales incorrectas');
        }
                        // Si la contraseña es correcta, verificar el estado
                if ($usuario->id_estado == 3) {
                    // Usuario con clave temporal - debe cambiar contraseña
                    return ResponseUtil::success([
                        'usuario' => [
                            'id_usuario' => $usuario->id_usuario,
                            'correo' => $usuario->correo,
                            'nombres' => $usuario->nombres,
                            'apellidos' => $usuario->apellidos,
                            'requiere_cambio_password' => true,
                            'estado' => 'pendiente'
                        ]
                    ], 'Debe cambiar su contraseña temporal');
                }

        
        // 🔒 LOGIN EXITOSO - Limpiar intentos fallidos
        $this->limpiarIntentosFallidos($data['correo']);
        
        // Preparar datos del usuario
        $tipoUsuario = $usuario->id_doctor ? 'doctor' : ($usuario->id_paciente ? 'paciente' : 'admin');
        
        $userData = [
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'cedula' => $usuario->cedula,
                'username' => $usuario->username,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'nombre_completo' => $usuario->nombres . ' ' . $usuario->apellidos,
                'sexo' => $usuario->sexo,
                'nacionalidad' => $usuario->nacionalidad,
                'correo' => $usuario->correo,
                'rol' => $usuario->nombre_rol,
                'tipo_usuario' => $tipoUsuario,
                'id_paciente' => $usuario->id_paciente,
                'id_doctor' => $usuario->id_doctor,
                'especialidad' => $usuario->nombre_especialidad,
                'fecha_registro' => $usuario->fecha_creacion
            ]
        ];
        
        // Log de éxito
        error_log("Login successful: {$data['correo']} ({$usuario->nombre_rol}) from IP: $clientIp");
        
        return ResponseUtil::success($userData, 'Inicio de sesión exitoso');
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ResponseUtil::error('Error interno del servidor');
    }
}

// 🔒 INCREMENTAR INTENTOS FALLIDOS (USANDO SESIONES)
private function incrementarIntentosFallidos($correo, $clientIp)
{
    // Inicializar array de intentos si no existe
    if (!isset($_SESSION['intentos'])) {
        $_SESSION['intentos'] = [];
    }
    
    // Incrementar contador para este correo
    if (!isset($_SESSION['intentos'][$correo])) {
        $_SESSION['intentos'][$correo] = 1;
    } else {
        $_SESSION['intentos'][$correo]++;
    }
    
    $intentos = $_SESSION['intentos'][$correo];
    
    error_log("Failed login attempt $intentos/3 for: $correo from IP: $clientIp");
    
    return $intentos;
}

// 🔒 BLOQUEAR USUARIO (CAMBIAR ESTADO A 2)
private function bloquearUsuario($idUsuario, $correo, $clientIp)
{
    try {
        DB::table('usuarios')
            ->where('id_usuario', $idUsuario)
            ->update(['id_estado' => 2]); // Estado bloqueado
            
        error_log("User blocked after 3 failed attempts: $correo (ID: $idUsuario) from IP: $clientIp");
        
        // Limpiar intentos de la sesión ya que el usuario está bloqueado
        unset($_SESSION['intentos'][$correo]);
        
    } catch (Exception $e) {
        error_log("Error blocking user: " . $e->getMessage());
    }
}

// 🔒 LIMPIAR INTENTOS FALLIDOS (LOGIN EXITOSO)
private function limpiarIntentosFallidos($correo)
{
    if (isset($_SESSION['intentos'][$correo])) {
        unset($_SESSION['intentos'][$correo]);
    }
}
    
    // NUEVO: Cambiar contraseña temporal por nueva (CON VALIDACIONES)
public function changePassword(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    
    // Validaciones básicas
    $requiredFields = ['correo', 'password_actual', 'password_nueva', 'confirmar_password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return ResponseUtil::badRequest("El campo {$field} es requerido");
        }
    }
    
    // Validar que las contraseñas coincidan
    if ($data['password_nueva'] !== $data['confirmar_password']) {
        return ResponseUtil::badRequest('Las contraseñas no coinciden');
    }
    
    // 🔥 VALIDAR NUEVA CONTRASEÑA CON EL VALIDATOR
    $passwordErrors = PasswordValidator::validate($data['password_nueva']);
    if (!empty($passwordErrors)) {
        return ResponseUtil::badRequest('Contraseña no válida', $passwordErrors);
    }
    
    try {
        // Buscar usuario
        $usuario = DB::table('usuarios')
            ->select('id_usuario', 'correo', 'password', 'id_estado', 'nombres', 'apellidos')
            ->where('correo', $data['correo'])
            ->first();
        
        if (!$usuario) {
            return ResponseUtil::unauthorized('Usuario no encontrado');
        }
        
        // Verificar que esté en estado pendiente (3)
        if ($usuario->id_estado != 3) {
            return ResponseUtil::badRequest('El usuario no está en proceso de cambio de contraseña');
        }
        
        // Verificar contraseña temporal actual
        if (!password_verify($data['password_actual'], $usuario->password)) {
            return ResponseUtil::unauthorized('La contraseña temporal es incorrecta');
        }
        
        // Verificar que la nueva no sea igual a la temporal
        if (password_verify($data['password_nueva'], $usuario->password)) {
            return ResponseUtil::badRequest('La nueva contraseña debe ser diferente a la temporal');
        }
        
        // 🔥 ENCRIPTAR NUEVA CONTRASEÑA
        $nuevaPasswordEncriptada = password_hash($data['password_nueva'], PASSWORD_DEFAULT);
        
        // ✅ ACTUALIZAR CONTRASEÑA Y CAMBIAR ESTADO A ACTIVO (1)
        DB::table('usuarios')
            ->where('id_usuario', $usuario->id_usuario)
            ->update([
                'password' => $nuevaPasswordEncriptada,
                'id_estado' => 1  // 🔄 ESTADO ACTIVO
            ]);
        
        // Log de seguridad
        error_log("Password changed successfully: {$data['correo']} - User ID: {$usuario->id_usuario}");
        
        return ResponseUtil::success([
            'password_changed' => true,
            'usuario_activado' => true,
            'mensaje_usuario' => 'Contraseña cambiada exitosamente. Ya puede iniciar sesión con su nueva contraseña.'
        ], 'Contraseña actualizada correctamente');
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        return ResponseUtil::error('Error cambiando la contraseña. Intente nuevamente');
    }
}


    
    // MEJORADO: Enviar clave temporal por email CON CAMBIO DE ESTADO
public function enviarClaveTemporalEmail(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    
    // Validar email
    if (empty($data['correo'])) {
        return ResponseUtil::badRequest('El correo es requerido');
    }
    
    // Validar formato de email
    $erroresEmail = EmailValidator::validate($data['correo']);
    if (!empty($erroresEmail)) {
        return ResponseUtil::badRequest('Email inválido', $erroresEmail);
    }
    
    try {
        // Verificar que el usuario existe
        $usuario = DB::table('usuarios')
            ->select('id_usuario', 'correo', 'nombres', 'apellidos', 'id_estado')
            ->where('correo', $data['correo'])
            ->first();
        
        if (!$usuario) {
            // 🔒 MENSAJE SEGURO - No revelar si existe o no
            return ResponseUtil::success([
                'clave_temporal_generada' => true,
                'correo_enviado' => true,
                'mensaje_usuario' => 'Si el correo existe en nuestro sistema, recibirás una clave temporal.'
            ], 'Solicitud procesada exitosamente');
        }
        
        // Verificar que no esté bloqueado
        if ($usuario->id_estado == 2) {
            return ResponseUtil::badRequest('Cuenta bloqueada. Contacte al administrador');
        }
        
        if ($usuario->id_estado == 4) {
            return ResponseUtil::badRequest('Cuenta deshabilitada. Contacte al administrador');
        }
        
        // Generar clave temporal ESPECÍFICA para recuperación
        require_once __DIR__ . '/../../../config/MailService.php';
        $claveTemporalRecuperacion = \MailService::generarClaveRecuperacion();
        
        // 🔥 ENCRIPTAR CON BCRYPT
        $passwordEncriptada = password_hash($claveTemporalRecuperacion, PASSWORD_DEFAULT);
        
        // ✅ ACTUALIZAR CONTRASEÑA Y CAMBIAR ESTADO A 3 (PENDIENTE)
        DB::table('usuarios')
            ->where('id_usuario', $usuario->id_usuario)
            ->update([
                'password' => $passwordEncriptada,
                'id_estado' => 3  // 🔄 ESTADO PENDIENTE
            ]);
        
        // Enviar por email
        $mailService = new \MailService();
        $nombreCompleto = $usuario->nombres . ' ' . $usuario->apellidos;
        
        $emailEnviado = $mailService->enviarClaveRecuperacion(
            $data['correo'],
            $nombreCompleto,
            $claveTemporalRecuperacion
        );
        
        if ($emailEnviado) {
            // Log de seguridad
            error_log("Password recovery sent: {$data['correo']} - User ID: {$usuario->id_usuario}");
            
            return ResponseUtil::success([
                'clave_temporal_generada' => true,
                'correo_enviado' => true,
                'mensaje_usuario' => 'Te hemos enviado una clave temporal a tu correo electrónico. Úsala para cambiar tu contraseña.'
            ], 'Clave temporal de recuperación enviada exitosamente');
        } else {
            return ResponseUtil::error('Error enviando el correo. Intenta nuevamente');
        }
        
    } catch (Exception $e) {
        error_log("Password recovery error: " . $e->getMessage());
        return ResponseUtil::error('Error procesando la solicitud. Intenta nuevamente');
    }

    
    
}

// NUEVO: Cambiar contraseña para usuario logueado (NO temporal)
public function changePasswordLoggedUser(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    
    // Validaciones básicas
    $requiredFields = ['id_usuario', 'password_actual', 'password_nueva', 'confirmar_password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return ResponseUtil::badRequest("El campo {$field} es requerido");
        }
    }
    
    // Validar que las contraseñas coincidan
    if ($data['password_nueva'] !== $data['confirmar_password']) {
        return ResponseUtil::badRequest('Las contraseñas no coinciden');
    }
    
    // Validar que la nueva contraseña sea diferente a la actual
    if ($data['password_actual'] === $data['password_nueva']) {
        return ResponseUtil::badRequest('La nueva contraseña debe ser diferente a la actual');
    }
    
    // 🔥 VALIDAR NUEVA CONTRASEÑA CON EL VALIDATOR
    $passwordErrors = PasswordValidator::validate($data['password_nueva']);
    if (!empty($passwordErrors)) {
        return ResponseUtil::badRequest('La nueva contraseña no cumple los requisitos de seguridad', $passwordErrors);
    }
    
    try {
        // Buscar usuario por ID
        $usuario = DB::table('usuarios')
            ->select('id_usuario', 'cedula', 'correo', 'password', 'id_estado', 'nombres', 'apellidos')
            ->where('id_usuario', $data['id_usuario'])
            ->first();
        
        if (!$usuario) {
            return ResponseUtil::notFound('Usuario no encontrado');
        }
        
        // Verificar que el usuario esté activo
        if ($usuario->id_estado != 1) {
            return ResponseUtil::badRequest('El usuario no está activo. Contacte al administrador');
        }
        
        // Verificar contraseña actual
        if (!password_verify($data['password_actual'], $usuario->password)) {
            return ResponseUtil::unauthorized('La contraseña actual es incorrecta');
        }
        
        // 🔥 ENCRIPTAR NUEVA CONTRASEÑA
        $nuevaPasswordEncriptada = password_hash($data['password_nueva'], PASSWORD_DEFAULT);
        
        // ✅ ACTUALIZAR CONTRASEÑA (mantener estado activo)
        DB::table('usuarios')
            ->where('id_usuario', $usuario->id_usuario)
            ->update([
                'password' => $nuevaPasswordEncriptada
            ]);
        
        // Log de seguridad
        error_log("Password changed by logged user: {$usuario->correo} (ID: {$usuario->id_usuario})");
        
        return ResponseUtil::success([
            'password_changed' => true,
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'correo' => $usuario->correo,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos
            ]
        ], 'Contraseña actualizada exitosamente');
        
    } catch (Exception $e) {
        error_log("Password change error for logged user: " . $e->getMessage());
        return ResponseUtil::error('Error cambiando la contraseña. Intente nuevamente');
    }
}


}
?>