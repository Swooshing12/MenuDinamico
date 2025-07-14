<?php  
session_start(); 
$error = isset($_SESSION["error"]) ? $_SESSION["error"] : ""; 
unset($_SESSION["error"]);  

$alerta = isset($_SESSION["alerta"]) ? $_SESSION["alerta"] : null; 
unset($_SESSION["alerta"]);
?>  

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Iniciar Sesión</title>
    <link rel="stylesheet" type="text/css" href="../estilos/login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-wrapper">
        <!-- Panel izquierdo con imagen -->
        <div class="login-image-panel">
            <div class="image-overlay">
                <div class="brand-content">
                    <div class="brand-logo">
                        <i class="fas fa-hospital-alt"></i>
                        <h1>MediSys</h1>
                    </div>
                    <p class="brand-tagline">Sistema de Gestión Hospitalaria</p>
                    <div class="features">
                        <div class="feature-item">
                            <i class="fas fa-user-md"></i>
                            <span>Gestión de Médicos</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Citas Médicas</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-file-medical"></i>
                            <span>Historiales Clínicos</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel derecho con formulario -->
<div class="login-form-panel">
    <div class="login-container">
        <!-- Header elegante con avatar -->
        <div class="login-header">
            <div class="avatar-container">
                <div class="avatar-circle">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="avatar-glow"></div>
            </div>
            <h2>Bienvenido</h2>
            <p>Accede de forma segura al sistema MediSys</p>
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Conexión Segura</span>
            </div>
        </div>

        <form action="../controladores/LoginControlador/LoginController.php" method="POST" class="login-form">
            <!-- Campo de email mejorado -->
            <div class="input-group">
                <label class="input-label">Correo Electrónico</label>
                <div class="input-wrapper">
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" name="username" placeholder="ejemplo@medisys.com" required class="form-input">
                    <div class="input-border"></div>
                    <div class="input-glow"></div>
                </div>
            </div>

            <!-- Campo de contraseña mejorado -->
            <div class="input-group">
                <label class="input-label">Contraseña</label>
                <div class="input-wrapper">
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" name="password" placeholder="••••••••" required id="password" class="form-input">

                    <div class="input-border"></div>
                    <div class="input-glow"></div>
                </div>
                <div class="password-strength">
                    <div class="strength-indicator">
                        <div class="strength-bar"></div>
                    </div>
                </div>
            </div>

            <!-- Opciones del formulario elegantes -->
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span class="checkmark">
                        <i class="fas fa-check"></i>
                    </span>
                    <span class="label-text">Recordarme</span>
                </label>

            </div>

            <!-- Botón de login premium -->
            <button type="submit" class="login-btn">
                <div class="btn-background"></div>
                <div class="btn-content">
                    <span class="btn-text">Iniciar Sesión</span>
                    <div class="btn-icon">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
                <div class="btn-ripple"></div>
            </button>

            <!-- Separador elegante -->
            <div class="divider">
                <div class="divider-line"></div>
                <span class="divider-text">Acceso Seguro</span>
                <div class="divider-line"></div>
            </div>
        </form>

        <!-- Footer elegante -->
        <div class="login-footer">
            <div class="footer-content">
                <div class="security-info">
                    <i class="fas fa-lock"></i>
                    <span>Protegido por SSL</span>
                </div>
                <p class="copyright">&copy; 2025 MediSys. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</div>

    <!-- JavaScript para mostrar/ocultar contraseña -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>

    <?php if (!empty($error)): ?>
    <script>
        Swal.fire({
            title: "Error de Acceso",
            text: "<?php echo $error; ?>",
            icon: "error",
            confirmButtonColor: "#dc3545",
            confirmButtonText: "Intentar de nuevo"
        });
    </script>
    <?php endif; ?>

    <?php if (!empty($alerta)): ?>
    <script>
        Swal.fire({
            title: "<?php echo $alerta['titulo']; ?>",
            text: "<?php echo $alerta['mensaje']; ?>",
            icon: "<?php echo $alerta['icono']; ?>",
            confirmButtonColor: "#28a745",
            confirmButtonText: "Aceptar"
        });
    </script>
    <?php endif; ?>
</body>
</html>