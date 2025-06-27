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
    
    <style>
        .fc-event {
            border: none !important;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }
        .fc-event.estado-pendiente {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        .fc-event.estado-confirmada {
            background-color: #198754 !important;
            color: #fff !important;
        }
        .fc-event.estado-completada {
            background-color: #0dcaf0 !important;
            color: #000 !important;
        }
        .fc-event.estado-cancelada {
            background-color: #dc3545 !important;
            color: #fff !important;
        }
        .calendario-container {
            min-height: 600px;
        }
        .agenda-doctor {
            max-height: 400px;
            overflow-y: auto;
        }
        .cita-item {
            border-left: 4px solid #007bff;
            padding: 8px;
            margin-bottom: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .cita-item.pendiente { border-left-color: #ffc107; }
        .cita-item.confirmada { border-left-color: #198754; }
        .cita-item.completada { border-left-color: #0dcaf0; }
        .cita-item.cancelada { border-left-color: #dc3545; }
    </style>
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
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="card bg-info text-white">
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
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Sucursal</label>
                        <select class="form-select" id="filtroSucursal">
                            <option value="">Todas</option>
                            <?php foreach($sucursales as $sucursal): ?>
                            <option value="<?= $sucursal['id_sucursal'] ?>">
                                <?= htmlspecialchars($sucursal['nombre_sucursal']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Especialidad</label>
                        <select class="form-select" id="filtroEspecialidad">
                            <option value="">Todas</option>
                            <?php foreach($especialidades as $especialidad): ?>
                            <option value="<?= $especialidad['id_especialidad'] ?>">
                                <?= htmlspecialchars($especialidad['nombre_especialidad']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="filtroEstado">
                            <option value="">Todos</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select class="form-select" id="filtroDoctor">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100" id="btnAplicarFiltros">
                        <i class="bi bi-search me-1"></i>Aplicar Filtros
                    </button>
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
                    <div class="d-flex align-items-center">
                        <div class="bg-danger" style="width: 20px; height: 15px; border-radius: 3px;"></div>
                        <span class="ms-2 small">Cancelada</span>
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

    <!-- Panel de Agenda del Día (se muestra al hacer clic en una fecha) -->
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

<!-- Modal Nueva Cita -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" id="formNuevaCita">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Registrar Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Aquí va el formulario completo que tenía antes -->
                <div class="row g-3">
                    <!-- Paso 1: Buscar Paciente -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">1. Buscar Paciente</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Cédula del Paciente</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="cedulaPaciente" 
                                                   placeholder="Ingrese cédula" required>
                                            <button type="button" class="btn btn-outline-primary" id="btnBuscarPaciente">
                                                <i class="bi bi-search me-1"></i>Buscar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="infoPaciente" class="alert alert-info d-none">
                                            <strong>Paciente encontrado:</strong>
                                            <div id="datosPaciente"></div>
                                        </div>
                                        <div id="pacienteNoEncontrado" class="alert alert-warning d-none">
                                            <strong>Paciente no encontrado.</strong>
                                            <button type="button" class="btn btn-sm btn-warning ms-2" id="btnRegistrarPaciente">
                                                <i class="bi bi-person-plus me-1"></i>Registrar Nuevo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="idPacienteSeleccionado" name="id_paciente">
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Seleccionar Sucursal y Especialidad -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">2. Seleccionar Sucursal y Especialidad</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Sucursal</label>
                                        <select class="form-select" id="sucursalCita" name="id_sucursal" required>
                                            <option value="">Seleccione sucursal</option>
                                            <?php foreach($sucursales as $sucursal): ?>
                                            <option value="<?= $sucursal['id_sucursal'] ?>">
                                                <?= htmlspecialchars($sucursal['nombre_sucursal']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Especialidad</label>
                                        <select class="form-select" id="especialidadCita" name="id_especialidad" required disabled>
                                            <option value="">Primero seleccione sucursal</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Seleccionar Doctor y Fecha/Hora -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">3. Seleccionar Doctor y Horario</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Doctor</label>
                                        <select class="form-select" id="doctorCita" name="id_doctor" required disabled>
                                            <option value="">Primero seleccione especialidad</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha</label>
                                        <input type="date" class="form-control" id="fechaCita" name="fecha" 
                                               min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Hora</label>
                                        <select class="form-select" id="horaCita" name="hora" required disabled>
                                            <option value="">Seleccione fecha y doctor</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" id="fechaHoraCompleta" name="fecha_hora">
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Detalles -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">4. Detalles de la Cita</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Motivo de la Consulta</label>
                                        <textarea class="form-control" id="motivoCita" name="motivo" rows="3" 
                                                  placeholder="Describa el motivo de la consulta" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Notas Adicionales</label>
                                        <textarea class="form-control" id="notasCita" name="notas" rows="3" 
                                                  placeholder="Notas adicionales (opcional)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Registrar Cita
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver/Editar Cita -->
<div class="modal fade" id="modalVerCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-1"></i>Detalles de la Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detallesCita">
                <!-- Los detalles de la cita se cargarán aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php if($permisos['puede_editar']): ?>
                <button type="button" class="btn btn-warning" id="btnEditarCita">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmarCita">
                    <i class="bi bi-check-circle me-1"></i>Confirmar
                </button>
                <?php endif; ?>
                <?php if($permisos['puede_eliminar']): ?>
                <button type="button" class="btn btn-danger" id="btnCancelarCita">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Paciente (incluir aquí el modal completo) -->
<!-- ... (el modal de registrar paciente que ya tenías) ... -->

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