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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #4fc3f7;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            overflow-x: hidden;
        }

        /* ===== HERO SECTION CON IMAGEN ===== */
        .hero-section {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.8) 50%, rgba(79, 195, 247, 0.9) 100%),
                url('<?= BASE_URL ?>fotos/clinicaimagen.png') center/cover no-repeat;
            z-index: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 30% 70%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255,255,255,0.1) 0%, transparent 50%),
                linear-gradient(45deg, rgba(102, 126, 234, 0.3) 0%, rgba(79, 195, 247, 0.2) 100%);
            z-index: 2;
        }

        .hero-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 3;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
            backdrop-filter: blur(2px);
        }

        .particle:nth-child(1) { width: 100px; height: 100px; top: 15%; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; top: 60%; left: 85%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 120px; height: 120px; top: 75%; left: 15%; animation-delay: 4s; }
        .particle:nth-child(4) { width: 80px; height: 80px; top: 25%; left: 75%; animation-delay: 6s; }
        .particle:nth-child(5) { width: 40px; height: 40px; top: 50%; left: 50%; animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.2; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 0.4; }
        }

        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
            color: white;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-logo {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
        }

        .hero-logo-icon {
            font-size: 6rem;
            color: var(--white);
            text-shadow: 0 0 40px rgba(255,255,255,0.6);
            animation: heartbeat 2.5s ease-in-out infinite;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 4px 20px rgba(255,255,255,0.3));
        }

        .hero-logo-pulse {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            animation: pulse-ring 4s ease-in-out infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); filter: drop-shadow(0 4px 30px rgba(255,255,255,0.5)); }
        }

        @keyframes pulse-ring {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.8; }
            100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: 4.5rem;
            font-weight: 900;
            margin-bottom: 25px;
            background: linear-gradient(45deg, #ffffff, #e3f2fd, #bbdefb, #90caf9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 6px 25px rgba(0,0,0,0.4);
            letter-spacing: -3px;
            position: relative;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--white));
            border-radius: 2px;
            box-shadow: 0 2px 10px rgba(79, 195, 247, 0.5);
        }

        .hero-subtitle {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 35px;
            opacity: 0.95;
            text-shadow: 0 3px 15px rgba(0,0,0,0.4);
            background: rgba(255,255,255,0.1);
            padding: 15px 30px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            display: inline-block;
        }

        .hero-description {
            font-size: 1.3rem;
            margin-bottom: 50px;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.05);
            padding: 25px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 20px;
            background: linear-gradient(45deg, var(--white), #f8f9fa);
            color: var(--primary-color);
            padding: 22px 50px;
            border-radius: 60px;
            font-size: 1.3rem;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 
                0 20px 40px rgba(0,0,0,0.3),
                inset 0 1px 0 rgba(255,255,255,0.9);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hero-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .hero-cta:hover::before {
            left: 100%;
        }

        .hero-cta:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 
                0 30px 60px rgba(0,0,0,0.4),
                inset 0 1px 0 rgba(255,255,255,0.9);
            color: var(--primary-color);
        }

        .hero-cta i {
            font-size: 1.5rem;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-cta:hover i {
            transform: translateX(8px) rotate(15deg);
        }

        /* ===== SECTION CON IMAGEN DE FONDO ===== */
        .medical-showcase {
            padding: 120px 0;
            background: 
                linear-gradient(rgba(248, 249, 250, 0.95), rgba(233, 236, 239, 0.95)),
                url('<?= BASE_URL ?>fotos/clinicaimagen.png') center/cover no-repeat fixed;
            position: relative;
            backdrop-filter: blur(1px);
        }

        .medical-showcase::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(79, 195, 247, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .showcase-content {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 60px;
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* ===== FEATURES SECTION MEJORADA ===== */
        .features-section {
            padding: 120px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-top: 70px;
        }

        .feature-card {
            background: var(--white);
            padding: 50px 40px;
            border-radius: 25px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 35px 70px rgba(0,0,0,0.2);
        }

        .feature-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2.5rem;
            color: var(--white);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .feature-icon.security { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .feature-icon.management { 
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            box-shadow: 0 10px 30px rgba(79, 195, 247, 0.4);
        }
        .feature-icon.appointments { 
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
        }
        .feature-icon.analytics { 
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.4);
        }

        .feature-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        .feature-description {
            color: #6c757d;
            line-height: 1.8;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .feature-benefits {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .benefit-tag {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* ===== STATS SECTION CON OVERLAY ===== */
        .stats-section {
            padding: 100px 0;
            background: 
                linear-gradient(135deg, rgba(102, 126, 234, 0.95), rgba(118, 75, 162, 0.95)),
                url('<?= BASE_URL ?>fotos/clinicaimagen.png') center/cover no-repeat fixed;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 50px;
            margin-top: 60px;
        }

        .stat-item {
            text-align: center;
            position: relative;
            background: rgba(255,255,255,0.1);
            padding: 40px 20px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.15);
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #ffffff, #e3f2fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 1.2rem;
            font-weight: 600;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== FOOTER ===== */
        .footer-section {
            background: var(--dark-color);
            color: var(--white);
            padding: 80px 0 40px;
            text-align: center;
        }

        .footer-logo {
            margin-bottom: 40px;
        }

        .footer-logo i {
            font-size: 4rem;
            color: var(--accent-color);
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 20px rgba(79, 195, 247, 0.5));
        }

        .footer-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .footer-description {
            font-size: 1.2rem;
            opacity: 0.8;
            margin-bottom: 50px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
        }

        .footer-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .footer-feature {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 25px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .footer-feature:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-5px);
        }

        .footer-feature i {
            font-size: 1.8rem;
            color: var(--accent-color);
        }

        .footer-feature span {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .footer-copyright {
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 40px;
            opacity: 0.7;
            font-size: 1rem;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 3rem;
                letter-spacing: -2px;
            }
            
            .hero-subtitle {
                font-size: 1.3rem;
                padding: 12px 25px;
            }
            
            .hero-description {
                font-size: 1.1rem;
                padding: 20px;
            }
            
            .hero-cta {
                padding: 18px 35px;
                font-size: 1.1rem;
            }
            
            .showcase-content {
                padding: 40px 30px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
            
            .stat-number {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-logo-icon {
                font-size: 4rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .feature-card {
                padding: 35px 25px;
            }
        }
    </style>
</head>
<body>
    <!-- HERO SECTION CON IMAGEN DE FONDO -->
    <section class="hero-section">
        <div class="hero-background"></div>
        <div class="hero-overlay"></div>
        
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-logo" data-aos="zoom-in" data-aos-duration="1200">
                    <i class="bi bi-heart-pulse-fill hero-logo-icon"></i>
                    <div class="hero-logo-pulse"></div>
                </div>
                
                <h1 class="hero-title" data-aos="fade-up" data-aos-delay="300">
                    MediSys
                </h1>
                
                <div class="hero-subtitle" data-aos="fade-up" data-aos-delay="500">
                    Sistema de Gestión Hospitalaria de Nueva Generación
                </div>
                
                <div class="hero-description" data-aos="fade-up" data-aos-delay="700">
                    Transformamos la atención médica con tecnología innovadora, 
                    brindando soluciones integrales para hospitales, clínicas y centros de salud 
                    que priorizan la excelencia en el cuidado del paciente.
                </div>
                
                <a href="<?= BASE_URL ?>vistas/login.php" class="hero-cta" data-aos="fade-up" data-aos-delay="900">
                    <i class="bi bi-arrow-right-circle-fill"></i>
                    <span>Acceder al Sistema</span>
                </a>
            </div>
        </div>
    </section>

    <!-- MEDICAL SHOWCASE SECTION -->
    <section class="medical-showcase">
        <div class="container">
            <div class="showcase-content" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
                        <h2 class="display-4 fw-bold mb-4" style="color: var(--dark-color);">
                            Tecnología al Servicio de la Salud
                        </h2>
                        <p class="lead mb-4" style="color: #6c757d;">
                            Nuestro sistema integra las mejores prácticas médicas con tecnología de vanguardia 
                            para ofrecer una experiencia completa tanto para profesionales como para pacientes.
                        </p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary rounded-circle p-2 me-3">
                                        <i class="bi bi-check-lg text-white"></i>
                                    </div>
                                    <span class="fw-semibold">Gestión Integral</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success rounded-circle p-2 me-3">
                                        <i class="bi bi-check-lg text-white"></i>
                                    </div>
                                    <span class="fw-semibold">Seguridad Avanzada</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-info rounded-circle p-2 me-3">
                                        <i class="bi bi-check-lg text-white"></i>
                                    </div>
                                    <span class="fw-semibold">Interfaz Intuitiva</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning rounded-circle p-2 me-3">
                                        <i class="bi bi-check-lg text-white"></i>
                                    </div>
                                    <span class="fw-semibold">Soporte 24/7</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6" data-aos="fade-left" data-aos-delay="400">
                        <div class="text-center">
                            <i class="bi bi-hospital display-1" style="color: var(--primary-color); opacity: 0.8;"></i>
                            <h4 class="mt-3" style="color: var(--dark-color);">Instalaciones Modernas</h4>
                            <p style="color: #6c757d;">
                                Equipamiento de última generación para brindar el mejor servicio médico
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features-section">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="display-4 fw-bold mb-4">Características Principales</h2>
                <p class="lead text-muted">
                    Descubre las herramientas que revolucionarán la gestión de tu centro médico
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon security">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="feature-title">Seguridad Avanzada</h3>
                    <p class="feature-description">
                        Sistema de autenticación robusto con control de acceso por roles, 
                        encriptación de datos y auditoría completa de actividades.
                    </p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Encriptado</span>
                        <span class="benefit-tag">Auditado</span>
                        <span class="benefit-tag">Confiable</span>
                    </div>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon management">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3 class="feature-title">Gestión Integral</h3>
                    <p class="feature-description">
                        Administra pacientes, personal médico, especialidades y sucursales 
                        desde una plataforma centralizada e intuitiva.
                        </p>
                        <div class="feature-benefits">
                        <span class="benefit-tag">Centralizado</span>
                        <span class="benefit-tag">Intuitivo</span>
                        <span class="benefit-tag">Completo</span>
                </div>
                    </div>
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon appointments">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <h3 class="feature-title">Citas Inteligentes</h3>
                <p class="feature-description">
                    Sistema avanzado de programación de citas con detección automática 
                    de disponibilidad y recordatorios personalizados.
                </p>
                <div class="feature-benefits">
                    <span class="benefit-tag">Automático</span>
                    <span class="benefit-tag">Inteligente</span>
                    <span class="benefit-tag">Eficiente</span>
                </div>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon analytics">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h3 class="feature-title">Análisis en Tiempo Real</h3>
                <p class="feature-description">
                    Dashboard ejecutivo con métricas clave, reportes personalizables 
                    y análisis predictivo para la toma de decisiones estratégicas.
                </p>
                <div class="feature-benefits">
                    <span class="benefit-tag">Tiempo Real</span>
                    <span class="benefit-tag">Predictivo</span>
                    <span class="benefit-tag">Estratégico</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS SECTION CON IMAGEN DE FONDO -->
<section class="stats-section">
    <div class="container">
        <div class="text-center" data-aos="fade-up">
            <h2 class="display-4 fw-bold mb-4">Números que Importan</h2>
            <p class="lead">
                Resultados comprobados que respaldan la confianza en MediSys
            </p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-item" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-number" data-counter="99">0</div>
                <div class="stat-label">% Disponibilidad</div>
            </div>
            
            <div class="stat-item" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-number" data-counter="24">0</div>
                <div class="stat-label">Hrs Soporte</div>
            </div>
            
            <div class="stat-item" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-number" data-counter="100">0</div>
                <div class="stat-label">% Seguridad</div>
            </div>
            
            <div class="stat-item" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-number" data-counter="500">0</div>
                <div class="stat-label">+ Usuarios</div>
            </div>
        </div>
    </div>
</section>

<!-- CALL TO ACTION SECTION -->
<section class="cta-section" style="padding: 100px 0; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center" data-aos="fade-up">
                <div class="bg-white p-5 rounded-4 shadow-lg">
                    <i class="bi bi-rocket-takeoff display-2 text-primary mb-4"></i>
                    <h2 class="display-5 fw-bold mb-4" style="color: var(--dark-color);">
                        ¿Listo para Transformar tu Centro Médico?
                    </h2>
                    <p class="lead text-muted mb-4">
                        Únete a cientos de instituciones de salud que ya confían en MediSys 
                        para optimizar sus procesos y mejorar la atención al paciente.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="<?= BASE_URL ?>vistas/login.php" class="btn btn-primary btn-lg px-5 py-3 rounded-pill">
                            <i class="bi bi-play-circle-fill me-2"></i>
                            Comenzar Ahora
                        </a>
                        <a href="#features" class="btn btn-outline-primary btn-lg px-5 py-3 rounded-pill">
                            <i class="bi bi-info-circle me-2"></i>
                            Más Información
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER SECTION -->
<footer class="footer-section">
    <div class="container">
        <div class="footer-logo" data-aos="fade-up">
            <i class="bi bi-heart-pulse-fill"></i>
            <h3 class="footer-title">MediSys</h3>
            <p class="footer-description">
                La solución tecnológica que necesita tu centro médico para brindar 
                la mejor atención a tus pacientes y optimizar todos tus procesos administrativos.
            </p>
        </div>
        
        <div class="footer-features">
            <div class="footer-feature" data-aos="fade-up" data-aos-delay="100">
                <i class="bi bi-shield-check"></i>
                <span>Datos Protegidos con Encriptación</span>
            </div>
            
            <div class="footer-feature" data-aos="fade-up" data-aos-delay="200">
                <i class="bi bi-cloud-check"></i>
                <span>Backup Automático Diario</span>
            </div>
            
            <div class="footer-feature" data-aos="fade-up" data-aos-delay="300">
                <i class="bi bi-headset"></i>
                <span>Soporte Técnico 24/7</span>
            </div>
            
            <div class="footer-feature" data-aos="fade-up" data-aos-delay="400">
                <i class="bi bi-lightning-charge"></i>
                <span>Alto Rendimiento Garantizado</span>
            </div>
        </div>
        
        <div class="footer-copyright" data-aos="fade-up" data-aos-delay="500">
            <p>&copy; 2025 MediSys - Sistema de Gestión Hospitalaria. Todos los derechos reservados.</p>
            <p>Desarrollado con ❤️ para revolucionar el cuidado de la salud en Ecuador y el mundo.</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    // Inicializar AOS con configuración mejorada
    AOS.init({
        duration: 1000,
        easing: 'ease-out-cubic',
        once: true,
        offset: 50,
        delay: 100
    });

    // Animación de contadores mejorada
    function animateCounters() {
        const counters = document.querySelectorAll('[data-counter]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-counter'));
                    const duration = 2000; // 2 segundos
                    const start = performance.now();
                    
                    const updateCounter = (currentTime) => {
                        const elapsed = currentTime - start;
                        const progress = Math.min(elapsed / duration, 1);
                        
                        // Función de easing para animación más suave
                        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                        const current = Math.floor(easeOutQuart * target);
                        
                        counter.textContent = current;
                        
                        if (progress < 1) {
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target;
                        }
                    };
                    
                    requestAnimationFrame(updateCounter);
                    observer.unobserve(counter);
                }
            });
        }, {
            threshold: 0.7
        });
        
        counters.forEach(counter => observer.observe(counter));
    }

    // Efecto parallax para las partículas y fondos
    function handleParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        
        // Mover partículas
        const particles = document.querySelectorAll('.particle');
        particles.forEach((particle, index) => {
            const speed = 0.3 + (index * 0.1);
            particle.style.transform = `translateY(${scrolled * speed}px) rotate(${scrolled * 0.1}deg)`;
        });
        
        // Efecto parallax en las secciones con imagen de fondo
        const heroBackground = document.querySelector('.hero-background');
        if (heroBackground) {
            heroBackground.style.transform = `translateY(${rate}px)`;
        }
    }

    // Smooth scroll para los enlaces internos
    function initSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Efecto de hover mejorado para las tarjetas
    function initCardEffects() {
        const cards = document.querySelectorAll('.feature-card, .stat-item');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    // Lazy loading para optimizar performance
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }

    // Inicializar todas las funcionalidades cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
        // Pequeño delay para asegurar que AOS se haya inicializado
        setTimeout(() => {
            animateCounters();
            initSmoothScroll();
            initCardEffects();
            initLazyLoading();
        }, 300);
    });

    // Throttle para el scroll event (optimización de performance)
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                handleParallax();
                ticking = false;
            });
            ticking = true;
        }
    });

    // Preloader opcional (se puede activar si se necesita)
    window.addEventListener('load', () => {
        document.body.classList.add('loaded');
        
        // Animación adicional de entrada
        setTimeout(() => {
            document.querySelector('.hero-content').style.opacity = '1';
            document.querySelector('.hero-content').style.transform = 'translateY(0)';
        }, 100);
    });

    // Easter egg: Konami Code para desarrolladores 🎮
    let konamiCode = [];
    const konami = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];
    
    document.addEventListener('keydown', (e) => {
        konamiCode.push(e.keyCode);
        if (konamiCode.length > konami.length) {
            konamiCode.shift();
        }
        
        if (JSON.stringify(konamiCode) === JSON.stringify(konami)) {
            // Activar modo desarrollador
            document.body.style.background = 'linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96c93d, #feca57)';
            document.body.style.backgroundSize = '400% 400%';
            document.body.style.animation = 'gradientShift 3s ease infinite';
            
            // Resetear después de 5 segundos
            setTimeout(() => {
                location.reload();
            }, 5000);
        }
    });
</script>

<!-- Estilo adicional para el easter egg -->
<style>
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* Transición suave para el estado inicial */
    .hero-content {
        transition: all 0.8s ease;
    }
    
    /* Loading state mejorado */
    body:not(.loaded) .hero-content {
        opacity: 0;
        transform: translateY(30px);
    }
</style>