<?php
require_once __DIR__ . "/../config/config.php"; // Incluir BASE_URL

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario no está autenticado
if (!isset($_SESSION['id_usuario'])) {
    // Redirigir al login con un parámetro único para evitar caché
    header("Location: " . BASE_URL . "/vistas/login.php?timestamp=" . time());
    exit();
}

// 🔹 OPCIONAL: Si quieres restringir el acceso por roles, puedes hacer algo como esto:
// if ($_SESSION['id_rol'] != 1) {  // Suponiendo que el rol 1 es "Administrador"
//     header("Location: " . BASE_URL . "/vistas/sin_permisos.php");
//     exit();
// }
?>
