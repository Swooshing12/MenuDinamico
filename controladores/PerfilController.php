<?php
// controladores/PerfilController.php
require_once __DIR__ . "/../modelos/Usuario.php";
require_once __DIR__ . "/../config/MailService.php";

class PerfilController {
    private $usuarioModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->usuarioModel = new Usuario();
    }
    
    public function manejarSolicitud() {
        if (!isset($_SESSION['id_usuario'])) {
            $this->responderJSON([
                'success' => false,
                'message' => 'Sesión no válida'
            ]);
            return;
        }
        
        $action = $_GET['action'] ?? $_POST['action'] ?? 'obtener';
        
        switch ($action) {
            case 'obtener':
                $this->obtenerPerfil();
                break;
            case 'actualizar':
                $this->actualizarPerfil();
                break;
            case 'cambiarPassword':
                $this->cambiarPassword();
                break;
            default:
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Acción no válida'
                ]);
        }
    }
    
    private function obtenerPerfil() {
        try {
            $id_usuario = $_SESSION['id_usuario'];
            
            // Obtener datos completos del usuario con rol y estado
            $query = "SELECT 
                        u.id_usuario, u.cedula, u.username, u.nombres, u.apellidos,
                        u.sexo, u.nacionalidad, u.correo, u.fecha_creacion,
                        r.nombre_rol, e.nombre_estado,
                        -- Datos adicionales según el rol
                        CASE 
                            WHEN p.id_paciente IS NOT NULL THEN 'paciente'
                            WHEN d.id_doctor IS NOT NULL THEN 'doctor' 
                            ELSE 'staff'
                        END as tipo_usuario,
                        p.fecha_nacimiento, p.tipo_sangre, p.telefono,
                        d.titulo_profesional, esp.nombre_especialidad
                      FROM usuarios u
                      LEFT JOIN roles r ON u.id_rol = r.id_rol
                      LEFT JOIN estados e ON u.id_estado = e.id_estado
                      LEFT JOIN pacientes p ON u.id_usuario = p.id_usuario
                      LEFT JOIN doctores d ON u.id_usuario = d.id_usuario
                      LEFT JOIN especialidades esp ON d.id_especialidad = esp.id_especialidad
                      WHERE u.id_usuario = :id_usuario";
            
            $stmt = $this->usuarioModel->conn->prepare($query);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                $this->responderJSON([
                    'success' => true,
                    'data' => $usuario
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error obteniendo perfil: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al obtener el perfil'
            ]);
        }
    }
    
    private function actualizarPerfil() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        try {
            $id_usuario = $_SESSION['id_usuario'];
            
            // Validar campos requeridos
            $campos = ['nombres', 'apellidos', 'sexo', 'nacionalidad', 'correo'];
            foreach ($campos as $campo) {
                if (empty($_POST[$campo])) {
                    $this->responderJSON([
                        'success' => false,
                        'message' => "El campo $campo es requerido"
                    ]);
                    return;
                }
            }
            
            // Actualizar datos básicos
            $query = "UPDATE usuarios SET 
                        nombres = :nombres,
                        apellidos = :apellidos,
                        sexo = :sexo,
                        nacionalidad = :nacionalidad,
                        correo = :correo
                      WHERE id_usuario = :id_usuario";
            
            $stmt = $this->usuarioModel->conn->prepare($query);
            $resultado = $stmt->execute([
                ':nombres' => trim($_POST['nombres']),
                ':apellidos' => trim($_POST['apellidos']),
                ':sexo' => $_POST['sexo'],
                ':nacionalidad' => $_POST['nacionalidad'],
                ':correo' => trim($_POST['correo']),
                ':id_usuario' => $id_usuario
            ]);
            
            // Actualizar datos específicos según el tipo de usuario
            if (isset($_POST['telefono']) && !empty($_POST['telefono'])) {
                $queryPaciente = "UPDATE pacientes SET telefono = :telefono WHERE id_usuario = :id_usuario";
                $stmtPaciente = $this->usuarioModel->conn->prepare($queryPaciente);
                $stmtPaciente->execute([
                    ':telefono' => trim($_POST['telefono']),
                    ':id_usuario' => $id_usuario
                ]);
            }
            
            if ($resultado) {
                // Actualizar datos de sesión
                $_SESSION['nombres'] = trim($_POST['nombres']);
                $_SESSION['apellidos'] = trim($_POST['apellidos']);
                
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Perfil actualizado exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el perfil'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error actualizando perfil: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ]);
        }
    }
    
    private function cambiarPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        try {
            $id_usuario = $_SESSION['id_usuario'];
            $passwordActual = $_POST['password_actual'] ?? '';
            $passwordNueva = $_POST['password_nueva'] ?? '';
            $passwordConfirmar = $_POST['password_confirmar'] ?? '';
            
            // Validaciones
            if (empty($passwordActual) || empty($passwordNueva) || empty($passwordConfirmar)) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ]);
                return;
            }
            
            if ($passwordNueva !== $passwordConfirmar) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Las contraseñas nuevas no coinciden'
                ]);
                return;
            }
            
            if (strlen($passwordNueva) < 6) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ]);
                return;
            }
            
            // Verificar contraseña actual
            $usuario = $this->usuarioModel->obtenerPorId($id_usuario);
            if (!$usuario || !password_verify($passwordActual, $usuario['password'])) {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ]);
                return;
            }
            
            // Cambiar contraseña
            $resultado = $this->usuarioModel->cambiarPassword($id_usuario, $passwordNueva);
            
            if ($resultado) {
                // Activar usuario si está pendiente
                if ($usuario['id_estado'] == 3) { // Estado Pendiente
                    $queryActivar = "UPDATE usuarios SET id_estado = 1 WHERE id_usuario = :id_usuario";
                    $stmtActivar = $this->usuarioModel->conn->prepare($queryActivar);
                    $stmtActivar->execute([':id_usuario' => $id_usuario]);
                    $_SESSION['id_estado'] = 1;
                }
                
                $this->responderJSON([
                    'success' => true,
                    'message' => 'Contraseña actualizada exitosamente'
                ]);
            } else {
                $this->responderJSON([
                    'success' => false,
                    'message' => 'Error al cambiar la contraseña'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            $this->responderJSON([
                'success' => false,
                'message' => 'Error al cambiar la contraseña'
            ]);
        }
    }
    
    private function responderJSON($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

// Manejar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new PerfilController();
    $controller->manejarSolicitud();
}
?>