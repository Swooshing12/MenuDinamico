<?php
require_once __DIR__ . "/../config/config.php";

session_start();

// Verificar si el usuario está autenticado y en estado "Pendiente" (id_estado = 3)
if (!isset($_SESSION["id_usuario"]) || $_SESSION["id_estado"] != 3) {
    header("Location: " . BASE_URL . "/vistas/login.php");
    exit();
}

// Capturar alerta si existe
$alerta = isset($_SESSION["alerta"]) ? $_SESSION["alerta"] : null;
unset($_SESSION["alerta"]); // Eliminar alerta después de mostrarla
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="../estilos/login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <h2>Cambiar Contraseña</h2>
        <form action="../controladores/CambiarContraseñaControlador/CambiarContraseñaController.php" method="POST">
            <div class="input-group">
                <label for="password">Nueva Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit">Actualizar Contraseña</button>
        </form>
    </div>

    <!-- Mostrar alerta si existe -->
    <?php if (!empty($alerta)): ?>
    <script>
        Swal.fire({
            title: "<?php echo $alerta['titulo']; ?>",
            text: "<?php echo $alerta['mensaje']; ?>",
            icon: "<?php echo $alerta['icono']; ?>",
            confirmButtonColor: "#d33",
            confirmButtonText: "Aceptar"
        });
    </script>
    <?php endif; ?>

</body>
<script src="<?= BASE_URL ?>/js/bloquear.js"></script>

</html>
