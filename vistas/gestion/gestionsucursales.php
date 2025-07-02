<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($sucursales)) {
    require_once __DIR__ . '/../../controladores/SucursalesControlador/SucursalesController.php';
    $controller = new SucursalesController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Gestión de Sucursales</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../../estilos/gestionsucursales.css">
    
    <style>
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .badge-estado {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
        
        .tabla-sucursales .btn {
            margin: 0 2px;
        }
        
        .estadisticas-card {
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        
        .estadisticas-card:hover {
            transform: translateY(-2px);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .form-floating > label {
            color: #6c757d;
        }
        
        .btn-outline-primary:hover {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        /* Estilos para Select2 especialidades */
        .especialidades-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 15px;
        }
        
        .especialidad-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 10px;
            margin: 5px 0;
            transition: all 0.2s;
        }
        
        .especialidad-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .especialidad-item input[type="checkbox"]:checked + label {
            color: #667eea;
            font-weight: 500;
        }
        
        /* Filtros mejorados */
        .filtros-container {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .busqueda-container {
            position: relative;
        }
        
        .busqueda-container .form-control {
            padding-left: 45px;
        }
        
        .busqueda-container .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        
        /* Paginación mejorada */
        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-font-size: 0.875rem;
            --bs-pagination-color: #667eea;
            --bs-pagination-bg: #fff;
            --bs-pagination-border-width: 1px;
            --bs-pagination-border-color: #dee2e6;
            --bs-pagination-border-radius: 0.375rem;
            --bs-pagination-hover-color: #fff;
            --bs-pagination-hover-bg: #667eea;
            --bs-pagination-hover-border-color: #667eea;
            --bs-pagination-focus-color: #fff;
            --bs-pagination-focus-bg: #667eea;
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #667eea;
            --bs-pagination-active-border-color: #667eea;
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: #fff;
            --bs-pagination-disabled-border-color: #dee2e6;
        }
        
        .table-info {
            background: #e7f3ff;
            padding: 10px 15px;
            border-radius: 0.375rem;
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .filtros-container {
                padding: 15px;
            }
        }
    </style>
</head>

<body class="bg-light">
    <?php include __DIR__ . "/../../navbars/header.php"; ?>
    <?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

    <div class="container-fluid mt-4">
        <!-- Título y estadísticas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h3 mb-0">
                            <i class="bi bi-building me-2 text-primary"></i>
                            Gestión de Sucursales
                        </h2>
                        <p class="text-muted mb-0">Administra las sucursales y especialidades del sistema médico</p>
                    </div>
                    
                    <?php if ($permisos['puede_crear']): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearSucursalModal">
                        <i class="bi bi-plus-lg me-1"></i>
                        Nueva Sucursal
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="row mb-4" id="estadisticas-container">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card estadisticas-card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-building-check fs-1 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Sucursales Activas</div>
                                <div class="h5 mb-0 font-weight-bold text-primary" id="total-activas">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card estadisticas-card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-calendar-check fs-1 text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Citas Hoy</div>
                                <div class="h5 mb-0 font-weight-bold text-success" id="citas-hoy">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card estadisticas-card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-people fs-1 text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Total Doctores</div>
                                <div class="h5 mb-0 font-weight-bold text-info" id="total-doctores">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card estadisticas-card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-journal-medical fs-1 text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Especialidades</div>
                                <div class="h5 mb-0 font-weight-bold text-warning" id="total-especialidades">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta principal -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-table me-2"></i>
                            Listado de Sucursales
                        </h4>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filtros mejorados -->
                <div class="filtros-container">
                    <div class="row g-3">
                        <!-- Búsqueda -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-search me-1"></i>Búsqueda Global
                            </label>
                            <div class="busqueda-container">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" 
                                       class="form-control" 
                                       id="busquedaGlobal" 
                                       placeholder="Buscar por nombre, dirección, teléfono...">
                            </div>
                        </div>
                        
                        <!-- Filtro por estado -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-funnel me-1"></i>Estado
                            </label>
                            <select class="form-select" id="filtroEstado">
                                <option value="">🔄 Todos los estados</option>
                                <option value="1">✅ Activas</option>
                                <option value="0">❌ Inactivas</option>
                            </select>
                        </div>
                        
                        <!-- Registros por página -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-list-ol me-1"></i>Mostrar
                            </label>
                            <select class="form-select" id="registrosPorPagina">
                                <option value="10">10 registros</option>
                                <option value="25">25 registros</option>
                                <option value="50">50 registros</option>
                                <option value="100">100 registros</option>
                            </select>
                        </div>
                        
                        <!-- Botones de control -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-transparent">Acciones</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm" id="limpiarFiltros" title="Limpiar filtros">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <button class="btn btn-outline-primary btn-sm" id="refrescarTabla" title="Refrescar">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la tabla -->
                <div class="table-info" id="infoTabla">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Cargando información...</span>
                </div>

                <!-- Tabla responsive -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover tabla-sucursales align-middle" id="tablaSucursales">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th width="60">
                                    <i class="bi bi-hash me-1"></i>ID
                                </th>
                                <th>
                                    <i class="bi bi-building me-1"></i>Nombre
                                </th>
                                <th>
                                    <i class="bi bi-geo-alt me-1"></i>Dirección
                                </th>
                                <th>
                                    <i class="bi bi-telephone me-1"></i>Contacto
                                </th>
                                <th>
                                    <i class="bi bi-clock me-1"></i>Horario
                                </th>
                                <th>
                                    <i class="bi bi-journal-medical me-1"></i>Especialidades
                                </th>
                                <th width="100">
                                    <i class="bi bi-toggle-on me-1"></i>Estado
                                </th>
                                <th width="120">
                                    <i class="bi bi-graph-up me-1"></i>Stats
                                </th>
                                <th width="150" class="text-center">
                                    <i class="bi bi-gear me-1"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tablaSucursalesBody">
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-3 text-muted">Cargando sucursales...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación mejorada -->
                <div class="row mt-4">
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="dataTables_info text-muted" id="infoRegistros">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Paginación de sucursales" class="d-flex justify-content-end">
                            <ul class="pagination pagination-sm mb-0" id="paginacion">
                                <!-- Se llena dinámicamente -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Sucursal -->
    <div class="modal fade" id="crearSucursalModal" tabindex="-1" aria-labelledby="crearSucursalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearSucursalModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Sucursal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <form id="formCrearSucursal">
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Información básica -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Información Básica
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nombreSucursal" name="nombre_sucursal" 
                                           placeholder="Nombre de la sucursal" required maxlength="100">
                                    <label for="nombreSucursal">
                                        <i class="bi bi-building me-1"></i>
                                        Nombre de la Sucursal *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="telefonoSucursal" name="telefono" 
                                           placeholder="Teléfono" required maxlength="20">
                                    <label for="telefonoSucursal">
                                        <i class="bi bi-telephone me-1"></i>
                                        Teléfono *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <textarea class="form-control" id="direccionSucursal" name="direccion" 
                                              placeholder="Dirección completa" required maxlength="255" style="height: 80px;"></textarea>
                                    <label for="direccionSucursal">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Dirección Completa *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="estadoSucursal" name="estado" required>
                                        <option value="1" selected>Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                    <label for="estadoSucursal">
                                        <i class="bi bi-toggle-on me-1"></i>
                                        Estado
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="emailSucursal" name="email" 
                                           placeholder="Correo electrónico" maxlength="100">
                                    <label for="emailSucursal">
                                        <i class="bi bi-envelope me-1"></i>
                                        Correo Electrónico
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="horarioAtencion" name="horario_atencion" 
                                              placeholder="Horarios de atención" maxlength="255" style="height: 60px;"></textarea>
                                    <label for="horarioAtencion">
                                        <i class="bi bi-calendar-week me-1"></i>
                                        Horarios de Atención
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Especialidades -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-journal-medical me-2"></i>
                                    Especialidades Disponibles
                                </h6>
                                <p class="text-muted small mb-3">
                                    Selecciona las especialidades médicas que estarán disponibles en esta sucursal
                                </p>
                            </div>
                            
                            <div class="col-12">
                                <div class="especialidades-container">
                                    <div class="row" id="especialidadesCrear">
                                        <!-- Se cargan dinámicamente -->
                                        <div class="col-12 text-center">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Cargando especialidades...</span>
                                            </div>
                                            <p class="mt-2 text-muted small">Cargando especialidades disponibles...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            Crear Sucursal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Sucursal -->
    <div class="modal fade" id="editarSucursalModal" tabindex="-1" aria-labelledby="editarSucursalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarSucursalModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Sucursal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <form id="formEditarSucursal">
                    <input type="hidden" id="editarIdSucursal" name="id_sucursal">
                    
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Información básica -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Información Básica
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="editarNombreSucursal" name="nombre_sucursal" 
                                           placeholder="Nombre de la sucursal" required maxlength="100">
                                    <label for="editarNombreSucursal">
                                        <i class="bi bi-building me-1"></i>
                                        Nombre de la Sucursal *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="editarTelefonoSucursal" name="telefono" 
                                           placeholder="Teléfono" required maxlength="20">
                                    <label for="editarTelefonoSucursal">
                                        <i class="bi bi-telephone me-1"></i>
                                        Teléfono *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <textarea class="form-control" id="editarDireccionSucursal" name="direccion" 
                                              placeholder="Dirección completa" required maxlength="255" style="height: 80px;"></textarea>
                                    <label for="editarDireccionSucursal">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Dirección Completa *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="editarEstadoSucursal" name="estado" required>
                                        <option value="1">Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                    <label for="editarEstadoSucursal">
                                        <i class="bi bi-toggle-on me-1"></i>
                                        Estado
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="editarEmailSucursal" name="email" 
                                           placeholder="Correo electrónico" maxlength="100">
                                    <label for="editarEmailSucursal">
                                        <i class="bi bi-envelope me-1"></i>
                                        Correo Electrónico
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="editarHorarioAtencion" name="horario_atencion" 
                                              placeholder="Horarios de atención" maxlength="255" style="height: 60px;"></textarea>
                                    <label for="editarHorarioAtencion">
                                        <i class="bi bi-calendar-week me-1"></i>
                                        Horarios de Atención
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Especialidades -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-journal-medical me-2"></i>
                                    Especialidades Disponibles
                                </h6>
                                <p class="text-muted small mb-3">
                                    Actualiza las especialidades médicas disponibles en esta sucursal
                                </p>
                            </div>
                            
                            <div class="col-12">
                                <div class="especialidades-container">
                                    <div class="row" id="especialidadesEditar">
                                        <!-- Se cargan dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="verSucursalModal" tabindex="-1" aria-labelledby="verSucursalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verSucursalModalLabel">
                       <i class="bi bi-eye me-2"></i>
                       Detalles de la Sucursal
                   </h5>
                   <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               
               <div class="modal-body" id="contenidoVerSucursal">
                   <div class="text-center py-4">
                       <div class="spinner-border text-primary" role="status">
                           <span class="visually-hidden">Cargando...</span>
                       </div>
                       <p class="mt-2 text-muted">Cargando información...</p>
                   </div>
               </div>
               
               <div class="modal-footer bg-light">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                       <i class="bi bi-x-circle me-1"></i>
                       Cerrar
                   </button>
               </div>
           </div>
       </div>
   </div>

   <!-- Scripts -->
   <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
   
   <!-- Select2 JS -->
   <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
   
   <!-- SweetAlert2 JS -->
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>
   
   <!-- Script de configuración -->
   <script>
       // Configuración global para el módulo de sucursales
       window.sucursalesConfig = {
           permisos: <?php echo json_encode($permisos); ?>,
           submenuId: <?php echo $id_submenu; ?>,
           especialidades: <?php echo json_encode($especialidades); ?>
       };
   </script>
   
   <!-- Script principal de sucursales -->
   <script src="../../js/gestionsucursales.js"></script>
</body>
</html>