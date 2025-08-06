<?php
// Incluir configuración
require_once __DIR__ . "/config/config.php";

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['id_usuario'])) {
    header("Location: " . BASE_URL . "/vistas/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Sistema de Gestión Hospitalaria</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .welcome-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 60px 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
        }

        .logo-icon {
            font-size: 4rem;
            color: #667eea;
            animation: pulse 2s ease-in-out infinite;
        }

        .logo-pulse {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            animation: pulseRing 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes pulseRing {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
        }

        .brand-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 40px;
            font-weight: 500;
        }

        .welcome-text {
            font-size: 1rem;
            color: #4a5568;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .features {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(113, 128, 150, 0.2);
        }

        .feature-item {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
            font-size: 0.95rem;
            color: #4a5568;
        }

        .feature-icon {
            color: #667eea;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        @media (max-width: 576px) {
            .welcome-container {
                padding: 40px 30px;
                margin: 10px;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .login-btn {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <!-- Logo y título -->
        <div class="logo-container">
            <i class="bi bi-heart-pulse-fill logo-icon"></i>
            <div class="logo-pulse"></div>
        </div>
        
        <h1 class="brand-title">MediSys</h1>
        <p class="brand-subtitle">Sistema de Gestión Hospitalaria</p>
        
        <p class="welcome-text">
            Bienvenido al sistema integral de gestión hospitalaria. 
            Administra pacientes, citas, personal médico y más desde una plataforma unificada.
        </p>
        
        <!-- Botón principal de acceso -->
        <a href="<?= BASE_URL ?>/vistas/login.php" class="login-btn">
            <i class="bi bi-box-arrow-in-right"></i>
            Acceder al Sistema
        </a>
        
        <!-- Características del sistema -->
        <div class="features">
            <div class="feature-item">
                <i class="bi bi-shield-check feature-icon"></i>
                <span>Sistema seguro con control de acceso</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-people feature-icon"></i>
                <span>Gestión integral de pacientes y personal</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-calendar-check feature-icon"></i>
                <span>Administración de citas y consultas</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-graph-up feature-icon"></i>
                <span>Reportes y análisis en tiempo real</span>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>