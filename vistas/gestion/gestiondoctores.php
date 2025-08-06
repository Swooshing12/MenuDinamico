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
    <link rel="stylesheet" href="../../estilos/gestiondoctores.css">
  
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

  <!-- Modal Crear Doctor - DISEÑO MEJORADO COMPLETO -->
<div class="modal fade" id="crearDoctorModal" tabindex="-1" aria-labelledby="crearDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header con gradiente -->
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <h5 class="modal-title text-black fw-bold" id="crearDoctorModalLabel">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    ✨ Registrar Nuevo Doctor
                </h5>
                <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <form id="formCrearDoctor">
                <div class="modal-body" style="background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);">
                    <div class="row g-4">
                        
                        <!-- SECCIÓN 1: INFORMACIÓN PERSONAL -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-person-fill me-2"></i>
                                        👤 Información Personal
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        
                                        <!-- Cédula -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="cedula" name="cedula" 
                                                    placeholder="Cédula" required maxlength="15">
                                                <label for="cedula" class="text-primary fw-semibold">
                                                    <i class="bi bi-card-text me-1"></i>
                                                    🆔 Cédula de Identidad *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Cédula válida
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ La cédula debe tener entre 10 y 15 dígitos
                                            </div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="btnBuscarCedulaDoctor">
                                                    <i class="bi bi-search me-1"></i>
                                                    🔍 Buscar datos
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Username -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="username" name="username" 
                                                    placeholder="Username" required maxlength="50">
                                                <label for="username" class="text-primary fw-semibold">
                                                    <i class="bi bi-person-badge me-1"></i>
                                                    👨‍💼 Nombre de Usuario *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Username disponible
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Username no válido o ya existe
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Solo letras, números y guiones bajos. Mínimo 3 caracteres.
                                            </small>
                                        </div>

                                        <!-- Nombres -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="nombres" name="nombres" 
                                                    placeholder="Nombres" required maxlength="100">
                                                <label for="nombres" class="text-primary fw-semibold">
                                                    <i class="bi bi-person me-1"></i>
                                                    👤 Nombres *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Nombres válidos
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Solo se permiten letras y espacios
                                            </div>
                                        </div>
                                        
                                        <!-- Apellidos -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="apellidos" name="apellidos" 
                                                    placeholder="Apellidos" required maxlength="100">
                                                <label for="apellidos" class="text-primary fw-semibold">
                                                    <i class="bi bi-person me-1"></i>
                                                    👥 Apellidos *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Apellidos válidos
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Solo se permiten letras y espacios
                                            </div>
                                        </div>
                                        
                                        <!-- Sexo -->
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="sexo" name="sexo" required>
                                                    <option value="">Seleccionar...</option>
                                                    <option value="M">👨 Masculino</option>
                                                    <option value="F">👩 Femenino</option>
                                                </select>
                                                <label for="sexo" class="text-primary fw-semibold">
                                                    <i class="bi bi-gender-ambiguous me-1"></i>
                                                    ⚧️ Sexo *
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Nacionalidad -->
                                        <div class="col-md-4">
                                            <label for="nacionalidad" class="form-label text-primary fw-semibold">
                                                <i class="bi bi-globe me-1"></i>
                                                🌍 Nacionalidad *
                                            </label>
                                            <select class="form-select border-2" id="nacionalidad" name="nacionalidad" required>
                                                <option value="">🌎 Seleccionar nacionalidad...</option>
                                                <!-- Se llena dinámicamente -->
                                            </select>
                                            <input type="hidden" id="nacionalidad_hidden" name="nacionalidad_hidden">
                                            <small class="text-muted">
                                                <i class="bi bi-search me-1"></i>
                                                Busca escribiendo el nombre del país
                                            </small>
                                        </div>
                                        
                                        <!-- Estado -->
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="id_estado" name="id_estado">
                                                    <option value="1" selected>✅ Activo</option>
                                                    <option value="2">🚫 Bloqueado</option>
                                                    <option value="3">⏳ Pendiente</option>
                                                    <option value="4">❌ Inactivo</option>
                                                </select>
                                                <label for="id_estado" class="text-primary fw-semibold">
                                                    <i class="bi bi-toggle-on me-1"></i>
                                                    🔄 Estado
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Correo -->
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="email" class="form-control border-2" id="correo" name="correo" 
                                                    placeholder="Correo electrónico" required maxlength="255">
                                                <label for="correo" class="text-primary fw-semibold">
                                                    <i class="bi bi-envelope me-1"></i>
                                                    📧 Correo Electrónico *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Correo válido
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Ingrese un correo electrónico válido
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SECCIÓN 2: ASIGNACIÓN DE SUCURSAL (MODIFICADO) -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-dark" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-building me-2"></i>
                                        🏢 Asignación de Sucursal
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="alert alert-info border-0 shadow-sm">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <strong>📍 Instrucciones:</strong> Seleccione la sucursal donde trabajará el doctor.
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="id_sucursal" name="id_sucursal" required>
                                                    <option value="">🏥 Seleccionar sucursal...</option>
                                                    <?php foreach ($sucursales as $sucursal): ?>
                                                    <option value="<?= $sucursal['id_sucursal'] ?>">
                                                        🏢 <?= htmlspecialchars($sucursal['nombre_sucursal']) ?> - <?= htmlspecialchars($sucursal['direccion']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="id_sucursal" class="text-warning fw-semibold">
                                                    <i class="bi bi-building me-1"></i>
                                                    🏥 Sucursal de Trabajo *
                                                </label>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Primero seleccione la sucursal para cargar las especialidades disponibles
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: INFORMACIÓN MÉDICA (MODIFICADO) -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-journal-medical me-2"></i>
                                        🩺 Información Médica
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        
                                        <!-- Especialidad (se carga dinámicamente) -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="id_especialidad" name="id_especialidad" required disabled>
                                                    <option value="">🔄 Primero seleccione una sucursal...</option>
                                                </select>
                                                <label for="id_especialidad" class="text-info fw-semibold">
                                                    <i class="bi bi-journal-medical me-1"></i>
                                                    🎓 Especialidad Médica *
                                                </label>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-arrow-up me-1"></i>
                                                Las especialidades se cargarán según la sucursal seleccionada
                                            </small>
                                        </div>
                                        
                                        <!-- Título Profesional -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="titulo_profesional" name="titulo_profesional" 
                                                    placeholder="Título profesional" maxlength="100">
                                                <label for="titulo_profesional" class="text-info fw-semibold">
                                                    <i class="bi bi-mortarboard me-1"></i>
                                                    🎓 Título Profesional
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Título válido
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Solo letras, números y signos básicos
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Ej: MSc Cardiólogo, Dr. en Medicina, etc.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 4: CONFIGURACIÓN DE HORARIOS (SIMPLIFICADO) -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-clock-fill me-2"></i>
                                        🕒 Configuración de Horarios
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="alert alert-info border-0 shadow-sm">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <strong>⏰ Instrucciones:</strong> Configure los horarios de atención para la sucursal seleccionada. 
                                        Puede agregar múltiples turnos por día.
                                    </div>
                                    
                                    <div class="row g-3">
                                        <!-- Mensaje para seleccionar sucursal -->
                                        <div class="col-12" id="mensajeSucursalHorarios">
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-building display-1 text-warning mb-3"></i>
                                                <h5 class="text-muted">🏥 Seleccione una sucursal primero</h5>
                                                <p class="mb-0">Los horarios se configurarán para la sucursal seleccionada arriba</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Controles de horarios (ocultos inicialmente) -->
                                        <div class="col-12 d-none" id="controlesHorarios">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h6 class="mb-1 fw-bold text-purple">
                                                        <i class="bi bi-calendar-week me-1"></i>
                                                        📅 Horarios para: <span id="nombreSucursalSeleccionada" class="text-primary"></span>
                                                    </h6>
                                                    <small class="text-muted">Configure los días y horarios de atención</small>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm rounded-pill shadow-sm" id="btnAgregarHorario">
                                                    <i class="bi bi-plus-circle-fill me-1"></i>
                                                    ➕ Agregar Horario
                                                </button>
                                            </div>
                                            
                                            <!-- Container de horarios -->
                                            <div id="horariosContainer" class="border-2 rounded-3 p-4 bg-white shadow-sm" 
                                                 style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                                                <div class="text-center text-muted py-4" id="noHorariosMessage">
                                                    <i class="bi bi-clock-history display-1 text-purple mb-3"></i>
                                                    <h5 class="text-muted">⏰ No hay horarios configurados</h5>
                                                    <p class="mb-0">Haga clic en "Agregar Horario" para comenzar</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer mejorado -->
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-lg rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        ❌ Cancelar
                    </button>
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-4 shadow">
                        <i class="bi bi-save me-1"></i>
                        ✅ Registrar Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal para agregar/editar horario individual - DISEÑO MEJORADO -->
<div class="modal fade" id="modalHorario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header con gradiente dinámico -->
            <div class="modal-header border-0" id="headerModalHorario" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                <h5 class="modal-title text-white fw-bold d-flex align-items-center">
                    <div class="me-3">
                        <i class="bi bi-clock-history fs-3"></i>
                    </div>
                    <div>
                        <span id="tituloModalHorario">⏰ Agregar Horario</span>
                        <br>
                        <small class="opacity-75 fw-normal">Configure los horarios de atención</small>
                    </div>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-4" style="background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);">
                <form id="formHorario">
                    <input type="hidden" id="horarioIndex" value="">
                    
                    <!-- Información contextual -->
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
                            <div>
                                <strong>💡 Instrucciones:</strong> Configure el día y horario de atención. 
                                Asegúrese de que no se solape con otros horarios existentes.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Día de la semana -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-calendar-week me-2"></i>
                                        📅 Selección de Día
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="form-floating">
                                        <select class="form-select border-2" id="diaSemana" required>
                                            <option value="">🗓️ Seleccionar día de la semana...</option>
                                            <option value="1">🌅 Lunes</option>
                                            <option value="2">🌄 Martes</option>
                                            <option value="3">🌆 Miércoles</option>
                                            <option value="4">🌇 Jueves</option>
                                            <option value="5">🌉 Viernes</option>
                                            <option value="6">🌌 Sábado</option>
                                            <option value="7">🌠 Domingo</option>
                                        </select>
                                        <label for="diaSemana" class="text-primary fw-semibold">
                                            <i class="bi bi-calendar-week me-1"></i>
                                            Día de la Semana *
                                        </label>
                                    </div>
                                    <div class="valid-feedback">
                                        <i class="bi bi-check-circle-fill me-1"></i>✅ Día seleccionado
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-x-circle-fill me-1"></i>❌ Debe seleccionar un día
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Horarios -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-clock me-2"></i>
                                        🕐 Configuración de Horarios
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="time" class="form-control border-2" id="horaInicio" required>
                                                <label for="horaInicio" class="text-info fw-semibold">
                                                    <i class="bi bi-clock me-1"></i>
                                                    🕐 Hora de Inicio *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Hora válida
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Hora de inicio requerida
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Ej: 08:00, 14:00, 20:00
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="time" class="form-control border-2" id="horaFin" required>
                                                <label for="horaFin" class="text-info fw-semibold">
                                                    <i class="bi bi-clock-fill me-1"></i>
                                                    🕕 Hora de Fin *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Hora válida
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Hora de fin debe ser mayor que la de inicio
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Debe ser mayor que la hora de inicio
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Vista previa del horario -->
                                    <div class="mt-3">
                                        <div class="alert alert-light border border-info" id="previsualizacionHorario" style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-eye fs-4 me-3 text-info"></i>
                                                <div>
                                                    <strong>👀 Vista Previa:</strong>
                                                    <span id="textoPreview"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuración adicional -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-gear me-2"></i>
                                        ⚙️ Configuración de Citas
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="duracionCita">
                                                    <option value="30" selected>⭐ 30 minutos (Consulta estándar)</option>
                                                </select>
                                                <label for="duracionCita" class="text-success fw-semibold">
                                                    <i class="bi bi-stopwatch me-1"></i>
                                                    ⏱️ Duración por Cita
                                                </label>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Tiempo estimado para cada cita médica
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="bg-white rounded-3 p-3 border border-success shadow-sm">
                                                <div class="text-center">
                                                    <i class="bi bi-calculator fs-2 text-success mb-2"></i>
                                                    <div class="fw-bold text-success">Citas Estimadas</div>
                                                    <div class="fs-4 fw-bold text-primary" id="citasEstimadas">0</div>
                                                    <small class="text-muted">por este horario</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer mejorado -->
            <div class="modal-footer bg-light border-0 p-4">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <div class="text-muted">
                        <i class="bi bi-shield-check me-1"></i>
                        <small>Los horarios se validarán automáticamente</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary btn-lg rounded-pill px-4 me-2" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            ❌ Cancelar
                        </button>
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 shadow" id="btnGuardarHorario">
                            <i class="bi bi-save me-1"></i>
                            <span id="textoBotonGuardar">✅ Guardar Horario</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Modal Editar Doctor - DISEÑO MEJORADO Y CORREGIDO -->
<div class="modal fade" id="editarDoctorModal" tabindex="-1" aria-labelledby="editarDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header con gradiente -->
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                <h5 class="modal-title text-black fw-bold" id="editarDoctorModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    ✏️ Editar Doctor
                </h5>
                <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <form id="formEditarDoctor">
                <input type="hidden" id="editarIdDoctor" name="id_doctor">
                
                <div class="modal-body" style="background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);">
                    <div class="row g-4">
                        
                        <!-- SECCIÓN 1: INFORMACIÓN PERSONAL -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-person-fill me-2"></i>
                                        👤 Información Personal
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        
                                        <!-- Cédula -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="editarCedula" name="cedula" 
                                                    placeholder="Cédula" required maxlength="15" readonly>
                                                <label for="editarCedula" class="text-primary fw-semibold">
                                                    <i class="bi bi-card-text me-1"></i>
                                                    🆔 Cédula de Identidad *
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-lock me-1"></i>
                                                La cédula no se puede modificar
                                            </small>
                                        </div>

                                        <!-- Username -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="editarUsername" name="username" 
                                                    placeholder="Username" required maxlength="50">
                                                <label for="editarUsername" class="text-primary fw-semibold">
                                                    <i class="bi bi-person-badge me-1"></i>
                                                    👨‍💼 Nombre de Usuario *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Username disponible
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Username no válido o ya existe
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Solo letras, números y guiones bajos. Mínimo 3 caracteres.
                                            </small>
                                        </div>

                                        <!-- Nombres -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="editarNombres" name="nombres" 
                                                    placeholder="Nombres" required maxlength="100">
                                                <label for="editarNombres" class="text-primary fw-semibold">
                                                    <i class="bi bi-person me-1"></i>
                                                    👤 Nombres *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Nombres válidos
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Solo se permiten letras y espacios
                                            </div>
                                        </div>
                                        
                                        <!-- Apellidos -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="editarApellidos" name="apellidos" 
                                                    placeholder="Apellidos" required maxlength="100">
                                                <label for="editarApellidos" class="text-primary fw-semibold">
                                                    <i class="bi bi-person me-1"></i>
                                                    👥 Apellidos *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Apellidos válidos
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Solo se permiten letras y espacios
                                            </div>
                                        </div>
                                        
                                        <!-- Sexo -->
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="editarSexo" name="sexo" required>
                                                    <option value="">Seleccionar...</option>
                                                    <option value="M">👨 Masculino</option>
                                                    <option value="F">👩 Femenino</option>
                                                </select>
                                                <label for="editarSexo" class="text-primary fw-semibold">
                                                    <i class="bi bi-gender-ambiguous me-1"></i>
                                                    ⚧️ Sexo *
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Nacionalidad -->
                                        <div class="col-md-4">
                                            <label for="editarNacionalidad" class="form-label text-primary fw-semibold">
                                                <i class="bi bi-globe me-1"></i>
                                                🌍 Nacionalidad *
                                            </label>
                                            <select class="form-select border-2" id="editarNacionalidad" name="nacionalidad" required>
                                                <option value="">🌎 Seleccionar nacionalidad...</option>
                                                <!-- Se llena dinámicamente -->
                                            </select>
                                            <input type="hidden" id="editarNacionalidad_hidden" name="nacionalidad_hidden">
                                            <small class="text-muted">
                                                <i class="bi bi-search me-1"></i>
                                                Busca escribiendo el nombre del país
                                            </small>
                                        </div>
                                        
                                        <!-- Estado -->
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="editarIdEstado" name="id_estado">
                                                    <option value="1">✅ Activo</option>
                                                    <option value="2">🚫 Bloqueado</option>
                                                    <option value="3">⏳ Pendiente</option>
                                                    <option value="4">❌ Inactivo</option>
                                                </select>
                                                <label for="editarIdEstado" class="text-primary fw-semibold">
                                                    <i class="bi bi-toggle-on me-1"></i>
                                                    🔄 Estado
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Correo -->
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="email" class="form-control border-2" id="editarCorreo" name="correo" 
                                                    placeholder="Correo electrónico" required maxlength="255">
                                                <label for="editarCorreo" class="text-primary fw-semibold">
                                                    <i class="bi bi-envelope me-1"></i>
                                                    📧 Correo Electrónico *
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Correo válido
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Ingrese un correo electrónico válido
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SECCIÓN 2: ASIGNACIÓN DE SUCURSAL (MODIFICADO) -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-dark" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-building me-2"></i>
                                        🏢 Asignación de Sucursal
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="alert alert-info border-0 shadow-sm">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <strong>📍 Instrucciones:</strong> Seleccione la sucursal donde trabajará el doctor.
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="editarIdSucursal" name="id_sucursal" required>
                                                    <option value="">🏥 Seleccionar sucursal...</option>
                                                    <?php foreach ($sucursales as $sucursal): ?>
                                                    <option value="<?= $sucursal['id_sucursal'] ?>">
                                                        🏢 <?= htmlspecialchars($sucursal['nombre_sucursal']) ?> - <?= htmlspecialchars($sucursal['direccion']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="editarIdSucursal" class="text-warning fw-semibold">
                                                    <i class="bi bi-building me-1"></i>
                                                    🏥 Sucursal de Trabajo *
                                                </label>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Primero seleccione la sucursal para cargar las especialidades disponibles
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: INFORMACIÓN MÉDICA (MODIFICADO) -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-journal-medical me-2"></i>
                                        🩺 Información Médica
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        
                                        <!-- Especialidad (se carga dinámicamente) -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select border-2" id="editarIdEspecialidad" name="id_especialidad" required disabled>
                                                    <option value="">🔄 Primero seleccione una sucursal...</option>
                                                </select>
                                                <label for="editarIdEspecialidad" class="text-info fw-semibold">
                                                    <i class="bi bi-journal-medical me-1"></i>
                                                    🎓 Especialidad Médica *
                                                </label>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="bi bi-arrow-up me-1"></i>
                                                Las especialidades se cargarán según la sucursal seleccionada
                                            </small>
                                        </div>
                                        
                                        <!-- Título Profesional -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control border-2" id="editarTituloProfesional" name="titulo_profesional" 
                                                    placeholder="Título profesional" maxlength="100">
                                                <label for="editarTituloProfesional" class="text-info fw-semibold">
                                                    <i class="bi bi-mortarboard me-1"></i>
                                                    🎓 Título Profesional
                                                </label>
                                            </div>
                                            <div class="valid-feedback">
                                                <i class="bi bi-check-circle-fill me-1"></i>✅ Título válido
                                            </div>
                                            <div class="invalid-feedback">
                                                <i class="bi bi-x-circle-fill me-1"></i>❌ Solo letras, números y signos básicos
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Ej: MSc Cardiólogo, Dr. en Medicina, etc.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 4: CONFIGURACIÓN DE HORARIOS (SIMPLIFICADO) -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-clock-fill me-2"></i>
                                        🕒 Configuración de Horarios
                                        <button type="button" class="btn btn-outline-light btn-sm ms-2 rounded-pill" id="btnRecargarHorarios">
                                            <i class="bi bi-arrow-clockwise me-1"></i>
                                            🔄 Recargar
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="alert alert-info border-0 shadow-sm">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <strong>⏰ Instrucciones:</strong> Configure los horarios de atención para la sucursal seleccionada. 
                                        Puede agregar múltiples turnos por día.
                                    </div>
                                    
                                    <div class="row g-3">
                                        <!-- Mensaje para seleccionar sucursal -->
                                        <div class="col-12" id="editarMensajeSucursalHorarios">
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-building display-1 text-warning mb-3"></i>
                                                <h5 class="text-muted">🏥 Seleccione una sucursal primero</h5>
                                                <p class="mb-0">Los horarios se configurarán para la sucursal seleccionada arriba</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Controles de horarios (ocultos inicialmente) -->
                                        <div class="col-12 d-none" id="editarControlesHorarios">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h6 class="mb-1 fw-bold text-purple">
                                                        <i class="bi bi-calendar-week me-1"></i>
                                                        📅 Horarios para: <span id="editarNombreSucursalSeleccionada" class="text-primary"></span>
                                                    </h6>
                                                    <small class="text-muted">Configure los días y horarios de atención</small>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm rounded-pill shadow-sm" id="btnAgregarHorarioEditar">
                                                    <i class="bi bi-plus-circle-fill me-1"></i>
                                                    ➕ Agregar Horario
                                                </button>
                                            </div>
                                            
                                            <!-- Container de horarios -->
                                            <div id="editarHorariosContainer" class="border-2 rounded-3 p-4 bg-white shadow-sm" 
                                                 style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                                                <div class="text-center text-muted py-4" id="editarNoHorariosMessage">
                                                    <i class="bi bi-clock-history display-1 text-purple mb-3"></i>
                                                    <h5 class="text-muted">⏰ No hay horarios configurados</h5>
                                                    <p class="mb-0">Haga clic en "Agregar Horario" para comenzar</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer mejorado -->
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-lg rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        ❌ Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 shadow">
                        <i class="bi bi-save me-1"></i>
                        ✅ Actualizar Doctor
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

      <script src="../../js/horarios_doctores.js"></script>
      <script>
$(document).ready(function() {
    // ===== ACTUALIZAR VISTA PREVIA Y CÁLCULOS =====
    function actualizarPreview() {
        const dia = $('#diaSemana option:selected').text();
        const horaInicio = $('#horaInicio').val();
        const horaFin = $('#horaFin').val();
        const duracion = $('#duracionCita').val();
        
        if (dia && dia !== '🗓️ Seleccionar día de la semana...' && horaInicio && horaFin) {
            const preview = `${dia} de ${horaInicio} a ${horaFin} (${duracion} min/cita)`;
            $('#textoPreview').text(preview);
            $('#previsualizacionHorario').show();
            
            // Calcular citas estimadas
            if (horaInicio < horaFin) {
                const inicio = new Date(`2000-01-01 ${horaInicio}`);
                const fin = new Date(`2000-01-01 ${horaFin}`);
                const minutosTotales = (fin - inicio) / (1000 * 60);
                const citasEstimadas = Math.floor(minutosTotales / parseInt(duracion));
                $('#citasEstimadas').text(citasEstimadas);
            }
        } else {
            $('#previsualizacionHorario').hide();
            $('#citasEstimadas').text('0');
        }
    }
    
    // Event listeners para la vista previa
    $('#diaSemana, #horaInicio, #horaFin, #duracionCita').on('change input', actualizarPreview);
    
    // ===== CAMBIAR TÍTULO DEL MODAL DINÁMICAMENTE =====
    $('#modalHorario').on('show.bs.modal', function() {
        const esEdicion = horarioEditando !== null;
        const titulo = esEdicion ? '✏️ Editar Horario' : '➕ Agregar Horario';
        const subtitulo = esEdicion ? 'Modifique los datos del horario existente' : 'Configure los horarios de atención';
        const headerColor = esEdicion ? 'linear-gradient(135deg, #fd7e14 0%, #ffc107 100%)' : 'linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%)';
        
        $('#tituloModalHorario').text(titulo);
        $('#tituloModalHorario').next('br').next('small').text(subtitulo);
        $('#headerModalHorario').attr('style', `background: ${headerColor};`);
        $('#textoBotonGuardar').text(esEdicion ? '✅ Actualizar Horario' : '✅ Guardar Horario');
    });
    
    // ===== LIMPIAR AL CERRAR =====
    $('#modalHorario').on('hidden.bs.modal', function() {
        $('#previsualizacionHorario').hide();
        $('#citasEstimadas').text('0');
    });
});
</script>

</body>
</html>