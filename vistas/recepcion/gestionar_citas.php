<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($sucursales)) {
    require_once __DIR__ . '/../../controladores/RecepcionistaControlador/RecepcionistaController.php';
    $controller = new RecepcionistaController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Gestionar Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    
    <link rel="stylesheet" href="../../estilos/recepcionista/gestioncitas.css">
    
</head>
<body>
<?php include __DIR__ . "/../../navbars/header.php"; ?>
<?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="bi bi-calendar-check text-primary me-2"></i>Gestión de Citas Médicas</h2>
                <div class="btn-group">
                    <?php if($permisos['puede_crear']): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">
                        <i class="bi bi-plus-circle me-1"></i>Nueva Cita
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-outline-primary" id="btnVistaDia">
                        <i class="bi bi-calendar-day me-1"></i>Día
                    </button>
                    <button class="btn btn-outline-primary" id="btnVistaSemana">
                        <i class="bi bi-calendar-week me-1"></i>Semana
                    </button>
                    <button class="btn btn-outline-primary" id="btnVistaMes">
                        <i class="bi bi-calendar-month me-1"></i>Mes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Citas Hoy</h6>
                            <h3 id="citasHoy">-</h3>
                        </div>
                        <i class="bi bi-calendar-event fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pendientes</h6>
                            <h3 id="citasPendientes">-</h3>
                        </div>
                        <i class="bi bi-clock fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Confirmadas</h6>
                            <h3 id="citasConfirmadas">-</h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Presenciales</h6>
                            <h3 id="citasPresenciales">-</h3>
                        </div>
                        <i class="bi bi-building fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Virtuales</h6>
                            <h3 id="citasVirtuales">-</h3>
                        </div>
                        <i class="bi bi-camera-video fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pacientes Nuevos</h6>
                            <h3 id="pacientesNuevos">-</h3>
                        </div>
                        <i class="bi bi-person-plus fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filtros Lateral -->
<div class="col-md-3">
    <!-- Panel de Filtros -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="bi bi-funnel-fill me-2"></i>Filtros de Búsqueda
            </h6>
        </div>
        <div class="card-body">
            <form id="formFiltros">
                <!-- Filtro por Sucursal -->
                <div class="mb-3">
                    <label for="filtroSucursal" class="form-label fw-semibold">
                        <i class="bi bi-building me-1 text-primary"></i>Sucursal
                    </label>
                    <select class="form-select" id="filtroSucursal" name="sucursal">
                        <option value="">📍 Todas las sucursales</option>
                        <?php foreach($sucursales as $sucursal): ?>
                        <option value="<?= $sucursal['id_sucursal'] ?>">
                            <?= htmlspecialchars($sucursal['nombre_sucursal']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por Tipo de Cita -->
                <div class="mb-3">
                    <label for="filtroTipoCita" class="form-label fw-semibold">
                        <i class="bi bi-type me-1 text-success"></i>Tipo de Cita
                    </label>
                    <select class="form-select" id="filtroTipoCita" name="tipo_cita">
                        <option value="">💊 Todos los tipos</option>
                        <option value="presencial">🏢 Presencial</option>
                        <option value="virtual">💻 Virtual</option>
                    </select>
                </div>

                <!-- Filtro por Especialidad -->
                <div class="mb-3">
                    <label for="filtroEspecialidad" class="form-label fw-semibold">
                        <i class="bi bi-heart-pulse me-1 text-info"></i>Especialidad
                    </label>
                    <select class="form-select" id="filtroEspecialidad" name="especialidad">
                        <option value="">🏥 Todas las especialidades</option>
                        <?php foreach($especialidades as $especialidad): ?>
                        <option value="<?= $especialidad['id_especialidad'] ?>">
                            <?= htmlspecialchars($especialidad['nombre_especialidad']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por Estado -->
                <div class="mb-3">
                    <label for="filtroEstado" class="form-label fw-semibold">
                        <i class="bi bi-check-circle me-1 text-warning"></i>Estado
                    </label>
                    <select class="form-select" id="filtroEstado" name="estado">
                        <option value="">📋 Todos los estados</option>
                        <option value="Pendiente">⏳ Pendiente</option>
                        <option value="Confirmada">✅ Confirmada</option>
                        <option value="Completada">🎯 Completada</option>
                        <option value="Cancelada">❌ Cancelada</option>
                    </select>
                </div>

                <!-- Filtro por Doctor -->
                <div class="mb-3">
                    <label for="filtroDoctor" class="form-label fw-semibold">
                        <i class="bi bi-person-badge me-1 text-secondary"></i>Doctor
                    </label>
                    <select class="form-select" id="filtroDoctor" name="doctor">
                        <option value="">👨‍⚕️ Todos los doctores</option>
                        <!-- Se carga dinámicamente -->
                    </select>
                </div>

                <!-- Botones de Control -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="btnAplicarFiltros">
                        <i class="bi bi-search me-1"></i>Aplicar Filtros
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                        <i class="bi bi-eraser me-1"></i>Limpiar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>


            <!-- Leyenda de Estados -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Leyenda</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-warning" style="width: 20px; height: 15px; border-radius: 3px;"></div>
                        <span class="ms-2 small">Pendiente</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-success" style="width: 20px; height: 15px; border-radius: 3px;"></div>
                        <span class="ms-2 small">Confirmada</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-info" style="width: 20px; height: 15px; border-radius: 3px;"></div>
                        <span class="ms-2 small">Completada</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-danger" style="width: 20px; height: 15px; border-radius: 3px;"></div>
                        <span class="ms-2 small">Cancelada</span>
                    </div>
                    <hr>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-building text-primary"></i>
                        <span class="ms-2 small">Presencial</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-camera-video text-secondary"></i>
                        <span class="ms-2 small">Virtual</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendario Principal -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Calendario de Citas</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" id="btnHoy">Hoy</button>
                        <button class="btn btn-outline-secondary" id="btnAnterior">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="btn btn-outline-secondary" id="btnSiguiente">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body calendario-container">
                    <div id="calendario"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Agenda del Día -->
    <div class="row mt-3" id="panelAgendaDia" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-day me-2"></i>Agenda del Día: 
                        <span id="fechaSeleccionada" class="text-primary"></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="citasDelDia" class="agenda-doctor">
                                <!-- Las citas del día se cargarán aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Cita con Wizard de Pasos -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Registrar Nueva Cita Médica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Wizard de Pasos -->
                <div class="step-wizard">
                    <div class="step-item active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-title">Tipo de Cita</div>
                    </div>
                    <div class="step-item" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-title">Paciente</div>
                    </div>
                    <div class="step-item" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-title">Ubicación</div>
                    </div>
                    <div class="step-item" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-title">Doctor & Horario</div>
                    </div>
                    <div class="step-item" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-title">Detalles</div>
                    </div>
                    <div class="step-item" data-step="6">
                        <div class="step-number">6</div>
                        <div class="step-title">Confirmación</div>
                    </div>
                </div>

                <form id="formNuevaCita">
                    <!-- PASO 1: Seleccionar Tipo de Cita -->
                    <div class="step-content active" id="step1">
                        <div class="text-center mb-4">
                            <h4>¿Qué tipo de cita desea programar?</h4>
                            <p class="text-muted">Seleccione el tipo de cita que mejor se adapte a las necesidades del paciente</p>
                        </div>
                        
                        <div class="row g-4 justify-content-center">
                            <div class="col-md-5">
                                <div class="card tipo-cita-card" data-tipo="1" data-nombre="presencial">
                                    <div class="card-body text-center p-4">
                                        <i class="bi bi-building text-primary tipo-cita-icon"></i>
                                        <h4 class="card-title text-primary">Cita Presencial</h4>
                                        <p class="card-text">
                                            El paciente acude físicamente al consultorio o centro médico para la consulta.
                                        </p>
                                        <ul class="list-unstyled text-start mt-3">
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Examen físico completo</li>
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Procedimientos médicos</li>
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Uso de equipos especializados</li>
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Interacción directa</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <div class="card tipo-cita-card" data-tipo="2" data-nombre="virtual">
                                    <div class="card-body text-center p-4">
                                        <i class="bi bi-camera-video text-info tipo-cita-icon"></i>
                                        <h4 class="card-title text-info">Cita Virtual</h4>
                                        <p class="card-text">
                                            Consulta médica por videollamada desde la comodidad del hogar del paciente.
                                        </p>
                                        <ul class="list-unstyled text-start mt-3">
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Consultas de seguimiento</li>
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Revisión de resultados</li>
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Consultas psicológicas</li>
                                            <li><i class="bi bi-check-circle text-success me-2"></i>Ahorro de tiempo y traslado</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="tipoCitaSeleccionado" name="id_tipo_cita">
                        <input type="hidden" id="tipoConsulta" name="tipo_cita">
                    </div>

                    <!-- PASO 2: Buscar/Registrar Paciente -->
                        <div class="step-content" id="step2">
                            <div class="text-center mb-4">
                                <h4>Identificar Paciente</h4>
                                <p class="text-muted">Busque al paciente por su cédula</p>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Cédula del Paciente</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                                <input type="text" class="form-control" id="cedulaPaciente" 
                                                    placeholder="Ingrese número de cédula" required>
                                                <button type="button" class="btn btn-primary" id="btnBuscarPaciente">
                                                    <i class="bi bi-search me-1"></i>Buscar
                                                </button>
                                            </div>
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Si el paciente no está registrado, se abrirá automáticamente el formulario de registro.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <div id="infoPaciente" class="alert alert-success d-none">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                                                <div>
                                                    <strong>✅ Paciente encontrado</strong>
                                                    <div id="datosPaciente" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="pacienteNoEncontrado" class="alert alert-info d-none">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-plus-fill me-2 fs-4"></i>
                                                <div>
                                                    <strong>🔄 Paciente no encontrado</strong>
                                                    <p class="mb-0 mt-1">Se abrirá el formulario de registro para crear un nuevo paciente...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="idPacienteSeleccionado" name="id_paciente">
                                </div>
                            </div>
                        </div>

                    <!-- PASO 3: Seleccionar Ubicación (Sucursal + Especialidad) -->
                    <div class="step-content" id="step3">
                        <div class="text-center mb-4">
                            <h4>Seleccionar Ubicación y Especialidad</h4>
                            <p class="text-muted">Elija la sucursal y especialidad médica para la cita</p>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-building me-2"></i>Sucursal</h6>
                                    </div>
                                    <div class="card-body">
                                        <select class="form-select form-select-lg" id="sucursalCita" name="id_sucursal" required>
                                            <option value="">Seleccione una sucursal</option>
                                            <?php foreach($sucursales as $sucursal): ?>
                                            <option value="<?= $sucursal['id_sucursal'] ?>" 
                                                    data-direccion="<?= htmlspecialchars($sucursal['direccion']) ?>"
                                                    data-telefono="<?= htmlspecialchars($sucursal['telefono']) ?>">
                                                <?= htmlspecialchars($sucursal['nombre_sucursal']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="infoSucursal" class="mt-3 d-none">
                                            <div class="alert alert-info">
                                                <div id="detallesSucursal"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Especialidad</h6>
                                    </div>
                                    <div class="card-body">
                                        <select class="form-select form-select-lg" id="especialidadCita" name="id_especialidad" required disabled>
                                            <option value="">Primero seleccione sucursal</option>
                                        </select>
                                        <div id="infoEspecialidad" class="mt-3 d-none">
                                            <div class="alert alert-info">
                                                <div id="detallesEspecialidad"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos adicionales para citas virtuales -->
                        <div class="campos-virtuales mt-4" id="camposVirtuales">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bi bi-camera-video me-2"></i>Configuración para Cita Virtual</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Plataforma de Videollamada</label>
                                            <select class="form-select" id="plataformaVirtual" name="plataforma_virtual">
                                                <option value="">Seleccione plataforma</option>
                                                <option value="zoom">Zoom</option>
                                                <option value="meet">Google Meet</option>
                                                <option value="teams">Microsoft Teams</option>
                                                <option value="whatsapp">WhatsApp Video</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">ID de Sala Virtual (opcional)</label>
                                            <input type="text" class="form-control" id="salaVirtual" name="sala_virtual" 
                                                   placeholder="Ej: 123-456-789">
                                       </div>
                                       <div class="col-12">
                                           <label class="form-label">Enlace de Videollamada (se puede agregar después)</label>
                                           <input type="url" class="form-control" id="enlaceVirtual" name="enlace_virtual" 
                                                  placeholder="https://zoom.us/j/123456789 o se generará automáticamente">
                                       </div>
                                       <div class="col-12">
                                           <div class="alert alert-info">
                                               <i class="bi bi-info-circle me-2"></i>
                                               <strong>Nota:</strong> El enlace de la videollamada se enviará al paciente por email 
                                               y SMS 24 horas antes de la cita.
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>

                   <!-- PASO 4: Seleccionar Doctor y Horario -->
                        <div class="step-content" id="step4">
                            <div class="text-center mb-4">
                                <h4>Seleccionar Doctor y Horario</h4>
                                <p class="text-muted">Elija el médico especialista y el horario disponible</p>
                            </div>

                            <div class="row g-4">
                                <!-- Selector de Doctor -->
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Doctor</h6>
                                        </div>
                                        <div class="card-body">
                                            <select class="form-select form-select-lg" id="doctorCita" name="id_doctor" required disabled>
                                                <option value="">Seleccione especialidad primero</option>
                                            </select>
                                            <div id="infoDoctor" class="mt-3 d-none">
                                                <div class="alert alert-info">
                                                    <div id="detallesDoctor"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selector de Fecha -->
                                <div class="col-md-8">
                                    <div class="card h-100">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="bi bi-calendar-date me-2"></i>Seleccionar Fecha y Hora</h6>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" id="btnSemanaAnterior">
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" id="btnSemanaActual">Hoy</button>
                                                <button type="button" class="btn btn-outline-secondary" id="btnSemanaSiguiente">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            <!-- Mini Calendario Semanal -->
                                            <div id="calendarioHorarios" class="calendario-horarios">
                                                <div class="text-center p-4 text-muted">
                                                    <i class="bi bi-calendar-x fs-1"></i>
                                                    <p class="mt-2">Seleccione un doctor para ver los horarios disponibles</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="fechaCita" name="fecha">
                            <input type="hidden" id="horaCita" name="hora">
                            <input type="hidden" id="fechaHoraCompleta" name="fecha_hora">
                        </div>
                                        <!-- PASO 5: Detalles de la Cita -->
                   <div class="step-content" id="step5">
                       <div class="text-center mb-4">
                           <h4>Detalles de la Consulta</h4>
                           <p class="text-muted">Proporcione información adicional sobre la cita médica</p>
                       </div>

                       <div class="row g-4">
                           <div class="col-md-8">
                               <div class="card h-100">
                                   <div class="card-header bg-light">
                                       <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Motivo de la Consulta</h6>
                                   </div>
                                   <div class="card-body">
                                       <textarea class="form-control" id="motivoCita" name="motivo" rows="4" 
                                                 placeholder="Describa el motivo principal de la consulta médica..." required></textarea>
                                       <div class="form-text">
                                           <i class="bi bi-info-circle me-1"></i>
                                           Sea específico para ayudar al doctor a prepararse mejor para la consulta.
                                       </div>
                                   </div>
                               </div>
                           </div>

                           <div class="col-md-4">
                               <div class="card h-100">
                                   <div class="card-header bg-light">
                                       <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Prioridad</h6>
                                   </div>
                                   <div class="card-body">
                                       <select class="form-select" id="prioridadCita" name="prioridad">
                                           <option value="normal">Normal</option>
                                           <option value="urgente">Urgente</option>
                                           <option value="muy_urgente">Muy Urgente</option>
                                       </select>
                                       <div class="mt-3">
                                           <small class="text-muted" id="descripcionPrioridad">
                                               Consulta médica de rutina o seguimiento
                                           </small>
                                       </div>
                                   </div>
                               </div>
                           </div>

                           <div class="col-12">
                               <div class="card">
                                   <div class="card-header bg-light">
                                       <h6 class="mb-0"><i class="bi bi-sticky me-2"></i>Notas Adicionales</h6>
                                   </div>
                                   <div class="card-body">
                                       <textarea class="form-control" id="notasCita" name="notas" rows="3" 
                                                 placeholder="Notas adicionales, instrucciones especiales, recordatorios... (opcional)"></textarea>
                                       <div class="form-text">
                                           <i class="bi bi-lightbulb me-1"></i>
                                           Puede incluir instrucciones para el paciente, documentos a traer, etc.
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>

                   <!-- PASO 6: Resumen y Confirmación -->
                   <div class="step-content" id="step6">
                       <div class="text-center mb-4">
                           <h4>Confirmar Datos de la Cita</h4>
                           <p class="text-muted">Revise todos los datos antes de registrar la cita médica</p>
                       </div>

                       <div class="row g-4">
                           <!-- Resumen del Paciente -->
                           <div class="col-md-6">
                               <div class="card h-100">
                                   <div class="card-header bg-primary text-white">
                                       <h6 class="mb-0"><i class="bi bi-person me-2"></i>Datos del Paciente</h6>
                                   </div>
                                   <div class="card-body">
                                       <div id="resumenPaciente">
                                           <!-- Se llenará dinámicamente -->
                                       </div>
                                   </div>
                               </div>
                           </div>

                           <!-- Resumen de la Cita -->
                           <div class="col-md-6">
                               <div class="card h-100">
                                   <div class="card-header bg-success text-white">
                                       <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Datos de la Cita</h6>
                                   </div>
                                   <div class="card-body">
                                       <div id="resumenCita">
                                           <!-- Se llenará dinámicamente -->
                                       </div>
                                   </div>
                               </div>
                           </div>

                           <!-- Información Adicional -->
                           <div class="col-12">
                               <div class="card">
                                   <div class="card-header bg-info text-white">
                                       <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información Adicional</h6>
                                   </div>
                                   <div class="card-body">
                                       <div id="resumenAdicional">
                                           <!-- Se llenará dinámicamente -->
                                       </div>
                                   </div>
                               </div>
                           </div>

                           <!-- Confirmaciones -->
                           <div class="col-12">
                               <div class="card border-warning">
                                   <div class="card-body">
                                       <div class="form-check">
                                           <input class="form-check-input" type="checkbox" id="confirmarDatos" required>
                                           <label class="form-check-label" for="confirmarDatos">
                                               <strong>Confirmo que todos los datos son correctos</strong>
                                           </label>
                                       </div>
                                       <div class="form-check mt-2">
                                           <input class="form-check-input" type="checkbox" id="enviarNotificacion" checked>
                                           <label class="form-check-label" for="enviarNotificacion">
                                               Enviar notificación al paciente por email y SMS
                                           </label>
                                       </div>
                                       <div class="form-check mt-2" id="checkRecordarVirtual" style="display: none;">
                                           <input class="form-check-input" type="checkbox" id="recordatorioVirtual" checked>
                                           <label class="form-check-label" for="recordatorioVirtual">
                                               Enviar enlace de videollamada 24 horas antes de la cita
                                           </label>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </form>
           </div>
           
           <div class="modal-footer d-flex justify-content-between">
               <button type="button" class="btn btn-secondary" id="btnAnteriorPaso" style="display: none;">
                   <i class="bi bi-arrow-left me-1"></i>Anterior
               </button>
               
               <div class="ms-auto">
                   <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                       <i class="bi bi-x-circle me-1"></i>Cancelar
                   </button>
                   <button type="button" class="btn btn-primary" id="btnSiguientePaso">
                       Siguiente <i class="bi bi-arrow-right ms-1"></i>
                   </button>
                   <button type="submit" class="btn btn-success" id="btnConfirmarCita" style="display: none;" form="formNuevaCita">
                       <i class="bi bi-check-circle me-1"></i>Confirmar y Registrar Cita
                   </button>
               </div>
           </div>
       </div>
   </div>
</div>

<!-- Modal Ver/Editar Cita - Versión Corregida y Estructurada -->
<div class="modal fade" id="modalVerCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <!-- Header dinámico con gradiente según estado -->
            <div class="modal-header border-0 text-white position-relative overflow-hidden rounded-top-4" id="headerVerCita">
                <div class="position-relative z-3 d-flex align-items-center w-100">
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold mb-1 d-flex align-items-center">
                            <i class="bi bi-calendar-event me-2 fs-4"></i>
                            Detalles de la Cita
                        </h5>
                        <small class="opacity-75 d-flex align-items-center">
                            <i class="bi bi-info-circle me-1"></i>
                            Información completa de la cita médica
                        </small>
                    </div>
                    
                    <!-- Badge de estado flotante -->
                    <div class="ms-3">
                        <span class="badge fs-6 px-3 py-2 rounded-pill border border-white border-opacity-25" 
                              id="badgeEstadoVerCita">
                            <i class="bi bi-clock me-1"></i>
                            <span id="textoEstadoCita">Estado</span>
                        </span>
                    </div>
                </div>
                
                <button type="button" class="btn-close btn-close-white position-relative z-3 ms-3" 
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
                
                <!-- Patrón de fondo médico -->
                <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10">
                    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="medical-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                <circle cx="20" cy="20" r="1" fill="white" opacity="0.3"/>
                                <path d="M18 20h4M20 18v4" stroke="white" stroke-width="0.5" opacity="0.2"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#medical-pattern)"/>
                    </svg>
                </div>
            </div>
            
            <!-- Body estructurado -->
            <div class="modal-body p-0">
                <!-- Loading State -->
                <div id="loadingVerCita" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando detalles...</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-center text-muted">
                        <i class="bi bi-hourglass-split me-2"></i>
                        Cargando información de la cita...
                    </div>
                </div>
                
                <!-- Información del Paciente - Header -->
                <div class="patient-header bg-gradient p-4 border-bottom">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="patient-avatar">
                                <i class="bi bi-person-fill fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-badge text-primary me-2 fs-5"></i>
                                <h6 class="mb-0 fw-bold text-dark">Información del Paciente</h6>
                            </div>
                            <h4 class="mb-2 text-dark fw-bold" id="nombreCompletoPaciente">
                                <i class="bi bi-person-heart me-2 text-success"></i>
                                <span id="nombrePacienteDetalle">Cargando...</span>
                            </h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="patient-info-item">
                                        <i class="bi bi-credit-card-2-front text-info me-2"></i>
                                        <span class="text-muted small">Cédula:</span>
                                        <span class="fw-semibold ms-1" id="cedulaPacienteDetalle">---</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="patient-info-item">
                                        <i class="bi bi-telephone text-success me-2"></i>
                                        <span class="text-muted small">Teléfono:</span>
                                        <span class="fw-semibold ms-1" id="telefonoPacienteDetalle">---</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="patient-info-item">
                                        <i class="bi bi-envelope text-warning me-2"></i>
                                        <span class="text-muted small">Email:</span>
                                        <span class="fw-semibold ms-1" id="emailPacienteDetalle">---</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contenido Principal en Grid -->
                <div class="modal-body-content p-4">
                    <div class="row g-4">
                        <!-- COLUMNA IZQUIERDA -->
                        <div class="col-md-6">
                            <!-- Fecha y Hora -->
                            <div class="detail-card mb-4">
                                <div class="detail-card-header">
                                    <i class="bi bi-calendar-check detail-icon bg-info"></i>
                                    <h6 class="detail-title">Fecha y Horario</h6>
                                </div>
                                <div class="detail-card-body">
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-calendar3 text-primary me-2"></i>
                                            <span class="detail-label">Fecha:</span>
                                            <span class="detail-value" id="fechaCitaDetalle">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-clock text-success me-2"></i>
                                            <span class="detail-label">Hora:</span>
                                            <span class="detail-value" id="horaCitaDetalle">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                                            <span class="detail-label">Duración:</span>
                                            <span class="detail-value" id="duracionCitaDetalle">30 minutos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ubicación -->
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="bi bi-geo-alt-fill detail-icon bg-primary"></i>
                                    <h6 class="detail-title">Ubicación</h6>
                                </div>
                                <div class="detail-card-body">
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-building text-primary me-2"></i>
                                            <span class="detail-label">Sucursal:</span>
                                            <span class="detail-value" id="sucursalCitaDetalle">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-door-open text-info me-2"></i>
                                            <span class="detail-label">Consultorio:</span>
                                            <span class="detail-value" id="consultorioCitaDetalle">Por asignar</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-pin-map text-danger me-2"></i>
                                            <span class="detail-label">Dirección:</span>
                                            <span class="detail-value" id="direccionSucursalDetalle">---</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- COLUMNA DERECHA -->
                        <div class="col-md-6">
                            <!-- Información Médica -->
                            <div class="detail-card mb-4">
                                <div class="detail-card-header">
                                    <i class="bi bi-heart-pulse-fill detail-icon bg-success"></i>
                                    <h6 class="detail-title">Información Médica</h6>
                                </div>
                                <div class="detail-card-body">
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-person-badge text-success me-2"></i>
                                            <span class="detail-label">Doctor:</span>
                                            <span class="detail-value" id="doctorCitaDetalle">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-hospital text-info me-2"></i>
                                            <span class="detail-label">Especialidad:</span>
                                            <span class="detail-value" id="especialidadCitaDetalle">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-laptop text-primary me-2" id="iconTipoCita"></i>
                                            <span class="detail-label">Tipo:</span>
                                            <span class="detail-value" id="tipoCitaDetalle">---</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Estado y Control -->
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="bi bi-activity detail-icon bg-warning"></i>
                                    <h6 class="detail-title">Estado y Control</h6>
                                </div>
                                <div class="detail-card-body">
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            <span class="detail-label">Estado:</span>
                                            <span class="detail-value" id="estadoActualCita">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-calendar-plus text-info me-2"></i>
                                            <span class="detail-label">Registrada:</span>
                                            <span class="detail-value" id="fechaRegistroCita">---</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-item">
                                            <i class="bi bi-person-check text-primary me-2"></i>
                                            <span class="detail-label">Registrada por:</span>
                                            <span class="detail-value" id="usuarioRegistroCita">Sistema</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Motivo - Ancho completo -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="bi bi-chat-text-fill detail-icon bg-warning"></i>
                                    <h6 class="detail-title">Motivo de la Consulta</h6>
                                </div>
                                <div class="detail-card-body">
                                    <div class="motivo-content">
                                        <i class="bi bi-quote text-muted me-2"></i>
                                        <span id="motivoCitaDetalle" class="text-muted fst-italic">---</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones (condicional) -->
                    <div class="row mt-3" id="contenedorObservacionesCita" style="display: none;">
                        <div class="col-12">
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="bi bi-journal-text detail-icon bg-secondary"></i>
                                    <h6 class="detail-title">Observaciones Adicionales</h6>
                                </div>
                                <div class="detail-card-body">
                                    <div class="observaciones-content">
                                        <i class="bi bi-pencil-square text-muted me-2"></i>
                                        <span id="observacionesCitaDetalle" class="text-muted">---</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- El div original se mantiene oculto para compatibilidad -->
                <div id="detallesCita" style="display: none;">
                    <!-- Aquí se cargará el contenido dinámico como antes -->
                </div>
            </div>
            
            <!-- Footer con botones originales mejorados -->
            <div class="modal-footer border-0 bg-light rounded-bottom-4 d-flex justify-content-between align-items-center p-4">
                <!-- Información de registro -->
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-shield-check me-2 text-success"></i>
                    <span>ID Cita: <span id="idCitaDetalle" class="fw-bold text-primary">#---</span></span>
                </div>
                
                <!-- Botones de acción (originales mantenidos) -->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cerrar
                    </button>
                    
                    <?php if($permisos['puede_editar']): ?>
                    <button type="button" class="btn btn-warning rounded-pill px-3" id="btnEditarCita">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-3" id="btnConfirmarCita">
                        <i class="bi bi-check-circle me-1"></i>Confirmar
                    </button>
                    <?php endif; ?>
                    
                    <?php if($permisos['puede_eliminar']): ?>
                    <button type="button" class="btn btn-danger rounded-pill px-3" id="btnCancelarCita">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Paciente -->
<div class="modal fade" id="modalRegistrarPaciente" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl">
       <form class="modal-content" id="formRegistrarPaciente">
           <div class="modal-header bg-success text-white">
               <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i>Registrar Nuevo Paciente</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
           </div>
           <div class="modal-body">
               <!-- Datos Básicos -->
               <div class="card mb-3">
                   <div class="card-header">
                       <h6 class="mb-0">📋 Datos Básicos</h6>
                   </div>
                   <div class="card-body">
                       <div class="row g-3">
                           <div class="col-md-6">
                               <label class="form-label">Cédula</label>
                               <div class="input-group">
                                   <input type="text" class="form-control" id="cedulaNuevoPaciente" name="cedula" required>
                                   <button type="button" class="btn btn-outline-secondary" id="btnObtenerDatosCedula">
                                       <i class="bi bi-download me-1"></i>Obtener Datos
                                   </button>
                               </div>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Tipo de Sangre</label>
                               <select class="form-select" id="tipoSangreNuevo" name="tipo_sangre">
                                   <option value="">Seleccione...</option>
                                   <option value="A+">A+</option>
                                   <option value="A-">A-</option>
                                   <option value="B+">B+</option>
                                   <option value="B-">B-</option>
                                   <option value="AB+">AB+</option>
                                   <option value="AB-">AB-</option>
                                   <option value="O+">O+</option>
                                   <option value="O-">O-</option>
                               </select>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Nombres</label>
                               <input type="text" class="form-control" id="nombresNuevoPaciente" name="nombres" required>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Apellidos</label>
                               <input type="text" class="form-control" id="apellidosNuevoPaciente" name="apellidos" required>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Fecha de Nacimiento</label>
                               <input type="date" class="form-control" id="fechaNacimientoNuevo" name="fecha_nacimiento" required>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Género</label>
                               <select class="form-select" id="generoNuevo" name="genero" required>
                                   <option value="">Seleccione...</option>
                                   <option value="M">Masculino</option>
                                   <option value="F">Femenino</option>
                               </select>
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Datos de Contacto -->
               <div class="card mb-3">
                   <div class="card-header">
                       <h6 class="mb-0">📞 Datos de Contacto</h6>
                   </div>
                   <div class="card-body">
                       <div class="row g-3">
                           <div class="col-md-6">
                               <label class="form-label">Teléfono Principal</label>
                               <input type="tel" class="form-control" id="telefonoNuevo" name="telefono" required>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Email</label>
                               <input type="email" class="form-control" id="correoNuevoPaciente" name="correo">
                           </div>
                           <div class="col-12">
                               <label class="form-label">Dirección</label>
                               <input type="text" class="form-control" id="direccionNuevo" name="direccion" required>
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Contacto de Emergencia -->
               <div class="card mb-3">
                   <div class="card-header">
                       <h6 class="mb-0">🚨 Contacto de Emergencia</h6>
                   </div>
                   <div class="card-body">
                       <div class="row g-3">
                           <div class="col-md-6">
                               <label class="form-label">Nombre del Contacto</label>
                               <input type="text" class="form-control" id="contactoEmergenciaNuevo" name="contacto_emergencia" 
                                      placeholder="Ej: María González (Madre)">
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Teléfono de Emergencia</label>
                               <input type="tel" class="form-control" id="telefonoEmergenciaNuevo" name="telefono_emergencia" 
                                      placeholder="Ej: 0991234567">
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Información Médica -->
               <div class="card mb-3">
                   <div class="card-header">
                       <h6 class="mb-0">⚕️ Información Médica</h6>
                   </div>
                   <div class="card-body">
                       <div class="row g-3">
                           <div class="col-md-6">
                               <label class="form-label">Número de Seguro</label>
                               <input type="text" class="form-control" id="numeroSeguroNuevo" name="numero_seguro" 
                                      placeholder="Ej: IESS, Seguro Privado">
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Alergias</label>
                               <textarea class="form-control" id="alergiasNuevo" name="alergias" rows="2" 
                                         placeholder="Alergias conocidas (opcional)"></textarea>
                           </div>
                           <div class="col-12">
                               <label class="form-label">Antecedentes Médicos</label>
                               <textarea class="form-control" id="antecedentesMedicosNuevo" name="antecedentes_medicos" rows="3" 
                                         placeholder="Enfermedades previas, cirugías, tratamientos actuales, etc. (opcional)"></textarea>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                   <i class="bi bi-x-circle me-1"></i>Cancelar
               </button>
               <button type="submit" class="btn btn-success">
                   <i class="bi bi-save me-1"></i>Registrar Paciente
               </button>
           </div>
       </form>
   </div>
</div>

<!-- Modal Cancelar Cita -->
<div class="modal fade" id="modalCancelarCita" tabindex="-1" aria-labelledby="modalCancelarCitaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalCancelarCitaLabel">
                    <i class="bi bi-x-circle me-2"></i>Cancelar Cita Médica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Alerta de advertencia -->
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                    <div>
                        <strong>¡Atención!</strong> Esta acción no se puede deshacer. La cita será cancelada permanentemente.
                    </div>
                </div>

                <!-- Información de la cita -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información de la Cita</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Paciente:</strong></p>
                                <p class="text-primary" id="nombrePacienteCancelar">-</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Fecha y Hora:</strong></p>
                                <p class="text-info" id="fechaHoraCancelar">-</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Doctor:</strong></p>
                                <p id="doctorCancelar">-</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Estado Actual:</strong></p>
                                <span class="badge bg-secondary" id="estadoActualCancelar">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de cancelación -->
                <form id="formCancelarCita">
                    <input type="hidden" id="idCitaCancelar" name="id_cita">
                    
                    <div class="mb-4">
                        <label for="motivoCancelacion" class="form-label fw-bold">
                            <i class="bi bi-chat-left-text me-2"></i>Motivo de Cancelación <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            class="form-control" 
                            id="motivoCancelacion" 
                            name="motivo_cancelacion" 
                            rows="4" 
                            placeholder="Describa detalladamente el motivo de la cancelación de la cita..."
                            required 
                            maxlength="500"></textarea>
                        <div class="form-text">
                            <span id="contadorCaracteres">0/500 caracteres</span> - Mínimo 10 caracteres requeridos
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enviarNotificacionCancelacion" name="enviar_notificacion" checked>
                            <label class="form-check-label" for="enviarNotificacionCancelacion">
                                <i class="bi bi-envelope me-2"></i>Enviar notificación al paciente por email
                            </label>
                        </div>
                        <small class="text-muted">El paciente será informado automáticamente sobre la cancelación</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-2"></i>No, Mantener Cita
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarCancelacion">
                    <i class="bi bi-trash3 me-2"></i>Sí, Cancelar Cita
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js'></script>

<script>
// Configuración global
window.recepcionConfig = {
   submenuId: <?= $id_submenu ?>,
   permisos: <?= json_encode($permisos) ?>,
   sucursales: <?= json_encode($sucursales) ?>,
   especialidades: <?= json_encode($especialidades) ?>
};
</script>
<script src="../../js/recepcion/gestionar_citas.js"></script>

</body>
</html>