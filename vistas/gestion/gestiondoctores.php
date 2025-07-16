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
                            
                            <!-- En la sección de cédula del modal crear doctor -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="cedula" name="cedula" 
                                            placeholder="Cédula" required maxlength="10" min="1000000000" max="9999999999">
                                        <label for="cedula">
                                            <i class="bi bi-card-text me-1"></i>
                                            Cédula *
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnBuscarCedulaDoctor">
                                            <i class="bi bi-search me-1"></i>
                                            Buscar datos
                                        </button>
                                    </div>
                                </div>

                                    <!-- Actualizar el campo de username para mostrar feedback -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="username" name="username" 
                                                placeholder="Username" required maxlength="50">
                                            <label for="username">
                                                <i class="bi bi-person-badge me-1"></i>
                                                Nombre de Usuario *
                                            </label>
                                        </div>
                                        <div id="usernameFeedback" class="form-text"></div>
                                    </div>

                                    <!-- Actualizar el campo de nacionalidad -->
                                                                    <!-- Campo de nacionalidad con Select2 -->
                                    <div class="col-md-6">
                                        <label for="nacionalidad" class="form-label">
                                            <i class="bi bi-globe me-1"></i>
                                            Nacionalidad *
                                        </label>
                                        <select class="form-select" id="nacionalidad" name="nacionalidad" required 
                                                data-bs-theme="bootstrap-5">
                                            <option value="">Seleccionar nacionalidad...</option>
                                            <!-- Se llena dinámicamente -->
                                        </select>
                                        
                                        <!-- 🔥 NUEVO: Input hidden para cuando el select esté disabled -->
                                        <input type="hidden" id="nacionalidad_hidden" name="nacionalidad_hidden">
                                        
                                        <div class="form-text">
                                            <i class="bi bi-search me-1"></i>
                                            Puedes buscar escribiendo el nombre del país o nacionalidad
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
                            
                            <!-- ASIGNACIÓN DE SUCURSALES -->
                        <div class="col-12">
                            <h6 class="text-success border-bottom pb-2 mb-3">
                                <i class="bi bi-building me-2"></i>
                                Asignación de Sucursales
                            </h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="row" id="sucursalesCrear">
                                <?php foreach ($sucursales as $sucursal): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               value="<?= $sucursal['id_sucursal'] ?>" 
                                               id="sucursal_<?= $sucursal['id_sucursal'] ?>"
                                               name="sucursales[]">
                                        <label class="form-check-label" for="sucursal_<?= $sucursal['id_sucursal'] ?>">
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
                            <h6 class="text-success border-bottom pb-2 mb-3">
                                <i class="bi bi-clock me-2"></i>
                                Configuración de Horarios
                            </h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Instrucciones:</strong> Configure los horarios de atención por sucursal y día de la semana. 
                                Puede agregar múltiples turnos por día.
                            </div>
                        </div>
                        
                        <!-- Selector de sucursal para horarios -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-building me-1"></i>
                                Sucursal para horarios:
                            </label>
                            <select class="form-select" id="sucursalHorarios">
                                <option value="">Seleccione una sucursal...</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= $sucursal['id_sucursal'] ?>">
                                    <?= htmlspecialchars($sucursal['nombre_sucursal']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Primero marque las sucursales arriba</small>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">Horarios configurados:</label>
                                <button type="button" class="btn btn-primary btn-sm" id="btnAgregarHorario">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Agregar Horario
                                </button>
                            </div>
                        </div>
                        
                        <!-- Container de horarios -->
                        <div class="col-12">
                            <div id="horariosContainer" class="border rounded p-3" style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-muted py-4" id="noHorariosMessage">
                                    <i class="bi bi-clock-history display-4 d-block mb-2"></i>
                                    <p>No hay horarios configurados</p>
                                    <small>Seleccione una sucursal y agregue horarios</small>
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
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>
                        Registrar Doctor
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