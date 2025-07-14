<?php
// ✅ VERIFICAR QUE LAS VARIABLES EXISTAN CON VALORES POR DEFECTO
$nombre_medico = $nombre_medico ?? 'Médico';
$especialidad = $especialidad ?? 'Medicina General';
$titulo_profesional = $titulo_profesional ?? '';
$id_medico = $id_medico ?? null;
$permisos = $permisos ?? ['puede_crear' => 1, 'puede_editar' => 1, 'puede_eliminar' => 0];
$id_submenu = $id_submenu ?? 999;

require_once "../../helpers/permisos.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Médicas | Sistema MediSys</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Flatpickr para fechas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../../estilos/consulta_medica.css">
    
    <!-- ✅ CONFIGURACIÓN PARA JAVASCRIPT -->
    <script>
        // ⭐ PASAR DATOS DESDE PHP A JAVASCRIPT
        window.consultasConfig = {
            debug: true,
            baseUrl: '../../controladores/ConsultasMedicasControlador/ConsultasMedicasController.php',
            permisos: <?php echo json_encode($permisos); ?>,
            submenuId: <?php echo json_encode($id_submenu); ?>,
            idMedico: <?php echo json_encode($id_medico); ?>,
            nombreMedico: <?php echo json_encode($nombre_medico); ?>,
            especialidad: <?php echo json_encode($especialidad); ?>,
            tituloMedico: <?php echo json_encode($titulo_profesional); ?>
        };
        
        console.log('🩺 Configuración cargada:', window.consultasConfig);
        
        // ✅ VERIFICAR QUE TENGAMOS ID DE MÉDICO
        if (!window.consultasConfig.idMedico) {
            console.error('❌ ERROR CRÍTICO: ID del médico no está disponible');
            console.log('🔍 Variables PHP disponibles:');
            console.log('- id_medico:', <?php echo json_encode($id_medico); ?>);
            console.log('- nombre_medico:', <?php echo json_encode($nombre_medico); ?>);
            console.log('- especialidad:', <?php echo json_encode($especialidad); ?>);
        }
    </script>
</head>
<body>
    <!-- Incluir navegación -->
    <?php include "../../navbars/header.php"; ?>
    <?php include "../../navbars/sidebar.php"; ?>

    <!-- Contenido principal -->
   <!-- Contenido principal -->
<main class="dashboard-container">
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="fw-bold text-primary">
                            <i class="bi bi-heart-pulse me-2"></i>
                            Consultas Médicas
                        </h1>
                        <p class="text-muted mb-0">
                            <?php echo htmlspecialchars($titulo_profesional . ' ' . $nombre_medico); ?> - 
                            <?php echo htmlspecialchars($especialidad); ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" id="btnRefrescar">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Actualizar
                        </button>
                        <button class="btn btn-outline-success" onclick="imprimirListaPacientes()">
                            <i class="bi bi-printer me-1"></i>
                            Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary-light me-3">
                            <i class="bi bi-calendar-event text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Citas Hoy</h6>
                            <h3 class="mb-0" id="citasHoy">-</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success-light me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Consultados</h6>
                            <h3 class="mb-0" id="consultasHoy">-</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning-light me-3">
                            <i class="bi bi-clock text-warning fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Pendientes</h6>
                            <h3 class="mb-0" id="pendientesHoy">-</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-info-light me-3">
                            <i class="bi bi-graph-up text-info fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Esta Semana</h6>
                            <h3 class="mb-0" id="citasSemana">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controles -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Consulta</label>
                                <input type="date" id="fechaConsulta" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filtrar por Estado</label>
                                <select id="filtroEstado" class="form-select">
                                    <option value="todos">Todos los pacientes</option>
                                    <option value="pendientes">Solo pendientes</option>
                                    <option value="consultados">Solo consultados</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Buscar Paciente</label>
                                <input type="text" id="buscarPaciente" class="form-control" placeholder="Nombre, cédula o motivo...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div class="display-6 text-primary">
                                <i class="bi bi-activity"></i>
                            </div>
                            <h6 class="mt-2">Sistema Activo</h6>
                            <small class="text-muted" id="horaActual"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pacientes con Triaje Completado (width full) -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>
                            Pacientes con Triaje Completado
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="listaPacientes">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Paciente e Historial Clínico en nueva fila -->
        <div class="row mt-4">
            <div class="col-md-6">
                <!-- Información del Paciente -->
                <div class="card mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="bi bi-person-badge me-2"></i>
                            Información del Paciente
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="infoPaciente">
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-person-plus fs-1 mb-3"></i>
                                <p>Selecciona un paciente para ver su información</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Historial del Paciente -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="bi bi-clipboard2-data me-2"></i>
                            Historial Clínico
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="historialPaciente" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-file-medical fs-1 mb-3"></i>
                                <p>Selecciona un paciente para ver su historial</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ✅ MODAL CONSULTA MÉDICA REDISEÑADO -->
<div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modal-consulta">
            <!-- Header mejorado -->
            <div class="modal-header modal-header-consulta">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div class="header-info">
                        <h5 class="modal-title" id="modalConsultaLabel">
                            Realizar Consulta Médica
                        </h5>
                        <p class="header-subtitle">Complete la información médica del paciente</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body modal-body-consulta">
                <form id="formConsulta">
                    <input type="hidden" id="idCita" name="id_cita">
                    <input type="hidden" id="idHistorial" name="id_historial">
                    
                    <!-- Sección 1: Información del Paciente -->
                    <div class="consulta-section paciente-section">
                        <div class="section-header">
                            <div class="section-icon paciente-icon">
                                <i class="bi bi-person-heart"></i>
                            </div>
                            <div class="section-info">
                                <h6>Información del Paciente</h6>
                                <small>Datos personales y médicos relevantes</small>
                            </div>
                        </div>
                        
                        <div class="paciente-grid">
                            <div class="paciente-card">
                                <div class="paciente-avatar">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="paciente-datos">
                                    <div class="dato-principal">
                                        <span class="dato-label">Nombre Completo</span>
                                        <span id="nombrePacienteModal" class="dato-valor">-</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-cards-grid">
                                <div class="info-card cedula-card">
                                    <div class="card-icon">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                    <div class="card-content">
                                        <span class="card-label">Cédula</span>
                                        <span id="cedulaPacienteModal" class="card-value">-</span>
                                    </div>
                                </div>
                                
                                <div class="info-card edad-card">
                                    <div class="card-icon">
                                        <i class="bi bi-calendar-heart"></i>
                                    </div>
                                    <div class="card-content">
                                        <span class="card-label">Edad</span>
                                        <span id="edadPacienteModal" class="card-value">-</span>
                                    </div>
                                </div>
                                
                                <div class="info-card sangre-card">
                                    <div class="card-icon">
                                        <i class="bi bi-droplet-fill"></i>
                                    </div>
                                    <div class="card-content">
                                        <span class="card-label">Tipo de Sangre</span>
                                        <span id="tipoSangreModal" class="card-value">-</span>
                                    </div>
                                </div>
                                
                                <div class="info-card alergias-card">
                                    <div class="card-icon">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </div>
                                    <div class="card-content">
                                        <span class="card-label">Alergias</span>
                                        <span id="alergiasModal" class="card-value alergias-text">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Signos Vitales del Triaje -->
                    <div class="consulta-section triaje-section">
                        <div class="section-header">
                            <div class="section-icon triaje-icon">
                                <i class="bi bi-activity"></i>
                            </div>
                            <div class="section-info">
                                <h6>Signos Vitales (Triaje)</h6>
                                <small>Datos obtenidos en la evaluación inicial</small>
                            </div>
                        </div>
                        
                        <div class="vitales-modal-grid">
                            <div class="vital-modal-card peso-card">
                                <div class="vital-modal-icon">
                                    <i class="bi bi-person-standing"></i>
                                </div>
                                <div class="vital-modal-info">
                                    <span class="vital-modal-label">Peso</span>
                                    <span id="pesoTriaje" class="vital-modal-value">-</span>
                                    <span class="vital-modal-unit">kg</span>
                                </div>
                            </div>
                            
                            <div class="vital-modal-card talla-card">
                                <div class="vital-modal-icon">
                                    <i class="bi bi-rulers"></i>
                                </div>
                                <div class="vital-modal-info">
                                    <span class="vital-modal-label">Talla</span>
                                    <span id="tallaTriaje" class="vital-modal-value">-</span>
                                    <span class="vital-modal-unit">cm</span>
                                </div>
                            </div>
                            
                            <div class="vital-modal-card presion-card">
                                <div class="vital-modal-icon">
                                    <i class="bi bi-heart-pulse"></i>
                                </div>
                                <div class="vital-modal-info">
                                    <span class="vital-modal-label">Presión Arterial</span>
                                    <span id="presionTriaje" class="vital-modal-value">-</span>
                                    <span class="vital-modal-unit">mmHg</span>
                                </div>
                            </div>
                            
                            <div class="vital-modal-card frecuencia-card">
                                <div class="vital-modal-icon">
                                    <i class="bi bi-heart"></i>
                                </div>
                                <div class="vital-modal-info">
                                    <span class="vital-modal-label">Frecuencia Cardíaca</span>
                                    <span id="frecuenciaTriaje" class="vital-modal-value">-</span>
                                    <span class="vital-modal-unit">bpm</span>
                                </div>
                            </div>
                            
                            <div class="vital-modal-card temperatura-card">
                                <div class="vital-modal-icon">
                                    <i class="bi bi-thermometer-half"></i>
                                </div>
                                <div class="vital-modal-info">
                                    <span class="vital-modal-label">Temperatura</span>
                                    <span id="temperaturaTriaje" class="vital-modal-value">-</span>
                                    <span class="vital-modal-unit">°C</span>
                                </div>
                            </div>
                            
                            <div class="vital-modal-card saturacion-card">
                                <div class="vital-modal-icon">
                                    <i class="bi bi-lungs"></i>
                                </div>
                                <div class="vital-modal-info">
                                    <span class="vital-modal-label">Saturación O₂</span>
                                    <span id="saturacionTriaje" class="vital-modal-value">-</span>
                                    <span class="vital-modal-unit">%</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información adicional del triaje -->
                        <div class="triaje-extra">
                            <div class="prioridad-container">
                                <div class="prioridad-icon">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <div class="prioridad-info">
                                    <span class="prioridad-label">Nivel de Prioridad</span>
                                    <span id="prioridadTriaje" class="badge prioridad-badge">-</span>
                                </div>
                            </div>
                            
                            <div class="observaciones-triaje" id="observacionesTriajeContainer" style="display: none;">
                                <div class="obs-icon">
                                    <i class="bi bi-chat-square-text"></i>
                                </div>
                                <div class="obs-content">
                                    <span class="obs-label">Observaciones del Triaje</span>
                                    <p id="sintomasTriaje" class="obs-text">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Formulario de Consulta -->
                    <div class="consulta-section formulario-section">
                        <div class="section-header">
                            <div class="section-icon formulario-icon">
                                <i class="bi bi-clipboard2-pulse"></i>
                            </div>
                            <div class="section-info">
                                <h6>Consulta Médica</h6>
                                <small>Complete la información de la consulta</small>
                            </div>
                        </div>
                        
                        <div class="formulario-grid">
                            <!-- Primera fila -->
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="bi bi-chat-text"></i>
                                    <span>Motivo de Consulta</span>
                                    <span class="required-indicator">*</span>
                                </label>
                                <textarea name="motivo_consulta" class="form-control-enhanced" rows="4" required 
                                          placeholder="Describa detalladamente el motivo principal de la consulta..."></textarea>
                                <div class="form-help">Campo obligatorio</div>
                            </div>
                            
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="bi bi-symptoms"></i>
                                    <span>Sintomatología</span>
                                </label>
                                <textarea name="sintomatologia" class="form-control-enhanced" rows="4" 
                                          placeholder="Describa los síntomas presentados por el paciente..."></textarea>
                                <div class="form-help">Opcional</div>
                            </div>
                            
                            <!-- Segunda fila -->
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="bi bi-clipboard-check"></i>
                                    <span>Diagnóstico</span>
                                    <span class="required-indicator">*</span>
                                </label>
                                <textarea name="diagnostico" class="form-control-enhanced" rows="4" required 
                                          placeholder="Establezca el diagnóstico médico basado en la evaluación..."></textarea>
                                <div class="form-help">Campo obligatorio</div>
                            </div>
                            
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="bi bi-prescription2"></i>
                                    <span>Tratamiento</span>
                                </label>
                                <textarea name="tratamiento" class="form-control-enhanced" rows="4" 
                                          placeholder="Indique el tratamiento prescrito, medicamentos y dosis..."></textarea>
                                <div class="form-help">Opcional</div>
                            </div>
                            
                            <!-- Tercera fila -->
                            <div class="form-group-enhanced observaciones-group">
                                <label class="form-label-enhanced">
                                    <i class="bi bi-journal-text"></i>
                                    <span>Observaciones Adicionales</span>
                                </label>
                                <textarea name="observaciones" class="form-control-enhanced" rows="3" 
                                          placeholder="Agregue cualquier observación adicional relevante..."></textarea>
                                <div class="form-help">Opcional</div>
                            </div>
                            
                            <div class="form-group-enhanced seguimiento-group">
                                <label class="form-label-enhanced">
                                    <i class="bi bi-calendar-event"></i>
                                    <span>Fecha de Seguimiento</span>
                                </label>
                                <input type="date" name="fecha_seguimiento" class="form-control-enhanced" 
                                       min="<?php echo date('Y-m-d'); ?>">
                                <div class="form-help">Opcional - Programar próxima consulta</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer mejorado -->
            <div class="modal-footer modal-footer-consulta">
                <div class="footer-info">
                    <div class="info-item">
                        <i class="bi bi-shield-check"></i>
                        <span>Información médica confidencial</span>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-clock"></i>
                        <span id="tiempoConsulta">Iniciado: <span id="horaInicio"></span></span>
                    </div>
                </div>
                
                <div class="footer-actions">
                    <button type="button" class="btn btn-secondary-enhanced" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancelar</span>
                    </button>
                    <button type="button" class="btn btn-success-enhanced" id="btnGuardarConsulta">
                        <i class="bi bi-check-circle"></i>
                        <span>Guardar Consulta</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Consulta Médica (igual que antes) -->
    <!-- ... Resto del modal igual ... -->

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    
    <!-- ✅ TU JAVASCRIPT PERSONALIZADO -->
    <script src="../../js/consultas_medicas/consultas_medicas.js"></script>
    
    <script>
        // Mostrar hora actual
        function actualizarHora() {
            const ahora = new Date();
            document.getElementById('horaActual').textContent = ahora.toLocaleTimeString('es-ES');
        }
        
        actualizarHora();
        setInterval(actualizarHora, 1000);
        
        // Evento de búsqueda
        $('#buscarPaciente').on('keyup', function() {
            const termino = $(this).val();
            if (window.ConsultasMedicas && window.ConsultasMedicas.buscarPaciente) {
                window.ConsultasMedicas.buscarPaciente(termino);
            }
        });
    </script>
</body>
</html>