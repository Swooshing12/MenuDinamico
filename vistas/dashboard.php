<?php
require_once "../helpers/permisos.php"; // Protección de sesión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys Dashboard | Centro Médico Digital</title>
    
    <!-- Bootstrap CSS -->
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../estilos/dashboard.css">
</head>
<body>
    <!-- Incluir la barra de navegación -->
    <?php include "../navbars/header.php"; ?>
    
    <!-- Incluir el sidebar -->
    <?php include "../navbars/sidebar.php"; ?>

    <!-- Contenido principal -->
<main class="dashboard-container">
    <div class="container-fluid p-4">
        <!-- Header Hero Section Rediseñado -->
        <div class="hero-header-clinic" data-aos="fade-down">
            <div class="hero-overlay"></div>
            <div class="hero-content-clinic">
                <div class="welcome-section">
                    <div class="welcome-icon">
                        <i class="bi bi-hospital-fill"></i>
                    </div>
                    <div class="welcome-text">
                        <h1 class="clinic-title">
                            <span class="clinic-name">MediSys</span>
                            <span class="clinic-subtitle">Centro Médico de Excelencia</span>
                        </h1>
                        <p class="welcome-message">
                            Bienvenido/a, <span class="user-name"><?php echo $_SESSION["username"]; ?></span>
                        </p>
                        <p class="clinic-tagline">
                            "Cuidando tu salud con tecnología de vanguardia y el mejor equipo médico"
                        </p>
                    </div>
                </div>
                
                <div class="clinic-stats-hero">
                    <div class="stat-hero-item">
                        <div class="stat-hero-number">15+</div>
                        <div class="stat-hero-label">Años de Experiencia</div>
                    </div>
                    <div class="stat-hero-item">
                        <div class="stat-hero-number">25K+</div>
                        <div class="stat-hero-label">Pacientes Satisfechos</div>
                    </div>
                    <div class="stat-hero-item">
                        <div class="stat-hero-number">98%</div>
                        <div class="stat-hero-label">Índice de Satisfacción</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección Sobre Nosotros -->
        <div class="about-clinic-section" data-aos="fade-up" data-aos-delay="200">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="about-content">
                        <div class="section-badge">
                            <i class="bi bi-award me-2"></i>
                            Certificados y Acreditados
                        </div>
                        <h2 class="section-title">
                            Líder en Atención Médica
                            <span class="title-highlight">Integral</span>
                        </h2>
                        <p class="section-description">
                            En MediSys, nos dedicamos a proporcionar atención médica de la más alta calidad, 
                            combinando experiencia médica excepcional con tecnología de vanguardia para 
                            garantizar el mejor cuidado para nuestros pacientes.
                        </p>
                        
                        <div class="features-list">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="feature-text">
                                    <h6>Atención 24/7</h6>
                                    <p>Servicios de emergencia disponibles las 24 horas del día</p>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="feature-text">
                                    <h6>Equipo Especializado</h6>
                                    <p>Médicos certificados en más de 15 especialidades</p>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="bi bi-cpu"></i>
                                </div>
                                <div class="feature-text">
                                    <h6>Tecnología Avanzada</h6>
                                    <p>Equipos médicos de última generación para diagnósticos precisos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="about-visual">
                        <div class="medical-grid">
                            <div class="medical-card card-1" data-aos="zoom-in" data-aos-delay="300">
                                <i class="bi bi-heart-pulse-fill"></i>
                                <span>Cardiología</span>
                            </div>
                            <div class="medical-card card-2" data-aos="zoom-in" data-aos-delay="400">
                                <i class="bi bi-brain"></i>
                                <span>Neurología</span>
                            </div>
                            <div class="medical-card card-3" data-aos="zoom-in" data-aos-delay="500">
                                <i class="bi bi-eye-fill"></i>
                                <span>Oftalmología</span>
                            </div>
                            <div class="medical-card card-4" data-aos="zoom-in" data-aos-delay="600">
                                <i class="bi bi-clipboard2-pulse"></i>
                                <span>Medicina General</span>
                            </div>
                            <div class="medical-card card-5" data-aos="zoom-in" data-aos-delay="700">
                                <i class="bi bi-emoji-smile"></i>
                                <span>Pediatría</span>
                            </div>
                            <div class="medical-card card-6" data-aos="zoom-in" data-aos-delay="800">
                                <i class="bi bi-gender-female"></i>
                                <span>Ginecología</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Servicios -->
        <div class="services-showcase" data-aos="fade-up" data-aos-delay="400">
            <div class="text-center mb-5">
                <div class="section-badge mx-auto">
                    <i class="bi bi-hospital me-2"></i>
                    Nuestros Servicios
                </div>
                <h2 class="section-title">
                    Atención Médica <span class="title-highlight">Completa</span>
                </h2>
                <p class="section-subtitle">
                    Ofrecemos una amplia gama de servicios médicos para cuidar tu salud y la de tu familia
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="service-card" data-aos="flip-left" data-aos-delay="500">
                        <div class="service-icon emergency">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h5 class="service-title">Urgencias</h5>
                        <p class="service-description">
                            Atención médica inmediata las 24 horas del día con personal 
                            especializado en emergencias médicas.
                        </p>
                        <div class="service-features">
                            <span class="feature-tag">24/7</span>
                            <span class="feature-tag">Ambulancia</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="service-card" data-aos="flip-left" data-aos-delay="600">
                        <div class="service-icon consultation">
                            <i class="bi bi-camera-video"></i>
                        </div>
                        <h5 class="service-title">Telemedicina</h5>
                        <p class="service-description">
                            Consultas médicas virtuales desde la comodidad de tu hogar 
                            con nuestros especialistas certificados.
                        </p>
                        <div class="service-features">
                            <span class="feature-tag">Virtual</span>
                            <span class="feature-tag">Seguro</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="service-card" data-aos="flip-left" data-aos-delay="700">
                        <div class="service-icon laboratory">
                            <i class="bi bi-clipboard2-data"></i>
                        </div>
                        <h5 class="service-title">Laboratorio</h5>
                        <p class="service-description">
                            Análisis clínicos completos con tecnología de última generación 
                            y resultados en tiempo record.
                        </p>
                        <div class="service-features">
                            <span class="feature-tag">Rápido</span>
                            <span class="feature-tag">Preciso</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="service-card" data-aos="flip-left" data-aos-delay="800">
                        <div class="service-icon surgery">
                            <i class="bi bi-scissors"></i>
                        </div>
                        <h5 class="service-title">Cirugía</h5>
                        <p class="service-description">
                            Procedimientos quirúrgicos con tecnología mínimamente invasiva 
                            y los más altos estándares de seguridad.
                        </p>
                        <div class="service-features">
                            <span class="feature-tag">Seguro</span>
                            <span class="feature-tag">Moderno</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="service-card" data-aos="flip-left" data-aos-delay="900">
                        <div class="service-icon imaging">
                            <i class="bi bi-x-diamond"></i>
                        </div>
                        <h5 class="service-title">Imagenología</h5>
                        <p class="service-description">
                            Rayos X, tomografías, resonancias magnéticas y ecografías 
                            con equipos de última generación.
                        </p>
                        <div class="service-features">
                            <span class="feature-tag">HD</span>
                            <span class="feature-tag">3D</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="service-card" data-aos="flip-left" data-aos-delay="1000">
                        <div class="service-icon pharmacy">
                            <i class="bi bi-capsule"></i>
                        </div>
                        <h5 class="service-title">Farmacia</h5>
                        <p class="service-description">
                            Medicamentos originales y genéricos con asesoría farmacéutica 
                            profesional y entregas a domicilio.
                        </p>
                        <div class="service-features">
                            <span class="feature-tag">Delivery</span>
                            <span class="feature-tag">Asesoría</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Instalaciones -->
        <div class="facilities-section" data-aos="fade-up" data-aos-delay="600">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="facilities-content">
                        <div class="section-badge">
                            <i class="bi bi-building me-2"></i>
                            Nuestras Instalaciones
                        </div>
                        <h2 class="section-title">
                            Espacios Diseñados para
                            <span class="title-highlight">tu Bienestar</span>
                        </h2>
                        <p class="section-description">
                            Contamos con instalaciones modernas y cómodas, diseñadas pensando 
                            en la experiencia del paciente y equipadas con la tecnología médica más avanzada.
                        </p>
                        
                        <div class="facilities-list">
                            <div class="facility-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>50 Consultorios especializados</span>
                            </div>
                            <div class="facility-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>8 Quirófanos de última generación</span>
                            </div>
                            <div class="facility-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Unidad de Cuidados Intensivos</span>
                            </div>
                            <div class="facility-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Laboratorio certificado ISO</span>
                            </div>
                            <div class="facility-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Cafetería y áreas de descanso</span>
                            </div>
                            <div class="facility-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Estacionamiento gratuito</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="facilities-visual">
                        <div class="facility-highlight">
                            <div class="facility-image">
                                <div class="image-placeholder">
                                    <i class="bi bi-hospital"></i>
                                    <span>Instalaciones Modernas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Contacto y Ubicación -->
        <div class="contact-section" data-aos="fade-up" data-aos-delay="800">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="contact-card">
                        <div class="contact-header">
                            <h3 class="contact-title">
                                <i class="bi bi-geo-alt-fill me-2"></i>
                                Visítanos
                            </h3>
                            <p class="contact-subtitle">Estamos ubicados en el corazón de la ciudad</p>
                        </div>
                        
                        <div class="map-container">
                            <div class="map-placeholder">
                                <i class="bi bi-map"></i>
                                <h5>Nuestra Ubicación</h5>
                                <p>Av. Principal 123, Centro Médico<br>Quito, Ecuador</p>
                                <button class="btn btn-primary">
                                    <i class="bi bi-navigation me-2"></i>
                                    Cómo llegar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="contact-info-card">
                        <h4 class="contact-info-title">
                            <i class="bi bi-telephone-fill me-2"></i>
                            Contáctanos
                        </h4>
                        
                        <div class="contact-methods">
                            <div class="contact-method">
                                <div class="method-icon emergency-contact">
                                    <i class="bi bi-phone-vibrate"></i>
                                </div>
                                <div class="method-info">
                                    <h6>Emergencias</h6>
                                    <p>911 / (02) 2-911-911</p>
                                    <span class="availability">24 horas</span>
                                </div>
                            </div>
                            
                            <div class="contact-method">
                                <div class="method-icon general-contact">
                                    <i class="bi bi-telephone"></i>
                                </div>
                                <div class="method-info">
                                    <h6>Citas y Consultas</h6>
                                    <p>(02) 2-456-789</p>
                                    <span class="availability">Lun - Dom: 6:00 - 22:00</span>
                                </div>
                            </div>
                            
                            <div class="contact-method">
                                <div class="method-icon email-contact">
                                    <i class="bi bi-envelope"></i>
                                </div>
                                <div class="method-info">
                                    <h6>Correo Electrónico</h6>
                                    <p>info@medisys.com</p>
                                    <span class="availability">Respuesta en 2 horas</span>
                                </div>
                            </div>
                            
                            <div class="contact-method">
                                <div class="method-icon whatsapp-contact">
                                    <i class="bi bi-whatsapp"></i>
                                </div>
                                <div class="method-info">
                                    <h6>WhatsApp</h6>
                                    <p>+593 99 123 4567</p>
                                    <span class="availability">Chat directo</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
$(document).ready(function() {
    // Inicializar AOS (animaciones)
    AOS.init({
        duration: 800,
        easing: 'ease-out',
        once: true,
        offset: 100
    });

    // Inicializar efectos del dashboard
    initializeClinicDashboard();
    
    // Animaciones de entrada
    setTimeout(() => {
        $('.content-loading').addClass('content-loaded');
    }, 300);
    
    // Efectos de parallax suave
    initializeParallaxEffects();
    
    // Efectos hover mejorados
    initializeHoverEffects();
});

function initializeClinicDashboard() {
    console.log('🏥 Dashboard Clínica MediSys inicializado');
    
    // Animación de contador en stats hero
    animateHeroCounters();
    
    // Efectos de typing en el tagline
    initializeTypingEffect();
}

function animateHeroCounters() {
    $('.stat-hero-number').each(function() {
        const $this = $(this);
        const finalText = $this.text();
        const isPercentage = finalText.includes('%');
        const isPlus = finalText.includes('+');
        const numericValue = parseInt(finalText.replace(/[^0-9]/g, ''));
        
        $this.text('0');
        
        $({ countNum: 0 }).animate({
            countNum: numericValue
        }, {
            duration: 2500,
            easing: 'swing',
            step: function() {
                const current = Math.floor(this.countNum);
                let displayText = current.toLocaleString();
                
                if (isPercentage) displayText += '%';
                if (isPlus && current > 0) displayText += '+';
                
                $this.text(displayText);
            },
            complete: function() {
                $this.text(finalText);
            }
        });
    });
}

function initializeTypingEffect() {
    const tagline = $('.clinic-tagline');
    const text = tagline.text();
    tagline.text('');
    
    setTimeout(() => {
        let i = 0;
        const typeInterval = setInterval(() => {
            tagline.text(text.slice(0, i));
            i++;
            if (i > text.length) {
                clearInterval(typeInterval);
            }
        }, 50);
    }, 1000);
}

function initializeParallaxEffects() {
    $(window).scroll(function() {
        const scrollTop = $(this).scrollTop();
        const windowHeight = $(this).height();
        
        // Parallax suave en el hero
        $('.hero-overlay').css({
            'transform': `translateY(${scrollTop * 0.3}px)`
        });
        
        // Fade out del hero
        const heroOpacity = Math.max(0, 1 - scrollTop / windowHeight);
        $('.hero-header-clinic').css('opacity', heroOpacity);
    });
}

function initializeHoverEffects() {
    // Efecto hover en service cards
    $('.service-card').hover(
        function() {
            $(this).find('.service-icon').addClass('animate__animated animate__pulse');
        },
        function() {
            $(this).find('.service-icon').removeClass('animate__animated animate__pulse');
        }
    );
    
    // Efecto hover en medical cards
    $('.medical-card').hover(
        function() {
            $(this).siblings().css('opacity', '0.7');
        },
        function() {
            $(this).siblings().css('opacity', '1');
        }
    );
    
    // Efecto click en contact methods
    $('.contact-method').click(function() {
        const method = $(this);
        method.addClass('animate__animated animate__heartBeat');
        
        setTimeout(() => {
            method.removeClass('animate__animated animate__heartBeat');
        }, 1000);
    });
}

// Funciones adicionales para interactividad
function showNotification(message, type = 'info') {
    const notification = $(`
        <div class="notification notification-${type}">
            <i class="bi bi-info-circle me-2"></i>
            ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.addClass('show');
    }, 100);
    
    setTimeout(() => {
        notification.removeClass('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Smooth scroll para navegación interna
$('a[href^="#"]').click(function(e) {
    e.preventDefault();
    const target = $($(this).attr('href'));
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top - 80
        }, 800);
    }
});

// Lazy loading para imágenes (si las agregas después)
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}
</script>
</body>
</html>