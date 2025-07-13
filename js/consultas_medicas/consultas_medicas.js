/**
 * Sistema de Gestión de Consultas Médicas - JavaScript
 * Autor: Sistema MediSys
 * Descripción: Manejo completo de consultas médicas para médicos
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: true,
    baseUrl: window.consultasConfig?.baseUrl || '../../controladores/ConsultasMedicasControlador/ConsultasMedicasController.php',
    permisos: window.consultasConfig?.permisos || {},
    submenuId: window.consultasConfig?.submenuId || null,
    idMedico: window.consultasConfig?.idMedico || null,
    nombreMedico: window.consultasConfig?.nombreMedico || 'Médico',
    especialidad: window.consultasConfig?.especialidad || 'Medicina General'
};

// Variables globales
let citasDelDia = [];
let citaSeleccionada = null;
let modalConsulta = null;

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🩺 === INICIANDO SISTEMA DE CONSULTAS MÉDICAS ===');
    
    // ✅ DEBUG COMPLETO
    console.log('🔧 window.consultasConfig:', window.consultasConfig);
    console.log('🔧 config final:', config);
    console.log('🔧 ID Médico:', config.idMedico);
    console.log('🔧 Base URL:', config.baseUrl);
    
    // ✅ VERIFICACIÓN CRÍTICA
    if (!config.idMedico) {
        console.error('❌ ERROR CRÍTICO: ID del médico no está disponible');
        mostrarError('Error: No se pudo identificar al médico. Por favor, contacte al administrador.');
        return;
    }
    
    console.log('✅ ID Médico encontrado:', config.idMedico);

    inicializarEventos();
    inicializarFlatpickr();
    inicializarModal();
    cargarDatosIniciales();
    
    console.log('✅ Sistema de consultas médicas inicializado completamente');
});

// ===== EVENTOS PRINCIPALES =====
function inicializarEventos() {
    console.log('🎯 Inicializando eventos...');
    
    // Eventos de fecha
    $('#fechaConsulta').on('change', function() {
        console.log('📅 Fecha cambiada a:', $(this).val());
        cargarCitasConTriaje();
    });
    
    // Eventos de botones principales
    $('#btnRefrescar').on('click', function() {
        console.log('🔄 Botón refrescar clickeado');
        cargarCitasConTriaje();
    });
    
    // Eventos de filtros
    $('#filtroEstado').on('change', function() {
        console.log('🔍 Filtro cambiado a:', $(this).val());
        filtrarCitas();
    });
    
    // Eventos del formulario de consulta
    $('#formConsulta').on('submit', function(e) {
        e.preventDefault();
        console.log('💾 Formulario enviado');
        guardarConsulta();
    });
    
    $('#btnGuardarConsulta').on('click', function() {
        console.log('💾 Botón guardar consulta clickeado');
        guardarConsulta();
    });
    
    // Eventos de modales
    $('#modalConsulta').on('hidden.bs.modal', function() {
        console.log('🧹 Modal cerrado, limpiando formulario');
        limpiarFormularioConsulta();
    });
    
    console.log('✅ Eventos inicializados');
}

function inicializarFlatpickr() {
    flatpickr("#fechaConsulta", {
        locale: "es",
        dateFormat: "Y-m-d",
        defaultDate: "today",
        maxDate: new Date().fp_incr(7) // Máximo 7 días en el futuro
    });
    console.log('📅 Flatpickr inicializado');
}

function inicializarModal() {
    modalConsulta = new bootstrap.Modal(document.getElementById('modalConsulta'));
    console.log('🪟 Modal inicializado');
}

// ===== CARGAR DATOS INICIALES =====
function cargarDatosIniciales() {
    console.log('📊 Cargando datos iniciales...');
    mostrarLoading(true);
    cargarEstadisticas();
    cargarCitasConTriaje();
}

// ===== CARGAR ESTADÍSTICAS =====
function cargarEstadisticas() {
    console.log('📊 Cargando estadísticas del médico...');
    
    $.ajax({
        url: config.baseUrl,
        method: 'GET',
        data: {
            action: 'obtenerEstadisticasMedico'
        },
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta estadísticas:', response);
            
            if (response.success) {
                const stats = response.data;
                $('#citasHoy').text(stats.citas_hoy || 0);
                $('#consultasHoy').text(stats.consultas_hoy || 0);
                $('#pendientesHoy').text(stats.pendientes_hoy || 0);
                $('#citasSemana').text(stats.citas_semana || 0);
                console.log('✅ Estadísticas actualizadas');
            } else {
                console.error('❌ Error en estadísticas:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX estadísticas:', error);
            console.error('❌ Estado:', status);
            console.error('❌ Respuesta:', xhr.responseText);
        }
    });
}

// ===== CARGAR CITAS CON TRIAJE =====
function cargarCitasConTriaje() {
    const fecha = $('#fechaConsulta').val();
    
    console.log('📅 Cargando citas para fecha:', fecha);
    console.log('🩺 ID Médico para consulta:', config.idMedico);
    
    // Mostrar loading
    mostrarLoading(true);
    
    $.ajax({
        url: config.baseUrl,
        method: 'GET',
        data: {
            action: 'obtenerCitasConTriaje',
            fecha: fecha
        },
        dataType: 'json',
        success: function(response) {
            console.log('📋 Respuesta completa citas:', response);
            
            mostrarLoading(false);
            
            if (response.success) {
                citasDelDia = response.data;
                console.log('✅ Citas cargadas:', citasDelDia.length);
                console.log('📊 Datos de citas:', citasDelDia);
                
                mostrarCitas(citasDelDia);
                actualizarContadores();
            } else {
                console.error('❌ Error en respuesta:', response.message);
                mostrarError('Error al cargar citas: ' + response.message);
                $('#listaPacientes').html(crearMensajeVacio('error', 'Error al cargar las citas: ' + response.message));
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX citas:', error);
            console.error('❌ Estado:', status);
            console.error('❌ Respuesta completa:', xhr.responseText);
            console.error('❌ Status code:', xhr.status);
            
            mostrarLoading(false);
            mostrarError('Error de conexión al cargar las citas. Código: ' + xhr.status);
            $('#listaPacientes').html(crearMensajeVacio('error', 'Error de conexión. Verifique la consola para más detalles.'));
        }
    });
}

// ===== MOSTRAR CITAS =====
function mostrarCitas(citas) {
    const container = $('#listaPacientes');
    
    console.log('🎨 Mostrando citas:', citas.length);
    
    if (!citas || citas.length === 0) {
        container.html(crearMensajeVacio('info', 'No hay pacientes con triaje completado para la fecha seleccionada'));
        return;
    }
    
    let html = '';
    
    citas.forEach((cita, index) => {
        console.log(`📋 Procesando cita ${index + 1}:`, cita);
        
        const yaConsultado = cita.tiene_consulta == 1;
        const prioridadClass = getPrioridadClass(cita.prioridad);
        const prioridadColor = getPrioridadColor(cita.prioridad);
        const edadPaciente = cita.edad_paciente || calcularEdad(cita.fecha_nacimiento);
        
        html += `
            <div class="card card-paciente ${prioridadClass} ${yaConsultado ? 'consultado' : ''} mb-3" 
                 data-cita-id="${cita.id_cita}" 
                 onclick="${yaConsultado ? '' : 'seleccionarPaciente(' + cita.id_cita + ')'}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1">
                                    <i class="bi bi-person-circle me-2"></i>
                                    ${cita.nombres_paciente} ${cita.apellidos_paciente}
                                    ${yaConsultado ? '<span class="badge bg-success ms-2">CONSULTADO</span>' : ''}
                                </h6>
                                <span class="badge bg-${prioridadColor}">${cita.prioridad}</span>
                            </div>
                            
                            <div class="row text-muted small mb-2">
                                <div class="col-md-6">
                                    <i class="bi bi-card-text me-1"></i>
                                    CI: ${cita.cedula_paciente}
                                </div>
                                <div class="col-md-6">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    ${edadPaciente} años
                                </div>
                                <div class="col-md-6">
                                    <i class="bi bi-clock me-1"></i>
                                    ${formatearHora(cita.fecha_hora)}
                                </div>
                                <div class="col-md-6">
                                    <i class="bi bi-droplet me-1"></i>
                                    ${cita.tipo_sangre || 'No especificado'}
                                </div>
                            </div>
                            
                            <p class="mb-1">
                                <strong>Motivo:</strong> ${cita.motivo}
                            </p>
                            
                            ${cita.alergias ? `
                                <div class="alert alert-warning alert-sm mt-2 mb-0 py-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Alergias:</strong> ${cita.alergias}
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <!-- Signos Vitales Resumidos -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">P.A.</small>
                                    <strong class="small">${cita.presion_arterial || '--'}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Temp</small>
                                    <strong class="small">${cita.temperatura || '--'}°C</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">F.C.</small>
                                    <strong class="small">${cita.frecuencia_cardiaca || '--'} bpm</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Sat O2</small>
                                    <strong class="small">${cita.saturacion_oxigeno || '--'}%</strong>
                                </div>
                            </div>
                            
                            <!-- Botones de Acción -->
                            <div class="d-grid gap-2">
                                ${!yaConsultado ? `
                                    <button class="btn btn-consultar btn-sm" onclick="event.stopPropagation(); abrirModalConsulta(${cita.id_cita})">
                                        <i class="bi bi-heart-pulse me-1"></i>
                                        Realizar Consulta
                                    </button>
                                ` : `
                                    <button class="btn btn-outline-success btn-sm" disabled>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Consulta Completada
                                    </button>
                                `}
                                <button class="btn btn-outline-info btn-sm" onclick="event.stopPropagation(); verHistorialCompleto(${cita.id_cita})">
                                    <i class="bi bi-clipboard2-data me-1"></i>
                                    Ver Historial
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
    console.log('✅ Citas mostradas en el DOM');
}

// ===== SELECCIONAR PACIENTE =====
function seleccionarPaciente(idCita) {
    console.log('👤 Seleccionando paciente con cita ID:', idCita);
    
    const cita = citasDelDia.find(c => c.id_cita == idCita);
    if (!cita) {
        console.error('❌ Cita no encontrada:', idCita);
        mostrarError('No se encontró la cita seleccionada');
        return;
    }
    
    citaSeleccionada = cita;
    console.log('✅ Paciente seleccionado:', cita);
    
    mostrarInfoPaciente(cita);
    cargarHistorialPaciente(cita);
    
    // Resaltar la cita seleccionada
    $('.card-paciente').removeClass('border-primary');
    $(`.card-paciente[data-cita-id="${idCita}"]`).addClass('border-primary');
}

// ===== MOSTRAR INFORMACIÓN DEL PACIENTE =====
function mostrarInfoPaciente(cita) {
    console.log('ℹ️ Mostrando información del paciente');
    
    const edadPaciente = cita.edad_paciente || calcularEdad(cita.fecha_nacimiento);
    
    const html = `
        <div class="paciente-info">
            <h6 class="mb-3">
                <i class="bi bi-person-circle me-2"></i>
                ${cita.nombres_paciente} ${cita.apellidos_paciente}
            </h6>
            
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Cédula:</strong><br>
                    <span class="text-primary">${cita.cedula_paciente}</span>
                </div>
                <div class="col-6">
                    <strong>Edad:</strong><br>
                    <span class="text-primary">${edadPaciente} años</span>
                </div>
                <div class="col-6">
                    <strong>Tipo Sangre:</strong><br>
                    <span class="text-primary">${cita.tipo_sangre || 'No especificado'}</span>
                </div>
                <div class="col-6">
                    <strong>Prioridad:</strong><br>
                    <span class="badge bg-${getPrioridadColor(cita.prioridad)}">${cita.prioridad}</span>
                </div>
            </div>
            
            ${cita.alergias ? `
                <div class="alert alert-warning py-2">
                    <strong><i class="bi bi-exclamation-triangle me-1"></i>Alergias:</strong><br>
                    ${cita.alergias}
                </div>
            ` : ''}
            
            <hr>
            
            <h6><i class="bi bi-activity me-2"></i>Signos Vitales (Triaje)</h6>
            <div class="row text-center">
                <div class="col-6 mb-2">
                    <div class="signo-vital">
                        <strong>${cita.peso || '--'} kg</strong>
                        <small class="d-block text-muted">Peso</small>
                    </div>
                </div>
                <div class="col-6 mb-2">
                    <div class="signo-vital">
                        <strong>${cita.talla || '--'} cm</strong>
                        <small class="d-block text-muted">Talla</small>
                    </div>
                </div>
                <div class="col-6 mb-2">
                    <div class="signo-vital">
                        <strong>${cita.presion_arterial || '--'}</strong>
                        <small class="d-block text-muted">P.A.</small>
                    </div>
                </div>
                <div class="col-6 mb-2">
                    <div class="signo-vital">
                        <strong>${cita.frecuencia_cardiaca || '--'} bpm</strong>
                        <small class="d-block text-muted">F.C.</small>
                    </div>
                </div>
                <div class="col-6 mb-2">
                    <div class="signo-vital">
                        <strong>${cita.temperatura || '--'} °C</strong>
                        <small class="d-block text-muted">Temp.</small>
                    </div>
                </div>
                <div class="col-6 mb-2">
                    <div class="signo-vital">
                        <strong>${cita.saturacion_oxigeno || '--'} %</strong>
                        <small class="d-block text-muted">Sat O2</small>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <strong>Nivel Urgencia:</strong> 
                <span class="badge bg-${getPrioridadColor(cita.prioridad)}">${cita.prioridad} (${cita.nivel_urgencia}/4)</span>
            </div>
            
            ${cita.observaciones ? `
                <div class="mt-3">
                    <strong>Observaciones:</strong><br>
                    <span class="text-muted">${cita.observaciones}</span>
                </div>
            ` : ''}
        </div>
    `;
    
    $('#infoPaciente').html(html);
}

// ===== CARGAR HISTORIAL DEL PACIENTE =====
function cargarHistorialPaciente(cita) {
    console.log('📋 Cargando historial del paciente ID:', cita.id_paciente);
    
    if (!cita.id_paciente) {
        $('#historialPaciente').html(crearMensajeVacio('warning', 'No se pudo obtener el ID del paciente'));
        return;
    }
    
    $.ajax({
        url: config.baseUrl,
        method: 'GET',
        data: {
            action: 'obtenerHistorialPaciente',
            id_paciente: cita.id_paciente
        },
        dataType: 'json',
        success: function(response) {
            console.log('📋 Historial obtenido:', response);
            
            if (response.success) {
                mostrarHistorialPaciente(response.data);
            } else {
                $('#historialPaciente').html(crearMensajeVacio('error', 'Error: ' + response.message));
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando historial:', error);
            $('#historialPaciente').html(crearMensajeVacio('error', 'Error de conexión al cargar historial'));
        }
    });
}

// ===== RESTO DE FUNCIONES =====

function mostrarHistorialPaciente(historial) {
    const container = $('#historialPaciente');
    
    if (!historial || historial.length === 0) {
        container.html(crearMensajeVacio('info', 'Paciente nuevo: No hay consultas médicas anteriores registradas.'));
        return;
    }
    
    let html = '<div class="timeline">';
    
    historial.forEach((consulta, index) => {
        html += `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <h6 class="mb-2">
                        <i class="bi bi-calendar3 me-1"></i>
                        ${formatearFecha(consulta.fecha_cita)}
                    </h6>
                    <p class="mb-1">
                        <strong>Dr. ${consulta.doctor_nombres} ${consulta.doctor_apellidos}</strong>
                        <span class="badge bg-primary ms-2">${consulta.nombre_especialidad}</span>
                    </p>
                    <p class="mb-1"><strong>Diagnóstico:</strong> ${consulta.diagnostico}</p>
                    ${consulta.tratamiento ? `<p class="mb-1"><strong>Tratamiento:</strong> ${consulta.tratamiento}</p>` : ''}
                    ${consulta.observaciones ? `<p class="mb-0"><strong>Observaciones:</strong> ${consulta.observaciones}</p>` : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.html(html);
}

// ===== ABRIR MODAL CONSULTA =====
function abrirModalConsulta(idCita) {
    console.log('🪟 Abriendo modal para cita:', idCita);
    
    const cita = citasDelDia.find(c => c.id_cita == idCita);
    if (!cita) {
        mostrarError('No se encontró la cita seleccionada');
        return;
    }
    
    // Llenar datos del modal
    $('#idCita').val(cita.id_cita);
    $('#idHistorial').val(cita.id_historial || '');
    
    // Información del paciente
    const edadPaciente = cita.edad_paciente || calcularEdad(cita.fecha_nacimiento);
    $('#nombrePacienteModal').text(`${cita.nombres_paciente} ${cita.apellidos_paciente}`);
    $('#cedulaPacienteModal').text(cita.cedula_paciente);
    $('#edadPacienteModal').text(edadPaciente + ' años');
    $('#tipoSangreModal').text(cita.tipo_sangre || 'No especificado');
    $('#alergiasModal').text(cita.alergias || 'Ninguna registrada');
    
    // Signos vitales del triaje
    $('#pesoTriaje').text(cita.peso || '--');
    $('#tallaTriaje').text(cita.talla || '--');
    $('#presionTriaje').text(cita.presion_arterial || '--');
    $('#frecuenciaTriaje').text(cita.frecuencia_cardiaca || '--');
    $('#temperaturaTriaje').text(cita.temperatura || '--');
    $('#saturacionTriaje').text(cita.saturacion_oxigeno || '--');
    $('#sintomasTriaje').text(cita.observaciones || 'No especificados');
    
    const prioridadBadge = $('#prioridadTriaje');
    prioridadBadge.text(cita.prioridad);
    prioridadBadge.attr('class', `badge bg-${getPrioridadColor(cita.prioridad)}`);
    
    // Pre-llenar motivo de consulta
    $('textarea[name="motivo_consulta"]').val(cita.motivo);
    
    // Abrir modal
    modalConsulta.show();
}

function guardarConsulta() {
    const form = document.getElementById('formConsulta');
    if (!form) {
        console.error('❌ Formulario no encontrado');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'crearConsulta');
    formData.append('submenu_id', config.submenuId);
    
    // 🔥 IMPORTANTE: Capturar el ID de la cita antes del envío
    const id_cita = formData.get('id_cita');
    
    if (!id_cita) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar la cita. Por favor, intente nuevamente.'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando consulta...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('✅ Respuesta guardar consulta:', response);
            
            if (response.success) {
                Swal.close();
                
                // 🔥 MOSTRAR MENSAJE DE ÉXITO CON BOTÓN CORREGIDO
                const mensajePDF = response.pdf_enviado ? 
                    '<span class="text-success">✅ PDF enviado automáticamente al correo del paciente.</span>' :
                    '<span class="text-warning">⚠️ Error al enviar PDF automáticamente.</span>';
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Consulta Guardada Exitosamente!',
                    html: `
                        <div class="text-center">
                            <p class="mb-3"><strong>${response.message}</strong></p>
                            <div class="alert alert-info mb-3">
                                ${mensajePDF}
                            </div>
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-danger btn-sm" onclick="generarPDFConsulta(${id_cita})" type="button">
                                    <i class="bi bi-file-pdf me-1"></i>Descargar PDF
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="imprimirConsulta(${id_cita})" type="button">
                                    <i class="bi bi-printer me-1"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#198754',
                    width: '500px'
                }).then(() => {
                    // Cerrar modal y recargar datos
                    if (modalConsulta) {
                        modalConsulta.hide();
                    }
                    cargarCitasDelDia();
                });
                
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al guardar la consulta'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX guardar consulta:', error);
            console.error('❌ Respuesta del servidor:', xhr.responseText);
            
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'Error al comunicarse con el servidor: ' + error
            });
        }
    });
}

// 🔥 NUEVA FUNCIÓN: Generar PDF desde consultas médicas
function generarPDFConsulta(id_cita) {
    if (!id_cita) {
        console.error('❌ ID de cita no válido');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'ID de cita no válido'
        });
        return;
    }
    
    console.log('📄 Generando PDF para cita:', id_cita);
    
    // Mostrar loading
    Swal.fire({
        title: 'Generando PDF...',
        text: 'Por favor espere mientras se genera el documento',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // 🔥 USAR LA RUTA CORRECTA PARA CONSULTAS MÉDICAS
    const url = `${config.baseUrl}?action=generarPDFConsulta&id_cita=${id_cita}`;
    
    // Crear iframe oculto para la descarga
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    
    // Simular tiempo de generación y cerrar loading
    setTimeout(() => {
        Swal.close();
        // Limpiar iframe después de un tiempo
        setTimeout(() => {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 2000);
    }, 1500);
}

// 🔥 NUEVA FUNCIÓN: Imprimir consulta
function imprimirConsulta(id_cita) {
    if (!id_cita) {
        console.error('❌ ID de cita no válido');
        return;
    }
    
    console.log('🖨️ Imprimiendo consulta:', id_cita);
    
    // Por ahora, redirigir al PDF para imprimir
    const url = `${config.baseUrl}?action=generarPDFConsulta&id_cita=${id_cita}`;
    window.open(url, '_blank');
}
// 🔥 NUEVA FUNCIÓN: Mostrar modal de resumen
function mostrarModalResumen(id_cita) {
    // Obtener datos completos de la consulta
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerDatosConsultaCompleta',
            id_cita: id_cita
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const datos = response.data;
                
                // Crear el modal dinámicamente
                const modalHTML = crearModalResumen(datos);
                
                // Agregar al DOM
                $('body').append(modalHTML);
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalResumenConsulta'));
                modal.show();
                
                // Limpiar modal al cerrar
                $('#modalResumenConsulta').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
                
            } else {
                console.error('❌ Error obteniendo datos:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX obtener datos:', error);
        }
    });
}

// 🔥 NUEVA FUNCIÓN: Crear HTML del modal de resumen
function crearModalResumen(datos) {
    const fechaCita = new Date(datos.fecha_hora).toLocaleString('es-ES');
    const fechaConsulta = datos.fecha_consulta ? new Date(datos.fecha_consulta).toLocaleString('es-ES') : 'No registrada';
    
    return `
    <div class="modal fade" id="modalResumenConsulta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>
                        Consulta Completada - Cita #${datos.id_cita}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Información del Paciente -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-person me-2"></i>Información del Paciente</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong> ${datos.nombres_paciente} ${datos.apellidos_paciente}</p>
                                    <p><strong>Cédula:</strong> ${datos.cedula_paciente}</p>
                                    <p><strong>Correo:</strong> ${datos.correo_paciente || 'No disponible'}</p>
                                    <p><strong>Tipo de Sangre:</strong> ${datos.tipo_sangre || 'No especificado'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de la Cita -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bi bi-calendar me-2"></i>Información de la Cita</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Fecha/Hora:</strong> ${fechaCita}</p>
                                    <p><strong>Especialidad:</strong> ${datos.nombre_especialidad}</p>
                                    <p><strong>Sucursal:</strong> ${datos.nombre_sucursal}</p>
                                    <p><strong>Estado:</strong> <span class="badge bg-success">${datos.estado}</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Triaje -->
                        ${datos.nivel_urgencia ? `
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-clipboard-pulse me-2"></i>Triaje</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Temperatura:</strong> ${datos.temperatura || '-'}°C</p>
                                    <p><strong>Presión:</strong> ${datos.presion_arterial || '-'}</p>
                                    <p><strong>Peso:</strong> ${datos.peso || '-'} kg</p>
                                    <p><strong>Urgencia:</strong> ${datos.nivel_urgencia}/5</p>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Consulta Médica -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Consulta Médica</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Fecha Consulta:</strong> ${fechaConsulta}</p>
                                    <p><strong>Motivo:</strong> ${datos.motivo_consulta || '-'}</p>
                                    <p><strong>Diagnóstico:</strong> ${datos.diagnostico || '-'}</p>
                                    <p><strong>Tratamiento:</strong> ${datos.tratamiento || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    ${datos.consulta_observaciones ? `
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-chat-text me-2"></i>Observaciones</h6>
                        </div>
                        <div class="card-body">
                            <p>${datos.consulta_observaciones}</p>
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="modal-footer">
                    <div class="alert alert-success flex-grow-1 mb-0 me-3">
                        <i class="bi bi-envelope-check me-2"></i>
                        <strong>PDF enviado automáticamente</strong> al correo del paciente.
                    </div>
                    
                    <button type="button" class="btn btn-danger" onclick="generarPDFModal(${datos.id_cita})">
                        <i class="bi bi-file-pdf me-1"></i>Descargar PDF
                    </button>
                    
                    <button type="button" class="btn btn-primary" onclick="imprimirResumen()">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </button>
                    
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>`;
}

// 🔥 NUEVA FUNCIÓN: Generar PDF desde modal
function generarPDFModal(id_cita) {
    window.open(`../../controladores/PacientesControlador/GenerarPDFCita.php?accion=generar_pdf&id_cita=${id_cita}`, '_blank');
}

// 🔥 NUEVA FUNCIÓN: Imprimir resumen
function imprimirResumen() {
    const contenido = document.getElementById('modalResumenConsulta').querySelector('.modal-body').innerHTML;
    
    const ventanaImpresion = window.open('', '_blank');
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Resumen de Consulta</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h2 class="text-center mb-4">🏥 MediSys - Resumen de Consulta</h2>
            ${contenido}
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                        window.close();
                    }, 500);
                };
            </script>
        </body>
        </html>
    `);
    ventanaImpresion.document.close();
}

// ===== FUNCIONES AUXILIARES =====

function mostrarLoading(mostrar) {
    if (mostrar) {
        $('#listaPacientes').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3 text-muted">Cargando pacientes...</p>
            </div>
        `);
    }
}

function crearMensajeVacio(tipo, mensaje) {
    const iconos = {
        'info': 'bi-info-circle',
        'warning': 'bi-exclamation-triangle',
        'error': 'bi-exclamation-circle'
    };
    
    const colores = {
        'info': 'text-info',
        'warning': 'text-warning',
        'error': 'text-danger'
    };
    
    return `
        <div class="text-center ${colores[tipo]} py-4">
            <i class="${iconos[tipo]} fs-1 mb-3"></i>
            <p>${mensaje}</p>
        </div>
    `;
}

function limpiarFormularioConsulta() {
    $('#formConsulta')[0].reset();
    citaSeleccionada = null;
    console.log('🧹 Formulario de consulta limpiado');
}

function calcularEdad(fechaNacimiento) {
    if (!fechaNacimiento) return 0;
    
    const hoy = new Date();
    const nacimiento = new Date(fechaNacimiento);
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    
    return edad;
}

function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatearHora(fechaHora) {
    return new Date(fechaHora).toLocaleTimeString('es-ES', {
       hour: '2-digit',
        minute: '2-digit'
    });
}

function getPrioridadClass(prioridad) {
    switch (prioridad) {
        case 'Urgente': return 'prioridad-urgente';
        case 'Moderada': return 'prioridad-moderada';
        case 'Baja': return 'prioridad-baja';
        default: return '';
    }
}

function getPrioridadColor(prioridad) {
    switch (prioridad) {
        case 'Urgente': return 'danger';
        case 'Moderada': return 'warning';
        case 'Baja': return 'success';
        default: return 'secondary';
    }
}

function filtrarCitas() {
    const filtro = $('#filtroEstado').val();
    let citasFiltradas = citasDelDia;
    
    console.log('🔍 Filtrando citas por:', filtro);
    
    switch (filtro) {
        case 'pendientes':
            citasFiltradas = citasDelDia.filter(cita => cita.tiene_consulta == 0);
            break;
        case 'consultados':
            citasFiltradas = citasDelDia.filter(cita => cita.tiene_consulta == 1);
            break;
        default:
            citasFiltradas = citasDelDia;
            break;
    }
    
    console.log('🔍 Citas filtradas:', citasFiltradas.length);
    mostrarCitas(citasFiltradas);
}

function buscarPaciente(termino) {
    console.log('🔍 Buscando paciente:', termino);
    
    if (!termino || termino.length < 3) {
        mostrarCitas(citasDelDia);
        return;
    }
    
    const terminoLower = termino.toLowerCase();
    const citasFiltradas = citasDelDia.filter(cita => {
        return cita.nombres_paciente.toLowerCase().includes(terminoLower) ||
               cita.apellidos_paciente.toLowerCase().includes(terminoLower) ||
               cita.cedula_paciente.includes(termino) ||
               cita.motivo.toLowerCase().includes(terminoLower);
    });
    
    console.log('🔍 Resultados búsqueda:', citasFiltradas.length);
    mostrarCitas(citasFiltradas);
}

function verHistorialCompleto(idCita) {
    console.log('📋 Ver historial completo para cita:', idCita);
    
    const cita = citasDelDia.find(c => c.id_cita == idCita);
    if (!cita) return;
    
    seleccionarPaciente(idCita);
    
    // Scroll al panel de historial
    document.getElementById('historialPaciente').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

function actualizarContadores() {
    const total = citasDelDia.length;
    const consultados = citasDelDia.filter(c => c.tiene_consulta == 1).length;
    const pendientes = total - consultados;
    
    console.log(`📊 Contadores - Total: ${total}, Consultados: ${consultados}, Pendientes: ${pendientes}`);
}

function mostrarError(mensaje) {
    console.error('❌ Error mostrado al usuario:', mensaje);
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        confirmButtonColor: '#dc3545'
    });
}

function mostrarExito(mensaje) {
    console.log('✅ Éxito mostrado al usuario:', mensaje);
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: mensaje,
        timer: 2000,
        showConfirmButton: false
    });
}

function mostrarInfo(mensaje) {
    Swal.fire({
        icon: 'info',
        title: 'Información',
        text: mensaje,
        confirmButtonColor: '#17a2b8'
    });
}

// ===== FUNCIONES DE IMPRESIÓN =====

function imprimirListaPacientes() {
    console.log('🖨️ Imprimiendo lista de pacientes...');
    
    const contenido = document.getElementById('listaPacientes').innerHTML;
    const ventanaImpresion = window.open('', '_blank');
    
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Lista de Pacientes - ${$('#fechaConsulta').val()}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; }
                .card-paciente { page-break-inside: avoid; margin-bottom: 20px; }
                @media print {
                    .btn { display: none; }
                    .card { border: 1px solid #000 !important; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Lista de Pacientes - ${formatearFecha(new Date())}</h2>
                <p>${config.nombreMedico} - ${config.especialidad}</p>
                <hr>
                ${contenido}
            </div>
        </body>
        </html>
    `);
    
    ventanaImpresion.document.close();
    ventanaImpresion.print();
}

// ===== MANEJO DE ERRORES GLOBALES =====

window.addEventListener('error', function(e) {
    if (config.debug) {
        console.error('❌ Error global capturado:', e.error);
        console.error('❌ Archivo:', e.filename);
        console.error('❌ Línea:', e.lineno);
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (config.debug) {
        console.error('❌ Promise rechazada capturada:', e.reason);
    }
});

// ===== EVENTOS DE TECLADO =====

$(document).keydown(function(e) {
    // ESC para cerrar modales
    if (e.key === 'Escape') {
        if ($('#modalConsulta').hasClass('show')) {
            modalConsulta.hide();
        }
    }
    
    // F5 para refrescar datos
    if (e.key === 'F5') {
        e.preventDefault();
        cargarCitasConTriaje();
    }
    
    // Ctrl+S para guardar consulta (si el modal está abierto)
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        if ($('#modalConsulta').hasClass('show')) {
            guardarConsulta();
        }
    }
});

// ===== CONFIGURACIÓN DE TOOLTIPS =====

$(document).ready(function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    console.log('🎯 Tooltips y popovers inicializados');
});

// ===== EXPORTAR FUNCIONES GLOBALMENTE =====

window.ConsultasMedicas = {
    cargarCitasConTriaje,
    seleccionarPaciente,
    abrirModalConsulta,
    guardarConsulta,
    verHistorialCompleto,
    filtrarCitas,
    buscarPaciente,
    imprimirListaPacientes,
    config,
    citasDelDia,
    citaSeleccionada
};

// ===== LOG DE FINALIZACIÓN =====

console.log('✅ JavaScript de Consultas Médicas cargado completamente');
console.log('🔧 Funciones disponibles en window.ConsultasMedicas:', Object.keys(window.ConsultasMedicas));