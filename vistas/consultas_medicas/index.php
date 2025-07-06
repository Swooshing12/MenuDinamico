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
    <link rel="stylesheet" href="../../estilos/triaje.css">
    <style>
        .card-paciente {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .card-paciente:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-paciente.border-primary {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .prioridad-urgente { border-left: 4px solid #dc3545; }
        .prioridad-moderada { border-left: 4px solid #ffc107; }
        .prioridad-baja { border-left: 4px solid #198754; }
        .consultado { opacity: 0.7; }
        .signo-vital {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e9ecef;
        }
        .btn-consultar {
            background: linear-gradient(45deg, #0d6efd, #6610f2);
            border: none;
            color: white;
        }
        .btn-consultar:hover {
            background: linear-gradient(45deg, #0b5ed7, #5a0fc1);
            color: white;
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-primary-light { background-color: rgba(13, 110, 253, 0.1); }
        .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
        .bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
        .bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
    </style>
    
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

            <!-- Resto del contenido igual que antes... -->
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

            <!-- Contenido Principal -->
            <div class="row">
                <!-- Lista de Pacientes -->
                <div class="col-md-8">
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

                <!-- Panel Información del Paciente -->
                <div class="col-md-4">
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
</main>

    <!-- ✅ MODAL CONSULTA MÉDICA COMPLETO -->
    <div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalConsultaLabel">
                        <i class="bi bi-heart-pulse me-2"></i>
                        Realizar Consulta Médica
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formConsulta">
                        <input type="hidden" id="idCita" name="id_cita">
                        <input type="hidden" id="idHistorial" name="id_historial">
                        
                        <!-- Información del Paciente -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-person-badge me-2"></i>
                                            Información del Paciente
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Nombre:</strong><br>
                                                <span id="nombrePacienteModal" class="text-primary">-</span>
                                            </div>
                                            <div class="col-md-2">
                                                <strong>Cédula:</strong><br>
                                                <span id="cedulaPacienteModal" class="text-primary">-</span>
                                            </div>
                                            <div class="col-md-2">
                                                <strong>Edad:</strong><br>
                                                <span id="edadPacienteModal" class="text-primary">-</span>
                                            </div>
                                            <div class="col-md-2">
                                                <strong>Tipo Sangre:</strong><br>
                                                <span id="tipoSangreModal" class="text-primary">-</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Alergias:</strong><br>
                                                <span id="alergiasModal" class="text-danger">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Triaje -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6><i class="bi bi-activity me-2"></i>Datos del Triaje</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <label class="form-label small">Peso</label>
                                                <div class="form-control-plaintext fw-bold" id="pesoTriaje">-</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Talla</label>
                                                <div class="form-control-plaintext fw-bold" id="tallaTriaje">-</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Presión Arterial</label>
                                                <div class="form-control-plaintext fw-bold" id="presionTriaje">-</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Frecuencia Cardíaca</label>
                                                <div class="form-control-plaintext fw-bold" id="frecuenciaTriaje">-</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Temperatura</label>
                                                <div class="form-control-plaintext fw-bold" id="temperaturaTriaje">-</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Saturación O2</label>
                                                <div class="form-control-plaintext fw-bold" id="saturacionTriaje">-</div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label small">Prioridad</label>
                                                <div><span id="prioridadTriaje" class="badge bg-secondary">-</span></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Observaciones del Triaje</label>
                                                <div class="form-control-plaintext small" id="sintomasTriaje">-</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de Consulta -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-chat-text me-1"></i>
                                        Motivo de Consulta <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="motivo_consulta" class="form-control" rows="4" required 
                                              placeholder="Describa el motivo principal de la consulta..."></textarea>
                                    <div class="form-text">Campo obligatorio</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-symptoms me-1"></i>
                                        Sintomatología
                                    </label>
                                    <textarea name="sintomatologia" class="form-control" rows="4" 
                                              placeholder="Describa los síntomas del paciente..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-clipboard-check me-1"></i>
                                        Diagnóstico <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="diagnostico" class="form-control" rows="4" required 
                                              placeholder="Diagnóstico médico..."></textarea>
                                    <div class="form-text">Campo obligatorio</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-prescription2 me-1"></i>
                                        Tratamiento
                                    </label>
                                    <textarea name="tratamiento" class="form-control" rows="4" 
                                              placeholder="Tratamiento prescrito..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-journal-text me-1"></i>
                                        Observaciones
                                    </label>
                                    <textarea name="observaciones" class="form-control" rows="3" 
                                              placeholder="Observaciones adicionales..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        Fecha de Seguimiento
                                    </label>
                                    <input type="date" name="fecha_seguimiento" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <div class="form-text">Opcional</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="btnGuardarConsulta">
                        <i class="bi bi-check-circle me-1"></i>
                        Guardar Consulta
                    </button>
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