<?php
// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit();
}

// Incluir el navbar y sidebar
include_once __DIR__ . '/../../navbars/header.php';
include_once __DIR__ . '/../../navbars/sidebar.php';
?>

<!-- ===== CDNs NECESARIOS ===== -->
<!-- Bootstrap CSS (si no está en header.php) -->
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<!-- CSS personalizado -->
<link href="../../estilos/historial_medico.css" rel="stylesheet">

<!-- Contenido Principal -->
<div class="main-content" id="main-content">
    <div class="container-fluid">
        <!-- Header de la página -->
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="bi bi-clipboard2-pulse me-2"></i>
                        Historial Médico
                    </h1>
                    <p class="page-subtitle">
                        Consulta completa del historial clínico por paciente
                    </p>
                </div>
                <div class="col-auto">
                    <div class="page-actions">
                        <button class="btn btn-outline-primary" id="btnAyuda">
                            <i class="bi bi-question-circle me-1"></i>
                            Ayuda
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Búsqueda Principal -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card search-card">
                    <div class="card-body">
                        <div class="search-header mb-4">
                            <h4 class="search-title">
                                <i class="bi bi-search me-2"></i>
                                Buscar Paciente
                            </h4>
                            <p class="search-subtitle">
                                Ingrese la cédula del paciente para consultar su historial médico completo
                            </p>
                        </div>
                        
                        <form id="formBuscarPaciente" class="search-form">
                            <div class="input-group search-input-group">
                                <span class="input-group-text search-icon">
                                    <i class="bi bi-person-vcard"></i>
                                </span>
                                <input type="text" 
                                       class="form-control search-input" 
                                       id="cedulaBusqueda" 
                                       placeholder="Número de cédula del paciente (ej: 1234567890)"
                                       required>
                                <button type="submit" class="btn btn-search">
                                    <i class="bi bi-search me-2"></i>
                                    Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Paciente Encontrado -->
        <div class="patient-section" id="pacienteInfoSection" style="display: none;">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card patient-card">
                        <div class="card-header patient-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Información del Paciente
                                </h5>
                                <button class="btn btn-sm btn-outline-light" id="btnEditarPaciente">
                                    <i class="bi bi-pencil me-1"></i>
                                    Editar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Información básica del paciente -->
                            <div class="patient-info-grid" id="pacienteInfo">
                                <!-- Se llenará dinámicamente -->
                            </div>
                            
                            <!-- Estadísticas rápidas -->
                            <div class="stats-section mt-4">
                                <h6 class="stats-title mb-3">Resumen de Atención Médica</h6>
                                <div class="row" id="estadisticasPaciente">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Filtros y Herramientas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card tools-card">
                        <div class="card-body">
                            <div class="tools-header mb-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="tools-title mb-0">
                                            <i class="bi bi-funnel me-2"></i>
                                            Herramientas de Búsqueda y Filtros
                                        </h6>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#filtrosAvanzados">
                                            <i class="bi bi-sliders me-1"></i>
                                            Filtros Avanzados
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Búsqueda rápida -->
                            <div class="quick-search mb-3">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="busquedaTermino" 
                                                   placeholder="Buscar en diagnósticos, tratamientos, observaciones...">
                                            <button class="btn btn-outline-primary" id="btnBuscarEnHistorial">
                                                Buscar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="export-buttons">
                                            <button class="btn btn-success" id="btnExportarPDF">
                                                <i class="bi bi-file-pdf me-1"></i>
                                                Exportar PDF
                                            </button>
                                            <button class="btn btn-info" id="btnExportarExcel">
                                                <i class="bi bi-file-excel me-1"></i>
                                                Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros avanzados (colapsable) -->
                            <div class="collapse" id="filtrosAvanzados">
                                <div class="advanced-filters">
                                    <form id="formFiltros">
                                        <input type="hidden" id="idPacienteFiltros" name="id_paciente">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Fecha Desde</label>
                                                <input type="date" class="form-control" id="fechaDesde" name="fecha_desde">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Fecha Hasta</label>
                                                <input type="date" class="form-control" id="fechaHasta" name="fecha_hasta">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Especialidad</label>
                                                <select class="form-select" id="filtroEspecialidad" name="id_especialidad">
                                                    <option value="">Todas las especialidades</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Estado</label>
                                                <select class="form-select" id="filtroEstado" name="estado">
                                                    <option value="">Todos los estados</option>
                                                    <option value="Pendiente">Pendiente</option>
                                                    <option value="Confirmada">Confirmada</option>
                                                    <option value="Completada">Completada</option>
                                                    <option value="Cancelada">Cancelada</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Doctor</label>
                                                <select class="form-select" id="filtroDoctor" name="id_doctor">
                                                    <option value="">Todos los doctores</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Sucursal</label>
                                                <select class="form-select" id="filtroSucursal" name="id_sucursal">
                                                    <option value="">Todas las sucursales</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <div class="filter-actions w-100">
                                                    <button type="submit" class="btn btn-primary me-2">
                                                        <i class="bi bi-funnel me-1"></i>
                                                        Aplicar
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        Limpiar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Historial Médico -->
            <div class="row">
                <div class="col-12">
                    <div class="card historial-card" id="historialSection" style="display: none;">
                        <div class="card-header historial-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="bi bi-clipboard2-data me-2"></i>
                                        Historial Médico Completo
                                    </h5>
                                    <small class="text-muted">
                                        <span id="totalRegistros">0 registros</span> encontrados
                                    </small>
                                </div>
                                <div class="header-actions">
                                    <button class="btn btn-sm btn-outline-light" id="btnRefrescar">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Actualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-historial" id="tablaHistorial">
                                    <thead>
                                        <tr>
                                            <th width="120">Fecha</th>
                                            <th width="150">Especialidad</th>
                                            <th width="180">Doctor</th>
                                            <th>Motivo</th>
                                            <th width="120">Estado</th>
                                            <th width="80" class="text-center">Triaje</th>
                                            <th width="80" class="text-center">Consulta</th>
                                            <th width="120" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historialTableBody">
                                        <!-- Se llenará dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Estado vacío -->
                            <div class="empty-state" id="historialVacio" style="display: none;">
                                <div class="empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <h4 class="empty-title">No hay registros médicos</h4>
                                <p class="empty-text">
                                    No se encontraron registros para los filtros aplicados.<br>
                                    Intente modificar los criterios de búsqueda.
                                </p>
                                <button class="btn btn-outline-primary" id="btnLimpiarBusqueda">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Mostrar todos los registros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle de Cita -->
<div class="modal fade" id="modalDetalleCita" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">
                    <i class="bi bi-file-medical me-2"></i>
                    Detalle Completo de la Cita
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalleCitaBody">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnImprimirDetalle">
                    <i class="bi bi-printer me-1"></i>
                    Imprimir
                </button>
                <button type="button" class="btn btn-info" id="btnCompartirDetalle">
                    <i class="bi bi-share me-1"></i>
                    Compartir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p class="loading-text">Cargando historial médico...</p>
    </div>
</div>

<!-- ===== SCRIPTS EN EL ORDEN CORRECTO ===== -->
<!-- jQuery (si no está en header.php) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script principal (DESPUÉS de todas las librerías) -->
<script src="../../js/historial_medico/historial_medico.js"></script>

<script>
// Configuración global para historial médico
window.historialConfig = {
    baseUrl: '../../controladores/HistorialMedicoControlador/HistorialMedicoController.php',
    especialidades: <?php echo json_encode($especialidades ?? []); ?>,
    sucursales: <?php echo json_encode($sucursales ?? []); ?>,
    debug: true
};

// Verificar que las librerías se cargaron correctamente
$(document).ready(function() {
    console.log('🔧 Verificando librerías...');
    console.log('jQuery:', typeof $ !== 'undefined' ? '✅' : '❌');
    console.log('DataTables:', typeof $.fn.DataTable !== 'undefined' ? '✅' : '❌');
    console.log('SweetAlert2:', typeof Swal !== 'undefined' ? '✅' : '❌');
    console.log('Bootstrap:', typeof bootstrap !== 'undefined' ? '✅' : '❌');
    
    console.log('🏥 Inicializando Sistema de Historial Médico');
});
</script>

<?php

?>