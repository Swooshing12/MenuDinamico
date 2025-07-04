<?php

if (!isset($_SESSION)) session_start();

// Verificar si es enfermero
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 73) {
    header('Location: ../../error_permisos.php');
    exit();
}

$titulo_pagina = "Triaje - Enfermería";
include_once '../../navbars/header.php';
include_once '../../navbars/sidebar.php';

?>

<!-- CSS específico para triaje -->
<link rel="stylesheet" href="../../estilos/triaje.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="container-fluid py-4">
    <!-- Header de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-primary">
                        <i class="bi bi-clipboard2-pulse me-2"></i>
                        Sistema de Triaje
                    </h1>
                    <p class="text-muted mb-0">Evaluación inicial de pacientes</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info" id="btnEstadisticas">
                        <i class="bi bi-graph-up"></i> Estadísticas
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnRefrescar">
                        <i class="bi bi-arrow-clockwise"></i> Refrescar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Selector de fecha -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <label for="fechaTriaje" class="form-label fw-bold">
                        <i class="bi bi-calendar3 me-1"></i>Fecha de atención
                    </label>
                    <input type="date" class="form-control" id="fechaTriaje" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4" id="estadisticasRapidas">
        <div class="col-6 col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill fs-2"></i>
                    <h4 class="card-title" id="totalCitas">0</h4>
                    <p class="card-text">Total Citas</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <i class="bi bi-clock-fill fs-2"></i>
                    <h4 class="card-title" id="citasPendientes">0</h4>
                    <p class="card-text">Pendientes</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill fs-2"></i>
                    <h4 class="card-title" id="triageCompletados">0</h4>
                    <p class="card-text">Completados</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    <h4 class="card-title" id="urgentes">0</h4>
                    <p class="card-text">Urgentes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de citas -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            Citas del Día
                        </h5>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="filtroEstado" style="width: auto;">
                                <option value="">Todos los estados</option>
                                <option value="Pendiente">Pendientes</option>
                                <option value="Triaje Completado">Con Triaje</option>
                                <option value="Triaje Urgente">Urgentes</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Loading -->
                    <div id="loadingCitas" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando citas...</p>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive d-none" id="tablaCitas">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="bi bi-clock"></i> Hora</th>
                                    <th><i class="bi bi-person"></i> Paciente</th>
                                    <th><i class="bi bi-credit-card"></i> Cédula</th>
                                    <th><i class="bi bi-person-badge"></i> Doctor</th>
                                    <th><i class="bi bi-hospital"></i> Especialidad</th>
                                    <th><i class="bi bi-clipboard-pulse"></i> Estado Triaje</th>
                                    <th><i class="bi bi-exclamation-circle"></i> Urgencia</th>
                                    <th><i class="bi bi-tools"></i> Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaCitas">
                                <!-- Se llenará dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Sin datos -->
                    <div id="sinCitas" class="text-center py-5 d-none">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No hay citas para esta fecha</h5>
                        <p class="text-muted">Selecciona otra fecha o espera a que lleguen los pacientes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para realizar triaje -->
<div class="modal fade" id="modalTriaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard2-pulse me-2"></i>
                    Realizar Triaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formTriaje">
                <div class="modal-body">
                    <!-- Información del paciente -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="bi bi-person-fill me-2"></i>
                                    Información del Paciente
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Nombre:</strong> <span id="nombrePacienteTriaje"></span><br>
                                        <strong>Cédula:</strong> <span id="cedulaPacienteTriaje"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Doctor:</strong> <span id="doctorTriaje"></span><br>
                                        <strong>Especialidad:</strong> <span id="especialidadTriaje"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Signos vitales -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-heart-pulse me-2"></i>
                                Signos Vitales
                            </h6>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Temperatura -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-thermometer-half text-danger"></i>
                                Temperatura (°C)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="temperatura" name="temperatura" 
                                       step="0.1" min="30" max="45" placeholder="36.5">
                                <span class="input-group-text">°C</span>
                            </div>
                        </div>

                        <!-- Presión Arterial -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-activity text-info"></i>
                                Presión Arterial
                            </label>
                            <input type="text" class="form-control" id="presionArterial" name="presion_arterial" 
                                   placeholder="120/80" pattern="[0-9]{2,3}/[0-9]{2,3}">
                        </div>

                        <!-- Frecuencia Cardíaca -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-heart text-danger"></i>
                                Freq. Cardíaca (lpm)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="frecuenciaCardiaca" name="frecuencia_cardiaca" 
                                       min="40" max="200" placeholder="80">
                                <span class="input-group-text">lpm</span>
                            </div>
                        </div>

                        <!-- Frecuencia Respiratoria -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-lungs text-primary"></i>
                                Freq. Respiratoria (rpm)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="frecuenciaRespiratoria" name="frecuencia_respiratoria" 
                                       min="10" max="40" placeholder="18">
                                <span class="input-group-text">rpm</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <!-- Saturación Oxígeno -->
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="bi bi-droplet text-info"></i>
                                Sat. O₂ (%)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="saturacionOxigeno" name="saturacion_oxigeno" 
                                       min="70" max="100" placeholder="98">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Peso -->
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="bi bi-speedometer2 text-warning"></i>
                                Peso (kg)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="peso" name="peso" 
                                       step="0.1" min="1" max="300" placeholder="70">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>

                        <!-- Talla -->
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="bi bi-rulers text-success"></i>
                                Talla (cm)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="talla" name="talla" 
                                       min="50" max="250" placeholder="170">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                        <!-- IMC (calculado automáticamente) -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-calculator text-info"></i>
                                IMC (kg/m²)
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="imc" readonly placeholder="Se calcula automáticamente">
                                <span class="input-group-text" id="categoriaIMC">-</span>
                            </div>
                        </div>

                        <!-- Nivel de Urgencia -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-exclamation-triangle text-danger"></i>
                                Nivel de Urgencia *
                            </label>
                            <select class="form-select" id="nivelUrgencia" name="nivel_urgencia" required>
                                <option value="">Seleccionar...</option>
                                <option value="1" class="text-success">🟢 Bajo - No urgente</option>
                                <option value="2" class="text-warning">🟡 Medio - Poco urgente</option>
                                <option value="3" class="text-danger">🟠 Alto - Urgente</option>
                                <option value="4" class="text-danger">🔴 Crítico - Muy urgente</option>
                            </select>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-chat-left-text text-secondary"></i>
                                Observaciones
                            </label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                      placeholder="Síntomas observados, comportamiento del paciente, alergias conocidas, etc."></textarea>
                        </div>
                    </div>

                    <!-- Alertas de signos vitales -->
                    <div id="alertasSignosVitales" class="mt-3"></div>

                    <input type="hidden" id="idCitaTriaje" name="id_cita">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar Triaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver triaje -->
<div class="modal fade" id="modalVerTriaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>
                    Ver Triaje Completado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body" id="contenidoTriaje">
                <!-- Se llenará dinámicamente -->
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" id="btnEditarTriaje">
                    <i class="bi bi-pencil"></i> Editar Triaje
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de estadísticas -->
<div class="modal fade" id="modalEstadisticas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-graph-up me-2"></i>
                    Estadísticas de Triaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body" id="contenidoEstadisticas">
                <!-- Se llenará dinámicamente -->
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>

<script>
// Configuración global para JavaScript
window.triajeConfig = <?= json_encode([
    'baseUrl' => '../../controladores/EnfermeriaControlador/EnfermeriaController.php',
    'permisos' => $permisos ?? [],
    'submenuId' => $id_submenu ?? null,
    'idEnfermero' => $_SESSION['id_usuario'] ?? null,
    'debug' => true
]) ?>;
</script>
<script src="../../js/triaje.js"></script>

