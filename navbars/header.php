<?php 
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id_rol'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$nombre_usuario = $_SESSION['username'];
$id_rol = $_SESSION['id_rol'];
$nombre_rol = $_SESSION['nombre_rol'] ?? 'Usuario';
$id_usuario = $_SESSION['id_usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Sistema de Gestión Hospitalaria</title>
    
    <!-- Enlaces a CSS y scripts necesarios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/estilos/header.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- Navbar principal -->
<header class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <!-- Logo y Título con efectos -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/vistas/dashboard.php">
            <div class="logo-container">
                <i class="bi bi-heart-pulse-fill logo-icon"></i>
                <div class="logo-pulse"></div>
            </div>
            <div class="brand-content">
                <span class="brand-text">MediSys</span>
                <span class="brand-subtitle">Sistema Hospitalario</span>
            </div>
        </a>

        <!-- Barra de búsqueda rápida -->
        <div class="quick-search-container d-none d-lg-flex">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control search-input" placeholder="Buscar pacientes, citas, doctores..." id="globalSearch">
                <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
            <button class="btn search-btn" type="button" id="searchBtn">
                <i class="bi bi-search"></i>
            </button>
        </div>

        <!-- Botón para sidebar móvil -->
        <button class="btn btn-link d-lg-none text-white sidebar-toggle" type="button" id="sidebarToggleMobile">
            <i class="bi bi-list fs-4"></i>
        </button>
        
        <!-- Navbar derecho con información del usuario -->
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
            <!-- Accesos rápidos -->
            <div class="quick-actions d-none d-xl-flex me-3">
                <button class="btn quick-action-btn" title="Nueva Cita" data-bs-toggle="tooltip" onclick="nuevaCita()">
                    <i class="bi bi-calendar-plus"></i>
                </button>
                <button class="btn quick-action-btn" title="Nuevo Paciente" data-bs-toggle="tooltip" onclick="nuevoPaciente()">
                    <i class="bi bi-person-plus"></i>
                </button>
                <button class="btn quick-action-btn" title="Reportes" data-bs-toggle="tooltip" onclick="verReportes()">
                    <i class="bi bi-graph-up"></i>
                </button>
            </div>

            <!-- Fecha completa -->
            <div class="nav-item me-3 d-none d-md-block">
                <div class="nav-link date-display">
                    <div class="date-content">
                        <i class="bi bi-calendar-date date-icon"></i>
                        <div class="date-info">
                            <span id="currentDate" class="date-text">Cargando...</span>
                            <small id="dayOfWeek" class="day-week">Domingo</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hora con zona horaria -->
            <div class="nav-item me-3 d-none d-md-block">
                <div class="nav-link time-display">
                    <div class="time-content">
                        <i class="bi bi-clock-history time-icon"></i>
                        <div class="time-info">
                            <span id="headerTime" class="time-text">00:00:00</span>
                            <small id="timeZone" class="time-zone">GMT-5</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ubicación con Clima -->
            <div class="nav-item me-3 d-none d-lg-block">
                <div class="nav-link location-display">
                    <div class="location-content">
                        <i class="bi bi-geo-alt location-icon"></i>
                        <div class="location-info">
                            <div class="location-main">
                                <span id="headerLocation" class="location-text">Detectando ubicación...</span>
                                <img id="headerFlag" src="" alt="" class="location-flag" style="display: none;">
                            </div>
                            <div class="weather-info" id="weatherInfo" style="display: none;">
                                <i id="weatherIcon" class="weather-icon"></i>
                                <span id="temperature" class="temperature">--°C</span>
                                <small id="weatherDesc" class="weather-desc">Cargando...</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métricas rápidas dinámicas -->
            <div class="nav-item dropdown me-2 d-none d-lg-block">
                <a class="nav-link metrics-display" href="#" id="metricsDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-speedometer2 metrics-icon"></i>
                    <div class="metrics-content">
                        <span class="metrics-title">Dashboard</span>
                        <span class="metrics-value" id="todayCitas">Cargando...</span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end metrics-dropdown">
                    <li><h6 class="dropdown-header">📊 Métricas en Tiempo Real</h6></li>
                    <li class="metric-item">
                        <div class="metric-icon success-bg">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Citas Completadas</span>
                            <span class="metric-number" id="citasCompletadas">--</span>
                        </div>
                    </li>
                    <li class="metric-item">
                        <div class="metric-icon warning-bg">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Citas Pendientes</span>
                            <span class="metric-number" id="citasPendientes">--</span>
                        </div>
                    </li>
                    <li class="metric-item">
                        <div class="metric-icon info-bg">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Pacientes Hoy</span>
                            <span class="metric-number" id="pacientesHoy">--</span>
                        </div>
                    </li>
                    <li class="metric-item">
                        <div class="metric-icon primary-bg">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Sistema</span>
                            <span class="metric-status" id="sistemaStatus">
                                <i class="bi bi-circle-fill text-success"></i> Operativo
                            </span>
                        </div>
                    </li>
                </ul>
            </div>
            
            <!-- Notificaciones dinámicas -->
            <div class="nav-item dropdown me-2">
                <a class="nav-link position-relative notification-container" href="#" id="notificationsDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill notification-icon"></i>
                    <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                    <div class="notification-pulse"></div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <li>
                        <div class="notification-header">
                            <h6 class="notification-title">
                                <i class="bi bi-bell me-2"></i>Notificaciones
                            </h6>
                            <button class="btn btn-sm mark-all-read" onclick="marcarTodasLeidas()">
                                <i class="bi bi-check2-all"></i>
                            </button>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <div id="notificationsList">
                        <li class="no-notifications">
                            <div class="text-center py-4">
                                <i class="bi bi-bell-slash display-6 text-muted"></i>
                                <p class="text-muted mt-2 mb-0">No hay notificaciones</p>
                            </div>
                        </li>
                    </div>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-center view-all" href="#" onclick="verTodasNotificaciones()">
                            <i class="bi bi-arrow-right-circle me-1"></i>
                            Ver todas las notificaciones
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Usuario y Rol mejorado -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <span class="avatar-text"><?= strtoupper(substr($nombre_usuario, 0, 1)); ?></span>
                        <div class="status-indicator online"></div>
                    </div>
                    <div class="d-none d-lg-block ms-2 user-info">
                        <div class="user-name"><?= htmlspecialchars($nombre_usuario); ?></div>
                        <div class="user-role">
                            <i class="bi bi-shield-check me-1"></i>
                            <?= htmlspecialchars($nombre_rol); ?>
                        </div>
                    </div>
                    <i class="bi bi-chevron-down dropdown-arrow ms-1"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu">
                    <li>
                        <div class="user-dropdown-header">
                            <div class="user-avatar-large">
                                <?= strtoupper(substr($nombre_usuario, 0, 1)); ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name-large"><?= htmlspecialchars($nombre_usuario); ?></div>
                                <div class="user-email">ID: <?= $id_usuario ?></div>
                                <div class="user-role-badge">
                                    <i class="bi bi-shield-check me-1"></i>
                                    <?= htmlspecialchars($nombre_rol); ?>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="verPerfil()">
                            <div class="menu-icon">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="menu-content">
                                <span class="menu-title">Mi Perfil</span>
                                <small class="menu-subtitle">Configurar información personal</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="abrirConfiguracion()">
                            <div class="menu-icon">
                                <i class="bi bi-gear"></i>
                            </div>
                            <div class="menu-content">
                                <span class="menu-title">Configuración</span>
                                <small class="menu-subtitle">Preferencias del sistema</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="toggleModoOscuro()">
                            <div class="menu-icon">
                                <i class="bi bi-moon" id="themeIcon"></i>
                            </div>
                            <div class="menu-content">
                                <span class="menu-title">Modo Oscuro</span>
                                <small class="menu-subtitle">Cambiar tema de la interfaz</small>
                            </div>
                            <div class="menu-toggle">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="darkModeToggle">
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="configurarNotificaciones()">
                            <div class="menu-icon">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div class="menu-content">
                                <span class="menu-title">Notificaciones</span>
                                <small class="menu-subtitle">Gestionar alertas</small>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="abrirAyuda()">
                            <div class="menu-icon">
                                <i class="bi bi-question-circle"></i>
                            </div>
                            <div class="menu-content">
                                <span class="menu-title">Ayuda</span>
                                <small class="menu-subtitle">Centro de soporte</small>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item logout-item" href="<?= BASE_URL ?>/controladores/LoginControlador/LoginController.php?logout=true">
                            <div class="menu-icon">
                                <i class="bi bi-box-arrow-right"></i>
                            </div>
                            <div class="menu-content">
                                <span class="menu-title">Cerrar Sesión</span>
                                <small class="menu-subtitle">Salir del sistema</small>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Scripts dinámicos avanzados -->
<script>
$(document).ready(function() {
    // Configuración global
    const config = {
        usuario_id: <?= $id_usuario ?>,
        rol_id: <?= $id_rol ?>,
        base_url: '<?= BASE_URL ?>',
        api_weather: '9c7b98b67c3c4a5c8c4f2a5e8b1d2a3f', // API key ejemplo
        update_intervals: {
            time: 1000,
            weather: 300000, // 5 minutos
            notifications: 30000, // 30 segundos
            metrics: 60000 // 1 minuto
        }
    };

    // Inicializar tooltips
    initializeTooltips();
    
    // Inicializar funciones principales
    initializeDateTime();
    initializeLocationAndWeather();
    initializeSearch();
    initializeNotifications();
    initializeMetrics();
    initializeDarkMode();
    
    // ===== FECHA Y HORA DINÁMICA =====
    function initializeDateTime() {
        function updateDateTime() {
            const now = new Date();
            
            // Actualizar hora
            const timeString = now.toLocaleTimeString('es-ES', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const $timeElement = $('#headerTime');
            if ($timeElement.text() !== timeString) {
                $timeElement.addClass('time-update');
                setTimeout(() => {
                    $timeElement.text(timeString).removeClass('time-update');
                }, 150);
            }

            // Actualizar fecha
            const dateString = now.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
            const dayString = now.toLocaleDateString('es-ES', {
                weekday: 'long'
            });

            $('#currentDate').text(dateString);
            $('#dayOfWeek').text(dayString.charAt(0).toUpperCase() + dayString.slice(1));
            
            // Actualizar zona horaria
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const offset = -now.getTimezoneOffset() / 60;
            $('#timeZone').text(`GMT${offset >= 0 ? '+' : ''}${offset}`);
        }
        
        updateDateTime();
        setInterval(updateDateTime, config.update_intervals.time);
    }
    
    // ===== UBICACIÓN Y CLIMA DINÁMICO =====
    function initializeLocationAndWeather() {
        $('#headerLocation').html('<i class="spinner-border spinner-border-sm me-1"></i>Detectando...');
        
        // Obtener ubicación
        fetch('https://ipapi.co/json/')
            .then(response => response.json())
            .then(data => {
                const city = data.city;
                const country = data.country_name;
                const countryCode = data.country_code.toLowerCase();
                const flagUrl = `https://flagcdn.com/16x12/${countryCode}.png`;
                const lat = data.latitude;
                const lon = data.longitude;
                
                // Actualizar ubicación
                $('#headerLocation').removeClass('location-loading').text(`${city}, ${country}`);
                $('#headerFlag')
                    .attr('src', flagUrl)
                    .attr('alt', country)
                    .css('display', 'inline')
                    .addClass('flag-appear');
                
                // Obtener clima
                getWeatherData(lat, lon, city);
            })
            .catch(error => {
                console.error("Error obteniendo ubicación:", error);
                $('#headerLocation').html('<i class="bi bi-exclamation-triangle text-warning"></i> Ubicación no disponible');
            });
    }
    
    function getWeatherData(lat, lon, city) {
        // Usar OpenWeatherMap API (necesitas registrarte para obtener una API key gratuita)
        const apiKey = 'TU_API_KEY_AQUI'; // Reemplazar con tu API key
        const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${apiKey}&units=metric&lang=es`;
        
        // Para demo, usar datos simulados
        simulateWeatherData();
        
        /* Código real para cuando tengas API key:
        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateWeatherDisplay(data);
            })
            .catch(error => {
                console.error("Error obteniendo clima:", error);
                simulateWeatherData();
            });
        */
    }
    
    function simulateWeatherData() {
        // Simulación de datos del clima
        const weatherConditions = ['soleado', 'nublado', 'lluvia', 'parcialmente nublado'];
        const condition = weatherConditions[Math.floor(Math.random() * weatherConditions.length)];
        const temp = Math.floor(Math.random() * 15) + 15; // 15-30°C
        
        const weatherIcons = {
            'soleado': 'bi-sun-fill',
            'nublado': 'bi-clouds-fill',
            'lluvia': 'bi-cloud-rain-fill',
            'parcialmente nublado': 'bi-cloud-sun-fill'
        };
        
        setTimeout(() => {
            $('#weatherIcon')
                .removeClass()
                .addClass(`bi ${weatherIcons[condition]} weather-icon`);
            $('#temperature').text(`${temp}°C`);
            $('#weatherDesc').text(condition.charAt(0).toUpperCase() + condition.slice(1));
            $('#weatherInfo').fadeIn();
        }, 1000);
    }
    
    // ===== BÚSQUEDA GLOBAL DINÁMICA =====
    function initializeSearch() {
        let searchTimeout;
        $('#globalSearch').on('input', function() {
            const query = $(this).val().trim();
            clearTimeout(searchTimeout);
            
            if (query.length >= 2) {
                $('#searchSuggestions').addClass('show loading');
                searchTimeout = setTimeout(() => {
                    performGlobalSearch(query);
                }, 300);
            } else {
                $('#searchSuggestions').removeClass('show');
            }
        });

        // Cerrar sugerencias al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-wrapper').length) {
                $('#searchSuggestions').removeClass('show');
            }
        });
    }
    
    function performGlobalSearch(query) {
        // Simulación de búsqueda - En producción, hacer llamada AJAX real
        setTimeout(() => {
            const mockResults = [
                { type: 'paciente', name: 'Juan Pérez García', id: '001', icon: 'bi-person', extra: 'Cédula: 1234567890' },
                { type: 'doctor', name: 'Dr. María González', id: '002', icon: 'bi-person-badge', extra: 'Cardiología' },
                { type: 'cita', name: 'Cita 15:30', id: '003', icon: 'bi-calendar', extra: 'Hoy - Consultorio 3' },
                { type: 'paciente', name: 'Ana María López', id: '004', icon: 'bi-person', extra: 'Cédula: 0987654321' }
            ];

            const filteredResults = mockResults.filter(item => 
                item.name.toLowerCase().includes(query.toLowerCase()) ||
                item.extra.toLowerCase().includes(query.toLowerCase())
            );

            let html = '';
            if (filteredResults.length > 0) {
                filteredResults.forEach(item => {
                    html += `
                        <div class="search-suggestion-item" onclick="selectSearchResult('${item.type}', '${item.id}')">
                            <i class="bi ${item.icon} suggestion-icon ${item.type}-icon"></i>
                            <div class="suggestion-content">
                                <div class="suggestion-name">${item.name}</div>
                                <div class="suggestion-type">${item.extra}</div>
                            </div>
                            <i class="bi bi-arrow-right suggestion-arrow"></i>
                        </div>
                    `;
                });
            } else {
                html = `
                    <div class="no-results">
                        <i class="bi bi-search"></i>
                        <span>No se encontraron resultados para "${query}"</span>
                    </div>
                `;
            }

            $('#searchSuggestions').removeClass('loading').html(html);
        }, 500);
    }
    
    // ===== NOTIFICACIONES DINÁMICAS =====
    function initializeNotifications() {
        loadNotifications();
        setInterval(loadNotifications, config.update_intervals.notifications);
    }
    
    function loadNotifications() {
        // Simulación de carga de notificaciones desde el servidor
        const mockNotifications = [
            {
                id: 1,
                type: 'cita_confirmada',
                title: 'Cita confirmada',
                message: 'Juan Pérez confirmó su cita para las 14:30',
                time: new Date(Date.now() - 5 * 60000), // 5 minutos atrás
                read: false,
                icon: 'bi-calendar-check',
                color: 'success'
            },
            {
                id: 2,
                type: 'nuevo_paciente',
                title: 'Nuevo paciente registrado',
                message: 'María García se registró en el sistema',
                time: new Date(Date.now() - 15 * 60000), // 15 minutos atrás
                read: false,
                icon: 'bi-person-plus',
                color: 'info'
            },
            {
                id: 3,
                type: 'recordatorio',
                title: 'Recordatorio',
                message: 'Tienes 3 citas programadas para mañana',
                time: new Date(Date.now() - 60 * 60000), // 1 hora atrás
                read: true,
                icon: 'bi-bell',
                color: 'warning'
            }
        ];
        
        displayNotifications(mockNotifications);
    }
    
    function displayNotifications(notifications) {
        const unreadCount = notifications.filter(n => !n.read).length;
        
        // Actualizar contador
        if (unreadCount > 0) {
            $('#notificationCount').text(unreadCount).show();
        } else {
            $('#notificationCount').hide();
        }
        
        // Generar HTML de notificaciones
        let html = '';
        if (notifications.length > 0) {
            notifications.forEach(notification => {
                const timeAgo = getTimeAgo(notification.time);
                html += `
                    <li>
                        <a class="dropdown-item notification-item ${!notification.read ? 'unread' : ''}" 
                           href="#" onclick="markAsRead(${notification.id})">
                            <div class="notification-icon-wrapper ${notification.color}-icon">
                                <i class="bi ${notification.icon}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-text">${notification.message}</div>
                                <div class="notification-time">
                                    <i class="bi bi-clock"></i>
                                    <span>${timeAgo}</span>
                                </div>
                            </div>
                            ${!notification.read ? '<div class="unread-indicator"></div>' : ''}
                        </a>
                    </li>
                `;
            });
        } else {
            html = `
                <li class="no-notifications">
                    <div class="text-center py-4">
                        <i class="bi bi-bell-slash display-6 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No hay notificaciones</p>
                    </div>
                </li>
            `;
        }
        
        $('#notificationsList').html(html);
    }
    
    // ===== MÉTRICAS DINÁMICAS =====
    function initializeMetrics() {
        loadMetrics();
        setInterval(loadMetrics, config.update_intervals.metrics);
    }
    
    function loadMetrics() {
        // Simulación de métricas dinámicas
        const metrics = {
            citasCompletadas: Math.floor(Math.random() * 5) + 8,
            citasPendientes: Math.floor(Math.random() * 3) + 3,
            pacientesHoy: Math.floor(Math.random() * 4) + 12,
            sistemaStatus: Math.random() > 0.1 ? 'operativo' : 'mantenimiento'
        };
        
        // Actualizar métricas
        $('#citasCompletadas').text(metrics.citasCompletadas);
        $('#citasPendientes').text(metrics.citasPendientes);
        $('#pacientesHoy').text(metrics.pacientesHoy);
        $('#todayCitas').text(`${metrics.citasCompletadas + metrics.citasPendientes} citas hoy`);
        
        // Actualizar estado del sistema
        const statusElement = $('#sistemaStatus');
        if (metrics.sistemaStatus === 'operativo') {
            statusElement.html('<i class="bi bi-circle-fill text-success"></i> Operativo');
        } else {
            statusElement.html('<i class="bi bi-circle-fill text-warning"></i> Mantenimiento');
        }
    }
    
    // ===== MODO OSCURO =====
    function initializeDarkMode() {
        // Recuperar preferencia guardada
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode === 'enabled') {
            $('body').addClass('dark-mode');
            $('#darkModeToggle').prop('checked', true);
            $('#themeIcon').removeClass('bi-moon').addClass('bi-sun');
        }
    }
    
    // ===== FUNCIONES AUXILIARES =====
    function initializeTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
function getTimeAgo(date) {
       const now = new Date();
       const diffInMinutes = Math.floor((now - date) / (1000 * 60));
       
       if (diffInMinutes < 1) return 'Ahora mismo';
       if (diffInMinutes < 60) return `Hace ${diffInMinutes} minuto${diffInMinutes !== 1 ? 's' : ''}`;
       
       const diffInHours = Math.floor(diffInMinutes / 60);
       if (diffInHours < 24) return `Hace ${diffInHours} hora${diffInHours !== 1 ? 's' : ''}`;
       
       const diffInDays = Math.floor(diffInHours / 24);
       return `Hace ${diffInDays} día${diffInDays !== 1 ? 's' : ''}`;
   }
   
   function showToast(message, type = 'info', duration = 5000) {
       const toastId = 'toast-' + Date.now();
       const iconClass = {
           'success': 'bi-check-circle-fill',
           'error': 'bi-x-circle-fill',
           'warning': 'bi-exclamation-triangle-fill',
           'info': 'bi-info-circle-fill'
       }[type] || 'bi-info-circle-fill';
       
       const toast = $(`
           <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
               <div class="d-flex">
                   <div class="toast-body">
                       <i class="bi ${iconClass} me-2"></i>${message}
                   </div>
                   <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
               </div>
           </div>
       `);
       
       if (!$('.toast-container').length) {
           $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
       }
       
       $('.toast-container').append(toast);
       const bsToast = new bootstrap.Toast(toast[0], { delay: duration });
       bsToast.show();
       
       toast.on('hidden.bs.toast', function() {
           $(this).remove();
       });
   }
   
   console.log('🏥 MediSys Header Dinámico cargado exitosamente');
});

// ===== FUNCIONES GLOBALES =====

// Funciones de acciones rápidas
function nuevaCita() {
   showToast('Abriendo formulario de nueva cita...', 'info');
   // Aquí implementar la lógica para abrir modal de nueva cita
}

function nuevoPaciente() {
   showToast('Abriendo formulario de nuevo paciente...', 'info');
   // Aquí implementar la lógica para abrir modal de nuevo paciente
}

function verReportes() {
   showToast('Cargando módulo de reportes...', 'info');
   // Aquí implementar la lógica para ir a reportes
}

// Funciones de búsqueda
function selectSearchResult(type, id) {
   $('#searchSuggestions').removeClass('show');
   $('#globalSearch').val('');
   showToast(`Abriendo ${type} con ID: ${id}`, 'success');
   // Aquí implementar la navegación según el tipo
}

// Funciones de notificaciones
function markAsRead(notificationId) {
   $(`.notification-item[onclick*="${notificationId}"]`).removeClass('unread');
   const currentCount = parseInt($('#notificationCount').text()) || 0;
   if (currentCount > 1) {
       $('#notificationCount').text(currentCount - 1);
   } else {
       $('#notificationCount').hide();
   }
   showToast('Notificación marcada como leída', 'success', 2000);
}

function marcarTodasLeidas() {
   $('.notification-item.unread').removeClass('unread');
   $('#notificationCount').hide();
   showToast('Todas las notificaciones marcadas como leídas', 'success');
}

function verTodasNotificaciones() {
   showToast('Abriendo centro de notificaciones...', 'info');
   // Aquí implementar navegación al centro de notificaciones
}

// Funciones de usuario
function verPerfil() {
   showToast('Abriendo perfil de usuario...', 'info');
   // Aquí implementar navegación al perfil
}

function abrirConfiguracion() {
   showToast('Abriendo configuración del sistema...', 'info');
   // Aquí implementar navegación a configuración
}

function toggleModoOscuro() {
   const isDark = $('body').hasClass('dark-mode');
   const $toggle = $('#darkModeToggle');
   const $icon = $('#themeIcon');
   
   if (isDark) {
       $('body').removeClass('dark-mode');
       $toggle.prop('checked', false);
       $icon.removeClass('bi-sun').addClass('bi-moon');
       localStorage.setItem('darkMode', 'disabled');
       showToast('Modo claro activado', 'info', 2000);
   } else {
       $('body').addClass('dark-mode');
       $toggle.prop('checked', true);
       $icon.removeClass('bi-moon').addClass('bi-sun');
       localStorage.setItem('darkMode', 'enabled');
       showToast('Modo oscuro activado', 'info', 2000);
   }
}

function configurarNotificaciones() {
   showToast('Abriendo configuración de notificaciones...', 'info');
   // Aquí implementar configuración de notificaciones
}

function abrirAyuda() {
   showToast('Abriendo centro de ayuda...', 'info');
   // Aquí implementar centro de ayuda
}

// Event listeners adicionales
$(document).on('change', '#darkModeToggle', function() {
   toggleModoOscuro();
});
</script>

<script src="<?= BASE_URL ?>/js/bloquear.js"></script>
</body>
</html>