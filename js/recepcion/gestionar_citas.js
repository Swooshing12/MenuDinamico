/**
 * Sistema de Gestión de Citas con FullCalendar
 * Autor: Sistema MediSys
 * Descripción: Manejo completo de citas médicas con calendario interactivo
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: true,
    submenuId: window.recepcionConfig?.submenuId || null,
    permisos: window.recepcionConfig?.permisos || {},
    sucursales: window.recepcionConfig?.sucursales || [],
    especialidades: window.recepcionConfig?.especialidades || [],
    baseUrl: '../../controladores/RecepcionistaControlador/RecepcionistaController.php'
};

// Variables globales
let calendario;
let citaSeleccionada = null;
let pacienteSeleccionado = null;

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🚀 Iniciando Sistema de Gestión de Citas');
    
    try {
        inicializarCalendario();
        inicializarEventos();
        inicializarSelect2();
        cargarEstadisticas();
        cargarDoctoresParaFiltro();
        
        // Establecer fecha mínima para citas (hoy)
        $('#fechaCita').attr('min', new Date().toISOString().split('T')[0]);
        
        console.log('✅ Sistema inicializado correctamente');
    } catch (error) {
        console.error('❌ Error al inicializar:', error);
        mostrarError('Error al inicializar el sistema');
    }
});

// ===== INICIALIZACIÓN DEL CALENDARIO =====
function inicializarCalendario() {
    const calendarEl = document.getElementById('calendario');
    
    calendario = new FullCalendar.Calendar(calendarEl, {
        // Configuración básica
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1, // Lunes como primer día
        height: 'auto',
        
        // Configuración de header
        headerToolbar: {
            left: 'title',
            center: '',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        
        // Configuración de botones personalizados
        customButtons: {
            hoy: {
                text: 'Hoy',
                click: function() {
                    calendario.today();
                }
            }
        },
        
        // Configuración de vistas
        views: {
            dayGridMonth: {
                titleFormat: { year: 'numeric', month: 'long' }
            },
            timeGridWeek: {
                titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                slotDuration: '00:30:00',
                allDaySlot: false
            },
            timeGridDay: {
                titleFormat: { weekday: 'long', month: 'long', day: 'numeric' },
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                slotDuration: '00:15:00',
                allDaySlot: false
            }
        },
        
        // Configuración de eventos
        eventDisplay: 'block',
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        
        // Carga de eventos
        events: function(info, successCallback, failureCallback) {
            cargarCitasCalendario(info.start, info.end, successCallback, failureCallback);
        },
        
        // Eventos del calendario
        dateClick: function(info) {
            manejarClickFecha(info);
        },
        
        eventClick: function(info) {
            manejarClickEvento(info);
        },
        
        eventMouseEnter: function(info) {
            mostrarTooltipCita(info);
        },
        
        // Configuración adicional
        nowIndicator: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        
        // Configuración responsive
        windowResizeDelay: 100
    });
    
    calendario.render();
    
    console.log('📅 Calendario inicializado');
}

// ===== CARGA DE CITAS PARA EL CALENDARIO =====
function cargarCitasCalendario(start, end, successCallback, failureCallback) {
    const filtros = obtenerFiltrosActivos();
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerCitas',
            fecha_desde: start.toISOString().split('T')[0],
            fecha_hasta: end.toISOString().split('T')[0],
            submenu_id: config.submenuId,
            ...filtros
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const eventos = convertirCitasAEventos(response.data);
                successCallback(eventos);
                console.log(`📋 Cargadas ${eventos.length} citas para el calendario`);
            } else {
                console.error('❌ Error cargando citas:', response.message);
                failureCallback();
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX cargando citas:', error);
            failureCallback();
        }
    });
}

// ===== CONVERSIÓN DE CITAS A EVENTOS =====
function convertirCitasAEventos(citas) {
    return citas.map(cita => {
        const estadoClass = `estado-${cita.estado.toLowerCase()}`;
        
        return {
            id: cita.id_cita,
            title: `${cita.paciente_nombres} ${cita.paciente_apellidos}`,
            start: cita.fecha_hora,
            backgroundColor: obtenerColorEstado(cita.estado),
            borderColor: obtenerColorEstado(cita.estado),
            textColor: obtenerColorTexto(cita.estado),
            className: estadoClass,
            extendedProps: {
                citaData: cita
            }
        };
    });
}

// ===== COLORES POR ESTADO =====
function obtenerColorEstado(estado) {
    const colores = {
        'Pendiente': '#ffc107',
        'Confirmada': '#198754',
        'Completada': '#0dcaf0',
        'Cancelada': '#dc3545'
    };
    return colores[estado] || '#6c757d';
}

function obtenerColorTexto(estado) {
    const textColors = {
        'Pendiente': '#000',
        'Confirmada': '#fff',
        'Completada': '#000',
        'Cancelada': '#fff'
    };
    return textColors[estado] || '#fff';
}

// ===== MANEJO DE EVENTOS DEL CALENDARIO =====
function manejarClickFecha(info) {
    if (config.permisos.puede_crear) {
        // Pre-llenar fecha en modal de nueva cita
        $('#fechaCita').val(info.dateStr);
        $('#modalNuevaCita').modal('show');
        console.log('📅 Creando nueva cita para:', info.dateStr);
    }
}

function manejarClickEvento(info) {
    const citaData = info.event.extendedProps.citaData;
    citaSeleccionada = citaData;
    mostrarDetallesCita(citaData);
}

function mostrarTooltipCita(info) {
    const cita = info.event.extendedProps.citaData;
    const tooltip = `
        <strong>${cita.paciente_nombres} ${cita.paciente_apellidos}</strong><br>
        Doctor: ${cita.doctor_nombres} ${cita.doctor_apellidos}<br>
        Especialidad: ${cita.nombre_especialidad}<br>
        Estado: ${cita.estado}<br>
        Hora: ${new Date(cita.fecha_hora).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}
    `;
    
    // Aquí puedes implementar una librería de tooltips como Tippy.js
    info.el.title = tooltip.replace(/<br>/g, '\n').replace(/<[^>]*>/g, '');
}

// ===== INICIALIZACIÓN DE EVENTOS =====
function inicializarEventos() {
    // Botones de vista del calendario
    $('#btnVistaDia').click(() => calendario.changeView('timeGridDay'));
    $('#btnVistaSemana').click(() => calendario.changeView('timeGridWeek'));
    $('#btnVistaMes').click(() => calendario.changeView('dayGridMonth'));
    $('#btnHoy').click(() => calendario.today());
    $('#btnAnterior').click(() => calendario.prev());
    $('#btnSiguiente').click(() => calendario.next());
    
    // ⭐ AGREGAR ESTA LÍNEA:
    $('#btnNuevaCita').click(function() {
        $('#modalNuevaCita').modal('show');
    });
    
    // Filtros
    $('#btnAplicarFiltros').click(aplicarFiltros);
    $('#filtroSucursal, #filtroEspecialidad, #filtroEstado, #filtroDoctor').change(aplicarFiltros);
    
    // Formulario nueva cita
    $('#formNuevaCita').submit(manejarSubmitNuevaCita);
    $('#btnBuscarPaciente').click(buscarPaciente);
    $('#btnRegistrarPaciente').click(abrirModalRegistrarPaciente);
    $('#btnObtenerDatosCedula').click(obtenerDatosCedula);
    
    // Formulario registrar paciente
    $('#formRegistrarPaciente').submit(manejarSubmitRegistrarPaciente);
    
    // Cascadas de selects
    $('#sucursalCita').change(cargarEspecialidadesPorSucursal);
    $('#especialidadCita').change(cargarDoctoresPorEspecialidad);
    $('#doctorCita, #fechaCita').change(cargarHorariosDisponibles);
    
    // Acciones de citas
    $('#btnEditarCita').click(editarCita);
    $('#btnConfirmarCita').click(confirmarCita);
    $('#btnCancelarCita').click(cancelarCita);
    
    // Limpiar modales al cerrar
    $('#modalNuevaCita').on('hidden.bs.modal', limpiarFormularioNuevaCita);
    $('#modalRegistrarPaciente').on('hidden.bs.modal', limpiarFormularioRegistrarPaciente);
    
    console.log('🎯 Eventos inicializados');
}

// ===== INICIALIZACIÓN DE SELECT2 =====
function inicializarSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione una opción',
        allowClear: true
    });
}

// ===== BUSCAR PACIENTE =====
function buscarPaciente() {
    const cedula = $('#cedulaPaciente').val().trim();
    
    if (!cedula) {
        mostrarError('Por favor ingrese una cédula');
        return;
    }
    
    if (cedula.length < 10) {
        mostrarError('La cédula debe tener al menos 10 dígitos');
        return;
    }
    
    mostrarCargando('#btnBuscarPaciente');
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'buscarPacientePorCedula',
            cedula: cedula,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            ocultarCargando('#btnBuscarPaciente', '<i class="bi bi-search me-1"></i>Buscar');
            
            if (response.success) {
                if (response.encontrado) {
                    mostrarPacienteEncontrado(response.data);
                } else {
                    mostrarPacienteNoEncontrado(cedula);
                }
            } else {
                mostrarError(response.message || 'Error al buscar paciente');
            }
        },
        error: function(xhr, status, error) {
            ocultarCargando('#btnBuscarPaciente', '<i class="bi bi-search me-1"></i>Buscar');
            console.error('❌ Error buscando paciente:', error);
            mostrarError('Error al buscar paciente');
        }
    });
}

function mostrarPacienteEncontrado(paciente) {
    pacienteSeleccionado = paciente;
    
    $('#datosPaciente').html(`
        <strong>${paciente.nombres} ${paciente.apellidos}</strong><br>
        <small>Cédula: ${paciente.cedula} | Correo: ${paciente.correo}</small>
    `);
    
    $('#infoPaciente').removeClass('d-none');
    $('#pacienteNoEncontrado').addClass('d-none');
    $('#idPacienteSeleccionado').val(paciente.id_paciente);
    
    console.log('✅ Paciente encontrado:', paciente.nombres);
}

function mostrarPacienteNoEncontrado(cedula) {
    pacienteSeleccionado = null;
    
    $('#infoPaciente').addClass('d-none');
    $('#pacienteNoEncontrado').removeClass('d-none');
    $('#idPacienteSeleccionado').val('');
    
    // Pre-llenar cédula en modal de registro
    $('#cedulaNuevoPaciente').val(cedula);
    
    console.log('⚠️ Paciente no encontrado para cédula:', cedula);
}

// ===== OBTENER DATOS DE CÉDULA =====
function obtenerDatosCedula() {
    const cedula = $('#cedulaNuevoPaciente').val().trim();
    
    if (!cedula) {
        mostrarError('Ingrese una cédula válida');
        return;
    }
    
    mostrarCargando('#btnObtenerDatosCedula');
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerDatosCedula',
            cedula: cedula,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            ocultarCargando('#btnObtenerDatosCedula', '<i class="bi bi-download me-1"></i>Obtener Datos');
            
            if (response.success) {
                // Llenar formulario con datos de la API
                $('#nombresNuevoPaciente').val(response.data.nombres);
                $('#apellidosNuevoPaciente').val(response.data.apellidos);
                $('#fechaNacimientoNuevo').val(response.data.fecha_nacimiento);
                
                mostrarExito('Datos obtenidos de la base de datos nacional');
                console.log('✅ Datos de cédula obtenidos');
            } else {
                mostrarAdvertencia(response.message || 'No se encontraron datos para esta cédula');
            }
        },
        error: function(xhr, status, error) {
            ocultarCargando('#btnObtenerDatosCedula', '<i class="bi bi-download me-1"></i>Obtener Datos');
            console.error('❌ Error obteniendo datos de cédula:', error);
            mostrarError('Error al consultar datos de cédula');
        }
    });
}

// ===== REGISTRAR PACIENTE =====
function abrirModalRegistrarPaciente() {
    $('#modalNuevaCita').modal('hide');
    $('#modalRegistrarPaciente').modal('show');
}

function manejarSubmitRegistrarPaciente(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'registrarPaciente');
    formData.append('submenu_id', config.submenuId);
    
    const submitBtn = $(this).find('button[type="submit"]');
    mostrarCargando(submitBtn);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarCargando(submitBtn, '<i class="bi bi-save me-1"></i>Registrar Paciente');
            
            if (response.success) {
                mostrarExito('Paciente registrado exitosamente');
                
                // Usar el paciente recién creado
                pacienteSeleccionado = {
                    id_paciente: response.data.id_paciente,
                    nombres: $('#nombresNuevoPaciente').val(),
                    apellidos: $('#apellidosNuevoPaciente').val(),
                    cedula: $('#cedulaNuevoPaciente').val(),
                    correo: $('#correoNuevoPaciente').val()
                };
                
                // Actualizar modal principal
                mostrarPacienteEncontrado(pacienteSeleccionado);
                
                // Cerrar modal de registro y volver al principal
                $('#modalRegistrarPaciente').modal('hide');
                setTimeout(() => {
                    $('#modalNuevaCita').modal('show');
                }, 300);
                
                console.log('✅ Paciente registrado:', response.data);
            } else {
                mostrarError(response.message || 'Error al registrar paciente');
            }
        },
        error: function(xhr, status, error) {
            ocultarCargando(submitBtn, '<i class="bi bi-save me-1"></i>Registrar Paciente');
            console.error('❌ Error registrando paciente:', error);
            mostrarError('Error al registrar paciente');
        }
    });
}

// ===== CASCADAS DE SELECTS =====
function cargarEspecialidadesPorSucursal() {
    const idSucursal = $('#sucursalCita').val();
    const $especialidad = $('#especialidadCita');
    
    if (!idSucursal) {
        $especialidad.html('<option value="">Primero seleccione sucursal</option>').prop('disabled', true);
        return;
    }
    
    $especialidad.html('<option value="">Cargando especialidades...</option>').prop('disabled', true);
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerEspecialidadesPorSucursal',
            id_sucursal: idSucursal,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Seleccione especialidad</option>';
                response.data.forEach(esp => {
                    options += `<option value="${esp.id_especialidad}">${esp.nombre_especialidad}</option>`;
                });
                $especialidad.html(options).prop('disabled', false);
                console.log(`✅ Cargadas ${response.data.length} especialidades`);
            } else {
                $especialidad.html('<option value="">Error cargando especialidades</option>');
                mostrarError('Error al cargar especialidades');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando especialidades:', error);
            $especialidad.html('<option value="">Error cargando especialidades</option>');
        }
    });
}

function cargarDoctoresPorEspecialidad() {
    const idEspecialidad = $('#especialidadCita').val();
    const idSucursal = $('#sucursalCita').val();
    const $doctor = $('#doctorCita');
    
    if (!idEspecialidad || !idSucursal) {
        $doctor.html('<option value="">Seleccione especialidad y sucursal</option>').prop('disabled', true);
        return;
    }
    
    $doctor.html('<option value="">Cargando doctores...</option>').prop('disabled', true);
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerDoctoresPorEspecialidad',
            id_especialidad: idEspecialidad,
            id_sucursal: idSucursal,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Seleccione doctor</option>';
                response.data.forEach(doctor => {
                    options += `<option value="${doctor.id_doctor}">
                        Dr. ${doctor.nombres} ${doctor.apellidos}
                    </option>`;
                });
                $doctor.html(options).prop('disabled', false);
                console.log(`✅ Cargados ${response.data.length} doctores`);
            } else {
                $doctor.html('<option value="">No hay doctores disponibles</option>');
                mostrarAdvertencia('No hay doctores disponibles para esta especialidad');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando doctores:', error);
            $doctor.html('<option value="">Error cargando doctores</option>');
        }
    });
}

function cargarHorariosDisponibles() {
    const idDoctor = $('#doctorCita').val();
    const fecha = $('#fechaCita').val();
    const $hora = $('#horaCita');
    
    if (!idDoctor || !fecha) {
        $hora.html('<option value="">Seleccione doctor y fecha</option>').prop('disabled', true);
        return;
    }
    
    $hora.html('<option value="">Cargando horarios...</option>').prop('disabled', true);
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerHorariosDisponibles',
            id_doctor: idDoctor,
            fecha: fecha,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Seleccione horario</option>';
                
                if (response.data.length > 0) {
                    response.data.forEach(horario => {
                        options += `<option value="${horario.hora}">${horario.hora_formato}</option>`;
                    });
                } else {
                    options = '<option value="">No hay horarios disponibles</option>';
                }
                
                $hora.html(options).prop('disabled', response.data.length === 0);
                console.log(`✅ Cargados ${response.data.length} horarios disponibles`);
            } else {
                $hora.html('<option value="">Error cargando horarios</option>');
                mostrarError('Error al cargar horarios disponibles');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando horarios:', error);
            $hora.html('<option value="">Error cargando horarios</option>');
        }
    });
}

// ===== REGISTRAR NUEVA CITA =====
function manejarSubmitNuevaCita(e) {
    e.preventDefault();
    
    // Construir fecha y hora completa
    const fecha = $('#fechaCita').val();
    const hora = $('#horaCita').val();
    
    if (!fecha || !hora) {
        mostrarError('Seleccione fecha y hora para la cita');
        return;
    }
    
    const fechaHoraCompleta = `${fecha} ${hora}`;
    $('#fechaHoraCompleta').val(fechaHoraCompleta);
    
    const formData = new FormData(this);
    formData.append('action', 'registrarCita');
    formData.append('submenu_id', config.submenuId);
    
    const submitBtn = $(this).find('button[type="submit"]');
    mostrarCargando(submitBtn);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarCargando(submitBtn, '<i class="bi bi-save me-1"></i>Registrar Cita');
            
            if (response.success) {
                mostrarExito('Cita registrada exitosamente');
                $('#modalNuevaCita').modal('hide');
                
                // Recargar calendario
                calendario.refetchEvents();
                cargarEstadisticas();
                
                console.log('✅ Cita registrada:', response.data);
            } else {
                mostrarError(response.message || 'Error al registrar cita');
            }
        },
        error: function(xhr, status, error) {
            ocultarCargando(submitBtn, '<i class="bi bi-save me-1"></i>Registrar Cita');
            console.error('❌ Error registrando cita:', error);
            mostrarError('Error al registrar cita');
        }
    });
}

// ===== MOSTRAR DETALLES DE CITA =====
function mostrarDetallesCita(cita) {
    const fecha = new Date(cita.fecha_hora);
    const fechaFormateada = fecha.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const horaFormateada = fecha.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const estadoBadge = `<span class="badge bg-${obtenerColorBootstrap(cita.estado)}">${cita.estado}</span>`;
    
    const detallesHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <h6><i class="bi bi-person me-2"></i>Información del Paciente</h6>
                <p><strong>Nombre:</strong> ${cita.paciente_nombres} ${cita.paciente_apellidos}</p>
                <p><strong>Cédula:</strong> ${cita.paciente_cedula}</p>
                <p><strong>Correo:</strong> ${cita.paciente_correo}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="bi bi-person-badge me-2"></i>Información del Doctor</h6>
                <p><strong>Doctor:</strong> ${cita.doctor_nombres} ${cita.doctor_apellidos}</p>
                <p><strong>Especialidad:</strong> ${cita.nombre_especialidad}</p>
                <p><strong>Sucursal:</strong> ${cita.nombre_sucursal}</p>
            </div>
            <div class="col-12">
                <h6><i class="bi bi-calendar-event me-2"></i>Información de la Cita</h6>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Fecha:</strong> ${fechaFormateada}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Hora:</strong> ${horaFormateada}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Estado:</strong> ${estadoBadge}</p>
                    </div>
                </div>
                <p><strong>Motivo:</strong> ${cita.motivo}</p>
                ${cita.notas ? `<p><strong>Notas:</strong> ${cita.notas}</p>` : ''}
            </div>
        </div>
    `;
    
    $('#detallesCita').html(detallesHTML);
    $('#modalVerCita').modal('show');
    
    // Configurar botones según estado
    configurarBotonesCita(cita);
}

function obtenerColorBootstrap(estado) {
    const colores = {
        'Pendiente': 'warning',
        'Confirmada': 'success',
        'Completada': 'info',
        'Cancelada': 'danger'
    };
    return colores[estado] || 'secondary';
}

function configurarBotonesCita(cita) {
    const $btnConfirmar = $('#btnConfirmarCita');
    const $btnCancelar = $('#btnCancelarCita');
    const $btnEditar = $('#btnEditarCita');
    
    // Mostrar/ocultar botones según estado y permisos
    if (cita.estado === 'Pendiente') {
        $btnConfirmar.show();
        $btnCancelar.show();
        $btnEditar.show();
    } else if (cita.estado === 'Confirmada') {
        $btnConfirmar.hide();
        $btnCancelar.show();
        $btnEditar.show();
    } else {
        $btnConfirmar.hide();
        $btnCancelar.hide();
        $btnEditar.hide();
    }
}

// ===== ACCIONES DE CITAS =====
function confirmarCita() {
    if (!citaSeleccionada) return;
    
    Swal.fire({
        title: '¿Confirmar cita?',
        text: 'Esta acción confirmará la cita médica',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarAccionCita('confirmarCita', citaSeleccionada.id_cita, 'Cita confirmada exitosamente');
        }
    });
}

function cancelarCita() {
    if (!citaSeleccionada) return;
    
    Swal.fire({
        title: '¿Cancelar cita?',
        text: 'Esta acción cancelará la cita médica',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarAccionCita('cancelarCita', citaSeleccionada.id_cita, 'Cita cancelada exitosamente');
       }
   });
}

function editarCita() {
   if (!citaSeleccionada) return;
   
   // Cerrar modal de detalles
   $('#modalVerCita').modal('hide');
   
   // Abrir modal de edición con datos pre-cargados
   setTimeout(() => {
       cargarDatosParaEdicion(citaSeleccionada);
       $('#modalNuevaCita').modal('show');
   }, 300);
}

function ejecutarAccionCita(action, idCita, mensajeExito) {
   $.ajax({
       url: config.baseUrl,
       type: 'POST',
       data: {
           action: action,
           id_cita: idCita,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               mostrarExito(mensajeExito);
               $('#modalVerCita').modal('hide');
               
               // Recargar calendario y estadísticas
               calendario.refetchEvents();
               cargarEstadisticas();
               
               console.log(`✅ Acción ${action} ejecutada correctamente`);
           } else {
               mostrarError(response.message || 'Error al ejecutar la acción');
           }
       },
       error: function(xhr, status, error) {
           console.error(`❌ Error ejecutando ${action}:`, error);
           mostrarError('Error al procesar la solicitud');
       }
   });
}

// ===== CARGAR DATOS PARA EDICIÓN =====
function cargarDatosParaEdicion(cita) {
   // Llenar datos del paciente
   pacienteSeleccionado = {
       id_paciente: cita.id_paciente,
       nombres: cita.paciente_nombres,
       apellidos: cita.paciente_apellidos,
       cedula: cita.paciente_cedula,
       correo: cita.paciente_correo
   };
   
   $('#cedulaPaciente').val(cita.paciente_cedula);
   mostrarPacienteEncontrado(pacienteSeleccionado);
   
   // Llenar sucursal
   $('#sucursalCita').val(cita.id_sucursal).trigger('change');
   
   // Esperar a que se carguen las especialidades y luego seleccionar
   setTimeout(() => {
       $('#especialidadCita').val(cita.id_especialidad).trigger('change');
       
       // Esperar a que se carguen los doctores y luego seleccionar
       setTimeout(() => {
           $('#doctorCita').val(cita.id_doctor).trigger('change');
           
           // Llenar fecha y hora
           const fecha = new Date(cita.fecha_hora);
           const fechaStr = fecha.toISOString().split('T')[0];
           const horaStr = fecha.toTimeString().slice(0, 5) + ':00';
           
           $('#fechaCita').val(fechaStr);
           
           // Esperar a que se carguen los horarios y luego seleccionar
           setTimeout(() => {
               $('#horaCita').val(horaStr);
           }, 1000);
           
       }, 1000);
   }, 1000);
   
   // Llenar otros campos
   $('#motivoCita').val(cita.motivo);
   $('#notasCita').val(cita.notas || '');
   
   // Cambiar título del modal
   $('#modalNuevaCita .modal-title').html('<i class="bi bi-pencil me-1"></i>Editar Cita');
   
   // Agregar campo oculto para indicar que es edición
   if (!$('#editandoCita').length) {
       $('#formNuevaCita').append('<input type="hidden" id="editandoCita" name="id_cita" value="' + cita.id_cita + '">');
   } else {
       $('#editandoCita').val(cita.id_cita);
   }
   
   console.log('✏️ Datos cargados para edición:', cita.id_cita);
}

// ===== FILTROS =====
function obtenerFiltrosActivos() {
   return {
       estado: $('#filtroEstado').val(),
       id_sucursal: $('#filtroSucursal').val(),
       id_especialidad: $('#filtroEspecialidad').val(),
       id_doctor: $('#filtroDoctor').val()
   };
}

function aplicarFiltros() {
   console.log('🔍 Aplicando filtros...');
   calendario.refetchEvents();
   cargarEstadisticas();
}

function cargarDoctoresParaFiltro() {
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'obtenerDoctores',
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               let options = '<option value="">Todos los doctores</option>';
               response.data.forEach(doctor => {
                   options += `<option value="${doctor.id_doctor}">
                       Dr. ${doctor.nombres} ${doctor.apellidos} - ${doctor.nombre_especialidad}
                   </option>`;
               });
               $('#filtroDoctor').html(options);
               console.log(`✅ Cargados ${response.data.length} doctores para filtro`);
           }
       },
       error: function(xhr, status, error) {
           console.error('❌ Error cargando doctores para filtro:', error);
       }
   });
}

// ===== ESTADÍSTICAS =====
function cargarEstadisticas() {
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'obtenerEstadisticas',
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               actualizarEstadisticas(response.data);
               console.log('📊 Estadísticas actualizadas');
           }
       },
       error: function(xhr, status, error) {
           console.error('❌ Error cargando estadísticas:', error);
       }
   });
}

function actualizarEstadisticas(stats) {
   $('#citasHoy').text(stats.citas_hoy || 0);
   $('#citasPendientes').text(stats.citas_pendientes || 0);
   $('#citasConfirmadas').text(stats.citas_confirmadas || 0);
   $('#pacientesNuevos').text(stats.pacientes_nuevos_hoy || 0);
   
   // Animación de contadores
   $('.card h3').each(function() {
       const $this = $(this);
       const countTo = parseInt($this.text()) || 0;
       
       $({ countNum: 0 }).animate({
           countNum: countTo
       }, {
           duration: 1000,
           easing: 'swing',
           step: function() {
               $this.text(Math.floor(this.countNum));
           },
           complete: function() {
               $this.text(this.countNum);
           }
       });
   });
}

// ===== LIMPIAR FORMULARIOS =====
function limpiarFormularioNuevaCita() {
   $('#formNuevaCita')[0].reset();
   $('#infoPaciente, #pacienteNoEncontrado').addClass('d-none');
   $('#idPacienteSeleccionado').val('');
   $('#especialidadCita, #doctorCita, #horaCita').prop('disabled', true);
   $('#editandoCita').remove();
   
   // Restaurar título del modal
   $('#modalNuevaCita .modal-title').html('<i class="bi bi-plus-circle me-1"></i>Registrar Nueva Cita');
   
   pacienteSeleccionado = null;
   console.log('🧹 Formulario de nueva cita limpiado');
}

function limpiarFormularioRegistrarPaciente() {
    $('#formRegistrarPaciente')[0].reset();
    
    // Limpiar campos específicos
    $('#cedulaNuevoPaciente').val('');
    $('#nombresNuevoPaciente').val('');
    $('#apellidosNuevoPaciente').val('');
    $('#fechaNacimientoNuevo').val('');
    $('#generoNuevo').val('');
    $('#telefonoNuevo').val('');
    $('#correoNuevoPaciente').val('');
    $('#direccionNuevo').val('');
    $('#tipoSangreNuevo').val('');
    $('#alergiasNuevo').val('');
    $('#contactoEmergenciaNuevo').val('');
    $('#telefonoEmergenciaNuevo').val('');
    $('#numeroSeguroNuevo').val('');
    $('#antecedentesMedicosNuevo').val('');
    
    console.log('🧹 Formulario de registrar paciente limpiado');
}

// ===== UTILIDADES DE UI =====
function mostrarCargando(elemento) {
   const $el = $(elemento);
   $el.prop('disabled', true)
      .data('original-html', $el.html())
      .html('<i class="spinner-border spinner-border-sm me-1"></i>Cargando...');
}

function ocultarCargando(elemento, textoOriginal = null) {
   const $el = $(elemento);
   const originalHtml = textoOriginal || $el.data('original-html') || 'Botón';
   $el.prop('disabled', false).html(originalHtml);
}

function mostrarExito(mensaje) {
   Swal.fire({
       icon: 'success',
       title: '¡Éxito!',
       text: mensaje,
       timer: 3000,
       timerProgressBar: true,
       showConfirmButton: false
   });
}

function mostrarError(mensaje) {
   Swal.fire({
       icon: 'error',
       title: 'Error',
       text: mensaje,
       confirmButtonColor: '#dc3545'
   });
}

function mostrarAdvertencia(mensaje) {
   Swal.fire({
       icon: 'warning',
       title: 'Advertencia',
       text: mensaje,
       confirmButtonColor: '#ffc107'
   });
}

function mostrarInfo(mensaje) {
   Swal.fire({
       icon: 'info',
       title: 'Información',
       text: mensaje,
       confirmButtonColor: '#0dcaf0'
   });
}

// ===== FUNCIONES AUXILIARES =====
function formatearFecha(fecha) {
   return new Date(fecha).toLocaleDateString('es-ES', {
       year: 'numeric',
       month: '2-digit',
       day: '2-digit'
   });
}

function formatearHora(fecha) {
   return new Date(fecha).toLocaleTimeString('es-ES', {
       hour: '2-digit',
       minute: '2-digit'
   });
}

function formatearFechaHora(fecha) {
   return new Date(fecha).toLocaleString('es-ES', {
       year: 'numeric',
       month: '2-digit',
       day: '2-digit',
       hour: '2-digit',
       minute: '2-digit'
   });
}

// ===== MANEJO DE ERRORES GLOBALES =====
window.addEventListener('error', function(e) {
   console.error('❌ Error global capturado:', e.error);
   if (config.debug) {
       mostrarError(`Error: ${e.message}`);
   }
});

// ===== RESPONSIVE CALENDAR =====
$(window).resize(function() {
   if (calendario) {
       calendario.updateSize();
   }
});

// ===== ATAJOS DE TECLADO =====
$(document).keydown(function(e) {
   // Ctrl + N = Nueva cita
   if (e.ctrlKey && e.which === 78 && config.permisos.puede_crear) {
       e.preventDefault();
       $('#modalNuevaCita').modal('show');
   }
   
   // Escape = Cerrar modales
   if (e.which === 27) {
       $('.modal').modal('hide');
   }
   
   // F5 = Actualizar calendario
   if (e.which === 116) {
       e.preventDefault();
       calendario.refetchEvents();
       cargarEstadisticas();
       mostrarInfo('Calendario actualizado');
   }
});

// ===== NOTIFICACIONES EN TIEMPO REAL (OPCIONAL) =====
function iniciarNotificacionesEnTiempoReal() {
   // Aquí puedes implementar WebSockets o polling para actualizaciones en tiempo real
   setInterval(function() {
       if (document.visibilityState === 'visible') {
           calendario.refetchEvents();
           cargarEstadisticas();
       }
   }, 300000); // Actualizar cada 5 minutos
}

// ===== AUTO-GUARDAR BORRADOR (OPCIONAL) =====
function iniciarAutoguardado() {
   let timeoutId;
   
   $('#formNuevaCita input, #formNuevaCita select, #formNuevaCita textarea').on('input change', function() {
       clearTimeout(timeoutId);
       timeoutId = setTimeout(function() {
           guardarBorrador();
       }, 2000);
   });
}

function guardarBorrador() {
   const formData = $('#formNuevaCita').serializeArray();
   const borrador = {};
   
   formData.forEach(field => {
       borrador[field.name] = field.value;
   });
   
   localStorage.setItem('borrador_cita', JSON.stringify(borrador));
   console.log('💾 Borrador guardado automáticamente');
}

function cargarBorrador() {
   const borrador = localStorage.getItem('borrador_cita');
   if (borrador) {
       try {
           const data = JSON.parse(borrador);
           Object.keys(data).forEach(key => {
               $(`#formNuevaCita [name="${key}"]`).val(data[key]);
           });
           console.log('📋 Borrador cargado');
       } catch (e) {
           console.warn('⚠️ Error cargando borrador:', e);
       }
   }
}

// ⭐ FUNCIÓN DE DEBUG ESPECÍFICA PARA DOCTORES
function debugDoctoresPorEspecialidad() {
    console.log('🧪 DEBUG: Testeando carga de doctores por especialidad...');
    
    // Test con valores específicos de tu BD
    const testSucursal = 1; // Cambia por un ID que sepas que existe
    const testEspecialidad = 1; // Cambia por un ID que sepas que existe
    
    console.log(`📋 Test con: Sucursal ${testSucursal}, Especialidad ${testEspecialidad}`);
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerDoctoresPorEspecialidad',
            id_especialidad: testEspecialidad,
            id_sucursal: testSucursal,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            console.log('✅ Response obtenerDoctoresPorEspecialidad:', response);
            if (response.success && response.data) {
                console.log(`📊 Doctores encontrados: ${response.data.length}`);
                if (response.data.length > 0) {
                    console.log('👨‍⚕️ Primer doctor:', response.data[0]);
                }
            } else {
                console.warn('⚠️ No hay doctores o error en respuesta');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando doctores:', error);
            console.error('📄 Response Text:', xhr.responseText);
        }
    });
}

// Agrega esto al final de $(document).ready:
$(document).ready(function() {
    // ... código existente ...
    
    // ⭐ AGREGAR ESTO AL FINAL:
    setTimeout(() => {
        debugDoctoresPorEspecialidad();
    }, 3000);
});

function limpiarBorrador() {
   localStorage.removeItem('borrador_cita');
   console.log('🗑️ Borrador eliminado');
}

// ===== EXPORTAR FUNCIONES PARA USO EXTERNO =====
window.GestionCitas = {
   calendario,
   mostrarExito,
   mostrarError,
   mostrarAdvertencia,
   cargarEstadisticas,
   aplicarFiltros
};

console.log('🎉 Sistema de Gestión de Citas cargado completamente');