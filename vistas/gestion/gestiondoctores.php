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
<!-- Modal para agregar/editar horario individual -->
<div class="modal fade" id="modalHorario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clock me-2"></i>
                    <span id="tituloModalHorario">Agregar Horario</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formHorario">
                    <input type="hidden" id="horarioIndex" value="">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="bi bi-calendar-week me-1"></i>
                                Día de la semana *
                            </label>
                            <select class="form-select" id="diaSemana" required>
                                <option value="">Seleccionar día...</option>
                                <option value="1">Lunes</option>
                                <option value="2">Martes</option>
                                <option value="3">Miércoles</option>
                                <option value="4">Jueves</option>
                                <option value="5">Viernes</option>
                                <option value="6">Sábado</option>
                                <option value="7">Domingo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-clock me-1"></i>
                                Hora de inicio *
                            </label>
                            <input type="time" class="form-control" id="horaInicio" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-clock-fill me-1"></i>
                                Hora de fin *
                            </label>
                            <input type="time" class="form-control" id="horaFin" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="bi bi-stopwatch me-1"></i>
                                Duración por cita (minutos)
                            </label>
                            <select class="form-select" id="duracionCita">
                                <option value="30" selected>30 minutos</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarHorario">
                    <i class="bi bi-save me-1"></i>
                    Guardar Horario
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Editar Doctor CON GESTIÓN DE HORARIOS -->
<div class="modal fade" id="editarDoctorModal" tabindex="-1" aria-labelledby="editarDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editarDoctorModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Doctor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formEditarDoctor">
                <input type="hidden" id="editarIdDoctor" name="id_doctor">
                
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- INFORMACIÓN PERSONAL -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person me-2"></i>
                                Información Personal
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="editarCedula" name="cedula" 
                                       placeholder="Cédula" required maxlength="10" readonly>
                                <label for="editarCedula">
                                    <i class="bi bi-card-text me-1"></i>
                                    Cédula *
                                </label>
                            </div>
                            <small class="text-muted">La cédula no se puede modificar</small>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editarUsername" name="username" 
                                       placeholder="Username" required maxlength="50">
                                <label for="editarUsername">
                                    <i class="bi bi-person-badge me-1"></i>
                                    Nombre de Usuario *
                                </label>
                            </div>
                            <div id="editarUsernameFeedback" class="mt-1"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editarNombres" name="nombres" 
                                       placeholder="Nombres" required>
                                <label for="editarNombres">
                                    <i class="bi bi-person me-1"></i>
                                    Nombres *
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editarApellidos" name="apellidos" 
                                       placeholder="Apellidos" required>
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
                            <label for="editarNacionalidad" class="form-label">
                                <i class="bi bi-globe me-1"></i>
                                Nacionalidad *
                            </label>
                            <select class="form-select" id="editarNacionalidad" name="nacionalidad" required>
                                <option value="">Seleccionar nacionalidad...</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="editarIdEstado" name="id_estado" required>
                                    <option value="1">Activo</option>
                                    <option value="2">Bloqueado</option>
                                    <option value="3">Pendiente</option>
                                    <option value="4">Inactivo</option>
                                </select>
                                <label for="editarIdEstado">
                                    <i class="bi bi-toggle-on me-1"></i>
                                    Estado *
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="editarCorreo" name="correo" 
                                       placeholder="Correo" required>
                                <label for="editarCorreo">
                                    <i class="bi bi-envelope me-1"></i>
                                    Correo Electrónico *
                                </label>
                            </div>
                        </div>

                        <!-- INFORMACIÓN MÉDICA -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3 mt-3">
                                <i class="bi bi-heart-pulse me-2"></i>
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
                                    <i class="bi bi-heart-pulse me-1"></i>
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

                        <!-- ASIGNACIÓN DE SUCURSALES -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-building me-2"></i>
                                Asignación de Sucursales
                            </h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="row" id="sucursalesEditar">
                                <?php foreach ($sucursales as $sucursal): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               value="<?= $sucursal['id_sucursal'] ?>" 
                                               id="editar_sucursal_<?= $sucursal['id_sucursal'] ?>"
                                               name="sucursales[]">
                                        <label class="form-check-label" for="editar_sucursal_<?= $sucursal['id_sucursal'] ?>">
                                            <strong><?= htmlspecialchars($sucursal['nombre_sucursal']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($sucursal['direccion']) ?></small>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 🕒 GESTIÓN DE HORARIOS -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-clock me-2"></i>
                                Gestión de Horarios
                                <button type="button" class="btn btn-outline-primary btn-sm ms-2" id="btnRecargarHorarios">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Recargar
                                </button>
                            </h6>
                        </div>
                        
                        <!-- Selector de sucursal para horarios -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-building me-1"></i>
                                Sucursal para horarios:
                            </label>
                            <select class="form-select" id="editarSucursalHorarios">
                                <option value="">Seleccione una sucursal...</option>
                            </select>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">Horarios configurados:</label>
                                <button type="button" class="btn btn-primary btn-sm" id="btnAgregarHorarioEditar">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Agregar Horario
                                </button>
                            </div>
                        </div>
                        
                        <!-- Container de horarios -->
                        <div class="col-12">
                            <div id="editarHorariosContainer" class="border rounded p-3" style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history display-4 d-block mb-2"></i>
                                    <p>Seleccione una sucursal para ver los horarios</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>
                        Actualizar Doctor
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

</body>
</html>