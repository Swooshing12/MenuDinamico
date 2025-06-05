<?php

session_start();
$error = isset($_SESSION["error"]) ? $_SESSION["error"] : "";
unset($_SESSION["error"]);

$alerta = isset($_SESSION["alerta"]) ? $_SESSION["alerta"] : null;
unset($_SESSION["alerta"]); // Eliminar alerta después de mostrarla
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <!-- Llamar CSS de manera más directa -->
    <link rel="stylesheet" type="text/css" href="../estilos/login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form action="../controladores/LoginControlador/LoginController.php" method="POST">
            <div class="input-group">
                <label for="username">Correo Electrónico</label>
                <input type="text" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Ingresar</button>
        </form>
    </div>

    <?php if (!empty($error)): ?>
    <script>
        Swal.fire({
            title: "Error",
            text: "<?php echo $error; ?>",
            icon: "error",
            confirmButtonColor: "#d33",
            confirmButtonText: "Aceptar"
        });
    </script>
    <?php endif; ?>

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

</html>
