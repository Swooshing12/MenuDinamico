<?php
require_once __DIR__ . "/../../modelos/Usuario.php";

echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el modelo Usuario
require_once __DIR__ . "/../../modelos/Usuario.php";

class LoginController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    // 🔹 Método para manejar el inicio de sesión con intentos
    public function login($username, $password) {
        $usuario = $this->usuarioModel->obtenerPorCorreo($username);

        if (!$usuario) {
            $_SESSION["error"] = "Usuario no encontrado.";
            header("Location: ../../vistas/login.php");
            exit();
        }

        // Verificar si el usuario está bloqueado (id_estado = 2)
        if ($usuario["id_estado"] == 2) {
            $_SESSION["alerta"] = [
                "titulo" => "Cuenta bloqueada",
                "mensaje" => "Has excedido el número de intentos permitidos. Contacta al administrador.",
                "icono" => "error"
            ];
            
            header("Location: ../../vistas/login.php");
            exit();
        }

        // Verificar si el usuario está inhabilitado (id_estado = 4)
        if ($usuario["id_estado"] == 4) {
            $_SESSION["alerta"] = [
                "titulo" => "Cuenta inhabilitada",
                "mensaje" => "Su Cuenta se encuentra inhabilitada. Contacta al administrador.",
                "icono" => "error"
            ];
            
            header("Location: ../../vistas/login.php");
            exit();
        }

        // Verificar la contraseña
        if (!password_verify($password, $usuario["password"])) {
            if (!isset($_SESSION["intentos"][$username])) {
                $_SESSION["intentos"][$username] = 1;
            } else {
                $_SESSION["intentos"][$username]++;
            }

            if ($_SESSION["intentos"][$username] >= 3) {
                $this->usuarioModel->bloquearUsuario($usuario["id_usuario"]);

                $_SESSION["alerta"] = [
                    "titulo" => "Usuario bloqueado",
                    "mensaje" => "Has superado el límite de intentos y tu cuenta ha sido bloqueada.",
                    "icono" => "error"
                ];

                header("Location: ../../vistas/login.php");
                exit();
            } else {
                $_SESSION["alerta"] = [
                    "titulo" => "Contraseña incorrecta",
                    "mensaje" => "Intento " . $_SESSION["intentos"][$username] . " de 3.",
                    "icono" => "warning"
                ];

                header("Location: ../../vistas/login.php");
                exit();
            }
        }

        // Si la contraseña es correcta, limpiar intentos y guardar sesión
        unset($_SESSION["intentos"][$username]);
        $_SESSION["id_usuario"] = $usuario["id_usuario"];
        $_SESSION["username"] = $usuario["username"];
        $_SESSION["id_rol"] = $usuario["id_rol"];
        $_SESSION["id_estado"] = $usuario["id_estado"];

        // 🔹 Si el usuario está en estado "Pendiente" (id_estado = 3), redirigir a CambiarContraseña.php
        if ($usuario["id_estado"] == 3) {
            header("Location: ../../vistas/CambiarContraseña.php");
            exit();
        }

        // 🔹 Si el usuario es válido y no está en estado Pendiente, redirigir al dashboard
        header("Location: ../../vistas/dashboard.php");
        exit();
    }

    // 🔹 Método para cerrar sesión
    public function logout() {
        session_destroy();
        header("Location: ../../vistas/login.php");
        exit();
    }

    // 🔹 Método helper para logging de errores
    private function logError($mensaje, $contexto = []) {
        $log = date('Y-m-d H:i:s') . " [LoginController] {$mensaje}";
        if (!empty($contexto)) {
            $log .= " - Contexto: " . json_encode($contexto);
        }
        error_log($log);
    }
}

// Manejo de peticiones POST para login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"]) && isset($_POST["password"])) {
    try {
        $controller = new LoginController();
        $controller->login($_POST["username"], $_POST["password"]);
    } catch (Exception $e) {
        error_log("Error en login: " . $e->getMessage());
        $_SESSION["alerta"] = [
            "titulo" => "Error del sistema",
            "mensaje" => "Ha ocurrido un error. Por favor, intenta nuevamente.",
            "icono" => "error"
        ];
        header("Location: ../../vistas/login.php");
        exit();
    }
}

// Manejo de logout
if (isset($_GET["logout"])) {
    try {
        $controller = new LoginController();
        $controller->logout();
    } catch (Exception $e) {
        error_log("Error en logout: " . $e->getMessage());
        // Redirigir al login aunque haya error
        header("Location: ../../vistas/login.php");
        exit();
    }
}
?>