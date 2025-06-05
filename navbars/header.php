<?php 
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id_rol'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$nombre_usuario = $_SESSION['username'];
$id_rol = $_SESSION['id_rol'];
$nombre_rol = $_SESSION['nombre_rol'] ?? 'Usuario';
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/estilos/header.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- Bootstrap JS (Popper.js incluido) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- Navbar principal -->
<header class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <!-- Logo y Título -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/vistas/dashboard.php">
            <div class="logo-container">
                <i class="bi bi-heart-pulse-fill logo-icon"></i>
            </div>
            <span class="brand-text">MediSys</span>
        </a>

        <!-- Botón para sidebar móvil -->
        <button class="btn btn-link d-lg-none text-white" type="button" id="sidebarToggleMobile">
            <i class="bi bi-list fs-4"></i>
        </button>
        
        <!-- Navbar derecho con información del usuario -->
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
            <!-- Fecha y Hora -->
            <div class="nav-item me-3 d-none d-md-block">
                <div class="nav-link time-display">
                    <i class="bi bi-clock me-1"></i>
                    <span id="headerTime">00:00:00</span>
                </div>
            </div>
            
            <!-- Ubicación con Bandera -->
            <div class="nav-item me-3 d-none d-md-block">
                <div class="nav-link location-display">
                    <i class="bi bi-geo-alt me-1"></i>
                    <span id="headerLocation">Cargando...</span>
                    <img id="headerFlag" src="" alt="" class="ms-1 location-flag" style="display: none;">
                </div>
            </div>
            
            <!-- Notificaciones -->
            <div class="nav-item dropdown me-2">
                <a class="nav-link position-relative notification-icon" href="#" id="notificationsDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill"></i>
                    <span class="notification-badge">3</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown">
                    <li><h6 class="dropdown-header">Notificaciones</h6></li>
                    <li>
                        <a class="dropdown-item notification-item" href="#">
                            <div class="notification-icon-wrapper success-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Nueva cita agendada</div>
                                <div class="notification-time">Hace 10 minutos</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item notification-item" href="#">
                            <div class="notification-icon-wrapper warning-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Nuevo mensaje</div>
                                <div class="notification-time">Hace 30 minutos</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item notification-item" href="#">
                            <div class="notification-icon-wrapper info-icon">
                                <i class="bi bi-clipboard2-pulse"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Resultados disponibles</div>
                                <div class="notification-time">Hace 2 horas</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-center view-all" href="#">
                            Ver todas <i class="bi bi-arrow-right-short"></i>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Usuario y Rol -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($nombre_usuario, 0, 1)); ?>
                    </div>
                    <div class="d-none d-lg-block ms-2">
                        <div class="user-name"><?= htmlspecialchars($nombre_usuario); ?></div>
                        <div class="user-role"><?= htmlspecialchars($nombre_rol); ?></div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person-circle me-2"></i> Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-gear me-2"></i> Configuración
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item logout-item" href="<?= BASE_URL ?>/controladores/LoginControlador/LoginController.php?logout=true">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Script para funcionalidades dinámicas del header -->
<script>
$(document).ready(function() {
    // Actualizar fecha y hora en tiempo real
    function updateHeaderTime() {
        const now = new Date();
        
        // Formato 24 horas con segundos
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        
        // Actualizar en la página
        $('#headerTime').text(timeString);
    }
    
    // Iniciar reloj y actualizar cada segundo
    updateHeaderTime();
    setInterval(updateHeaderTime, 1000);
    
    // Usar una API que permite CORS desde localhost
    fetch('https://ipwho.is/')
        .then(response => response.json())
        .then(data => {
            const countryName = data.country;
            const city = data.city;
            const countryCode = data.country_code.toLowerCase();
            const flagUrl = `https://flagcdn.com/16x12/${countryCode}.png`;
            
            $('#headerLocation').text(`${city}, ${countryName}`);
            $('#headerFlag')
                .attr('src', flagUrl)
                .attr('alt', countryName)
                .css('display', 'inline');
        })
        .catch(error => {
            console.error("Error al obtener la ubicación:", error);
            $('#headerLocation').text("Ubicación no disponible");
        });
    
    // Resto del código...
});
</script>
<script src="<?= BASE_URL ?>/js/bloquear.js"></script>
