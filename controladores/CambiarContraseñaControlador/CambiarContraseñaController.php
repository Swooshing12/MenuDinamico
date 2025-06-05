<?php
require_once __DIR__ . "/../../modelos/Usuario.php";

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class CambiarContraseñaController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    public function cambiarContraseña($password, $confirm_password) {
        // Verificar que las contraseñas coincidan
        if ($password !== $confirm_password) {
            $_SESSION["alerta"] = [
                "titulo" => "Error",
                "mensaje" => "Las contraseñas no coinciden.",
                "icono" => "error"
            ];
            header("Location: ../../vistas/CambiarContraseña.php");
            exit();
        }

        // Validar longitud mínima de contraseña
        if (strlen($password) < 6) {
            $_SESSION["alerta"] = [
                "titulo" => "Error",
                "mensaje" => "La contraseña debe tener al menos 6 caracteres.",
                "icono" => "error"
            ];
            header("Location: ../../vistas/CambiarContraseña.php");
            exit();
        }

        // Asegurar que el usuario está autenticado y en estado "Pendiente" (id_estado = 3)
        if (!isset($_SESSION["id_usuario"]) || $_SESSION["id_estado"] != 3) {
            header("Location: ../../vistas/login.php");
            exit();
        }

        try {
            $id_usuario = $_SESSION["id_usuario"];

            // Actualizar la contraseña
            if ($this->usuarioModel->cambiarPassword($id_usuario, $password)) {
                // Guardar alerta en una variable temporal
                $alerta = [
                    "titulo" => "Contraseña actualizada",
                    "mensaje" => "Tu contraseña ha sido cambiada exitosamente. Ya puedes iniciar sesión.",
                    "icono" => "success"
                ];
            
                // Cambiar el estado del usuario a "Activo" (id_estado = 1)
                $this->usuarioModel->actualizarEstado($id_usuario, 1);
            
                // Cerrar sesión sin perder la alerta
                session_destroy();
                session_start(); // Iniciar una nueva sesión para restaurar la alerta
                $_SESSION["alerta"] = $alerta;
            
                // Redirigir al login con la alerta
                header("Location: ../../vistas/login.php");
                exit();
            } else {
                $_SESSION["alerta"] = [
                    "titulo" => "Error",
                    "mensaje" => "Hubo un problema al actualizar la contraseña. Inténtalo nuevamente.",
                    "icono" => "error"
                ];
                header("Location: ../../vistas/CambiarContraseña.php");
                exit();
            }
        } catch (Exception $e) {
            // Log del error para debugging
            error_log("Error cambiando contraseña: " . $e->getMessage());
            
            $_SESSION["alerta"] = [
                "titulo" => "Error del sistema",
                "mensaje" => "Ha ocurrido un error interno. Por favor, contacta al administrador.",
                "icono" => "error"
            ];
            header("Location: ../../vistas/CambiarContraseña.php");
            exit();
        }
    }

    // Método para logging de errores
    private function logError($mensaje, $contexto = []) {
        $log = date('Y-m-d H:i:s') . " [CambiarContraseñaController] {$mensaje}";
        if (!empty($contexto)) {
            $log .= " - Contexto: " . json_encode($contexto);
        }
        error_log($log);
    }
}

// Manejo de peticiones POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["password"]) && isset($_POST["confirm_password"])) {
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    try {
        $controller = new CambiarContraseñaController();
        $controller->cambiarContraseña($password, $confirm_password);
    } catch (Exception $e) {
        error_log("Error en CambiarContraseñaController: " . $e->getMessage());
        $_SESSION["alerta"] = [
            "titulo" => "Error del sistema",
            "mensaje" => "Ha ocurrido un error. Por favor, intenta nuevamente.",
            "icono" => "error"
        ];
        header("Location: ../../vistas/CambiarContraseña.php");
        exit();
    }
} else {
    // Si no es una petición POST válida, redirigir al login
    header("Location: ../../vistas/login.php");
    exit();
}
?>