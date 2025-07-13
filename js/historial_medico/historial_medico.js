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
    console.log('📊 Estadísticas:', estadisticas);
    
    // Verificar que tenemos los datos básicos
    if (!paciente) {
        console.error('❌ No hay datos del paciente');
        return;
    }
    
    // 🎨 DISEÑO MEJORADO: Layout horizontal de ancho completo
    const infoHtml = `
        <!-- Sección 1: Datos Personales -->
        <div class="info-section-full mb-4">
            <div class="section-header">
                <i class="bi bi-person-fill text-primary me-2"></i>
                <h6 class="section-title">Datos Personales</h6>
            </div>
            <div class="section-content-horizontal">
                <div class="info-row-horizontal">
                    <span class="info-label">Nombre Completo</span>
                    <span class="info-value">${paciente.nombres || ''} ${paciente.apellidos || ''}</span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Cédula</span>
                    <span class="info-value">${paciente.cedula || 'No especificada'}</span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Fecha de Nacimiento</span>
                    <span class="info-value">
                        ${paciente.fecha_nacimiento_formateada || formatearFecha(paciente.fecha_nacimiento)}
                        <small class="text-muted ms-2">(${paciente.edad || 0} años)</small>
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Género</span>
                    <span class="info-value">
                        <span class="badge ${paciente.sexo === 'M' ? 'bg-primary' : 'bg-info'} rounded-pill">
                            ${paciente.genero_texto || 'No especificado'}
                        </span>
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Nacionalidad</span>
                    <span class="info-value">${paciente.nacionalidad || 'No especificada'}</span>
                </div>
            </div>
        </div>
        
        <!-- Sección 2: Información de Contacto -->
        <div class="info-section-full mb-4">
            <div class="section-header">
                <i class="bi bi-telephone-fill text-success me-2"></i>
                <h6 class="section-title">Información de Contacto</h6>
            </div>
            <div class="section-content-horizontal">
                <div class="info-row-horizontal">
                    <span class="info-label">Correo Electrónico</span>
                    <span class="info-value">
                        ${paciente.correo ? 
                            `<a href="mailto:${paciente.correo}" class="text-decoration-none link-primary">
                                <i class="bi bi-envelope me-1"></i>${paciente.correo}
                            </a>` : 
                            'No especificado'
                        }
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Teléfono Personal</span>
                    <span class="info-value">
                        ${paciente.telefono && paciente.telefono !== 'No especificado' ? 
                            `<a href="tel:${paciente.telefono}" class="text-decoration-none link-success">
                                <i class="bi bi-phone me-1"></i>${paciente.telefono}
                            </a>` : 
                            '<span class="text-muted">No especificado</span>'
                        }
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Contacto de Emergencia</span>
                    <span class="info-value">${paciente.contacto_emergencia}</span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Teléfono de Emergencia</span>
                    <span class="info-value">
                        ${paciente.telefono_emergencia && paciente.telefono_emergencia !== 'No registrado' ? 
                            `<a href="tel:${paciente.telefono_emergencia}" class="text-decoration-none link-danger">
                                <i class="bi bi-phone-vibrate me-1"></i>${paciente.telefono_emergencia}
                            </a>` : 
                            '<span class="text-muted">No registrado</span>'
                        }
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Número de Seguro</span>
                    <span class="info-value">${paciente.numero_seguro}</span>
                </div>
            </div>
        </div>
        
        <!-- Sección 3: Información Médica -->
        <div class="info-section-full mb-4">
            <div class="section-header">
                <i class="bi bi-heart-pulse-fill text-danger me-2"></i>
                <h6 class="section-title">Información Médica</h6>
            </div>
            <div class="section-content-horizontal">
                <div class="info-row-horizontal">
                    <span class="info-label">Tipo de Sangre</span>
                    <span class="info-value">
                        <span class="badge bg-danger text-white fs-6 px-3 py-2 rounded-pill">
                            <i class="bi bi-droplet-fill me-1"></i>${paciente.tipo_sangre}
                        </span>
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Alergias Conocidas</span>
                    <span class="info-value">
                        ${paciente.alergias !== 'Ninguna registrada' ? 
                            `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${paciente.alergias}</span>` : 
                            '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Ninguna registrada</span>'
                        }
                    </span>
                </div>
                <div class="info-row-horizontal">
                    <span class="info-label">Fecha de Registro en el Sistema</span>
                    <span class="info-value">
                        <i class="bi bi-calendar-plus me-1"></i>
                        ${paciente.fecha_registro_formateada || formatearFecha(paciente.fecha_registro)}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Antecedentes Médicos (si existen) -->
        ${paciente.antecedentes_medicos && paciente.antecedentes_medicos !== 'Ninguno registrado' ? `
        <div class="info-section-full">
            <div class="alert alert-info border-start border-4 border-info">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-clipboard2-pulse-fill text-info me-2"></i>
                    <strong>Antecedentes Médicos</strong>
                </div>
                <p class="mb-0">${paciente.antecedentes_medicos}</p>
            </div>
        </div>
        ` : ''}
    `;
    
    // Insertar la información del paciente
    $('#pacienteInfo').html(infoHtml);
    
    // Mostrar estadísticas si están disponibles
    if (estadisticas) {
        mostrarEstadisticasPaciente(estadisticas);
    }
    
    console.log('✅ Información del paciente mostrada correctamente');
}
// 🎨 FUNCIÓN MEJORADA: Estadísticas horizontales
function mostrarEstadisticasPaciente(stats) {
    const estadisticasHtml = `
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">${stats.total_citas || 0}</div>
                    <div class="stat-label">Total de Citas</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">${stats.citas_completadas || 0}</div>
                    <div class="stat-label">Citas Completadas</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="bi bi-hospital-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">${stats.especialidades_visitadas || 0}</div>
                    <div class="stat-label">Especialidades</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">${stats.doctores_diferentes || 0}</div>
                    <div class="stat-label">Doctores Atendidos</div>
                </div>
            </div>
        </div>
    `;
    
    $('#estadisticasPaciente').html(estadisticasHtml);
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
    
    console.log('📄 DEBUG - Detalle recibido:', detalle);
    
    // 🎨 DISEÑO COMPLETAMENTE REDISEÑADO
    const html = `
        <!-- Header del Modal con Información Clave -->
        <div class="modal-detail-header">
            <div class="header-content">
                <div class="cita-id">
                    <span class="id-label">CITA</span>
                    <span class="id-number">#${detalle.id_cita}</span>
                </div>
                <div class="cita-status">
                    <span class="status-badge status-${detalle.estado.toLowerCase().replace(' ', '-')}">
                        ${getEstadoIcon(detalle.estado)} ${detalle.estado}
                    </span>
                </div>
            </div>
            <div class="header-date">
                <i class="bi bi-calendar-event me-2"></i>
                ${formatearFechaHora(detalle.fecha_hora_cita)}
            </div>
        </div>
        
        <!-- Tabs de Navegación -->
        <div class="detail-tabs">
            <ul class="nav nav-pills justify-content-center" id="detailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                        <i class="bi bi-info-circle me-2"></i>General
                    </button>
                </li>
                ${detalle.id_triage ? `
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="triaje-tab" data-bs-toggle="pill" data-bs-target="#triaje" type="button" role="tab">
                        <i class="bi bi-heart-pulse me-2"></i>Triaje
                    </button>
                </li>
                ` : ''}
                ${detalle.id_consulta ? `
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="consulta-tab" data-bs-toggle="pill" data-bs-target="#consulta" type="button" role="tab">
                        <i class="bi bi-clipboard2-pulse me-2"></i>Consulta
                    </button>
                </li>
                ` : ''}
            </ul>
        </div>
        
        <!-- Contenido de los Tabs -->
        <div class="tab-content" id="detailTabsContent">
            <!-- Tab General -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="row g-4">
                    <!-- Tarjeta del Paciente -->
                    <div class="col-lg-6">
                        <div class="detail-card patient-card">
                            <div class="card-header">
                                <div class="card-icon patient-icon">
                                    <i class="bi bi-person-heart"></i>
                                </div>
                                <div class="card-title">
                                    <h5>Información del Paciente</h5>
                                    <p>Datos personales y médicos</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item featured">
                                        <div class="info-icon">
                                            <i class="bi bi-person-vcard"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Nombre Completo</span>
                                            <span class="info-value">${detalle.paciente_nombre}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="bi bi-credit-card-2-front"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Cédula</span>
                                            <span class="info-value">${detalle.paciente_cedula}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="bi bi-cake2"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Edad</span>
                                            <span class="info-value">${detalle.edad} años</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="bi bi-droplet-fill"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Tipo de Sangre</span>
                                            <span class="info-value blood-type">${detalle.tipo_sangre || 'No especificado'}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="bi bi-telephone"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Teléfono</span>
                                            <span class="info-value">${detalle.telefono || 'No registrado'}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item full-width">
                                        <div class="info-icon">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Alergias</span>
                                            <span class="info-value ${!detalle.alergias || detalle.alergias === 'Ninguna registrada' ? 'text-success' : 'text-warning'}">
                                                ${detalle.alergias || 'Ninguna registrada'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta de la Cita -->
                    <div class="col-lg-6">
                        <div class="detail-card appointment-card">
                            <div class="card-header">
                                <div class="card-icon appointment-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="card-title">
                                    <h5>Detalles de la Cita</h5>
                                    <p>Información médica y programación</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item featured">
                                        <div class="info-icon">
                                            <i class="bi bi-hospital"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Especialidad</span>
                                            <span class="info-value specialty-badge">${detalle.nombre_especialidad}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Doctor</span>
                                            <span class="info-value">Dr. ${detalle.doctor_nombre}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Sucursal</span>
                                            <span class="info-value">${detalle.nombre_sucursal}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item full-width">
                                        <div class="info-icon">
                                            <i class="bi bi-chat-text"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Motivo de la Cita</span>
                                            <span class="info-value">${detalle.motivo}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Triaje -->
            ${detalle.id_triage ? `
            <div class="tab-pane fade" id="triaje" role="tabpanel">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="detail-card triaje-card">
                            <div class="card-header">
                                <div class="card-icon triaje-icon">
                                    <i class="bi bi-heart-pulse-fill"></i>
                                </div>
                                <div class="card-title">
                                    <h5>Evaluación de Triaje</h5>
                                    <p>Signos vitales y evaluación inicial</p>
                                </div>
                                <div class="urgency-indicator urgency-${detalle.nivel_urgencia}">
                                    <span class="urgency-level">${detalle.nivel_urgencia}/5</span>
                                    <span class="urgency-text">${getUrgencyText(detalle.nivel_urgencia)}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="triaje-info">
                                            <div class="triaje-meta">
                                                <div class="meta-item">
                                                    <i class="bi bi-clock"></i>
                                                    <span>${formatearFechaHora(detalle.fecha_hora_triaje)}</span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="bi bi-person-check"></i>
                                                    <span>${detalle.enfermero_nombre || 'No registrado'}</span>
                                                </div>
                                            </div>
                                            
                                            ${detalle.observaciones_triaje ? `
                                            <div class="observaciones-box">
                                                <h6><i class="bi bi-chat-square-text me-2"></i>Observaciones</h6>
                                                <p>${detalle.observaciones_triaje}</p>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="vitals-grid">
                                            <h6 class="vitals-title">
                                                <i class="bi bi-activity me-2"></i>Signos Vitales
                                            </h6>
                                            
                                            ${detalle.temperatura ? `
                                            <div class="vital-item">
                                                <div class="vital-icon temperature">
                                                    <i class="bi bi-thermometer-half"></i>
                                                </div>
                                                <div class="vital-info">
                                                    <span class="vital-label">Temperatura</span>
                                                    <span class="vital-value">${detalle.temperatura}°C</span>
                                                </div>
                                            </div>
                                            ` : ''}
                                            
                                            ${detalle.presion_arterial ? `
                                            <div class="vital-item">
                                                <div class="vital-icon pressure">
                                                    <i class="bi bi-heart"></i>
                                                </div>
                                                <div class="vital-info">
                                                    <span class="vital-label">Presión Arterial</span>
                                                    <span class="vital-value">${detalle.presion_arterial}</span>
                                                </div>
                                            </div>
                                            ` : ''}
                                            
                                            ${detalle.frecuencia_cardiaca ? `
                                            <div class="vital-item">
                                                <div class="vital-icon heart-rate">
                                                    <i class="bi bi-heart-pulse"></i>
                                                </div>
                                                <div class="vital-info">
                                                    <span class="vital-label">Frecuencia Cardíaca</span>
                                                    <span class="vital-value">${detalle.frecuencia_cardiaca} bpm</span>
                                                </div>
                                            </div>
                                            ` : ''}
                                            
                                            ${detalle.peso ? `
                                            <div class="vital-item">
                                                <div class="vital-icon weight">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div class="vital-info">
                                                    <span class="vital-label">Peso</span>
                                                    <span class="vital-value">${detalle.peso} kg</span>
                                                </div>
                                            </div>
                                            ` : ''}
                                            
                                            ${detalle.talla ? `
                                            <div class="vital-item">
                                                <div class="vital-icon height">
                                                    <i class="bi bi-rulers"></i>
                                                </div>
                                                <div class="vital-info">
                                                    <span class="vital-label">Talla</span>
                                                    <span class="vital-value">${detalle.talla} cm</span>
                                                </div>
                                            </div>
                                            ` : ''}
                                            
                                            ${detalle.imc ? `
                                            <div class="vital-item">
                                                <div class="vital-icon bmi">
                                                    <i class="bi bi-calculator"></i>
                                                </div>
                                                <div class="vital-info">
                                                    <span class="vital-label">IMC</span>
                                                    <span class="vital-value">${detalle.imc}</span>
                                                </div>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
            
            <!-- Tab Consulta -->
            ${detalle.id_consulta ? `
            <div class="tab-pane fade" id="consulta" role="tabpanel">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="detail-card consulta-card">
                            <div class="card-header">
                                <div class="card-icon consulta-icon">
                                    <i class="bi bi-clipboard2-pulse-fill"></i>
                                </div>
                                <div class="card-title">
                                    <h5>Consulta Médica</h5>
                                    <p>Diagnóstico y tratamiento médico</p>
                                </div>
                                <div class="consulta-date">
                                    <i class="bi bi-clock me-1"></i>
                                    ${formatearFechaHora(detalle.fecha_hora_consulta)}
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="consulta-content">
                                    <div class="consulta-section">
                                        <h6 class="section-title">
                                            <i class="bi bi-chat-square-dots me-2"></i>Motivo de Consulta
                                        </h6>
                                        <div class="section-content">
                                            ${detalle.motivo_consulta}
                                        </div>
                                    </div>
                                    
                                    ${detalle.sintomatologia ? `
                                    <div class="consulta-section">
                                        <h6 class="section-title">
                                            <i class="bi bi-exclamation-diamond me-2"></i>Sintomatología
                                        </h6>
                                        <div class="section-content">
                                            ${detalle.sintomatologia}
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    <div class="consulta-section featured">
                                        <h6 class="section-title">
                                            <i class="bi bi-clipboard-check me-2"></i>Diagnóstico
                                        </h6>
                                        <div class="section-content diagnostico">
                                            ${detalle.diagnostico}
                                        </div>
                                    </div>
                                    
                                    ${detalle.tratamiento ? `
                                    <div class="consulta-section">
                                        <h6 class="section-title">
                                            <i class="bi bi-capsule me-2"></i>Tratamiento
                                        </h6>
                                        <div class="section-content tratamiento">
                                            ${detalle.tratamiento}
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    ${detalle.observaciones_consulta ? `
                                    <div class="consulta-section">
                                        <h6 class="section-title">
                                            <i class="bi bi-journal-text me-2"></i>Observaciones Adicionales
                                        </h6>
                                        <div class="section-content">
                                            ${detalle.observaciones_consulta}
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    ${detalle.fecha_seguimiento ? `
                                    <div class="consulta-section seguimiento">
                                        <h6 class="section-title">
                                            <i class="bi bi-calendar-plus me-2"></i>Próxima Cita de Seguimiento
                                        </h6>
                                        <div class="section-content">
                                            <span class="fecha-seguimiento">${formatearFecha(detalle.fecha_seguimiento)}</span>
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
        
        <!-- Footer con Acciones -->
        <div class="modal-footer-custom">
            <div class="footer-info">
                <div class="info-badge">
                    <i class="bi bi-shield-check me-2"></i>
                    Información médica confidencial
                </div>
                <small class="text-muted">
                    Cita registrada el ${formatearFecha(detalle.fecha_hora_cita)}
                </small>
            </div>
            
            <div class="footer-actions">
                <button type="button" class="btn btn-outline-primary" onclick="imprimirDetalleHistorial(${detalle.id_cita})">
                    <i class="bi bi-printer me-2"></i>
                    Imprimir
                </button>
                
                <button type="button" class="btn btn-primary" onclick="generarPDFHistorial(${detalle.id_cita})">
                    <i class="bi bi-file-pdf me-2"></i>
                    Descargar PDF
                </button>
            </div>
        </div>
    `;
    
    modalBody.html(html);
    window.citaActualHistorial = detalle;
    
    // Activar tooltips
    setTimeout(() => {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }, 100);
    
    $('#modalDetalleCita').modal('show');
}

// Funciones auxiliares para el modal mejorado
function getEstadoIcon(estado) {
    const icons = {
        'Pendiente': '<i class="bi bi-clock"></i>',
        'Confirmada': '<i class="bi bi-check-circle"></i>',
        'Completada': '<i class="bi bi-check-all"></i>',
        'Cancelada': '<i class="bi bi-x-circle"></i>',
        'No Asistio': '<i class="bi bi-person-x"></i>'
    };
    return icons[estado] || '<i class="bi bi-info-circle"></i>';
}

function getUrgencyText(nivel) {
    const levels = {
        1: 'Muy Baja',
        2: 'Baja', 
        3: 'Moderada',
        4: 'Alta',
        5: 'Crítica'
    };
    return levels[nivel] || 'No definida';
}

/**
 * 🔥 FUNCIÓN CORREGIDA: Generar PDF desde historial médico
 */
function generarPDFHistorial(id_cita) {
    if (!id_cita) {
        console.error('❌ ID de cita no válido');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'ID de cita no válido'
        });
        return;
    }
    
    console.log('📄 Generando PDF para cita del historial:', id_cita);
    
    // Mostrar loading
    Swal.fire({
        title: 'Generando PDF...',
        text: 'Por favor espere mientras se genera el documento del historial médico',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // 🔥 USAR LA NUEVA RUTA DEL GENERADOR DE PDF PARA HISTORIAL
    const url = `../../controladores/HistorialMedicoControlador/GenerarPDFHistorial.php?accion=generar_pdf_historial&id_cita=${id_cita}`;
    
    // Crear iframe oculto para la descarga
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    
    // Simular tiempo de generación y cerrar loading
    setTimeout(() => {
        Swal.close();
        
        // Mostrar mensaje de éxito
        Swal.fire({
            icon: 'success',
            title: '¡PDF Generado!',
            text: 'El archivo del historial se ha descargado correctamente',
            timer: 2000,
            showConfirmButton: false
        });
        
        // Limpiar iframe después de un tiempo
        setTimeout(() => {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 3000);
    }, 1500);
}
/**
 * 🔥 NUEVA FUNCIÓN: Imprimir detalle del historial
 */
function imprimirDetalleHistorial(id_cita) {
    if (!window.citaActualHistorial) {
        console.error('❌ No hay datos de la cita actual');
        return;
    }
    
    console.log('🖨️ Imprimiendo detalle del historial para cita:', id_cita);
    
    const detalle = window.citaActualHistorial;
    
    // Crear contenido para imprimir
    const contenidoImpresion = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Detalle de Cita Médica #${detalle.id_cita}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    padding: 20px;
                    background: white;
                }
                .header-print {
                    text-align: center;
                    border-bottom: 2px solid #0d6efd;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .logo-print {
                    color: #0d6efd;
                    font-size: 2rem;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .timeline-item {
                    margin-bottom: 15px;
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }
                .section-title {
                    color: #0d6efd;
                    font-weight: 600;
                    margin: 25px 0 15px 0;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #dee2e6;
                }
                .vital-signs {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 15px 0;
                }
                @media print {
                    .no-print { display: none !important; }
                    body { margin: 0; padding: 15px; }
                    .page-break { page-break-before: always; }
                }
            </style>
        </head>
        <body>
            <!-- Header -->
            <div class="header-print">
                <div class="logo-print">🏥 MediSys</div>
                <h4>Detalle Completo de Cita Médica</h4>
                <p class="text-muted mb-0">Cita #${detalle.id_cita} | Generado el ${new Date().toLocaleDateString('es-ES')}</p>
            </div>
            
            <!-- Información de la Cita -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="section-title">📅 Información de la Cita</h5>
                    <div class="timeline-item">
                        <strong>Fecha y Hora:</strong><br>
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
                    <h5 class="section-title">👤 Información del Paciente</h5>
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
                </div>
            </div>
            
            <!-- Triaje (si existe) -->
            ${detalle.id_triage ? `
            <div class="page-break">
                <h5 class="section-title">💓 Información del Triaje</h5>
                <div class="row">
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
                            <span class="badge bg-warning">${detalle.nivel_urgencia}/5</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="vital-signs">
                            <strong>Signos Vitales:</strong><br>
                            <ul class="list-unstyled mt-2">
                                ${detalle.temperatura ? `<li>• Temperatura: ${detalle.temperatura}°C</li>` : ''}
                                ${detalle.presion_arterial ? `<li>• Presión: ${detalle.presion_arterial}</li>` : ''}
                                ${detalle.frecuencia_cardiaca ? `<li>• Frecuencia cardíaca: ${detalle.frecuencia_cardiaca} bpm</li>` : ''}
                                ${detalle.peso ? `<li>• Peso: ${detalle.peso} kg</li>` : ''}
                                ${detalle.talla ? `<li>• Talla: ${detalle.talla} cm</li>` : ''}
                                ${detalle.imc ? `<li>• IMC: ${detalle.imc}</li>` : ''}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
            
            <!-- Consulta Médica (si existe) -->
            ${detalle.id_consulta ? `
            <div class="page-break">
                <h5 class="section-title">🩺 Consulta Médica</h5>
                <div class="row">
                    <div class="col-12">
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
                        <div class="timeline-item">
                            <strong>Diagnóstico:</strong><br>
                            <div class="alert alert-info mt-2">${detalle.diagnostico}</div>
                        </div>
                        ${detalle.tratamiento ? `
                        <div class="timeline-item">
                            <strong>Tratamiento:</strong><br>
                            <div class="alert alert-success mt-2">${detalle.tratamiento}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            ` : ''}
            
            <!-- Footer -->
            <div class="text-center mt-5 pt-4 border-top">
                <small class="text-muted">
                    Documento generado por MediSys | ${new Date().toLocaleString('es-ES')}<br>
                    Este documento contiene información médica confidencial
                </small>
            </div>
            
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
    `;
    
    // Abrir ventana de impresión
    const ventanaImpresion = window.open('', '_blank', 'width=800,height=600');
    ventanaImpresion.document.write(contenidoImpresion);
    ventanaImpresion.document.close();
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