<?php
if (!isset($_SESSION)) session_start();



$titulo_pagina = "Triaje - Enfermería";
include_once '../../navbars/header.php';
include_once '../../navbars/sidebar.php';
?>

<!-- CSS específico para triaje -->
<link rel="stylesheet" href="../../estilos/triaje.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="container-fluid py-4">
    <!-- ✅ HEADER PROFESIONAL REDISEÑADO -->
    <div class="triaje-header fade-in-up mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8 col-md-7">
                <h1 class="mb-3">
                    <i class="bi bi-clipboard2-pulse me-3"></i>
                    Sistema de Triaje Médico
                </h1>
                <p class="mb-3">
                    Evaluación inicial y clasificación de pacientes por prioridad médica
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="bi bi-person-badge me-2"></i>
                        Enfermero/a: <?php echo $_SESSION['nombres'] ?? 'Usuario'; ?>
                    </span>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="bi bi-calendar-check me-2"></i>
                        <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
            </div>
            <div class="col-lg-4 col-md-5 text-md-end text-start mt-3 mt-md-0">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <button type="button" class="btn btn-light px-4 py-2" id="btnEstadisticas">
                        <i class="bi bi-graph-up me-2"></i>
                        Estadísticas
                    </button>
                    <button type="button" class="btn btn-light px-4 py-2" id="btnRefrescar">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ESTADÍSTICAS PROFESIONALES REDISEÑADAS -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-primary h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon icon-primary me-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="totalCitas">0</div>
                        <div class="stat-label">Total de Citas</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-warning h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon icon-warning me-3">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="citasPendientes">0</div>
                        <div class="stat-label">Triajes Pendientes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-success h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon icon-success me-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="triageCompletados">0</div>
                        <div class="stat-label">Triajes Completados</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-danger h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon icon-danger me-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="urgentes">0</div>
                        <div class="stat-label">Casos Urgentes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ CARD PRINCIPAL PROFESIONAL -->
    <div class="card border-0 shadow-lg">
        <div class="card-header bg-white border-0 p-4">
            <div class="row align-items-center mb-4">
                <div class="col-lg-4">
                    <h5 class="mb-2 fw-bold">
                        <i class="bi bi-list-check me-2 text-primary"></i>
                        Gestión de Triaje
                    </h5>
                    <small class="text-muted">Control de pacientes del día</small>
                </div>
                <div class="col-lg-8">
                    <!-- ✅ CONTROLES PROFESIONALES MEJORADOS -->
                    <div class="row g-3">
                        <!-- Fecha -->
                        <div class="col-xl-2 col-lg-3 col-md-4">
                            <label class="form-label fw-semibold mb-2">
                                <i class="bi bi-calendar3 me-1 text-primary"></i>
                                Fecha
                            </label>
                            <input type="date" id="fechaTriaje" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- Buscador -->
                        <div class="col-xl-4 col-lg-5 col-md-8">
                            <label class="form-label fw-semibold mb-2">
                                <i class="bi bi-search me-1 text-primary"></i>
                                Buscar Paciente
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-credit-card text-muted"></i>
                                </span>
                                <input type="text" id="buscarCedula" class="form-control border-start-0" 
                                       placeholder="Ingrese cédula..." 
                                       pattern="[0-9]*" 
                                       maxlength="10"
                                       autocomplete="off">
                                <button class="btn btn-outline-primary" type="button" id="btnBuscarCedula">
                                    <i class="bi bi-search"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Mínimo 3 dígitos
                            </small>
                        </div>
                        
                        <!-- Filtro Estado -->
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label fw-semibold mb-2">
                                <i class="bi bi-funnel me-1 text-primary"></i>
                                Estado
                            </label>
                            <select id="filtroEstado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="Pendiente">⏳ Pendientes</option>
                                <option value="Completado">✅ Completados</option>
                                <option value="Urgente">🟠 Urgentes</option>
                                <option value="Critico">🔴 Críticos</option>
                            </select>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label fw-semibold mb-2 d-block">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary flex-fill" id="btnActualizarTabla">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Actualizar
                                </button>
                                <div class="badge bg-secondary align-self-center px-3 py-2">
                                    <div class="fw-bold" id="contadorResultados">0</div>
                                    <small>resultados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ✅ ÁREA DE RESULTADOS PROFESIONAL -->
            <div id="resultadosBusqueda" style="display: none;" class="mt-3">
                <!-- Se llena dinámicamente -->
            </div>
        </div>
        
        <div class="card-body p-0">
            <!-- ✅ LOADING PROFESIONAL -->
            <div id="loadingCitas" class="loading-container text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h5 class="text-muted">Cargando información de pacientes...</h5>
                <p class="text-muted mb-0">Por favor espere un momento</p>
            </div>

            <!-- ✅ TABLA PROFESIONAL -->
            <div class="table-responsive d-none" id="tablaCitas">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" width="8%">
                                <i class="bi bi-clock me-1"></i>
                                Hora
                            </th>
                            <th width="18%">
                                <i class="bi bi-person me-1"></i>
                                Paciente
                            </th>
                            <th width="12%">
                                <i class="bi bi-credit-card me-1"></i>
                                Cédula
                            </th>
                            <th width="16%">
                                <i class="bi bi-person-badge me-1"></i>
                                Médico
                            </th>
                            <th width="14%">
                                <i class="bi bi-hospital me-1"></i>
                                Especialidad
                            </th>
                            <th class="text-center" width="12%">
                                <i class="bi bi-clipboard-pulse me-1"></i>
                                Estado
                            </th>
                            <th class="text-center" width="10%">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                Urgencia
                            </th>
                            <th class="text-center" width="10%">
                                <i class="bi bi-tools me-1"></i>
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaCitas">
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- ✅ MENSAJES PROFESIONALES -->
            <div id="sinCitas" class="text-center py-5 d-none">
                <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                <h5 class="mt-3 text-muted">No hay citas programadas</h5>
                <p class="text-muted">No se encontraron citas médicas para la fecha seleccionada.</p>
                <button class="btn btn-outline-primary mt-2" onclick="$('#fechaTriaje').focus()">
                    <i class="bi bi-calendar-plus me-1"></i>
                    Seleccionar otra fecha
                </button>
            </div>

            <div id="sinResultados" class="text-center py-5 d-none">
                <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                <h5 class="mt-3 text-muted">Sin resultados de búsqueda</h5>
                <p class="text-muted">No se encontraron pacientes que coincidan con los criterios.</p>
                <button class="btn btn-outline-primary mt-2" onclick="limpiarBusqueda()">
                    <i class="bi bi-arrow-left me-1"></i>
                    Mostrar todos
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ MODAL PROFESIONAL PARA TRIAJE -->
<div class="modal fade" id="modalTriaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="bi bi-clipboard2-pulse me-2"></i>
                        Evaluación de Triaje Médico
                    </h5>
                    <small class="text-light opacity-75">
                        Clasificación inicial del paciente según prioridad médica
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formTriaje">
                <div class="modal-body">
                    <!-- ✅ INFORMACIÓN DEL PACIENTE PROFESIONAL -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-custom alert-info-custom">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                                    </div>
                                    <div class="col-md-11">
                                        <h6 class="mb-2">
                                            <i class="bi bi-person-badge me-2"></i>
                                            Información del Paciente
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-2">
                                                    <strong>Nombre Completo:</strong>
                                                    <div id="nombrePacienteTriaje" class="fw-bold text-primary">-</div>
                                                </div>
                                                <div>
                                                    <strong>Cédula de Identidad:</strong>
                                                    <div id="cedulaPacienteTriaje" class="fw-bold">-</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-2">
                                                    <strong>Médico Asignado:</strong>
                                                    <div id="doctorTriaje" class="fw-bold text-secondary">-</div>
                                                </div>
                                                <div>
                                                    <strong>Especialidad Médica:</strong>
                                                    <div id="especialidadTriaje" class="fw-bold">-</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SIGNOS VITALES PROFESIONALES -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="stat-icon icon-danger" style="width: 50px; height: 50px;">
                                        <i class="bi bi-heart-pulse"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1">Signos Vitales y Medidas Antropométricas</h5>
                                    <p class="text-muted mb-0">Registro de constantes vitales del paciente</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Primera fila de signos vitales -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-thermometer-half text-danger me-1"></i>
                                Temperatura Corporal
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="temperatura" name="temperatura" 
                                       step="0.1" min="30" max="45" placeholder="36.5">
                                <span class="input-group-text">°C</span>
                            </div>
                            <div class="form-text">Normal: 36.0 - 37.5°C</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-activity text-info me-1"></i>
                                Presión Arterial
                            </label>
                            <input type="text" class="form-control" id="presionArterial" name="presion_arterial" 
                                   placeholder="120/80" pattern="[0-9]{2,3}/[0-9]{2,3}">
                            <div class="form-text">Formato: 120/80 mmHg</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-heart text-danger me-1"></i>
                                Frecuencia Cardíaca
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="frecuenciaCardiaca" name="frecuencia_cardiaca" 
                                       min="40" max="200" placeholder="80">
                                <span class="input-group-text">lpm</span>
                            </div>
                            <div class="form-text">Normal: 60-100 lpm</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-lungs text-primary me-1"></i>
                                Frecuencia Respiratoria
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="frecuenciaRespiratoria" name="frecuencia_respiratoria" 
                                       min="10" max="40" placeholder="18">
                                <span class="input-group-text">rpm</span>
                            </div>
                            <div class="form-text">Normal: 12-20 rpm</div>
                        </div>
                    </div>

                    <!-- Segunda fila: medidas antropométricas y urgencia -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                <i class="bi bi-droplet text-info me-1"></i>
                                Saturación O₂
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="saturacionOxigeno" name="saturacion_oxigeno" 
                                       min="70" max="100" placeholder="98">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Normal: >95%</div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                <i class="bi bi-speedometer2 text-warning me-1"></i>
                                Peso Corporal
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="peso" name="peso" 
                                       step="0.1" min="1" max="300" placeholder="70.0">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                <i class="bi bi-rulers text-success me-1"></i>
                                Talla/Estatura
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="talla" name="talla" 
                                       min="50" max="250" placeholder="170">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-calculator text-info me-1"></i>
                                Índice de Masa Corporal
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="imc" readonly 
                                       placeholder="Se calcula automáticamente">
                                <span class="input-group-text fw-bold" id="categoriaIMC">-</span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                                Nivel de Urgencia *
                            </label>
                            <select class="form-select" id="nivelUrgencia" name="nivel_urgencia" required>
                                <option value="">Evaluar y seleccionar...</option>
                                <option value="1" class="text-success">🟢 Nivel 1 - No urgente</option>
                                <option value="2" class="text-warning">🟡 Nivel 2 - Poco urgente</option>
                                <option value="3" class="text-danger">🟠 Nivel 3 - Urgente</option>
                                <option value="4" class="text-danger">🔴 Nivel 4 - Muy urgente</option>
                            </select>
                        </div>
                    </div>

                    <!-- ✅ OBSERVACIONES PROFESIONALES -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="bi bi-chat-left-text text-secondary me-1"></i>
                                Observaciones Clínicas y Síntomas
                            </label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4" 
                                      placeholder="Registre aquí: síntomas observados, comportamiento del paciente, alergias conocidas, medicamentos actuales, antecedentes relevantes, etc."></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Esta información será visible para el médico tratante
                            </div>
                        </div>
                    </div>

                    <!-- ✅ ALERTAS DE SIGNOS VITALES -->
                    <div id="alertasSignosVitales"></div>

                    <input type="hidden" id="idCitaTriaje" name="id_cita">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar Evaluación
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Guardar Triaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ MODALES ADICIONALES MANTENIDOS PERO MEJORADOS -->
<div class="modal fade" id="modalVerTriaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>
                    Detalles del Triaje Completado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoTriaje">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
                <button type="button" class="btn btn-warning" id="btnEditarTriaje">
                    <i class="bi bi-pencil me-1"></i>
                    Editar Triaje
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEstadisticas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-graph-up me-2"></i>
                    Estadísticas y Métricas de Triaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoEstadisticas">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>

<script>
// ✅ CONFIGURACIÓN MEJORADA
window.triajeConfig = <?= json_encode([
    'baseUrl' => '../../controladores/EnfermeriaControlador/EnfermeriaController.php',
    'permisos' => $permisos ?? [],
    'submenuId' => $id_submenu ?? null,
    'idEnfermero' => $_SESSION['id_usuario'] ?? null,
    'nombreEnfermero' => ($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? ''),
    'debug' => true
]) ?>;

console.log('🏥 Sistema de triaje profesional cargado:', window.triajeConfig);
</script>
<script src="../../js/triaje.js"></script>

</document_content>