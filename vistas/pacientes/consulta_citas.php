<?php
require_once "../../helpers/permisos.php"; // Protección de sesión

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial de Citas | MediSys</title>
    
    <!-- Bootstrap CSS -->
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../../estilos/pacientes/consulta_cita.css">
    
    
</head>
<body>
    <!-- Incluir header y sidebar -->
    <?php include "../../navbars/header.php"; ?>
    <?php include "../../navbars/sidebar.php"; ?>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingIndicator">
        <div class="text-center text-white">
            <div class="spinner-border mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h5>Cargando datos...</h5>
        </div>
    </div>

    <!-- Contenido principal -->
    <main class="main-container">
        <!-- Header de la página -->
        <div class="page-header" data-aos="fade-down">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-calendar-heart me-3"></i>
                        Mi Historial de Citas
                    </h1>
                    <p class="mb-0 opacity-90">
                        Consulta y revisa el historial completo de tus citas médicas
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-light text-primary fs-6">
                            <i class="bi bi-person-check me-1"></i>
                            Paciente: <?php echo $_SESSION["username"]; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="header-actions">
                        <button class="btn btn-light btn-lg" id="btnRefresh" data-bs-toggle="tooltip" title="Actualizar datos">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-icon mb-3">
                        <i class="bi bi-calendar-check text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h3 class="stats-number text-success mb-1" id="totalCitas">0</h3>
                    <p class="stats-label text-muted mb-0">Total de Citas</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-icon mb-3">
                        <i class="bi bi-check-circle text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h3 class="stats-number text-primary mb-1" id="citasCompletadas">0</h3>
                    <p class="stats-label text-muted mb-0">Completadas</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-primary" id="progressCompletadas" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-icon mb-3">
                        <i class="bi bi-clock text-warning" style="font-size: 2.5rem;"></i>
                    </div>
                    <h3 class="stats-number text-warning mb-1" id="citasPendientes">0</h3>
                    <p class="stats-label text-muted mb-0">Pendientes</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-warning" id="progressPendientes" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-icon mb-3">
                        <i class="bi bi-camera-video text-info" style="font-size: 2.5rem;"></i>
                    </div>
                    <h3 class="stats-number text-info mb-1" id="citasVirtuales">0</h3>
                    <p class="stats-label text-muted mb-0">Virtuales</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-info" id="progressVirtuales" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximas citas widget -->
        <div class="row mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, var(--success-color), var(--info-color));">
                        <h6 class="mb-0">
                            <i class="bi bi-calendar-plus me-2"></i>
                            Próximas Citas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="proximasCitasWidget">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros y búsqueda mejorados -->
<div class="col-md-8">
    <div class="filter-card">
        <!-- Header del panel de filtros -->
        <div class="filter-header">
            <div class="filter-header-content">
                <div class="filter-title">
                    <i class="bi bi-funnel-fill me-2"></i>
                    <span>Filtros y Búsqueda</span>
                    <span class="badge bg-primary badge-notification" id="filtrosActivosBadge" style="display: none;">0</span>
                </div>
                <button class="btn btn-filter-toggle" id="toggleFiltros" data-bs-toggle="collapse" data-bs-target="#filtrosContainer">
                    <i class="bi bi-chevron-down transition-icon"></i>
                </button>
            </div>
        </div>
        
        <div class="filter-body collapse show" id="filtrosContainer">
            <!-- Búsqueda principal mejorada -->
            <div class="search-section">
                <div class="search-label">
                    <i class="bi bi-search me-2"></i>
                    <span>Búsqueda General</span>
                </div>
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control search-input" 
                               id="busquedaGeneral" 
                               placeholder="Buscar por doctor, especialidad, motivo o sucursal...">
                        <button class="btn btn-clear-search" type="button" id="btnLimpiarBusqueda">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div id="indicadorBusqueda" class="search-indicator"></div>
                </div>
            </div>
            
            <!-- Filtros organizados en grid -->
            <div class="filters-section">
                <div class="filters-label">
                    <i class="bi bi-sliders me-2"></i>
                    <span>Filtros Rápidos</span>
                </div>
                
                <div class="filters-grid">
                    <!-- Estado -->
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="bi bi-circle-fill status-icon"></i>
                            Estado
                        </label>
                        <select class="form-select filter-select" id="filtroEstado">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente">⏳ Pendiente</option>
                            <option value="Confirmada">✅ Confirmada</option>
                            <option value="Completada">🏁 Completada</option>
                            <option value="Cancelada">❌ Cancelada</option>
                            <option value="No Asistio">👤 No Asistió</option>
                        </select>
                    </div>
                    
                    <!-- Tipo de Cita -->
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="bi bi-geo-alt-fill location-icon"></i>
                            Modalidad
                        </label>
                        <select class="form-select filter-select" id="filtroTipoCita">
                            <option value="">Todas las modalidades</option>
                            <option value="presencial">🏥 Presencial</option>
                            <option value="virtual">💻 Virtual</option>
                        </select>
                    </div>
                    
                    <!-- Especialidad -->
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="bi bi-heart-pulse-fill specialty-icon"></i>
                            Especialidad
                        </label>
                        <select class="form-select filter-select" id="filtroEspecialidad">
                            <option value="">Todas las especialidades</option>
                            <!-- Se llena dinámicamente -->
                        </select>
                    </div>
                    
                    <!-- Items por página -->
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="bi bi-list-ol page-icon"></i>
                            Por Página
                        </label>
                        <select class="form-select filter-select" id="itemsPorPagina">
                            <option value="5">5 items</option>
                            <option value="10" selected>10 items</option>
                            <option value="20">20 items</option>
                            <option value="50">50 items</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Búsqueda por fechas mejorada -->
            <div class="date-section">
                <div class="date-label">
                    <i class="bi bi-calendar-range me-2"></i>
                    <span>Búsqueda por Fechas</span>
                </div>
                
                <div class="date-container">
                    <div class="date-inputs">
                        <div class="date-input-group">
                            <label class="date-input-label">Desde</label>
                            <div class="date-input-wrapper">
                                <i class="bi bi-calendar-event date-icon"></i>
                                <input type="date" class="form-control date-input" id="fechaDesde">
                            </div>
                        </div>
                        
                        <div class="date-separator">
                            <i class="bi bi-arrow-right"></i>
                        </div>
                        
                        <div class="date-input-group">
                            <label class="date-input-label">Hasta</label>
                            <div class="date-input-wrapper">
                                <i class="bi bi-calendar-check date-icon"></i>
                                <input type="date" class="form-control date-input" id="fechaHasta">
                            </div>
                        </div>
                    </div>
                    
                    <div class="date-actions">
                        <button class="btn btn-search-dates" id="btnBuscarFechas">
                            <i class="bi bi-search me-2"></i>
                            Buscar
                        </button>
                        <button class="btn btn-clear-filters" id="btnLimpiarFiltros">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Limpiar Todo
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Resumen de resultados mejorado -->
            <div id="resumenFiltros" class="results-summary" style="display: none;">
                <div class="summary-content">
                    <i class="bi bi-info-circle-fill summary-icon"></i>
                    <div class="summary-text">
                        <span class="summary-main">Mostrando <strong id="resumenTotal">0</strong> resultados</span>
                        <span class="summary-sub">con filtros aplicados</span>
                    </div>
                    <button class="btn btn-summary-clear" onclick="window.consultaCitasApp.limpiarFiltros()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Lista de citas -->
        <div class="citas-container" data-aos="fade-up" data-aos-delay="300">
            <div class="card-header bg-light border-0">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>
                            Historial de Citas
                        </h5>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm" id="btnExportarPDF" data-bs-toggle="tooltip" title="Exportar a PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </button>
                            <button class="btn btn-outline-success btn-sm" id="btnExportarExcel" data-bs-toggle="tooltip" title="Exportar a Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Contenedor de citas -->
                <div class="row" id="citasContainer">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando citas...</span>
                        </div>
                        <p class="mt-3 text-muted">Cargando historial de citas...</p>
                    </div>
                </div>
                
                <!-- Paginación -->
                <div id="paginacionContainer" class="mt-4"></div>
            </div>
        </div>
    </main>

    <!-- Modal de detalle de cita -->
    <div class="modal fade" id="modalDetalleCita" tabindex="-1" aria-labelledby="modalDetalleCitaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetalleCitaLabel">
                        <i class="bi bi-clipboard-heart me-2"></i>
                        Detalle de Cita Médica
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="detalleContent">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando detalles...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
       <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>

    
    <!-- JavaScript personalizado -->
    <script src="../../js/pacientes/consulta_citas.js"></script>

    <script>
    // Inicializar AOS
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // Configurar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Responsive sidebar
    $(document).ready(function() {
        // Detectar cambio de tamaño de ventana
        $(window).resize(function() {
            if ($(window).width() <= 992) {
                $('.main-container').css('margin-left', '0');
            } else {
                $('.main-container').css('margin-left', '260px');
            }
        });

        // Limpiar búsqueda
        $('#btnLimpiarBusqueda').on('click', function() {
            $('#busquedaGeneral').val('').trigger('input');
            $(this).fadeOut(200).fadeIn(200);
        });

        // Efectos hover para las tarjetas de estadísticas
        $('.stats-card').hover(
            function() {
                $(this).find('.stats-icon i').addClass('text-primary');
            },
            function() {
                $(this).find('.stats-icon i').removeClass('text-primary');
            }
        );

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);

        // Smooth scroll para la paginación
        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.citas-container').offset().top - 100
            }, 600);
        });

        // Detectar scroll para animaciones adicionales
        $(window).scroll(function() {
            var scroll = $(window).scrollTop();
            
            if (scroll >= 100) {
                $('.page-header').addClass('scrolled');
            } else {
                $('.page-header').removeClass('scrolled');
            }
        });

        // Prevenir envío de formularios con Enter
        $('input').keypress(function(e) {
            if (e.which == 13) {
                e.preventDefault();
                if ($(this).attr('id') === 'busquedaGeneral') {
                    // Trigger búsqueda
                    $(this).trigger('input');
                }
            }
        });

        console.log('🏥 MediSys - Vista de Consulta de Citas inicializada');
    });
</script>

    
</body>
</html>