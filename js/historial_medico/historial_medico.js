/**
 * Sistema de Historial Médico
 * Autor: Sistema MediSys
 * Descripción: Gestión completa del historial clínico por paciente
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    baseUrl: window.historialConfig?.baseUrl || '../../controladores/HistorialMedicoControlador/HistorialMedicoController.php',
    especialidades: window.historialConfig?.especialidades || [],
    sucursales: window.historialConfig?.sucursales || [],
    debug: window.historialConfig?.debug || false
};

// Variables globales
let pacienteActual = null;
let historialCompleto = [];
let tablaHistorial = null;
let filtrosAplicados = {};

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🏥 Iniciando Sistema de Historial Médico');
    
    if (config.debug) {
        console.log('Config:', config);
    }
    
    inicializarEventos();
    cargarDatosIniciales();
    inicializarFlatpickr();
});

// ===== EVENTOS =====
function inicializarEventos() {
    console.log('🔧 Configurando eventos...');
    
    // Formulario de búsqueda por cédula
    $('#formBuscarPaciente').on('submit', buscarPaciente);
    
    // Formulario de filtros
    $('#formFiltros').on('submit', aplicarFiltros);
    $('#btnLimpiarFiltros').on('click', limpiarFiltros);
    
    // Búsqueda en historial
    $('#btnBuscarEnHistorial').on('click', buscarEnHistorial);
    $('#busquedaTermino').on('keypress', function(e) {
        if (e.which === 13) { // Enter
            buscarEnHistorial();
        }
    });
    
    // Exportación
    $('#btnExportarPDF').on('click', exportarHistorial);
    $('#btnImprimirDetalle').on('click', imprimirDetalle);
    
    // Input de cédula - solo números
    $('#cedulaBusqueda').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    console.log('✅ Eventos configurados');
}

function inicializarFlatpickr() {
    // Configurar fechas con límites
    const fechaHoy = new Date();
    const fechaLimite = new Date();
    fechaLimite.setFullYear(fechaHoy.getFullYear() - 10); // 10 años atrás
    
    $('#fechaDesde, #fechaHasta').each(function() {
        $(this).attr('max', fechaHoy.toISOString().split('T')[0]);
        $(this).attr('min', fechaLimite.toISOString().split('T')[0]);
    });
}

function cargarDatosIniciales() {
    console.log('📊 Cargando datos iniciales...');
    
    // Cargar especialidades en el filtro
    cargarEspecialidades();
    
    // Cargar sucursales en el filtro
    cargarSucursales();
}

// ===== BÚSQUEDA DE PACIENTE =====
async function buscarPaciente(e) {
    e.preventDefault();
    
    const cedula = $('#cedulaBusqueda').val().trim();
    
    if (!cedula) {
        mostrarError('Por favor ingrese un número de cédula');
        return;
    }
    
    if (cedula.length < 10) {
        mostrarError('La cédula debe tener al menos 10 dígitos');
        return;
    }
    
    mostrarLoading(true);
    
    try {
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'POST',
            data: {
                action: 'buscar_paciente',
                cedula: cedula
            },
            dataType: 'json'
        });
        
        console.log('📥 Respuesta búsqueda paciente:', response);
        
        if (response.success) {
            pacienteActual = response.data.paciente;
            mostrarInformacionPaciente(response.data.paciente, response.data.estadisticas);
            await cargarHistorialPaciente(pacienteActual.id_paciente);
            mostrarSeccionesPaciente();
        } else {
            mostrarError(response.error || 'No se encontró el paciente');
            ocultarSeccionesPaciente();
        }
        
    } catch (error) {
        console.error('❌ Error buscando paciente:', error);
        mostrarError('Error al buscar el paciente');
        ocultarSeccionesPaciente();
    } finally {
        mostrarLoading(false);
    }
}

function mostrarInformacionPaciente(paciente, estadisticas) {
    console.log('👤 Mostrando información del paciente:', paciente);
    
    // Información básica del paciente
    const infoHtml = `
        <div class="col-md-6">
            <h6 class="text-primary">Datos Personales</h6>
            <div class="row">
                <div class="col-sm-6">
                    <strong>Nombre Completo:</strong><br>
                    <span class="text-muted">${paciente.nombres} ${paciente.apellidos}</span>
                </div>
                <div class="col-sm-6">
                    <strong>Cédula:</strong><br>
                    <span class="text-muted">${paciente.cedula}</span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-6">
                    <strong>Fecha de Nacimiento:</strong><br>
                    <span class="text-muted">${formatearFecha(paciente.fecha_nacimiento)} (${paciente.edad} años)</span>
                </div>
                <div class="col-sm-6">
                    <strong>Género:</strong><br>
                    <span class="text-muted">${paciente.genero || 'No especificado'}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <h6 class="text-primary">Información Médica</h6>
            <div class="row">
                <div class="col-sm-6">
                    <strong>Tipo de Sangre:</strong><br>
                    <span class="text-muted">${paciente.tipo_sangre || 'No especificado'}</span>
                </div>
                <div class="col-sm-6">
                    <strong>Alergias:</strong><br>
                    <span class="text-muted">${paciente.alergias || 'Ninguna registrada'}</span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <strong>Contacto de Emergencia:</strong><br>
                    <span class="text-muted">
                        ${paciente.contacto_emergencia_nombre || 'No registrado'}
                        ${paciente.contacto_emergencia_telefono ? `- ${paciente.contacto_emergencia_telefono}` : ''}
                    </span>
                </div>
            </div>
        </div>
    `;
    
    $('#pacienteInfo').html(infoHtml);
    
    // Estadísticas del paciente
    mostrarEstadisticasPaciente(estadisticas);
    
    // Configurar ID en filtros
    $('#idPacienteFiltros').val(paciente.id_paciente);
    
    // Cargar doctores del paciente para filtros
    cargarDoctoresPaciente(paciente.id_paciente);
}

function mostrarEstadisticasPaciente(stats) {
    const statsHtml = `
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-number text-primary">${stats.total_citas || 0}</div>
                <div class="text-muted">Total de Citas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-number text-success">${stats.consultas_realizadas || 0}</div>
                <div class="text-muted">Consultas Realizadas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div class="stat-number text-info">${stats.especialidades_visitadas || 0}</div>
                <div class="text-muted">Especialidades</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-warning">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-number text-warning">${stats.doctores_diferentes || 0}</div>
                <div class="text-muted">Doctores Diferentes</div>
            </div>
        </div>
    `;
    
    $('#estadisticasPaciente').html(statsHtml);
}

function mostrarSeccionesPaciente() {
    $('#pacienteInfoSection').show();
    $('#filtrosSection').show();
    $('#busquedaHistorialSection').show();
    $('#historialSection').show();
}

function ocultarSeccionesPaciente() {
    $('#pacienteInfoSection').hide();
    $('#filtrosSection').hide();
    $('#busquedaHistorialSection').hide();
    $('#historialSection').hide();
    pacienteActual = null;
    historialCompleto = [];
}

// ===== CARGA DE HISTORIAL =====
async function cargarHistorialPaciente(idPaciente, filtros = {}) {
    if (!idPaciente) return;
    
    mostrarLoading(true);
    
    try {
        const datos = {
            action: 'obtener_historial',
            id_paciente: idPaciente,
            ...filtros
        };
        
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'POST',
            data: datos,
            dataType: 'json'
        });
        
        console.log('📋 Respuesta historial:', response);
        
        if (response.success) {
            historialCompleto = response.data.historial;
            filtrosAplicados = response.data.filtros_aplicados;
            
            mostrarHistorialEnTabla(historialCompleto);
            actualizarContadorRegistros(response.data.total_registros);
        } else {
            mostrarError(response.error || 'Error al cargar el historial');
            mostrarHistorialVacio();
        }
        
    } catch (error) {
        console.error('❌ Error cargando historial:', error);
        mostrarError('Error al cargar el historial médico');
        mostrarHistorialVacio();
    } finally {
        mostrarLoading(false);
    }
}

function mostrarHistorialEnTabla(historial) {
    const tbody = $('#historialTableBody');
    
    if (!historial || historial.length === 0) {
        mostrarHistorialVacio();
        return;
    }
    
    $('#historialVacio').hide();
    
    let html = '';
    
    historial.forEach(cita => {
        html += `
            <tr>
                <td>
                    <div class="fw-bold">${formatearFecha(cita.fecha_hora)}</div>
                    <small class="text-muted">${formatearHora(cita.fecha_hora)}</small>
                </td>
                <td>
                    <span class="badge bg-primary">${cita.nombre_especialidad}</span>
                </td>
                <td>
                    <div class="fw-bold">${cita.doctor_nombre}</div>
                    <small class="text-muted">${cita.nombre_sucursal}</small>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 200px;" title="${cita.motivo_cita}">
                        ${cita.motivo_cita}
                    </div>
                </td>
                <td>
                    ${getEstadoBadge(cita.estado_proceso)}
                </td>
                <td class="text-center">
                    ${cita.id_triage ? 
                        `<i class="bi bi-check-circle-fill text-success" title="Triaje completado"></i>` : 
                        `<i class="bi bi-dash-circle text-muted" title="Sin triaje"></i>`
                    }
                </td>
                <td class="text-center">
                    ${cita.id_consulta ? 
                        `<i class="bi bi-clipboard-check-fill text-success" title="Consulta realizada"></i>` : 
                        `<i class="bi bi-dash-circle text-muted" title="Sin consulta"></i>`
                    }
                </td>
                <td>
                    <div class="btn-group action-buttons" role="group">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="verDetalleCita(${cita.id_cita})"
                                title="Ver detalle completo">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" 
                                onclick="verTimeline(${cita.id_cita})"
                                title="Ver timeline">
                            <i class="bi bi-clock-history"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
    
    // Inicializar DataTable si no existe
    if (!tablaHistorial) {
        inicializarDataTable();
    }
}

function mostrarHistorialVacio() {
    $('#historialTableBody').empty();
    $('#historialVacio').show();
    actualizarContadorRegistros(0);
}

function inicializarDataTable() {
    if (tablaHistorial) {
        tablaHistorial.destroy();
    }
    
    tablaHistorial = $('#tablaHistorial').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        order: [[0, 'desc']], // Ordenar por fecha descendente
        columnDefs: [
            { orderable: false, targets: [5, 6, 7] } // Columnas de triaje, consulta y acciones no ordenables
        ]
    });
}

function actualizarContadorRegistros(total) {
    $('#totalRegistros').text(`${total} registro${total !== 1 ? 's' : ''}`);
}

// ===== FILTROS =====
async function aplicarFiltros(e) {
    e.preventDefault();
    
    if (!pacienteActual) {
        mostrarError('Primero debe buscar un paciente');
        return;
    }
    
    const formData = new FormData(document.getElementById('formFiltros'));
    const filtros = Object.fromEntries(formData.entries());
    
    // Remover campos vacíos
    Object.keys(filtros).forEach(key => {
        if (!filtros[key]) {
            delete filtros[key];
        }
    });
    
    console.log('🔍 Aplicando filtros:', filtros);
    
    await cargarHistorialPaciente(pacienteActual.id_paciente, filtros);
    
    // Mostrar filtros aplicados
    mostrarFiltrosAplicados(filtros);
}

function limpiarFiltros() {
    $('#formFiltros')[0].reset();
    $('#idPacienteFiltros').val(pacienteActual?.id_paciente || '');
    
    if (pacienteActual) {
        cargarHistorialPaciente(pacienteActual.id_paciente);
    }
    
    ocultarFiltrosAplicados();
}

function mostrarFiltrosAplicados(filtros) {
    const filtrosCount = Object.keys(filtros).filter(key => key !== 'id_paciente').length;
    
    if (filtrosCount > 0) {
        // Agregar badge o indicador de filtros activos
        console.log(`✅ ${filtrosCount} filtro(s) aplicado(s)`);
    }
}

function ocultarFiltrosAplicados() {
    console.log('🧹 Filtros limpiados');
}

// ===== BÚSQUEDA EN HISTORIAL =====
async function buscarEnHistorial() {
    const termino = $('#busquedaTermino').val().trim();
    
    if (!termino) {
        mostrarError('Ingrese un término de búsqueda');
        return;
    }
    
    if (!pacienteActual) {
        mostrarError('Primero debe buscar un paciente');
        return;
    }
    
    if (termino.length < 3) {
        mostrarError('El término de búsqueda debe tener al menos 3 caracteres');
        return;
    }
    
    mostrarLoading(true);
    
    try {
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'POST',
            data: {
                action: 'buscar_en_historial',
                id_paciente: pacienteActual.id_paciente,
                termino: termino
            },
            dataType: 'json'
        });
        
        console.log('🔍 Resultados búsqueda:', response);
        
        if (response.success) {
            const resultados = response.data.resultados;
            
            if (resultados.length > 0) {
                mostrarHistorialEnTabla(resultados);
                actualizarContadorRegistros(resultados.length);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Búsqueda completada',
                    text: `Se encontraron ${resultados.length} resultado(s) para "${termino}"`,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                mostrarHistorialVacio();
                
                Swal.fire({
                    icon: 'info',
                    title: 'Sin resultados',
                    text: `No se encontraron resultados para "${termino}"`,
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        } else {
            mostrarError(response.error || 'Error en la búsqueda');
        }
        
    } catch (error) {
        console.error('❌ Error en búsqueda:', error);
        mostrarError('Error al realizar la búsqueda');
    } finally {
        mostrarLoading(false);
    }
}

// ===== DETALLE DE CITA =====
async function verDetalleCita(idCita) {
    mostrarLoading(true);
    
    try {
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'POST',
            data: {
                action: 'obtener_detalle_cita',
                id_cita: idCita
            },
            dataType: 'json'
        });
        
        console.log('📄 Detalle de cita:', response);
        
        if (response.success) {
            mostrarModalDetalle(response.data);
        } else {
            mostrarError(response.error || 'Error al obtener el detalle');
        }
        
    } catch (error) {
        console.error('❌ Error obteniendo detalle:', error);
        mostrarError('Error al cargar el detalle de la cita');
    } finally {
        mostrarLoading(false);
    }
}

function mostrarModalDetalle(detalle) {
    const modalBody = $('#modalDetalleCitaBody');
    
    console.log('📄 DEBUG - Detalle recibido:', detalle); // Para debugging
    
    const html = `
        <div class="row">
            <!-- Información de la Cita -->
            <div class="col-md-6">
                <h6 class="text-primary mb-3">
                    <i class="bi bi-calendar-event me-2"></i>
                    Información de la Cita
                </h6>
                <div class="timeline-item">
                    <strong>Fecha y Hora de la Cita:</strong><br>
                    <span class="text-muted">${formatearFechaHora(detalle.fecha_hora_cita)}</span>
                </div>
                <div class="timeline-item">
                    <strong>Especialidad:</strong><br>
                    <span class="badge bg-primary">${detalle.nombre_especialidad}</span>
                </div>
                <div class="timeline-item">
                    <strong>Doctor:</strong><br>
                    <span class="text-muted">${detalle.doctor_nombre}</span>
                </div>
                <div class="timeline-item">
                    <strong>Sucursal:</strong><br>
                    <span class="text-muted">${detalle.nombre_sucursal}</span>
                </div>
                <div class="timeline-item">
                    <strong>Motivo:</strong><br>
                    <span class="text-muted">${detalle.motivo}</span>
                </div>
                <div class="timeline-item">
                    <strong>Estado:</strong><br>
                    <span class="badge bg-info">${detalle.estado}</span>
                </div>
            </div>
            
            <!-- Información del Paciente -->
            <div class="col-md-6">
                <h6 class="text-primary mb-3">
                    <i class="bi bi-person-badge me-2"></i>
                    Información del Paciente
                </h6>
                <div class="timeline-item">
                    <strong>Nombre:</strong><br>
                    <span class="text-muted">${detalle.paciente_nombre}</span>
                </div>
                <div class="timeline-item">
                    <strong>Cédula:</strong><br>
                    <span class="text-muted">${detalle.paciente_cedula}</span>
                </div>
                <div class="timeline-item">
                    <strong>Edad:</strong><br>
                    <span class="text-muted">${detalle.edad} años</span>
                </div>
                <div class="timeline-item">
                    <strong>Tipo de Sangre:</strong><br>
                    <span class="text-muted">${detalle.tipo_sangre || 'No especificado'}</span>
                </div>
                <div class="timeline-item">
                    <strong>Alergias:</strong><br>
                    <span class="text-muted">${detalle.alergias || 'Ninguna registrada'}</span>
                </div>
                <div class="timeline-item">
                    <strong>Teléfono:</strong><br>
                    <span class="text-muted">${detalle.telefono || 'No registrado'}</span>
                </div>
            </div>
        </div>
        
        ${detalle.id_triage ? `
        <hr class="my-4">
        <div class="row">
            <div class="col-12">
                <h6 class="text-success mb-3">
                    <i class="bi bi-heart-pulse me-2"></i>
                    Información del Triaje
                </h6>
            </div>
            <div class="col-md-6">
                <div class="timeline-item">
                    <strong>Fecha del Triaje:</strong><br>
                    <span class="text-muted">${formatearFechaHora(detalle.fecha_hora_triaje)}</span>
                </div>
                <div class="timeline-item">
                    <strong>Enfermero:</strong><br>
                    <span class="text-muted">${detalle.enfermero_nombre || 'No registrado'}</span>
                </div>
                <div class="timeline-item">
                    <strong>Nivel de Urgencia:</strong><br>
                    <span class="badge ${getNivelUrgenciaBadge(detalle.nivel_urgencia)}">${detalle.nivel_urgencia}/5</span>
                </div>
                <div class="timeline-item">
                    <strong>Estado del Triaje:</strong><br>
                    <span class="badge bg-success">${detalle.estado_triaje || 'Completado'}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="timeline-item">
                    <strong>Signos Vitales:</strong><br>
                    <ul class="list-unstyled text-muted ms-3">
                        ${detalle.temperatura ? `<li>• Temperatura: ${detalle.temperatura}°C</li>` : ''}
                        ${detalle.presion_arterial ? `<li>• Presión: ${detalle.presion_arterial}</li>` : ''}
                        ${detalle.frecuencia_cardiaca ? `<li>• Frecuencia cardíaca: ${detalle.frecuencia_cardiaca} bpm</li>` : ''}
                        ${detalle.frecuencia_respiratoria ? `<li>• Frecuencia respiratoria: ${detalle.frecuencia_respiratoria} rpm</li>` : ''}
                        ${detalle.saturacion_oxigeno ? `<li>• Saturación O2: ${detalle.saturacion_oxigeno}%</li>` : ''}
                        ${detalle.peso ? `<li>• Peso: ${detalle.peso} kg</li>` : ''}
                        ${detalle.talla ? `<li>• Talla: ${detalle.talla} cm</li>` : ''}
                        ${detalle.imc ? `<li>• IMC: ${detalle.imc}</li>` : ''}
                    </ul>
                </div>
                ${detalle.observaciones_triaje ? `
                <div class="timeline-item">
                    <strong>Observaciones del Triaje:</strong><br>
                    <span class="text-muted">${detalle.observaciones_triaje}</span>
                </div>
                ` : ''}
            </div>
        </div>
        ` : ''}
        
        ${detalle.id_consulta ? `
        <hr class="my-4">
        <div class="row">
            <div class="col-12">
                <h6 class="text-info mb-3">
                    <i class="bi bi-clipboard2-pulse me-2"></i>
                    Consulta Médica
                </h6>
            </div>
            <div class="col-md-6">
                <div class="timeline-item">
                    <strong>Fecha de Consulta:</strong><br>
                    <span class="text-muted">${formatearFechaHora(detalle.fecha_hora_consulta)}</span>
                </div>
                <div class="timeline-item">
                    <strong>Motivo de Consulta:</strong><br>
                    <span class="text-muted">${detalle.motivo_consulta}</span>
                </div>
                ${detalle.sintomatologia ? `
                <div class="timeline-item">
                    <strong>Sintomatología:</strong><br>
                    <span class="text-muted">${detalle.sintomatologia}</span>
                </div>
                ` : ''}
            </div>
            <div class="col-md-6">
                <div class="timeline-item">
                    <strong>Diagnóstico:</strong><br>
                    <span class="text-muted">${detalle.diagnostico}</span>
                </div>
                ${detalle.tratamiento ? `
                <div class="timeline-item">
                    <strong>Tratamiento:</strong><br>
                    <span class="text-muted">${detalle.tratamiento}</span>
                </div>
                ` : ''}
                ${detalle.observaciones_consulta ? `
                <div class="timeline-item">
                    <strong>Observaciones:</strong><br>
                    <span class="text-muted">${detalle.observaciones_consulta}</span>
                </div>
                ` : ''}
                ${detalle.fecha_seguimiento ? `
                <div class="timeline-item">
                    <strong>Fecha de Seguimiento:</strong><br>
                    <span class="text-muted">${formatearFecha(detalle.fecha_seguimiento)}</span>
                </div>
                ` : ''}
            </div>
        </div>
        ` : ''}
    `;
    
    modalBody.html(html);
    $('#modalDetalleCita').modal('show');
}

function verTimeline(idCita) {
    // TODO: Implementar vista de timeline
    Swal.fire({
        icon: 'info',
        title: 'Timeline',
        text: 'Funcionalidad de timeline en desarrollo',
        confirmButtonColor: '#0077b6'
    });
}

// ===== CARGA DE DATOS AUXILIARES =====
async function cargarEspecialidades() {
    try {
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'GET',
            data: { action: 'obtener_especialidades' },
            dataType: 'json'
        });
        
        if (response.success) {
            const select = $('#filtroEspecialidad');
            response.data.forEach(esp => {
                select.append(`<option value="${esp.id_especialidad}">${esp.nombre_especialidad}</option>`);
            });
        }
    } catch (error) {
        console.error('Error cargando especialidades:', error);
    }
}

async function cargarSucursales() {
    try {
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'GET',
            data: { action: 'obtener_sucursales' },
            dataType: 'json'
        });
        
        if (response.success) {
            const select = $('#filtroSucursal');
            response.data.forEach(suc => {
                select.append(`<option value="${suc.id_sucursal}">${suc.nombre_sucursal}</option>`);
            });
        }
    } catch (error) {
        console.error('Error cargando sucursales:', error);
    }
}

async function cargarDoctoresPaciente(idPaciente) {
    try {
        const response = await $.ajax({
            url: config.baseUrl,
            method: 'POST',
            data: { 
                action: 'obtener_doctores_paciente',
                id_paciente: idPaciente
            },
            dataType: 'json'
        });
        
        if (response.success) {
            const select = $('#filtroDoctor');
            select.html('<option value="">Todos los doctores</option>');
            
            response.data.forEach(doc => {
                select.append(`<option value="${doc.id_doctor}">${doc.doctor_nombre} - ${doc.nombre_especialidad}</option>`);
            });
        }
    } catch (error) {
        console.error('Error cargando doctores del paciente:', error);
   }
}

// ===== EXPORTACIÓN =====
async function exportarHistorial() {
   if (!pacienteActual) {
       mostrarError('Primero debe buscar un paciente');
       return;
   }
   
   const { value: formato } = await Swal.fire({
       title: 'Exportar Historial',
       text: 'Seleccione el formato de exportación',
       icon: 'question',
       showCancelButton: true,
       confirmButtonText: 'Exportar PDF',
       cancelButtonText: 'Cancelar',
       confirmButtonColor: '#0077b6',
       cancelButtonColor: '#6c757d'
   });
   
   if (formato) {
       mostrarLoading(true);
       
       try {
           const response = await $.ajax({
               url: config.baseUrl,
               method: 'POST',
               data: {
                   action: 'exportar_historial',
                   id_paciente: pacienteActual.id_paciente,
                   formato: 'pdf'
               },
               dataType: 'json'
           });
           
           if (response.success) {
               Swal.fire({
                   icon: 'info',
                   title: 'Exportación',
                   text: response.message || 'Funcionalidad en desarrollo',
                   confirmButtonColor: '#0077b6'
               });
           } else {
               mostrarError(response.error || 'Error en la exportación');
           }
           
       } catch (error) {
           console.error('Error exportando:', error);
           mostrarError('Error al exportar el historial');
       } finally {
           mostrarLoading(false);
       }
   }
}

function imprimirDetalle() {
   window.print();
}

// ===== FUNCIONES AUXILIARES =====
function getEstadoBadge(estado) {
   const badges = {
       'Consulta Completada': '<span class="badge estado-completada">Consulta Completada</span>',
       'Triaje Completado': '<span class="badge estado-triaje">Triaje Completado</span>',
       'Cita Programada': '<span class="badge estado-programada">Cita Programada</span>'
   };
   
   return badges[estado] || `<span class="badge bg-secondary">${estado}</span>`;
}

function getNivelUrgenciaBadge(nivel) {
   if (nivel >= 4) return 'bg-danger';
   if (nivel >= 3) return 'bg-warning text-dark';
   return 'bg-success';
}

function formatearFecha(fecha) {
   if (!fecha) return 'No disponible';
   
   const date = new Date(fecha);
   return date.toLocaleDateString('es-ES', {
       year: 'numeric',
       month: 'long',
       day: 'numeric'
   });
}

function formatearHora(fecha) {
   if (!fecha) return '';
   
   const date = new Date(fecha);
   return date.toLocaleTimeString('es-ES', {
       hour: '2-digit',
       minute: '2-digit'
   });
}

function formatearFechaHora(fecha) {
   if (!fecha) return 'No disponible';
   
   const date = new Date(fecha);
   return date.toLocaleDateString('es-ES', {
       year: 'numeric',
       month: 'long',
       day: 'numeric',
       hour: '2-digit',
       minute: '2-digit'
   });
}

function mostrarLoading(mostrar) {
   if (mostrar) {
       $('#loadingOverlay').show();
   } else {
       $('#loadingOverlay').hide();
   }
}

function mostrarError(mensaje) {
   Swal.fire({
       icon: 'error',
       title: 'Error',
       text: mensaje,
       confirmButtonColor: '#ef476f'
   });
}

function mostrarExito(mensaje) {
   Swal.fire({
       icon: 'success',
       title: 'Éxito',
       text: mensaje,
       timer: 3000,
       showConfirmButton: false
   });
}

function mostrarInfo(mensaje) {
   Swal.fire({
       icon: 'info',
       title: 'Información',
       text: mensaje,
       confirmButtonColor: '#0077b6'
   });
}

// ===== FUNCIONES GLOBALES PARA BOTONES =====
window.verDetalleCita = verDetalleCita;
window.verTimeline = verTimeline;

// ===== DEBUG =====
if (config.debug) {
   window.historialDebug = {
       pacienteActual,
       historialCompleto,
       filtrosAplicados,
       config
   };
   
   console.log('🐛 Debug mode habilitado. Usa window.historialDebug para inspeccionar');
}