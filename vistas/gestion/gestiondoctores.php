<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($doctores)) {
    require_once __DIR__ . '/../../controladores/DoctoresControlador/DoctoresController.php';
    $controller = new DoctoresController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Gestión de Doctores</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../../estilos/gestion.css">
    
    <style>
        .card-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .badge-estado {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
        
        .tabla-doctores .btn {
            margin: 0 2px;
        }
        
        .estadisticas-card {
            border-left: 4px solid #28a745;
            transition: transform 0.2s;
        }
        
        .estadisticas-card:hover {
            transform: translateY(-2px);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .form-floating > label {
            color: #6c757d;
        }
        
        .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        /* Estilos para Select2 sucursales */
        .sucursales-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 15px;
        }
        
        .sucursal-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 10px;
            margin: 5px 0;
            transition: all 0.2s;
        }
        
        .sucursal-item:hover {
            border-color: #28a745;
            transform: translateX(5px);
        }
        
        .sucursal-item input[type="checkbox"]:checked + label {
            color: #28a745;
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
            --bs-pagination-color: #28a745;
            --bs-pagination-bg: #fff;
            --bs-pagination-border-color: #dee2e6;
            --bs-pagination-hover-color: #fff;
            --bs-pagination-hover-bg: #28a745;
            --bs-pagination-hover-border-color: #28a745;
            --bs-pagination-focus-color: #fff;
            --bs-pagination-focus-bg: #28a745;
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #28a745;
            --bs-pagination-active-border-color: #28a745;
        }
        
        .table-info {
            background: #d1eddb;
            padding: 10px 15px;
            border-radius: 0.375rem;
            border-left: 4px solid #28a745;
            margin-bottom: 15px;
        }
        
        .password-generator {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.375rem;
            padding: 15px;
            margin: 10px 0;
        }
        
        .password-display {
            background: white;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
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
                            <i class="bi bi-person-badge me-2 text-success"></i>
                            Gestión de Doctores
                        </h2>
                        <p class="text-muted mb-0">Administra el personal médico del sistema hospitalario</p>
                    </div>
                    
                    <?php if ($permisos['puede_crear']): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crearDoctorModal">
                        <i class="bi bi-plus-lg me-1"></i>
                        Nuevo Doctor
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
                                <i class="bi bi-person-check fs-1 text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Doctores Activos</div>
                                <div class="h5 mb-0 font-weight-bold text-success" id="total-activos">
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
                                <i class="bi bi-journal-medical fs-1 text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Especialidades</div>
                                <div class="h5 mb-0 font-weight-bold text-info" id="total-especialidades">
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
                                <i class="bi bi-building fs-1 text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Sucursales</div>
                                <div class="h5 mb-0 font-weight-bold text-warning" id="total-sucursales">
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
                                <i class="bi bi-people fs-1 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Total Doctores</div>
                                <div class="h5 mb-0 font-weight-bold text-primary" id="total-doctores">
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
                            Listado de Doctores
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
                                       placeholder="Buscar por nombre, cédula, especialidad...">
                            </div>
                        </div>
                        
                        <!-- Filtro por estado -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-funnel me-1"></i>Estado
                            </label>
                            <select class="form-select" id="filtroEstado">
                                <option value="">🔄 Todos</option>
                                <option value="1">✅ Activos</option>
                                <option value="2">🚫 Bloqueados</option>
                                <option value="3">⏳ Pendientes</option>
                                <option value="4">❌ Inactivos</option>
                            </select>
                        </div>
                        
                        <!-- Filtro por especialidad -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-journal-medical me-1"></i>Especialidad
                            </label>
                            <select class="form-select" id="filtroEspecialidad">
                                <option value="">🏥 Todas</option>
                                <?php foreach ($especialidades as $especialidad): ?>
                                <option value="<?= $especialidad['id_especialidad'] ?>">
                                    <?= htmlspecialchars($especialidad['nombre_especialidad']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por sucursal -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1"></i>Sucursal
                            </label>
                            <select class="form-select" id="filtroSucursal">
                                <option value="">🏢 Todas</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= $sucursal['id_sucursal'] ?>">
                                    <?= htmlspecialchars($sucursal['nombre_sucursal']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Registros por página -->
                        <div class="col-md-1">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-list-ol me-1"></i>Mostrar
                            </label>
                            <select class="form-select" id="registrosPorPagina">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        
                        <!-- Botones de control -->
                        <div class="col-md-1">
                            <label class="form-label fw-semibold text-transparent">Acciones</label>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline-secondary btn-sm" id="limpiarFiltros" title="Limpiar filtros">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm" id="refrescarTabla" title="Refrescar">
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
                    <table class="table table-striped table-hover tabla-doctores align-middle" id="tablaDoctores">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th width="60">
                                    <i class="bi bi-hash me-1"></i>ID
                                </th>
                                <th>
                                    <i class="bi bi-person me-1"></i>Doctor
                                </th>
                                <th>
                                    <i class="bi bi-card-text me-1"></i>Información
                                </th>
                                <th>
                                    <i class="bi bi-journal-medical me-1"></i>Especialidad
                                </th>
                                <th>
                                    <i class="bi bi-building me-1"></i>Sucursales
                                </th>
                                <th width="100">
                                    <i class="bi bi-toggle-on me-1"></i>Estado
                                </th>
                                <th width="120">
                                    <i class="bi bi-graph-up me-1"></i>Estadísticas
                                </th>
                                <th width="150" class="text-center">
                                    <i class="bi bi-gear me-1"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tablaDoctoresBody">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-3 text-muted">Cargando doctores...</p>
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
                        <nav aria-label="Paginación de doctores" class="d-flex justify-content-end">
                            <ul class="pagination pagination-sm mb-0" id="paginacion">
                                <!-- Se llena dinámicamente -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Doctor -->
    <div class="modal fade" id="crearDoctorModal" tabindex="-1" aria-labelledby="crearDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearDoctorModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Registrar Nuevo Doctor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <form id="formCrearDoctor">
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Información Personal -->
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2 mb-3">
                                    <i class="bi bi-person me-2"></i>
                                    Información Personal
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="cedula" name="cedula" 
                                           placeholder="Cédula" required maxlength="10" min="1000000000" max="9999999999">
                                    <label for="cedula">
                                        <i class="bi bi-card-text me-1"></i>
                                        Cédula *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Usuario" required maxlength="50">
                                    <label for="username">
                                        <i class="bi bi-person-circle me-1"></i>
                                        Nombre de Usuario *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nombres" name="nombres" 
                                           placeholder="Nombres" required maxlength="255">
                                    <label for="nombres">
                                        <i class="bi bi-person me-1"></i>
                                        Nombres *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                           placeholder="Apellidos" required maxlength="255">
                                    <label for="apellidos">
                                        <i class="bi bi-person me-1"></i>
                                        Apellidos *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="sexo" name="sexo" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                    <label for="sexo">
                                        <i class="bi bi-gender-ambiguous me-1"></i>
                                        Sexo *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nacionalidad" name="nacionalidad" 
                                           placeholder="Nacionalidad" required maxlength="255" value="Ecuatoriana">
                                    <label for="nacionalidad">
                                        <i class="bi bi-flag me-1"></i>
                                        Nacionalidad *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="id_estado" name="id_estado">
                                        <option value="1" selected>Activo</option>
                                        <option value="2">Bloqueado</option>
                                        <option value="3">Pendiente</option>
                                        <option value="4">Inactivo</option>
                                    </select>
                                    <label for="id_estado">
                                        <i class="bi bi-toggle-on me-1"></i>
                                        Estado
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="correo" name="correo" 
                                           placeholder="Correo electrónico" required maxlength="255">
                                    <label for="correo">
                                        <i class="bi bi-envelope me-1"></i>
                                        Correo Electrónico *
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Contraseña automática -->
                            <div class="col-md-6">
                                <div class="password-generator">
                                    <label class="form-label fw-semibold text-warning">
                                        <i class="bi bi-key me-1"></i>
                                        Contraseña Temporal (Generada Automáticamente)
                                    </label>
                                    <div class="d-flex gap-2">
                                        <div class="password-display flex-grow-1" id="passwordDisplay">
                                            Se generará automáticamente...
                                        </div>
                                        <button type="button" class="btn btn-outline-warning btn-sm" id="generarPassword">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        Esta contraseña será enviada por correo electrónico
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Información Médica -->
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2 mb-3 mt-3">
                                    <i class="bi bi-journal-medical me-2"></i>
                                    Información Médica
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="id_especialidad" name="id_especialidad" required>
                                        <option value="">Seleccionar especialidad...</option>
                                        <?php foreach ($especialidades as $especialidad): ?>
                                        <option value="<?= $especialidad['id_especialidad'] ?>">
                                            <?= htmlspecialchars($especialidad['nombre_especialidad']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="id_especialidad">
                                        <i class="bi bi-journal-medical me-1"></i>
                                        Especialidad Médica *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="titulo_profesional" name="titulo_profesional" 
                                           placeholder="Título profesional" maxlength="100">
                                    <label for="titulo_profesional">
                                        <i class="bi bi-mortarboard me-1"></i>
                                        Título Profesional
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Asignación de Sucursales -->
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2 mb-3">
                                    <i class="bi bi-building me-2"></i>
                                    Asignación de Sucursales
                                </h6>
                                <p class="text-muted small mb-3">
                                    Selecciona las sucursales donde trabajará este doctor
                                </p>
                            </div>
                            
                            <div class="col-12">
                                <div class="sucursales-container">
                                    <div class="row" id="sucursalesCrear">
                                        <?php foreach ($sucursales as $sucursal): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="sucursal-item">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           value="<?= $sucursal['id_sucursal'] ?>" 
                                                           name="sucursales[]" 
                                                           id="suc_<?= $sucursal['id_sucursal'] ?>">
                                                    <label class="form-check-label" for="suc_<?= $sucursal['id_sucursal'] ?>">
                                                        <strong><?= htmlspecialchars($sucursal['nombre_sucursal']) ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="bi bi-geo-alt me-1"></i>
                                                            <?= htmlspecialchars(substr($sucursal['direccion'], 0, 40)) ?>...
                                                        </small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
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
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>
                            Registrar Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Doctor -->
    <div class="modal fade" id="editarDoctorModal" tabindex="-1" aria-labelledby="editarDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarDoctorModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Doctor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formEditarDoctor">
                   <input type="hidden" id="editarIdDoctor" name="id_doctor">
                   
                   <div class="modal-body">
                       <div class="row g-4">
                           <!-- Información Personal -->
                           <div class="col-12">
                               <h6 class="text-success border-bottom pb-2 mb-3">
                                   <i class="bi bi-person me-2"></i>
                                   Información Personal
                               </h6>
                           </div>
                           
                           <div class="col-md-6">
                               <div class="form-floating">
                                   <input type="number" class="form-control" id="editarCedula" name="cedula" 
                                          placeholder="Cédula" required maxlength="10" min="1000000000" max="9999999999">
                                   <label for="editarCedula">
                                       <i class="bi bi-card-text me-1"></i>
                                       Cédula *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-6">
                               <div class="form-floating">
                                   <input type="text" class="form-control" id="editarUsername" name="username" 
                                          placeholder="Usuario" required maxlength="50">
                                   <label for="editarUsername">
                                       <i class="bi bi-person-circle me-1"></i>
                                       Nombre de Usuario *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-6">
                               <div class="form-floating">
                                   <input type="text" class="form-control" id="editarNombres" name="nombres" 
                                          placeholder="Nombres" required maxlength="255">
                                   <label for="editarNombres">
                                       <i class="bi bi-person me-1"></i>
                                       Nombres *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-6">
                               <div class="form-floating">
                                   <input type="text" class="form-control" id="editarApellidos" name="apellidos" 
                                          placeholder="Apellidos" required maxlength="255">
                                   <label for="editarApellidos">
                                       <i class="bi bi-person me-1"></i>
                                       Apellidos *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-4">
                               <div class="form-floating">
                                   <select class="form-select" id="editarSexo" name="sexo" required>
                                       <option value="">Seleccionar...</option>
                                       <option value="M">Masculino</option>
                                       <option value="F">Femenino</option>
                                   </select>
                                   <label for="editarSexo">
                                       <i class="bi bi-gender-ambiguous me-1"></i>
                                       Sexo *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-4">
                               <div class="form-floating">
                                   <input type="text" class="form-control" id="editarNacionalidad" name="nacionalidad" 
                                          placeholder="Nacionalidad" required maxlength="255">
                                   <label for="editarNacionalidad">
                                       <i class="bi bi-flag me-1"></i>
                                       Nacionalidad *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-4">
                               <div class="form-floating">
                                   <select class="form-select" id="editarIdEstado" name="id_estado">
                                       <option value="1">Activo</option>
                                       <option value="2">Bloqueado</option>
                                       <option value="3">Pendiente</option>
                                       <option value="4">Inactivo</option>
                                   </select>
                                   <label for="editarIdEstado">
                                       <i class="bi bi-toggle-on me-1"></i>
                                       Estado
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-12">
                               <div class="form-floating">
                                   <input type="email" class="form-control" id="editarCorreo" name="correo" 
                                          placeholder="Correo electrónico" required maxlength="255">
                                   <label for="editarCorreo">
                                       <i class="bi bi-envelope me-1"></i>
                                       Correo Electrónico *
                                   </label>
                               </div>
                           </div>
                           
                           <!-- Información Médica -->
                           <div class="col-12">
                               <h6 class="text-success border-bottom pb-2 mb-3 mt-3">
                                   <i class="bi bi-journal-medical me-2"></i>
                                   Información Médica
                               </h6>
                           </div>
                           
                           <div class="col-md-6">
                               <div class="form-floating">
                                   <select class="form-select" id="editarIdEspecialidad" name="id_especialidad" required>
                                       <option value="">Seleccionar especialidad...</option>
                                       <?php foreach ($especialidades as $especialidad): ?>
                                       <option value="<?= $especialidad['id_especialidad'] ?>">
                                           <?= htmlspecialchars($especialidad['nombre_especialidad']) ?>
                                       </option>
                                       <?php endforeach; ?>
                                   </select>
                                   <label for="editarIdEspecialidad">
                                       <i class="bi bi-journal-medical me-1"></i>
                                       Especialidad Médica *
                                   </label>
                               </div>
                           </div>
                           
                           <div class="col-md-6">
                               <div class="form-floating">
                                   <input type="text" class="form-control" id="editarTituloProfesional" name="titulo_profesional" 
                                          placeholder="Título profesional" maxlength="100">
                                   <label for="editarTituloProfesional">
                                       <i class="bi bi-mortarboard me-1"></i>
                                       Título Profesional
                                   </label>
                               </div>
                           </div>
                           
                           <!-- Asignación de Sucursales -->
                           <div class="col-12">
                               <h6 class="text-success border-bottom pb-2 mb-3">
                                   <i class="bi bi-building me-2"></i>
                                   Asignación de Sucursales
                               </h6>
                               <p class="text-muted small mb-3">
                                   Actualiza las sucursales donde trabajará este doctor
                               </p>
                           </div>
                           
                           <div class="col-12">
                               <div class="sucursales-container">
                                   <div class="row" id="sucursalesEditar">
                                       <?php foreach ($sucursales as $sucursal): ?>
                                       <div class="col-md-6 col-lg-4">
                                           <div class="sucursal-item">
                                               <div class="form-check">
                                                   <input class="form-check-input" 
                                                          type="checkbox" 
                                                          value="<?= $sucursal['id_sucursal'] ?>" 
                                                          name="sucursales[]" 
                                                          id="edit_suc_<?= $sucursal['id_sucursal'] ?>">
                                                   <label class="form-check-label" for="edit_suc_<?= $sucursal['id_sucursal'] ?>">
                                                       <strong><?= htmlspecialchars($sucursal['nombre_sucursal']) ?></strong>
                                                       <br>
                                                       <small class="text-muted">
                                                           <i class="bi bi-geo-alt me-1"></i>
                                                           <?= htmlspecialchars(substr($sucursal['direccion'], 0, 40)) ?>...
                                                       </small>
                                                   </label>
                                               </div>
                                           </div>
                                       </div>
                                       <?php endforeach; ?>
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
                       <button type="submit" class="btn btn-success">
                           <i class="bi bi-check-circle me-1"></i>
                           Guardar Cambios
                       </button>
                   </div>
               </form>
           </div>
       </div>
   </div>

   <!-- Modal Ver Detalles -->
   <div class="modal fade" id="verDoctorModal" tabindex="-1" aria-labelledby="verDoctorModalLabel" aria-hidden="true">
       <div class="modal-dialog modal-lg">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title" id="verDoctorModalLabel">
                       <i class="bi bi-eye me-2"></i>
                       Información del Doctor
                   </h5>
                   <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               
               <div class="modal-body" id="contenidoVerDoctor">
                   <div class="text-center py-4">
                       <div class="spinner-border text-success" role="status">
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
       // Configuración global para el módulo de doctores
       window.doctoresConfig = {
           permisos: <?php echo json_encode($permisos); ?>,
           submenuId: <?php echo $id_submenu; ?>,
           especialidades: <?php echo json_encode($especialidades); ?>,
           sucursales: <?php echo json_encode($sucursales); ?>
       };
   </script>
   
   <!-- Script principal de doctores -->
   <script src="../../js/gestiondoctores.js"></script>
</body>
</html>