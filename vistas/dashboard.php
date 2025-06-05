<?php
require_once "../helpers/permisos.php"; // Protección de sesión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistema de Gestión Hospitalaria</title>
    
    <!-- Bootstrap CSS -->
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            <!-- Encabezado del dashboard -->
            <div class="dashboard-header mb-4">
                <div>
                    <h1 class="fw-bold">Panel de Control</h1>
                    <p class="text-muted">Bienvenido/a, <span class="fw-semibold"><?php echo $_SESSION["username"]; ?></span></p>
                </div>
                <div class="d-flex">
                    <button class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-calendar-check me-1"></i> Agenda
                    </button>
                    <button class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Nuevo Paciente
                    </button>
                </div>
            </div>
            
            <!-- Tarjetas de estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon bg-primary-light">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle text-muted">Pacientes Hoy</h6>
                                <h2 class="card-title mb-0" id="pacientesHoy">-</h2>
                                <div id="tendenciaPacientes" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon bg-warning-light">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle text-muted">Citas Pendientes</h6>
                                <h2 class="card-title mb-0" id="citasPendientes">-</h2>
                                <div id="estadoCitas" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon bg-info-light">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle text-muted">Médicos Activos</h6>
                                <h2 class="card-title mb-0" id="medicosActivos">-</h2>
                                <div id="estadoMedicos" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon bg-success-light">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle text-muted">Ingresos Hoy</h6>
                                <h2 class="card-title mb-0" id="ingresosHoy">-</h2>
                                <div id="tendenciaIngresos" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
                <!-- Citas próximas -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Próximas Citas</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaCitasProximas">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Hora</th>
                                            <th>Doctor</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4" class="text-center py-3">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <span class="ms-2">Cargando citas...</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                

        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Script para cargar datos dinámicos -->
    <script>
    $(document).ready(function() {
        // Simulamos la carga de datos desde el servidor
        setTimeout(function() {
            cargarDashboard();
        }, 1000);
        
        // Inicializar gráficos vacíos
        inicializarGraficos();
    });
    
    function cargarDashboard() {
        // En un sistema real, aquí harías una petición AJAX para cargar los datos
        // Por ahora, solo actualizamos la interfaz
        $("#tablaCitasProximas tbody").html('<tr><td colspan="4" class="text-center py-4">No hay citas programadas</td></tr>');
    }
    
    function inicializarGraficos() {
        // Crear gráficos vacíos que se llenarán con datos reales
        const ctx1 = document.getElementById('citasChart').getContext('2d');
        const ctx2 = document.getElementById('especialidadesChart').getContext('2d');
        const ctx3 = document.getElementById('habitacionesChart').getContext('2d');
        
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Cargando datos...',
                    data: [],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    label: 'Cargando datos...',
                    data: [],
                    backgroundColor: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Cargando datos...',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    </script>
</body>
</html>