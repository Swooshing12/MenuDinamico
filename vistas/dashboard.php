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
            <!-- Header Hero Section -->
            <div class="hero-header" data-aos="fade-down">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title">
                            <i class="bi bi-heart-pulse me-3"></i>
                            Centro de Salud Digital
                        </h1>
                        <p class="hero-subtitle">
                            Bienvenido/a, <span class="user-name"><?php echo $_SESSION["username"]; ?></span>
                        </p>
                        <p class="hero-description">
                            Transformando la atención médica con tecnología de vanguardia
                        </p>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number" id="pacientesTotal">1,247</div>
                            <div class="stat-label">Pacientes Atendidos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="satisfaccion">98%</div>
                            <div class="stat-label">Satisfacción</div>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="medical-icon-grid">
                        <div class="floating-icon" style="--delay: 0s;">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <div class="floating-icon" style="--delay: 1s;">
                            <i class="bi bi-activity"></i>
                        </div>
                        <div class="floating-icon" style="--delay: 2s;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="floating-icon" style="--delay: 3s;">
                            <i class="bi bi-hospital"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de estadísticas métricas -->
            <div class="metrics-section" data-aos="fade-up" data-aos-delay="200">
                <div class="row g-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="metric-card" data-metric="patients">
                            <div class="metric-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-header">
                                    <h3 class="metric-number" id="pacientesHoy">127</h3>
                                    <div class="metric-trend positive">
                                        <i class="bi bi-arrow-up"></i>
                                        <span>+12%</span>
                                    </div>
                                </div>
                                <p class="metric-label">Pacientes Hoy</p>
                                <div class="metric-progress">
                                    <div class="progress-bar" style="width: 78%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="metric-card" data-metric="appointments">
                            <div class="metric-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-header">
                                    <h3 class="metric-number" id="citasPendientes">43</h3>
                                    <div class="metric-trend neutral">
                                        <i class="bi bi-dash"></i>
                                        <span>0%</span>
                                    </div>
                                </div>
                                <p class="metric-label">Citas Pendientes</p>
                                <div class="metric-progress">
                                    <div class="progress-bar" style="width: 65%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="metric-card" data-metric="doctors">
                            <div class="metric-icon">
                                <i class="bi bi-person-hearts"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-header">
                                    <h3 class="metric-number" id="medicosActivos">18</h3>
                                    <div class="metric-trend positive">
                                        <i class="bi bi-arrow-up"></i>
                                        <span>+2</span>
                                    </div>
                                </div>
                                <p class="metric-label">Médicos Activos</p>
                                <div class="metric-progress">
                                    <div class="progress-bar" style="width: 90%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="metric-card" data-metric="revenue">
                            <div class="metric-icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-header">
                                    <h3 class="metric-number" id="ingresosHoy">$5,280</h3>
                                    <div class="metric-trend positive">
                                        <i class="bi bi-arrow-up"></i>
                                        <span>+8%</span>
                                    </div>
                                </div>
                                <p class="metric-label">Ingresos Hoy</p>
                                <div class="metric-progress">
                                    <div class="progress-bar" style="width: 85%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de contenido principal -->
            <div class="main-content-section" data-aos="fade-up" data-aos-delay="400">
                <div class="row g-4">
                    <!-- Panel de actividad reciente -->
                    <div class="col-lg-8">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-activity me-2"></i>
                                    Actividad en Tiempo Real
                                </h5>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="activity-chart-container">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de citas próximas -->
                    <div class="col-lg-4">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Próximas Citas
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="appointments-list">
                                    <div class="appointment-item">
                                        <div class="appointment-time">09:30</div>
                                        <div class="appointment-details">
                                            <h6 class="patient-name">María González</h6>
                                            <p class="doctor-name">Dr. Carlos Mendez</p>
                                            <span class="specialty-tag">Cardiología</span>
                                        </div>
                                        <div class="appointment-status urgent">
                                            <i class="bi bi-exclamation-circle"></i>
                                        </div>
                                    </div>

                                    <div class="appointment-item">
                                        <div class="appointment-time">10:00</div>
                                        <div class="appointment-details">
                                            <h6 class="patient-name">José Rodríguez</h6>
                                            <p class="doctor-name">Dra. Ana Torres</p>
                                            <span class="specialty-tag">Neurología</span>
                                        </div>
                                        <div class="appointment-status confirmed">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                    </div>

                                    <div class="appointment-item">
                                        <div class="appointment-time">10:30</div>
                                        <div class="appointment-details">
                                            <h6 class="patient-name">Laura Pérez</h6>
                                            <p class="doctor-name">Dr. Miguel Ruiz</p>
                                            <span class="specialty-tag">Pediatría</span>
                                        </div>
                                        <div class="appointment-status pending">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                    </div>

                                    <div class="appointment-item">
                                        <div class="appointment-time">11:00</div>
                                        <div class="appointment-details">
                                            <h6 class="patient-name">Carmen Silva</h6>
                                            <p class="doctor-name">Dra. Patricia Vega</p>
                                            <span class="specialty-tag">Ginecología</span>
                                        </div>
                                        <div class="appointment-status confirmed">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de especialidades y servicios -->
            <div class="services-section" data-aos="fade-up" data-aos-delay="600">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-hospital me-2"></i>
                                    Especialidades Médicas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="specialties-grid">
                                    <div class="specialty-item">
                                        <div class="specialty-icon cardiology">
                                            <i class="bi bi-heart-pulse"></i>
                                        </div>
                                        <div class="specialty-info">
                                            <h6>Cardiología</h6>
                                            <p>3 doctores • 24 pacientes hoy</p>
                                        </div>
                                    </div>

                                    <div class="specialty-item">
                                        <div class="specialty-icon neurology">
                                            <i class="bi bi-brain"></i>
                                        </div>
                                        <div class="specialty-info">
                                            <h6>Neurología</h6>
                                            <p>2 doctores • 18 pacientes hoy</p>
                                        </div>
                                    </div>

                                    <div class="specialty-item">
                                        <div class="specialty-icon pediatrics">
                                            <i class="bi bi-emoji-smile"></i>
                                        </div>
                                        <div class="specialty-info">
                                            <h6>Pediatría</h6>
                                            <p>4 doctores • 32 pacientes hoy</p>
                                        </div>
                                    </div>

                                    <div class="specialty-item">
                                        <div class="specialty-icon general">
                                            <i class="bi bi-clipboard2-pulse"></i>
                                        </div>
                                        <div class="specialty-info">
                                            <h6>Medicina General</h6>
                                            <p>6 doctores • 45 pacientes hoy</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-shield-plus me-2"></i>
                                    Estado del Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="system-status">
                                    <div class="status-item">
                                        <div class="status-indicator online"></div>
                                        <span>Sistema Principal</span>
                                        <div class="status-value">Operativo</div>
                                    </div>
                                    
                                    <div class="status-item">
                                        <div class="status-indicator online"></div>
                                        <span>Base de Datos</span>
                                        <div class="status-value">Estable</div>
                                    </div>
                                    
                                    <div class="status-item">
                                        <div class="status-indicator warning"></div>
                                        <span>Respaldos</span>
                                        <div class="status-value">En Proceso</div>
                                    </div>
                                    
                                    <div class="status-item">
                                        <div class="status-indicator online"></div>
                                        <span>Red</span>
                                        <div class="status-value">Excelente</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos rápidos -->
            <div class="quick-actions-section" data-aos="fade-up" data-aos-delay="800">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-lightning me-2"></i>
                            Accesos Rápidos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="#" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="bi bi-person-plus"></i>
                                </div>
                                <span>Nuevo Paciente</span>
                            </a>

                            <a href="#" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="bi bi-calendar-plus"></i>
                                </div>
                                <span>Agendar Cita</span>
                            </a>

                            <a href="#" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="bi bi-file-medical"></i>
                                </div>
                                <span>Historial Médico</span>
                            </a>

                            <a href="#" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="bi bi-prescription2"></i>
                                </div>
                                <span>Recetas</span>
                            </a>

                            <a href="#" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <span>Reportes</span>
                            </a>

                            <a href="#" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <span>Configuración</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Script personalizado -->
    <script>
    $(document).ready(function() {
        // Inicializar AOS (animaciones)
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true
        });

        // Inicializar dashboard
        initializeDashboard();
        
        // Animaciones de contadores
        animateCounters();
        
        // Gráfico de actividad
        initializeActivityChart();
        
        // Actualizar cada 30 segundos
        setInterval(updateRealTimeData, 30000);
    });
    
    function initializeDashboard() {
        console.log('🏥 Dashboard médico inicializado');
        
        // Simular datos en tiempo real
        updateMetrics();
    }
    
    function animateCounters() {
        $('.metric-number').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.text().replace(/[^0-9]/g, ''));
            
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    const num = Math.floor(this.countNum);
                    if ($this.text().includes('$')) {
                        $this.text('$' + num.toLocaleString());
                    } else {
                        $this.text(num.toLocaleString());
                    }
                },
                complete: function() {
                    if ($this.text().includes('$')) {
                        $this.text('$' + countTo.toLocaleString());
                    } else {
                        $this.text(countTo.toLocaleString());
                    }
                }
            });
        });
    }
    
    function initializeActivityChart() {
        const ctx = document.getElementById('activityChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['6:00', '8:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
                datasets: [{
                    label: 'Pacientes por Hora',
                    data: [5, 12, 28, 35, 42, 38, 25],
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2e7d32',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                },
                elements: {
                    point: {
                        hoverRadius: 8
                    }
                }
            }
        });
    }
    
    function updateMetrics() {
        // Simular actualización de métricas
        const metrics = {
            patients: Math.floor(Math.random() * 50) + 100,
            appointments: Math.floor(Math.random() * 20) + 30,
            doctors: 18,
            revenue: Math.floor(Math.random() * 2000) + 4000
        };
        
        // Actualizar valores (opcional)
        // $('#pacientesHoy').text(metrics.patients);
        // $('#citasPendientes').text(metrics.appointments);
        // $('#ingresosHoy').text('$' + metrics.revenue.toLocaleString());
    }
    
    function updateRealTimeData() {
        // Actualizar datos en tiempo real
        console.log('📊 Actualizando datos en tiempo real...');
        updateMetrics();
    }
    
    // Efectos hover para las tarjetas métricas
    $('.metric-card').hover(
        function() {
            $(this).addClass('hovered');
        },
        function() {
            $(this).removeClass('hovered');
        }
    );
    </script>
</body>
</html>