/**
 * Sistema de Gestión de Citas con Wizard de Pasos y Tipos de Cita
 * Autor: Sistema MediSys
 * Descripción: Manejo completo de citas médicas con calendario interactivo y wizard
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: true,
    submenuId: window.recepcionConfig?.submenuId || null,
    permisos: window.recepcionConfig?.permisos || {},
    sucursales: window.recepcionConfig?.sucursales || [],
    especialidades: window.recepcionConfig?.especialidades || [],
    tipos_cita: window.recepcionConfig?.tipos_cita || [],
    baseUrl: '../../controladores/RecepcionistaControlador/RecepcionistaController.php'
};

// Variables globales
// ===== VARIABLES GLOBALES - VERSIÓN CORREGIDA =====
window.calendario = null;
window.citaSeleccionada = null;
window.pacienteSeleccionado = null;
window.pasoActual = 1;
window.totalPasos = 6;
window.datosCitaWizard = {};
window.semanaActual = new Date();
window.doctorSeleccionado = null;
window.slotSeleccionado = null;
window.sucursalSeleccionada = null;
window.horarioSeleccionado = false;

// También mantener las versiones sin window para compatibilidad
let calendario = window.calendario;
let citaSeleccionada = window.citaSeleccionada;
let pacienteSeleccionado = window.pacienteSeleccionado;
let pasoActual = window.pasoActual;
let totalPasos = window.totalPasos;
let datosCitaWizard = window.datosCitaWizard;
let semanaActual = window.semanaActual;
let doctorSeleccionado = window.doctorSeleccionado;
let slotSeleccionado = window.slotSeleccionado;
let sucursalSeleccionada = window.sucursalSeleccionada;
// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🚀 Iniciando Sistema de Gestión de Citas con Wizard');
    
    try {
        inicializarCalendario();
        inicializarEventos();
        inicializarSelect2();
        inicializarWizard();
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

// ⭐ REMOVER LA FUNCIÓN btnRegistrarPacienteNuevo DEL INICIALIZADOR
function inicializarWizard() {
    // Inicializar wizard en paso 1
    mostrarPaso(1);
    
    // Configurar eventos del wizard
    $('#btnSiguientePaso').click(avanzarPaso);
    $('#btnAnteriorPaso').click(retrocederPaso);
    $('#btnConfirmarCita').click(confirmarRegistroCita);
    
    // Eventos específicos por paso
    $('.tipo-cita-card').click(seleccionarTipoCita);
    // ❌ REMOVER ESTA LÍNEA: $('#btnRegistrarPacienteNuevo').click(abrirModalRegistrarPaciente);
    $('#prioridadCita').change(actualizarDescripcionPrioridad);
    
    console.log('🎯 Wizard inicializado');
}

// ===== MANEJO DEL WIZARD =====
function mostrarPaso(numeroPaso) {
    pasoActual = numeroPaso;
    
    // Actualizar indicadores visuales
    $('.step-item').each(function(index) {
        const $item = $(this);
        const paso = index + 1;
        
        if (paso < numeroPaso) {
            $item.removeClass('active').addClass('completed');
        } else if (paso === numeroPaso) {
            $item.removeClass('completed').addClass('active');
        } else {
            $item.removeClass('active completed');
        }
    });
    
    // Mostrar contenido del paso
    $('.step-content').removeClass('active');
    $(`#step${numeroPaso}`).addClass('active');
    
    // Configurar botones
    configurarBotonesPaso(numeroPaso);
    
    // Ejecutar lógica específica del paso
    ejecutarLogicaPaso(numeroPaso);
    
    console.log(`📍 Mostrando paso ${numeroPaso}`);
}

function configurarBotonesPaso(paso) {
    const $btnAnterior = $('#btnAnteriorPaso');
    const $btnSiguiente = $('#btnSiguientePaso');
    const $btnConfirmar = $('#btnConfirmarCita');
    
    // Botón anterior
    if (paso === 1) {
        $btnAnterior.hide();
    } else {
        $btnAnterior.show();
    }
    
    // Botón siguiente/confirmar
    if (paso === totalPasos) {
        $btnSiguiente.hide();
        $btnConfirmar.show();
    } else {
        $btnSiguiente.show();
        $btnConfirmar.hide();
    }
}

function ejecutarLogicaPaso(paso) {
    switch (paso) {
        case 1:
            // Paso 1: Seleccionar tipo de cita
            $('.tipo-cita-card').removeClass('selected');
            break;
        case 2:
            // Paso 2: Buscar paciente
            $('#cedulaPaciente').focus();
            break;
        case 3:
            // Paso 3: Ubicación - mostrar/ocultar campos virtuales
            const tipoSeleccionado = $('#tipoCitaSeleccionado').val(); // ⭐ Usar input directo
            if (tipoSeleccionado == 2) {
                $('#camposVirtuales').addClass('active');
                $('#checkRecordarVirtual').show();
            } else {
                $('#camposVirtuales').removeClass('active');
                $('#checkRecordarVirtual').hide();
            }
            break;
        case 4:
            // Paso 4: Doctor y horario
            break;
        case 5:
            // Paso 5: Detalles
            $('#motivoCita').focus();
            break;
        case 6:
            // Paso 6: Resumen
            generarResumenCita();
            break;
    }
}

function avanzarPaso() {
    if (validarPasoActual()) {
        guardarDatosPaso();
        if (pasoActual < totalPasos) {
            mostrarPaso(pasoActual + 1);
        }
    }
}

function retrocederPaso() {
    if (pasoActual > 1) {
        mostrarPaso(pasoActual - 1);
    }
}

function validarPasoActual() {
    switch (pasoActual) {
        case 1:
            // ⭐ CAMBIAR ESTO: Verificar directamente del input en lugar de datosCitaWizard
            const tipoSeleccionado = $('#tipoCitaSeleccionado').val();
            if (!tipoSeleccionado) {
                mostrarError('Por favor seleccione un tipo de cita');
                return false;
            }
            return true;
            
        case 2:
            if (!$('#idPacienteSeleccionado').val()) {
                mostrarError('Por favor seleccione o registre un paciente');
                return false;
            }
            return true;
            
        case 3:
            if (!$('#sucursalCita').val() || !$('#especialidadCita').val()) {
                mostrarError('Por favor seleccione sucursal y especialidad');
                return false;
            }
            
            // Validaciones adicionales para citas virtuales
            const tipoActual = $('#tipoCitaSeleccionado').val(); // ⭐ Usar input directo
            if (tipoActual == 2) {
                if (!$('#plataformaVirtual').val()) {
                    mostrarError('Por favor seleccione la plataforma virtual');
                    return false;
                }
            }
            return true;
            
       case 4:
    console.log('🔍 === VALIDANDO PASO 4 ===');
    debugCompleto();
    
    if (!$('#doctorCita').val()) {
        mostrarError('Por favor seleccione un doctor');
        return false;
    }
    
    if (!window.slotSeleccionado || !$('#fechaCita').val() || !$('#horaCita').val()) {
        console.log('❌ FALLA EN PASO 4 - usando window:');
        console.log('  - window.slotSeleccionado:', !!window.slotSeleccionado);
        console.log('  - fechaCita:', $('#fechaCita').val());
        console.log('  - horaCita:', $('#horaCita').val());
        
        mostrarError('Por favor seleccione una fecha y hora en el calendario');
        return false;
    }
    console.log('✅ PASO 4 VALIDADO CORRECTAMENTE');
    return true;
            
        case 5:
            if (!$('#motivoCita').val().trim()) {
                mostrarError('Por favor ingrese el motivo de la consulta');
                return false;
            }
            return true;
            
        default:
            return true;
    }
}
function guardarDatosPaso() {
    switch (pasoActual) {
        case 1:
            datosCitaWizard.id_tipo_cita = $('#tipoCitaSeleccionado').val();
            datosCitaWizard.tipo_cita = $('#tipoConsulta').val();
            break;
            
        case 2:
            datosCitaWizard.id_paciente = $('#idPacienteSeleccionado').val();
            datosCitaWizard.paciente = pacienteSeleccionado;
            break;
            
        case 3:
            datosCitaWizard.id_sucursal = $('#sucursalCita').val();
            datosCitaWizard.id_especialidad = $('#especialidadCita').val();
            datosCitaWizard.sucursal_nombre = $('#sucursalCita option:selected').text();
            datosCitaWizard.especialidad_nombre = $('#especialidadCita option:selected').text();
            
            if (datosCitaWizard.id_tipo_cita == 2) {
                datosCitaWizard.plataforma_virtual = $('#plataformaVirtual').val();
                datosCitaWizard.sala_virtual = $('#salaVirtual').val();
                datosCitaWizard.enlace_virtual = $('#enlaceVirtual').val();
            }
            break;
            
        case 4:
            datosCitaWizard.id_doctor = $('#doctorCita').val();
            datosCitaWizard.fecha = $('#fechaCita').val();
            datosCitaWizard.hora = $('#horaCita').val();
            datosCitaWizard.doctor_nombre = $('#doctorCita option:selected').text();
            break;
            
        case 5:
            datosCitaWizard.motivo = $('#motivoCita').val();
            datosCitaWizard.prioridad = $('#prioridadCita').val();
            datosCitaWizard.notas = $('#notasCita').val();
            break;
    }
    
    console.log('💾 Datos guardados paso', pasoActual, datosCitaWizard);
}

// ===== SELECCIÓN DE TIPO DE CITA =====
function seleccionarTipoCita() {
    const $card = $(this);
    const tipoId = $card.data('tipo');
    const tipoNombre = $card.data('nombre');
    
    // Actualizar UI
    $('.tipo-cita-card').removeClass('selected');
    $card.addClass('selected');
    
    // Guardar selección en los inputs hidden
    $('#tipoCitaSeleccionado').val(tipoId);
    $('#tipoConsulta').val(tipoNombre);
    
    // ⭐ AGREGAR ESTO: Guardar inmediatamente en datosCitaWizard
    datosCitaWizard.id_tipo_cita = tipoId;
    datosCitaWizard.tipo_cita = tipoNombre;
    
    console.log(`🎯 Tipo de cita seleccionado: ${tipoNombre} (ID: ${tipoId})`);
    console.log('💾 Datos guardados inmediatamente:', datosCitaWizard);
}

// ===== GENERAR RESUMEN DE CITA =====
function generarResumenCita() {
    const fecha = new Date(datosCitaWizard.fecha + ' ' + datosCitaWizard.hora);
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
    
    // Resumen del paciente
    const resumenPaciente = `
        <div class="resumen-item">
            <span class="resumen-label">Nombre:</span>
            <span class="resumen-valor">${datosCitaWizard.paciente.nombres} ${datosCitaWizard.paciente.apellidos}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Cédula:</span>
            <span class="resumen-valor">${datosCitaWizard.paciente.cedula}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Correo:</span>
            <span class="resumen-valor">${datosCitaWizard.paciente.correo || 'No registrado'}</span>
        </div>
    `;
    
    // Resumen de la cita
    const tipoCitaTexto = datosCitaWizard.id_tipo_cita == 1 ? 'Presencial' : 'Virtual';
    const iconoTipo = datosCitaWizard.id_tipo_cita == 1 ? 'bi-building' : 'bi-camera-video';
    
    const resumenCita = `
        <div class="resumen-item">
            <span class="resumen-label">Tipo:</span>
            <span class="resumen-valor"><i class="bi ${iconoTipo} me-1"></i>${tipoCitaTexto}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Fecha:</span>
            <span class="resumen-valor">${fechaFormateada}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Hora:</span>
            <span class="resumen-valor">${horaFormateada}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Doctor:</span>
            <span class="resumen-valor">${datosCitaWizard.doctor_nombre}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Especialidad:</span>
            <span class="resumen-valor">${datosCitaWizard.especialidad_nombre}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Sucursal:</span>
            <span class="resumen-valor">${datosCitaWizard.sucursal_nombre}</span>
        </div>
    `;
    
    // Información adicional
    let resumenAdicional = `
        <div class="resumen-item">
            <span class="resumen-label">Motivo:</span>
            <span class="resumen-valor">${datosCitaWizard.motivo}</span>
        </div>
        <div class="resumen-item">
            <span class="resumen-label">Prioridad:</span>
            <span class="resumen-valor">${obtenerTextoPrioridad(datosCitaWizard.prioridad)}</span>
        </div>
    `;
    
    if (datosCitaWizard.notas) {
        resumenAdicional += `
            <div class="resumen-item">
                <span class="resumen-label">Notas:</span>
                <span class="resumen-valor">${datosCitaWizard.notas}</span>
            </div>
        `;
    }
    
    // Información específica para citas virtuales
    if (datosCitaWizard.id_tipo_cita == 2) {
        resumenAdicional += `
            <div class="resumen-item">
                <span class="resumen-label">Plataforma:</span>
                <span class="resumen-valor">${datosCitaWizard.plataforma_virtual}</span>
            </div>
        `;
        
        if (datosCitaWizard.enlace_virtual) {
            resumenAdicional += `
                <div class="resumen-item">
                    <span class="resumen-label">Enlace:</span>
                    <span class="resumen-valor">${datosCitaWizard.enlace_virtual}</span>
                </div>
            `;
        }
        
        if (datosCitaWizard.sala_virtual) {
            resumenAdicional += `
                <div class="resumen-item">
                    <span class="resumen-label">ID Sala:</span>
                    <span class="resumen-valor">${datosCitaWizard.sala_virtual}</span>
                </div>
            `;
        }
    }
    
    // Actualizar HTML
    $('#resumenPaciente').html(resumenPaciente);
    $('#resumenCita').html(resumenCita);
    $('#resumenAdicional').html(resumenAdicional);
    
    console.log('📋 Resumen generado');
}

function obtenerTextoPrioridad(prioridad) {
    const prioridades = {
        'normal': 'Normal',
        'urgente': 'Urgente',
        'muy_urgente': 'Muy Urgente'
    };
    return prioridades[prioridad] || 'Normal';
}

function actualizarDescripcionPrioridad() {
    const prioridad = $('#prioridadCita').val();
    const descripciones = {
        'normal': 'Consulta médica de rutina o seguimiento',
        'urgente': 'Requiere atención pronta, dentro de 24-48 horas',
        'muy_urgente': 'Requiere atención inmediata'
    };
    
    $('#descripcionPrioridad').text(descripciones[prioridad] || descripciones.normal);
}

// ===== CONFIRMAR REGISTRO DE CITA =====
function confirmarRegistroCita() {
    if (!$('#confirmarDatos').is(':checked')) {
        mostrarError('Debe confirmar que los datos son correctos');
        return;
    }
    
    const formData = new FormData();
    
    // Datos básicos
    formData.append('action', 'registrarCita');
    formData.append('submenu_id', config.submenuId);
    formData.append('id_paciente', datosCitaWizard.id_paciente);
    formData.append('id_doctor', datosCitaWizard.id_doctor);
    formData.append('id_sucursal', datosCitaWizard.id_sucursal);
    formData.append('id_tipo_cita', datosCitaWizard.id_tipo_cita);
    formData.append('fecha', datosCitaWizard.fecha);
    formData.append('hora', datosCitaWizard.hora);
    formData.append('motivo', datosCitaWizard.motivo);
    formData.append('notas', datosCitaWizard.notas || '');
    formData.append('prioridad', datosCitaWizard.prioridad || 'normal');
    
    // Datos específicos para citas virtuales
    if (datosCitaWizard.id_tipo_cita == 2) {
        formData.append('plataforma_virtual', datosCitaWizard.plataforma_virtual || '');
        formData.append('sala_virtual', datosCitaWizard.sala_virtual || '');
        formData.append('enlace_virtual', datosCitaWizard.enlace_virtual || '');
    }
    
    // Opciones de notificación
    formData.append('enviar_notificacion', $('#enviarNotificacion').is(':checked') ? 'true' : 'false');
    
    const $btnConfirmar = $('#btnConfirmarCita');
    mostrarCargando($btnConfirmar);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarCargando($btnConfirmar, '<i class="bi bi-check-circle me-1"></i>Confirmar y Registrar Cita');
            
            if (response.success) {
                mostrarExito('¡Cita registrada exitosamente! 🎉');
                $('#modalNuevaCita').modal('hide');
                
                // Recargar calendario y estadísticas
                calendario.refetchEvents();
                cargarEstadisticas();
                
                console.log('✅ Cita registrada:', response.data);
            } else {
                mostrarError(response.message || 'Error al registrar cita');
            }
        },
        error: function(xhr, status, error) {
            ocultarCargando($btnConfirmar, '<i class="bi bi-check-circle me-1"></i>Confirmar y Registrar Cita');
            console.error('❌ Error registrando cita:', error);
            mostrarError('Error al registrar cita');
        }
    });
}

// ===== INICIALIZACIÓN DEL CALENDARIO =====
// ===== INICIALIZACIÓN DEL CALENDARIO =====
function inicializarCalendario() {
    const calendarEl = document.getElementById('calendario');
    
    calendario = new FullCalendar.Calendar(calendarEl, {
        // Configuración básica
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1, // Lunes como primer día
        height: 'auto',
        
        // 🔧 QUITAR las restricciones globales para permitir ver todo
        // validRange: NO usar esto
        // selectConstraint: NO usar esto
        
        // Configuración de header
        headerToolbar: {
            left: 'title',
            center: '',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
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
            // 🔧 VALIDACIÓN MEJORADA: Solo validar cuando se quiere crear nueva cita
            const fechaSeleccionada = new Date(info.dateStr + 'T00:00:00');
            const ahora = new Date();
            
            // Comparar solo fechas (sin hora) - permitir HOY
            const fechaHoy = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
            const fechaElegida = new Date(fechaSeleccionada.getFullYear(), fechaSeleccionada.getMonth(), fechaSeleccionada.getDate());
            
            console.log('📅 Click en fecha:');
            console.log('  - Fecha elegida:', fechaElegida.toDateString());
            console.log('  - Fecha hoy:', fechaHoy.toDateString());
            console.log('  - Es pasada?', fechaElegida < fechaHoy);
            
            // 🔧 SOLO bloquear fechas ANTERIORES a hoy (permitir hoy)
            if (fechaElegida < fechaHoy) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha no válida',
                    text: 'No se pueden agendar citas en fechas pasadas. Puede agendar desde hoy en adelante.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }
            
            // Si es fecha válida (hoy o futura), continuar con el flujo normal
            console.log('✅ Fecha válida para nueva cita');
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
    
    console.log('📅 Calendario inicializado con validación inteligente de fechas');
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
        const tipoIcon = cita.id_tipo_cita == 2 ? '📹' : '🏥'; // Virtual o Presencial
        
        return {
            id: cita.id_cita,
            title: `${tipoIcon} ${cita.paciente_nombres} ${cita.paciente_apellidos}`,
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
    const tipoTexto = cita.tipo_cita_nombre || (cita.id_tipo_cita == 2 ? 'Virtual' : 'Presencial');
    const tooltip = `
        <strong>${cita.paciente_nombres} ${cita.paciente_apellidos}</strong><br>
        Doctor: ${cita.doctor_nombres} ${cita.doctor_apellidos}<br>
        Especialidad: ${cita.nombre_especialidad}<br>
        Tipo: ${tipoTexto}<br>
        Estado: ${cita.estado}<br>
        Hora: ${new Date(cita.fecha_hora).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}
    `;
    
    info.el.title = tooltip.replace(/<br>/g, '\n').replace(/<[^>]*>/g, '');
}

function cargarTodosLosDoctores() {
    console.log('👨‍⚕️ Cargando todos los doctores...');
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerDoctores',
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta todos los doctores:', response);
            
            if (response.success && response.data) {
                const doctorSelect = $('#filtroDoctor');
                doctorSelect.html('<option value="">👨‍⚕️ Todos los doctores</option>');
                
                response.data.forEach(doctor => {
                    doctorSelect.append(`
                        <option value="${doctor.id_doctor}">
                            Dr. ${doctor.nombres} ${doctor.apellidos} - ${doctor.especialidad || doctor.nombre_especialidad || ''}
                        </option>
                    `);
                });
                console.log(`✅ Cargados ${response.data.length} doctores`);
            } else {
                console.log('⚠️ No se encontraron doctores o respuesta inválida');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando todos los doctores:', error);
            console.error('Respuesta del servidor:', xhr.responseText);
        }
    });
}

// ===== INICIALIZACIÓN DE EVENTOS =====
// ===== INICIALIZACIÓN DE EVENTOS =====
function inicializarEventos() {
    // Botones de vista del calendario
    $('#btnVistaDia').click(() => calendario.changeView('timeGridDay'));
    $('#btnVistaSemana').click(() => calendario.changeView('timeGridWeek'));
    $('#btnVistaMes').click(() => calendario.changeView('dayGridMonth'));
    $('#btnHoy').click(() => calendario.today());
    $('#btnAnterior').click(() => calendario.prev());
    $('#btnSiguiente').click(() => calendario.next());
    
    // Botón nueva cita (desde header)
    $('[data-bs-target="#modalNuevaCita"]').click(function() {
        $('#modalNuevaCita').modal('show');
    });
    
    // ===== FILTROS CORREGIDOS =====
    // Botón aplicar filtros
    $('#btnAplicarFiltros').click(function(e) {
        e.preventDefault();
        aplicarFiltros();
    });
    
    // Botón limpiar filtros
    $('#btnLimpiarFiltros').click(function(e) {
        e.preventDefault();
        limpiarFiltros();
    });
    
    // Filtros que no dependen de otros
    $('#filtroSucursal, #filtroTipoCita, #filtroEstado').change(function() {
        console.log(`🔄 Cambió ${this.id}:`, $(this).val());
        aplicarFiltros();
    });
    
    // Especialidad: cuando cambia, actualiza doctores Y aplica filtros
    $('#filtroEspecialidad').change(function() {
        console.log('🏥 Especialidad seleccionada:', $(this).val());
        cargarDoctoresPorEspecialidad();
        // Los filtros se aplicarán automáticamente después de cargar los doctores
    });
    
    // Doctor: cuando cambia, aplica filtros
    $('#filtroDoctor').change(function() {
        console.log('👨‍⚕️ Doctor seleccionado:', $(this).val());
        aplicarFiltros();
    });
    
    // Formulario búsqueda paciente
    $('#btnBuscarPaciente').click(buscarPaciente);
    $('#btnObtenerDatosCedula').click(obtenerDatosCedula);
    
    // Formulario registrar paciente
    $('#formRegistrarPaciente').submit(manejarSubmitRegistrarPaciente);
    
    // ===== CASCADAS DE SELECTS EN MODAL NUEVA CITA =====
    $('#sucursalCita').change(function() {
        cargarEspecialidadesPorSucursal();
        // Limpiar calendario cuando cambie sucursal
        mostrarCalendarioVacio('Seleccione un doctor para ver los horarios disponibles');
        slotSeleccionado = null;
        $('#fechaCita, #horaCita, #fechaHoraCompleta').val('');
    });
    
    $('#especialidadCita').change(function() {
        cargarDoctoresPorEspecialidad(); // Esta es diferente a la de filtros
    });
    
    $('#doctorCita').change(function() {
        cargarCalendarioHorarios(); // En lugar de cargarHorariosDisponibles
    });
    
    $('#fechaCita').change(cargarHorariosDisponibles);
    
    // Acciones de citas
    $('#btnEditarCita').click(editarCita);
    $('#btnConfirmarCita').click(confirmarCita);
    $('#btnCancelarCita').click(cancelarCita);
    
    // Limpiar modales al cerrar
    $('#modalNuevaCita').on('hidden.bs.modal', limpiarWizard);
    $('#modalRegistrarPaciente').on('hidden.bs.modal', limpiarFormularioRegistrarPaciente);
    
    // ===== NAVEGACIÓN DEL CALENDARIO DE HORARIOS =====
    $('#btnSemanaAnterior').click(() => cambiarSemana(-1));
    $('#btnSemanaSiguiente').click(() => cambiarSemana(1));
    $('#btnSemanaActual').click(() => {
        semanaActual = new Date();
        if (doctorSeleccionado) {
            cargarCalendarioHorarios();
        }
    });
    
    console.log('🎯 Eventos inicializados correctamente');
}
// ===== INICIALIZACIÓN DE SELECT2 =====
function inicializarSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione una opción',
        allowClear: true
    });
}

// ⭐ ACTUALIZAR LA FUNCIÓN DE BÚSQUEDA PARA MEJOR UX
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
    
    // Limpiar estados anteriores
    $('#infoPaciente, #pacienteNoEncontrado').addClass('d-none');
    
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
       <small>Cédula: ${paciente.cedula} | Correo: ${paciente.correo || 'No registrado'}</small>
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
    
    // ⭐ ABRIR AUTOMÁTICAMENTE EL MODAL DE REGISTRO DESPUÉS DE 1 SEGUNDO
    setTimeout(() => {
        abrirModalRegistrarPaciente();
    }, 1000);
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
    // No cerrar el modal principal, solo abrir el de registro encima
    $('#modalRegistrarPaciente').modal('show');
}
// ⭐ ACTUALIZAR EL MANEJO DEL SUBMIT DEL REGISTRO DE PACIENTE
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
                
                // ⭐ ACTUALIZAR EL PASO 2 CON EL PACIENTE REGISTRADO
                mostrarPacienteEncontrado(pacienteSeleccionado);
                
                // ⭐ OCULTAR MENSAJE DE "NO ENCONTRADO"
                $('#pacienteNoEncontrado').addClass('d-none');
                
                // Cerrar modal de registro
                $('#modalRegistrarPaciente').modal('hide');
                
                console.log('✅ Paciente registrado y seleccionado:', response.data);
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
               
               // Mostrar información de la sucursal
               const sucursal = config.sucursales.find(s => s.id_sucursal == idSucursal);
               if (sucursal) {
                   $('#detallesSucursal').html(`
                       <strong>${sucursal.nombre_sucursal}</strong><br>
                       <small><i class="bi bi-geo-alt me-1"></i>${sucursal.direccion}</small><br>
                       <small><i class="bi bi-telephone me-1"></i>${sucursal.telefono}</small>
                   `);
                   $('#infoSucursal').removeClass('d-none');
               }
               
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
               
               // Mostrar información de la especialidad
               const especialidad = config.especialidades.find(e => e.id_especialidad == idEspecialidad);
               if (especialidad) {
                   $('#detallesEspecialidad').html(`
                       <strong>${especialidad.nombre_especialidad}</strong><br>
                       <small>${especialidad.descripcion || 'Especialidad médica'}</small>
                   `);
                   $('#infoEspecialidad').removeClass('d-none');
               }
               
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

// ===== GENERAR CALENDARIO SEMANAL =====
// ===== GENERAR CALENDARIO SEMANAL CORREGIDO =====
function generarCalendarioSemanal(datosHorarios) {
    console.log('🔄 Datos recibidos del servidor:', datosHorarios);
    
    const inicioSemana = obtenerInicioSemana(semanaActual);
    const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const hoy = new Date();
    
    let html = '<div class="semana-container">';
    
    // Header con días de la semana
    html += '<div class="dias-header">';
    html += '<div class="dia-header"><strong>Hora</strong></div>';
    
    for (let i = 0; i < 7; i++) {
        const fecha = new Date(inicioSemana);
        fecha.setDate(fecha.getDate() + i);
        
        const esHoy = esMismaFecha(fecha, hoy);
        const claseHoy = esHoy ? 'hoy' : '';
        
        html += `<div class="dia-header ${claseHoy}">
            <div>${diasSemana[i]}</div>
            <div class="dia-numero">${fecha.getDate()}/${fecha.getMonth() + 1}</div>
        </div>`;
    }
    html += '</div>';
    
    // Grid de horarios
    html += '<div class="horarios-grid">';
    
    // Generar slots por hora (8:00 - 19:00, cada 30 minutos)
    const horaInicio = 8;
    const horaFin = 19;
    
    for (let hora = horaInicio; hora < horaFin; hora++) {
        for (let minutos = 0; minutos < 60; minutos += 30) {
            const horaStr = `${hora.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;
            
            // Label de hora
            html += `<div class="hora-label">${horaStr}</div>`;
            
            // Slots para cada día de la semana
            for (let dia = 0; dia < 7; dia++) {
                const fecha = new Date(inicioSemana);
                fecha.setDate(fecha.getDate() + dia);
                
                const fechaStr = formatearFechaParaPHP(fecha); // YYYY-MM-DD
                
                // ✅ CORREGIR EL CÁLCULO DEL DÍA DE LA SEMANA
                // dia=0 es Lunes, dia=1 es Martes, etc.
                const diaNumero = dia + 1; // Convertir a formato BD: 1=Lunes, 2=Martes, etc.
                
                // Verificar disponibilidad
                const disponibilidad = verificarDisponibilidadSlot(datosHorarios, diaNumero, horaStr, fechaStr, fecha, hoy);
                
                html += `<div class="${disponibilidad.clase}" ${disponibilidad.dataAtributos}>
                    ${disponibilidad.texto}
                </div>`;
            }
        }
    }
    
    html += '</div>';
    
    // Leyenda
    html += `
        <div class="leyenda-horarios">
            <div class="leyenda-item">
                <div class="leyenda-color" style="background-color: #d4edda;"></div>
                <span>Disponible</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-color" style="background-color: #f8d7da;"></div>
                <span>Ocupado</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-color" style="background-color: #e9ecef;"></div>
                <span>No disponible</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-color" style="background-color: #007bff;"></div>
                <span>Seleccionado</span>
            </div>
        </div>
    `;
    
    html += '</div>';
    
    $('#calendarioHorarios').html(html);
    
    // Agregar eventos de click SOLO a slots disponibles
    $('.slot-horario.disponible').click(function() {
        seleccionarSlotHorario(this);
    });
    
    console.log('📅 Calendario semanal generado correctamente');
}
// ===== SELECCIONAR SLOT DE HORARIO - CORREGIDO =====
function seleccionarSlotHorario(elemento) {
    // Remover selección anterior
    $('.slot-horario').removeClass('seleccionado');
    
    // Seleccionar nuevo slot
    $(elemento).addClass('seleccionado');
    
    const fecha = $(elemento).data('fecha');
    const hora = $(elemento).data('hora');
    
    console.log('✅ Slot seleccionado:', fecha, hora);
    
    // Crear fecha correctamente
    const fechaPartes = fecha.split('-');
    const año = parseInt(fechaPartes[0]);
    const mes = parseInt(fechaPartes[1]) - 1;
    const dia = parseInt(fechaPartes[2]);
    const fechaObj = new Date(año, mes, dia);
    
    // Convertir fecha para el formulario
    const fechaFormulario = `${fechaPartes[2]}/${fechaPartes[1]}/${fechaPartes[0]}`;
    
    // ✅ GUARDAR EN WINDOW Y VARIABLES LOCALES
    window.datosCitaWizard.fecha = fechaFormulario;
    window.datosCitaWizard.hora = hora + ':00';
    datosCitaWizard.fecha = fechaFormulario;
    datosCitaWizard.hora = hora + ':00';
    
    // Crear/llenar campos del DOM
    if ($('#fechaCita').length === 0) {
        $('body').append('<input type="hidden" id="fechaCita">');
    }
    if ($('#horaCita').length === 0) {
        $('body').append('<input type="hidden" id="horaCita">');
    }
    
    $('#fechaCita').val(fechaFormulario);
    $('#horaCita').val(hora + ':00');
    
    // Guardar slot seleccionado
    window.slotSeleccionado = {
        fecha: fechaFormulario,
        hora: hora + ':00',
        fechaCompleta: fecha + ' ' + hora + ':00',
        elemento: elemento
    };
    slotSeleccionado = window.slotSeleccionado;
    
    window.horarioSeleccionado = true;
    
    console.log('📝 Datos guardados:', {
        windowSlot: !!window.slotSeleccionado,
        windowDatos: window.datosCitaWizard,
        campoFecha: $('#fechaCita').val(),
        campoHora: $('#horaCita').val()
    });
    
    // Mostrar confirmación
    const fechaLegible = fechaObj.toLocaleDateString('es-ES', {
        weekday: 'long',
        day: 'numeric',
        month: 'long'
    });
    
    Swal.fire({
        icon: 'success',
        title: 'Horario seleccionado',
        text: `${fechaLegible} a las ${hora}`,
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// ===== FUNCIÓN AUXILIAR PARA FORMATEAR FECHA PARA PHP =====
function formatearFechaParaPHP(fecha) {
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
}
function mostrarCalendarioLoading() {
    $('#calendarioHorarios').html(`
        <div class="text-center p-4 calendario-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando horarios...</span>
            </div>
            <p class="mt-2">Cargando horarios disponibles...</p>
        </div>
    `);
}

function mostrarCalendarioError(mensaje) {
    $('#calendarioHorarios').html(`
        <div class="text-center p-4 calendario-error">
            <i class="bi bi-exclamation-triangle fs-1"></i>
            <p class="mt-2">${mensaje}</p>
            <button class="btn btn-outline-primary btn-sm mt-2" onclick="cargarCalendarioHorarios()">
                <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
            </button>
        </div>
    `);
}

// ===== FUNCIONES AUXILIARES PARA FECHAS =====
function obtenerInicioSemana(fecha) {
    const inicio = new Date(fecha);
    const dia = inicio.getDay();
    const diferencia = dia === 0 ? -6 : 1 - dia; // Lunes como inicio
    inicio.setDate(inicio.getDate() + diferencia);
    return inicio;
}

function esMismaFecha(fecha1, fecha2) {
    return fecha1.toDateString() === fecha2.toDateString();
}

function esFechaPasada(fecha, referencia) {
    const fechaComparar = new Date(fecha);
    fechaComparar.setHours(0, 0, 0, 0);
    const fechaRef = new Date(referencia);
    fechaRef.setHours(0, 0, 0, 0);
    return fechaComparar < fechaRef;
}

function formatearFechaLegible(fechaStr) {
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-ES', {
        weekday: 'long',
        day: 'numeric',
        month: 'long'
    });
}

// ===== NAVEGACIÓN DE SEMANAS =====
function cambiarSemana(direccion) {
    const nuevaFecha = new Date(semanaActual);
    nuevaFecha.setDate(nuevaFecha.getDate() + (direccion * 7));
    semanaActual = nuevaFecha;
    
    if (doctorSeleccionado) {
        cargarCalendarioHorarios();
    }
}

// ===== FUNCIONES DE ESTADOS DEL CALENDARIO =====
function mostrarCalendarioVacio(mensaje) {
    $('#calendarioHorarios').html(`
        <div class="text-center p-4 text-muted calendario-loading">
            <i class="bi bi-calendar-x fs-1"></i>
            <p class="mt-2">${mensaje}</p>
        </div>
    `);
}

// ===== VERIFICAR DISPONIBILIDAD DE SLOT =====
// ===== VERIFICAR DISPONIBILIDAD DE SLOT CORREGIDO =====
function verificarDisponibilidadSlot(datosHorarios, diaNumero, horaStr, fechaStr, fechaObj, hoy) {
    let clase = 'slot-horario';
    let texto = '';
    let dataAtributos = '';
    
    // 1. Verificar si es fecha pasada
    if (esFechaPasada(fechaObj, hoy)) {
        return {
            clase: clase + ' pasado',
            texto: '',
            dataAtributos: ''
        };
    }
    
    // 2. Verificar si el doctor trabaja este día
    const horariosDelDia = datosHorarios.horarios.filter(h => h.dia_semana == diaNumero);
    
    if (horariosDelDia.length === 0) {
        return {
            clase: clase + ' no-disponible',
            texto: '',
            dataAtributos: ''
        };
    }
    
    // 3. Verificar si la hora está dentro del horario de trabajo
    const horaCompleta = horaStr + ':00'; // 09:00:00
    let dentroDeTurno = false;
    
    for (const horario of horariosDelDia) {
        if (horaCompleta >= horario.hora_inicio && horaCompleta < horario.hora_fin) {
            dentroDeTurno = true;
            break;
        }
    }
    
    if (!dentroDeTurno) {
        return {
            clase: clase + ' no-disponible',
            texto: '',
            dataAtributos: ''
        };
    }
    
    // 4. Verificar excepciones
    const excepcion = datosHorarios.excepciones.find(e => e.fecha === fechaStr);
    if (excepcion && ['no_laborable', 'vacaciones', 'feriado'].includes(excepcion.tipo)) {
        return {
            clase: clase + ' no-disponible',
            texto: 'No disp.',
            dataAtributos: `title="${excepcion.motivo || excepcion.tipo}"`
        };
    }
    
    // 5. ✅ VERIFICAR SI HAY CITA OCUPADA (CORREGIDO)
    const citaOcupada = datosHorarios.citas_ocupadas.find(c => {
        return c.fecha === fechaStr && c.hora === horaCompleta;
    });
    
    if (citaOcupada) {
        console.log(`🔴 Slot ocupado encontrado: ${fechaStr} ${horaCompleta}`, citaOcupada);
        return {
            clase: clase + ' ocupado',
            texto: 'Ocupado',
            dataAtributos: `title="Cita: ${citaOcupada.motivo || 'Consulta médica'}"`
        };
    }
    
    // 6. Slot disponible
    return {
        clase: clase + ' disponible',
        texto: '✓',
        dataAtributos: `data-fecha="${fechaStr}" data-hora="${horaStr}" data-dia="${diaNumero}" title="Disponible - Click para seleccionar" style="cursor: pointer;"`
    };
}

// ===== ACTUALIZAR LA FUNCIÓN cargarHorariosDisponibles =====
function cargarHorariosDisponibles() {
    // Esta función ya no se usa, la reemplazamos con cargarCalendarioHorarios
    cargarCalendarioHorarios();
}

// ===== NUEVA FUNCIÓN PARA CARGAR CALENDARIO DE HORARIOS =====
function cargarCalendarioHorarios() {
    const idDoctor = $('#doctorCita').val();
    const idSucursal = $('#sucursalCita').val();
    
    if (!idDoctor) {
        mostrarCalendarioVacio('Seleccione un doctor para ver los horarios disponibles');
        return;
    }
    
    if (!idSucursal) {
        mostrarCalendarioVacio('Seleccione una sucursal primero');
        return;
    }
    
    doctorSeleccionado = idDoctor;
    sucursalSeleccionada = idSucursal;
    
    // Mostrar loading
    mostrarCalendarioLoading();
    
    // Obtener horarios del doctor
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
       data: {
    action: 'obtenerHorariosDoctor',
    id_doctor: idDoctor,
    id_sucursal: idSucursal,
    semana: semanaActual.getFullYear() + '-' + 
           String(semanaActual.getMonth() + 1).padStart(2, '0') + '-' + 
           String(semanaActual.getDate()).padStart(2, '0'),  // ✅ FORMATO CORRECTO
    submenu_id: config.submenuId
},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                generarCalendarioSemanal(response.data);
                
                // Mostrar información del doctor
                const doctorNombre = $('#doctorCita option:selected').text();
                $('#detallesDoctor').html(`
                    <strong>${doctorNombre}</strong><br>
                    <small>📅 Horarios disponibles cargados</small>
                `);
                $('#infoDoctor').removeClass('d-none');
                
                console.log('✅ Horarios cargados para doctor:', doctorNombre);
            } else {
                mostrarCalendarioError('Error al cargar horarios: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando horarios:', error);
            mostrarCalendarioError('Error de conexión al cargar horarios');
        }
    });
}

// ===== MOSTRAR DETALLES DE CITA - VERSIÓN CORREGIDA =====
function mostrarDetallesCita(cita) {
    console.log('🔍 Mostrando detalles de cita:', cita); // Debug
    
    // Formatear fecha y hora
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
    
    // Mostrar loading primero
    $('#loadingVerCita').show();
    $('.modal-body-content').hide();
    $('#detallesCita').hide();
    
    // Abrir el modal
    $('#modalVerCita').modal('show');
    
    // Simular carga y luego mostrar datos
    setTimeout(() => {
        // Ocultar loading y mostrar contenido
        $('#loadingVerCita').hide();
        $('.modal-body-content').show();
        
        // ===== POBLAR DATOS DEL PACIENTE =====
        $('#nombrePacienteDetalle').text(`${cita.paciente_nombres} ${cita.paciente_apellidos}`);
        $('#cedulaPacienteDetalle').text(cita.paciente_cedula || '---');
        
        // CORREGIR: El teléfono del paciente está en la tabla pacientes como 'telefono'
        // Necesitamos obtenerlo del JOIN con la consulta
        $('#telefonoPacienteDetalle').text(cita.telefono || cita.paciente_telefono || '---');
        $('#emailPacienteDetalle').text(cita.paciente_correo || '---');
        
        // ===== FECHA Y HORA =====
        $('#fechaCitaDetalle').text(fechaFormateada);
        $('#horaCitaDetalle').text(horaFormateada);
        $('#duracionCitaDetalle').text('30 minutos');
        
        // ===== INFORMACIÓN MÉDICA =====
        $('#doctorCitaDetalle').text(`Dr. ${cita.doctor_nombres} ${cita.doctor_apellidos}`);
        $('#especialidadCitaDetalle').text(cita.nombre_especialidad || '---');
        
        // Tipo de cita con icono dinámico
        const tipoTexto = cita.tipo_cita_nombre || (cita.id_tipo_cita == 2 ? 'Virtual' : 'Presencial');
        $('#tipoCitaDetalle').text(tipoTexto);
        
        const iconTipo = $('#iconTipoCita');
        if (cita.id_tipo_cita == 2) {
            iconTipo.removeClass('bi-building bi-laptop').addClass('bi-camera-video text-success');
        } else {
            iconTipo.removeClass('bi-camera-video bi-laptop').addClass('bi-building text-primary');
        }
        
        // ===== UBICACIÓN =====
        $('#sucursalCitaDetalle').text(cita.nombre_sucursal || '---');
        $('#consultorioCitaDetalle').text(cita.consultorio || 'Por asignar');
        
        // CORREGIR: La dirección está en la tabla sucursales como 'direccion'
        $('#direccionSucursalDetalle').text(cita.direccion || cita.sucursal_direccion || '---');
        
        // ===== ESTADO Y CONTROL =====
        $('#estadoActualCita').text(cita.estado);
        $('#fechaRegistroCita').text(cita.fecha_creacion ? 
            new Date(cita.fecha_creacion).toLocaleDateString('es-ES') : '---');
        
        // CORREGIR: Para mostrar quién registró la cita, necesitamos el nombre del usuario
        // que aparece en los datos de la cita
        let usuarioRegistro = 'Sistema';
        if (cita.usuario_registro) {
            usuarioRegistro = cita.usuario_registro;
        } else if (cita.registrado_por) {
            usuarioRegistro = cita.registrado_por;
        } else if (cita.usuario_nombres && cita.usuario_apellidos) {
            usuarioRegistro = `${cita.usuario_nombres} ${cita.usuario_apellidos}`;
        }
        $('#usuarioRegistroCita').text(usuarioRegistro);
        
        $('#idCitaDetalle').text(`#${cita.id_cita}`);
        
        // ===== MOTIVO =====
        $('#motivoCitaDetalle').text(cita.motivo || 'No especificado');
        
        // ===== OBSERVACIONES (condicional) =====
        if (cita.notas && cita.notas.trim()) {
            $('#observacionesCitaDetalle').text(cita.notas);
            $('#contenedorObservacionesCita').show();
        } else {
            $('#contenedorObservacionesCita').hide();
        }
        
        // ===== INFORMACIÓN VIRTUAL (si aplica) =====
        manejarInformacionVirtual(cita);
        
        // ===== ACTUALIZAR HEADER SEGÚN ESTADO =====
        actualizarHeaderEstado(cita.estado);
        
        console.log('✅ Detalles cargados correctamente en nueva estructura');
        
    }, 500); // Delay para mostrar el loading
    
    // ===== CONFIGURAR BOTONES SEGÚN ESTADO =====
    configurarBotonesCita(cita);
}

// ===== FUNCIÓN AUXILIAR PARA INFORMACIÓN VIRTUAL =====
function manejarInformacionVirtual(cita) {
    if (cita.id_tipo_cita == 2) {
        // Crear sección adicional para info virtual si no existe
        let seccionVirtual = $('#seccionInfoVirtual');
        if (seccionVirtual.length === 0) {
            const htmlVirtual = `
                <div class="row mt-3" id="seccionInfoVirtual">
                    <div class="col-12">
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <i class="bi bi-camera-video-fill detail-icon bg-info"></i>
                                <h6 class="detail-title">Información Virtual</h6>
                            </div>
                            <div class="detail-card-body">
                                <div class="detail-row" id="rowEnlaceVirtual" style="display: none;">
                                    <div class="detail-item">
                                        <i class="bi bi-link-45deg text-info me-2"></i>
                                        <span class="detail-label">Enlace:</span>
                                        <a href="#" id="enlaceVirtual" class="detail-value text-info" target="_blank">Ver enlace</a>
                                    </div>
                                </div>
                                <div class="detail-row" id="rowSalaVirtual" style="display: none;">
                                    <div class="detail-item">
                                        <i class="bi bi-door-open text-success me-2"></i>
                                        <span class="detail-label">ID de Sala:</span>
                                        <span class="detail-value" id="salaVirtual">---</span>
                                    </div>
                                </div>
                                <div class="detail-row" id="rowPlataformaVirtual">
                                    <div class="detail-item">
                                        <i class="bi bi-laptop text-primary me-2"></i>
                                        <span class="detail-label">Plataforma:</span>
                                        <span class="detail-value" id="plataformaVirtual">Jitsi Meet</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('.modal-body-content').append(htmlVirtual);
        }
        
        // Mostrar información virtual
        if (cita.enlace_virtual) {
            $('#enlaceVirtual').attr('href', cita.enlace_virtual).text('Acceder a videollamada');
            $('#rowEnlaceVirtual').show();
        } else {
            $('#rowEnlaceVirtual').hide();
        }
        
        if (cita.sala_virtual) {
            $('#salaVirtual').text(cita.sala_virtual);
            $('#rowSalaVirtual').show();
        } else {
            $('#rowSalaVirtual').hide();
        }
        
        $('#plataformaVirtual').text(cita.plataforma_virtual || 'Jitsi Meet');
        $('#seccionInfoVirtual').show();
    } else {
        // Ocultar sección virtual para citas presenciales
        $('#seccionInfoVirtual').hide();
    }
}

// ===== FUNCIÓN AUXILIAR PARA ACTUALIZAR HEADER =====
function actualizarHeaderEstado(estado) {
    const header = $('#headerVerCita');
    const badge = $('#badgeEstadoVerCita');
    
    // Limpiar clases previas
    header.removeClass('estado-pendiente estado-confirmada estado-completada estado-cancelada');
    badge.removeClass('estado-pendiente estado-confirmada estado-completada estado-cancelada');
    
    // Agregar clase según estado
    const estadoLower = estado.toLowerCase();
    const estadoClass = `estado-${estadoLower}`;
    header.addClass(estadoClass);
    badge.addClass(estadoClass);
    
    // Actualizar texto del badge
    $('#textoEstadoCita').text(estado);
    
    // Actualizar icono del badge
    const iconos = {
        'pendiente': 'bi-clock',
        'confirmada': 'bi-check-circle',
        'completada': 'bi-check-all',
        'cancelada': 'bi-x-circle'
    };
    
    const iconoClase = iconos[estadoLower] || 'bi-clock';
    badge.find('i').removeClass().addClass(`bi ${iconoClase} me-1`);
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
    if (!citaSeleccionada) {
        mostrarError('No hay ninguna cita seleccionada');
        return;
    }
    
    console.log('🔍 Cita seleccionada para cancelar:', citaSeleccionada);
    
    // Obtener datos de la cita
    const nombrePaciente = obtenerNombrePaciente(citaSeleccionada);
    const fechaHora = obtenerFechaHoraCita(citaSeleccionada);
    const doctorNombre = obtenerNombreDoctor(citaSeleccionada);
    const estadoActual = citaSeleccionada.estado || citaSeleccionada.extendedProps?.estado || 'No disponible';
    
    // Llenar datos en el modal
    $('#nombrePacienteCancelar').text(nombrePaciente);
    $('#fechaHoraCancelar').text(fechaHora);
    $('#doctorCancelar').text(doctorNombre);
    $('#estadoActualCancelar').text(estadoActual);
    
    // Configurar badge de estado
    const badgeClass = obtenerClaseBadgeEstado(estadoActual);
    $('#estadoActualCancelar').removeClass().addClass(`badge ${badgeClass}`);
    
    // Establecer ID de cita
    const idCita = citaSeleccionada.id_cita || citaSeleccionada.id || citaSeleccionada.extendedProps?.id_cita;
    $('#idCitaCancelar').val(idCita);
    
    // Limpiar formulario
    $('#motivoCancelacion').val('');
    $('#enviarNotificacionCancelacion').prop('checked', true);
    $('#contadorCaracteres').text('0/500 caracteres');
    
    // Mostrar modal
    $('#modalCancelarCita').modal('show');
}

// Funciones auxiliares para obtener datos
function obtenerNombrePaciente(cita) {
    if (cita.nombres_paciente && cita.apellidos_paciente) {
        return `${cita.nombres_paciente} ${cita.apellidos_paciente}`;
    } else if (cita.paciente_nombres && cita.paciente_apellidos) {
        return `${cita.paciente_nombres} ${cita.paciente_apellidos}`;
    } else if (cita.title) {
        return cita.title;
    } else if (cita.extendedProps?.nombres_paciente && cita.extendedProps?.apellidos_paciente) {
        return `${cita.extendedProps.nombres_paciente} ${cita.extendedProps.apellidos_paciente}`;
    }
    return 'Paciente no identificado';
}

function obtenerFechaHoraCita(cita) {
    let fecha = null;
    
    if (cita.fecha_hora) {
        fecha = new Date(cita.fecha_hora);
    } else if (cita.start) {
        fecha = new Date(cita.start);
    } else if (cita.extendedProps?.fecha_hora) {
        fecha = new Date(cita.extendedProps.fecha_hora);
    }
    
    if (fecha) {
        const fechaStr = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const horaStr = fecha.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        return `${fechaStr} a las ${horaStr}`;
    }
    
    return 'Fecha no disponible';
}

function obtenerNombreDoctor(cita) {
    if (cita.nombres_doctor && cita.apellidos_doctor) {
        return `Dr. ${cita.nombres_doctor} ${cita.apellidos_doctor}`;
    } else if (cita.doctor_nombres && cita.doctor_apellidos) {
        return `Dr. ${cita.doctor_nombres} ${cita.doctor_apellidos}`;
    } else if (cita.extendedProps?.nombres_doctor && cita.extendedProps?.apellidos_doctor) {
        return `Dr. ${cita.extendedProps.nombres_doctor} ${cita.extendedProps.apellidos_doctor}`;
    }
    return 'Doctor no identificado';
}

function obtenerClaseBadgeEstado(estado) {
    const clases = {
        'Pendiente': 'bg-warning text-dark',
        'Confirmada': 'bg-success',
        'Completada': 'bg-info',
        'Cancelada': 'bg-danger'
    };
    return clases[estado] || 'bg-secondary';
}

function ejecutarCancelacion(idCita, motivo, enviarNotificacion, callback) {
    $.ajax({
        url: '../../controladores/RecepcionistaControlador/RecepcionistaController.php',
        type: 'POST',
        data: {
            action: 'cancelarCita',
            id_cita: idCita,
            motivo_cancelacion: motivo,
            enviar_notificacion: enviarNotificacion ? 'true' : 'false',
            submenu_id: window.recepcionConfig.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (callback) callback();
            
            if (response.success) {
                // Cerrar modal
                $('#modalCancelarCita').modal('hide');
                
                // Mostrar éxito
                Swal.fire({
                    title: '✅ Cita Cancelada',
                    text: 'La cita ha sido cancelada exitosamente',
                    icon: 'success',
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                
                // Recargar datos
                if (typeof calendario !== 'undefined') {
                    calendario.refetchEvents();
                }
                cargarEstadisticas();
                
                // Cerrar modal de detalles si está abierto
                $('#modalVerCita').modal('hide');
                
                // Limpiar cita seleccionada
                citaSeleccionada = null;
                
                console.log('✅ Cita cancelada exitosamente');
            } else {
                mostrarError(response.message || 'Error al cancelar la cita');
            }
        },
        error: function(xhr, status, error) {
            if (callback) callback();
            console.error('Error cancelando cita:', error);
            
            let mensaje = 'Error de conexión al cancelar la cita';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            }
            
            mostrarError(mensaje);
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
// 🔧 CORREGIR: Función para obtener filtros activos
function obtenerFiltrosActivos() {
    return {
        estado: $('#filtroEstado').val() || '',
        id_sucursal: $('#filtroSucursal').val() || '',
        tipo_cita: $('#filtroTipoCita').val() || '', // 🔧 IMPORTANTE
        id_especialidad: $('#filtroEspecialidad').val() || '',
        id_doctor: $('#filtroDoctor').val() || ''
    };
}

// 🔧 NUEVA FUNCIÓN: Validar fechas y horas pasadas
// 🔧 FUNCIÓN MEJORADA: Validar fechas y horas pasadas
function validarFechaHoraPasada(fecha, hora) {
    const ahora = new Date();
    const fechaSeleccionada = new Date(`${fecha}T${hora}`);
    
    console.log('🕐 Validando fecha/hora:');
    console.log('  - Ahora:', ahora.toLocaleString());
    console.log('  - Seleccionada:', fechaSeleccionada.toLocaleString());
    
    // 🔧 MEJORAR: Dar margen de 5 minutos para evitar problemas de sincronización
    const margenMinutos = 5 * 60 * 1000; // 5 minutos en milisegundos
    const tiempoLimite = new Date(ahora.getTime() + margenMinutos);
    
    if (fechaSeleccionada <= tiempoLimite) {
        console.log('❌ Fecha/hora muy próxima o en el pasado');
        return false;
    }
    
    console.log('✅ Fecha/hora válida');
    return true;
}



// 🔧 CORREGIR: Función para aplicar filtros
function aplicarFiltros() {
    console.log('🔍 Aplicando filtros...');
    
    // Obtener valores de filtros
    const filtros = {
        estado: $('#filtroEstado').val(),
        id_sucursal: $('#filtroSucursal').val(),
        tipo_cita: $('#filtroTipoCita').val(), // 🔧 ASEGURAR que se envía
        id_especialidad: $('#filtroEspecialidad').val(),
        id_doctor: $('#filtroDoctor').val()
    };
    
    console.log('Filtros a aplicar:', filtros);
    
    // Recargar calendario con filtros
    calendario.refetchEvents();
    cargarEstadisticas();
}

// Función para limpiar filtros
function limpiarFiltros() {
    console.log('🧹 Limpiando filtros...');
    
    // Resetear todos los selects
    $('#filtroSucursal').val('');
    $('#filtroTipoCita').val('');
    $('#filtroEspecialidad').val('');
    $('#filtroEstado').val('');
    $('#filtroDoctor').val('');
    
    // Recargar todos los doctores (sin filtro de especialidad)
    cargarTodosLosDoctores();
    
    // Aplicar los filtros (ahora vacíos)
    aplicarFiltros();
    
    console.log('✅ Filtros limpiados');
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
   $('#citasPresenciales').text(stats.citas_presenciales || 0);
   $('#citasVirtuales').text(stats.citas_virtuales || 0);
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

// ⭐ MEJORAR EL LIMPIAR WIZARD PARA RESETEAR CORRECTAMENTE
function limpiarWizard() {
    // Resetear wizard
    pasoActual = 1;
    datosCitaWizard = {};
    // Limpiar calendario de horarios
semanaActual = new Date();
doctorSeleccionado = null;
slotSeleccionado = null;
sucursalSeleccionada = null;
    
    // Limpiar formularios
    $('#formNuevaCita')[0].reset();
    $('#infoPaciente, #pacienteNoEncontrado').addClass('d-none');
    $('#infoSucursal, #infoEspecialidad, #infoDoctor').addClass('d-none');
    $('#idPacienteSeleccionado').val('');
    $('#especialidadCita, #doctorCita, #horaCita').prop('disabled', true);
    $('#editandoCita').remove();
    
    // Limpiar campos virtuales
    $('#camposVirtuales').removeClass('active');
    $('#plataformaVirtual, #salaVirtual, #enlaceVirtual').val('');
    
    // Limpiar checkboxes
    $('#confirmarDatos, #enviarNotificacion, #recordatorioVirtual').prop('checked', false);
    $('#enviarNotificacion').prop('checked', true); // Por defecto marcado
    
    // ⭐ LIMPIAR CAMPO DE CÉDULA
    $('#cedulaPaciente').val('');
    
    // Restaurar título del modal
    $('#modalNuevaCita .modal-title').html('<i class="bi bi-plus-circle me-1"></i>Registrar Nueva Cita Médica');
    
    mostrarCalendarioVacio('Seleccione un doctor para ver los horarios disponibles');

    // Volver al paso 1
    mostrarPaso(1);
    
    pacienteSeleccionado = null;
    console.log('🧹 Wizard limpiado completamente');
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

// ===== VALIDACIONES ADICIONALES =====
function validarFechaMinima() {
   const fechaSeleccionada = new Date($('#fechaCita').val());
   const fechaHoy = new Date();
   fechaHoy.setHours(0, 0, 0, 0);
   
   if (fechaSeleccionada < fechaHoy) {
       mostrarError('No se puede agendar citas en fechas pasadas');
       $('#fechaCita').val('');
       return false;
   }
   return true;
}

function validarHorarioLaboral() {
   const hora = $('#horaCita').val();
   if (!hora) return true;
   
   const [horas] = hora.split(':').map(Number);
   
   if (horas < 7 || horas > 19) {
       mostrarAdvertencia('El horario seleccionado está fuera del horario laboral (07:00 - 19:00)');
       return false;
   }
   return true;
}

// ===== FUNCIONES DE ACCESIBILIDAD =====
function configurarAccesibilidad() {
   // Agregar atributos ARIA para lectores de pantalla
   $('.step-item').attr('role', 'tab');
   $('.step-content').attr('role', 'tabpanel');
   
   // Configurar navegación por teclado
   $('.step-item').attr('tabindex', '0').keydown(function(e) {
       if (e.key === 'Enter' || e.key === ' ') {
           const paso = $(this).data('step');
           if (paso <= pasoActual) {
               mostrarPaso(paso);
           }
       }
   });
}

// ===== FUNCIONES DE BÚSQUEDA AVANZADA =====
function busquedaAvanzadaPacientes() {
   // Implementar búsqueda por múltiples criterios
   const criterios = {
       cedula: $('#cedulaPaciente').val(),
       nombres: $('#nombresBusqueda').val(),
       apellidos: $('#apellidosBusqueda').val(),
       telefono: $('#telefonoBusqueda').val()
   };
   
   // Filtrar criterios vacíos
   const filtros = Object.fromEntries(
       Object.entries(criterios).filter(([key, value]) => value && value.trim())
   );
   
   if (Object.keys(filtros).length === 0) {
       mostrarError('Ingrese al menos un criterio de búsqueda');
       return;
   }
   
   // Realizar búsqueda con múltiples criterios
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'buscarPacienteAvanzado',
           ...filtros,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success && response.data.length > 0) {
               mostrarResultadosBusqueda(response.data);
           } else {
               mostrarPacienteNoEncontrado('');
           }
       },
       error: function(xhr, status, error) {
           console.error('❌ Error en búsqueda avanzada:', error);
           mostrarError('Error en la búsqueda de pacientes');
       }
   });
}

function mostrarResultadosBusqueda(pacientes) {
   if (pacientes.length === 1) {
       mostrarPacienteEncontrado(pacientes[0]);
   } else {
       // Mostrar modal con lista de pacientes para seleccionar
       mostrarModalSeleccionPaciente(pacientes);
   }
}

// ===== FUNCIONES DE NOTIFICACIONES =====
function configurarNotificacionesEnTiempoReal() {
   // Verificar si el navegador soporta notificaciones
   if ('Notification' in window) {
       // Solicitar permiso para notificaciones
       if (Notification.permission === 'default') {
           Notification.requestPermission();
       }
   }
   
   // Configurar polling para actualizaciones
   setInterval(verificarActualizaciones, 30000); // Cada 30 segundos
}

function verificarActualizaciones() {
   if (document.visibilityState === 'visible') {
       // Solo verificar si la página está visible
       $.ajax({
           url: config.baseUrl,
           type: 'GET',
           data: {
               action: 'verificarActualizaciones',
               ultima_verificacion: localStorage.getItem('ultima_verificacion') || new Date().toISOString(),
               submenu_id: config.submenuId
           },
           dataType: 'json',
           success: function(response) {
               if (response.success && response.actualizaciones > 0) {
                   mostrarNotificacionActualizacion();
                   localStorage.setItem('ultima_verificacion', new Date().toISOString());
               }
           },
           error: function() {
               // Silencioso en caso de error
           }
       });
   }
}

function mostrarNotificacionActualizacion() {
   // Mostrar notificación del navegador si está permitido
   if (Notification.permission === 'granted') {
       new Notification('MediSys - Actualización disponible', {
           body: 'Hay nuevas citas o cambios en el calendario',
           icon: '/favicon.ico',
           tag: 'mediSys-update'
       });
   }
   
   // Mostrar notificación en la interfaz
   mostrarToastNotificacion('Hay actualizaciones disponibles en el calendario', 'info');
}

function mostrarToastNotificacion(mensaje, tipo = 'info') {
   const toast = $(`
       <div class="toast align-items-center text-white bg-${tipo} border-0" role="alert">
           <div class="d-flex">
               <div class="toast-body">${mensaje}</div>
               <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
           </div>
       </div>
   `);
   
   $('#toastContainer').append(toast);
   const bsToast = new bootstrap.Toast(toast[0]);
   bsToast.show();
   
   // Remover del DOM después de ocultarse
   toast.on('hidden.bs.toast', function() {
       $(this).remove();
   });
}

// ===== FUNCIONES DE EXPORTACIÓN =====
function exportarCalendario(formato = 'pdf') {
   const filtros = obtenerFiltrosActivos();
   const fechaInicio = calendario.view.activeStart.toISOString().split('T')[0];
   const fechaFin = calendario.view.activeEnd.toISOString().split('T')[0];
   
   window.open(`${config.baseUrl}?action=exportarCalendario&formato=${formato}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&${new URLSearchParams(filtros)}`);
}

// ===== FUNCIONES DE IMPRESIÓN =====
function imprimirAgenda() {
   const fechaSeleccionada = calendario.getDate();
   const fechaStr = fechaSeleccionada.toISOString().split('T')[0];
   
   window.open(`${config.baseUrl}?action=imprimirAgenda&fecha=${fechaStr}`, '_blank');
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
   
   // Arrows para navegar en el wizard cuando está abierto
   if ($('#modalNuevaCita').hasClass('show')) {
       if (e.which === 39 && pasoActual < totalPasos) { // Flecha derecha
           e.preventDefault();
           avanzarPaso();
       }
       if (e.which === 37 && pasoActual > 1) { // Flecha izquierda
           e.preventDefault();
           retrocederPaso();
       }
   }
});

// ===== AUTO-GUARDAR BORRADOR =====
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
   const borrador = {
       paso: pasoActual,
       datos: datosCitaWizard,
       timestamp: new Date().getTime()
   };
   
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
           
           // Verificar si el borrador no es muy antiguo (más de 24 horas)
           const tiempoLimite = 24 * 60 * 60 * 1000; // 24 horas en milisegundos
           if (new Date().getTime() - data.timestamp > tiempoLimite) {
               limpiarBorrador();
               return;
           }
           
           // Preguntar al usuario si desea cargar el borrador
           Swal.fire({
               title: 'Borrador encontrado',
               text: '¿Desea continuar con la cita que estaba registrando?',
               icon: 'question',
               showCancelButton: true,
               confirmButtonText: 'Sí, continuar',
               cancelButtonText: 'No, empezar nuevo'
           }).then((result) => {
               if (result.isConfirmed) {
                   // Cargar datos del borrador
                   if (data.datos) {
                       datosCitaWizard = data.datos;
                   }
                   
                   // Ir al paso guardado o al siguiente
                   mostrarPaso(data.paso || 1);
                   
                   // Llenar campos del formulario
                   Object.keys(data).forEach(key => {
                       if (key !== 'paso' && key !== 'datos' && key !== 'timestamp') {
                           $(`#formNuevaCita [name="${key}"]`).val(data[key]);
                       }
                   });
                   
                   mostrarInfo('Borrador cargado correctamente');
               } else {
                   limpiarBorrador();
               }
           });
           
       } catch (e) {
           console.warn('⚠️ Error cargando borrador:', e);
           limpiarBorrador();
       }
   }
}

function limpiarBorrador() {
   localStorage.removeItem('borrador_cita');
   console.log('🗑️ Borrador eliminado');
}

// ===== VALIDACIONES EN TIEMPO REAL =====
function configurarValidacionesEnTiempoReal() {
   // Validación de cédula ecuatoriana
   $('#cedulaPaciente, #cedulaNuevoPaciente').on('input', function() {
       const cedula = $(this).val();
       if (cedula.length === 10) {
           if (validarCedulaEcuatoriana(cedula)) {
               $(this).removeClass('is-invalid').addClass('is-valid');
           } else {
               $(this).removeClass('is-valid').addClass('is-invalid');
               $(this).next('.invalid-feedback').remove();
               $(this).after('<div class="invalid-feedback">Cédula ecuatoriana no válida</div>');
           }
       } else {
           $(this).removeClass('is-valid is-invalid');
       }
   });
   
   // Validación de fecha no pasada
   $('#fechaCita').on('change', validarFechaMinima);
   
   // Validación de horario laboral
   $('#horaCita').on('change', validarHorarioLaboral);
   
   // Validación de email
   $('#correoNuevoPaciente').on('blur', function() {
       const email = $(this).val();
       if (email && !validarEmail(email)) {
           $(this).removeClass('is-valid').addClass('is-invalid');
           $(this).next('.invalid-feedback').remove();
           $(this).after('<div class="invalid-feedback">Formato de email no válido</div>');
       } else if (email) {
           $(this).removeClass('is-invalid').addClass('is-valid');
       }
   });
}

function validarCedulaEcuatoriana(cedula) {
   if (cedula.length !== 10) return false;
   
   const digitos = cedula.split('').map(Number);
   const digitoVerificador = digitos.pop();
   
   let suma = 0;
   for (let i = 0; i < 9; i++) {
       let digito = digitos[i];
       if (i % 2 === 0) {
           digito *= 2;
           if (digito > 9) digito -= 9;
       }
       suma += digito;
   }
   
   const residuo = suma % 10;
   const digitoCalculado = residuo === 0 ? 0 : 10 - residuo;
   
   return digitoCalculado === digitoVerificador;
}

function validarEmail(email) {
   const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   return regex.test(email);
}

// ===== FUNCIONES DE PERFORMANCE =====
function optimizarRendimiento() {
   // Debounce para búsquedas
   const debounce = (func, wait) => {
       let timeout;
       return function executedFunction(...args) {
           const later = () => {
               clearTimeout(timeout);
               func(...args);
           };
           clearTimeout(timeout);
           timeout = setTimeout(later, wait);
       };
   };
   
   // Aplicar debounce a filtros
   const aplicarFiltrosDebounced = debounce(aplicarFiltros, 300);
   $('#filtroSucursal, #filtroTipoCita, #filtroEspecialidad, #filtroEstado, #filtroDoctor').off('change').on('change', aplicarFiltrosDebounced);
   
   // Lazy loading para datos grandes
   if (config.sucursales.length > 50) {
       configurarLazyLoadingSelects();
   }
}

// ===== EXPORTAR FUNCIONES PARA USO EXTERNO =====
window.GestionCitas = {
   calendario,
   mostrarExito,
   mostrarError,
   mostrarAdvertencia,
   cargarEstadisticas,
   aplicarFiltros,
   wizard: {
       mostrarPaso,
       avanzarPaso,
       retrocederPaso,
       limpiarWizard
   },
   pacientes: {
       buscarPaciente,
       mostrarPacienteEncontrado,
       mostrarPacienteNoEncontrado
   },
   utilidades: {
       formatearFecha,
       formatearHora,
       formatearFechaHora,
       validarCedulaEcuatoriana,
       validarEmail
   }
};

// ===== INICIALIZACIÓN FINAL =====
$(document).ready(function() {
   // Configurar funcionalidades avanzadas
   configurarAccesibilidad();
   configurarValidacionesEnTiempoReal();
   iniciarAutoguardado();
   optimizarRendimiento();
   
   // Configurar notificaciones en tiempo real si está habilitado
   if (config.notificaciones_tiempo_real) {
       configurarNotificacionesEnTiempoReal();
   }
   
   // Cuando se abre el modal
    $('#modalVerCita').on('show.bs.modal', function() {
        console.log('🔄 Abriendo modal de detalles...');
        
        // Mostrar loading inicial
        $('#loadingVerCita').show();
        $('.modal-body-content').hide();
        $('#detallesCita').hide();
        $('#seccionInfoVirtual').hide();
        
        // Reset del header
        $('#headerVerCita').removeClass('estado-pendiente estado-confirmada estado-completada estado-cancelada');
        $('#badgeEstadoVerCita').removeClass('estado-pendiente estado-confirmada estado-completada estado-cancelada');
        $('#textoEstadoCita').text('Cargando...');
    });
    
    // Cuando se cierra el modal
    $('#modalVerCita').on('hidden.bs.modal', function() {
        console.log('❌ Cerrando modal de detalles...');
        
        // Limpiar contenido
        $('#detallesCita').empty().hide();
        $('.modal-body-content').hide();
        $('#loadingVerCita').hide();
        $('#seccionInfoVirtual').hide();
        
        // Reset valores por defecto
        $('.detail-value').text('---');
        $('#nombrePacienteDetalle').text('Cargando...');
        $('#duracionCitaDetalle').text('30 minutos');
        $('#usuarioRegistroCita').text('Sistema');
        $('#consultorioCitaDetalle').text('Por asignar');
        
        // Reset icono tipo
        $('#iconTipoCita').removeClass('bi-camera-video bi-building').addClass('bi-laptop');
    });
    
   // Verificar si hay borrador guardado
   setTimeout(() => {
       cargarBorrador();
   }, 1000);
   
   console.log('🎉 Sistema de Gestión de Citas con Wizard cargado completamente');
});

// ===== DEBUG Y TESTING =====
if (config.debug) {
   window.debugGestionCitas = {
       config,
       datosCitaWizard,
       pacienteSeleccionado,
       citaSeleccionada,
       pasoActual,
       calendario,
       testearWizard: function() {
           console.log('🧪 Iniciando test del wizard...');
           $('#modalNuevaCita').modal('show');
           
           setTimeout(() => {
               $('.tipo-cita-card[data-tipo="1"]').click();
               console.log('✅ Paso 1 completado');
               
               setTimeout(() => {
                   $('#btnSiguientePaso').click();
                   console.log('✅ Avanzado al paso 2');
               }, 500);
           }, 1000);
       }
   };
   
   console.log('🔧 Funciones de debug disponibles en window.debugGestionCitas');
}

// ===== DEBUG TEMPORAL - AGREGAR AL FINAL DEL ARCHIVO =====
function debugCompleto() {
    console.log('🔍 === DEBUG COMPLETO ===');
    console.log('Variables globales:');
    console.log('  - pasoActual:', window.pasoActual);
    console.log('  - datosCitaWizard:', window.datosCitaWizard);
    console.log('  - slotSeleccionado:', window.slotSeleccionado);
    console.log('  - doctorSeleccionado:', window.doctorSeleccionado);
    
    console.log('Campos del DOM:');
    console.log('  - #doctorCita valor:', $('#doctorCita').val());
    console.log('  - #fechaCita existe:', $('#fechaCita').length, 'valor:', $('#fechaCita').val());
    console.log('  - #horaCita existe:', $('#horaCita').length, 'valor:', $('#horaCita').val());
    console.log('  - #tipoCitaSeleccionado:', $('#tipoCitaSeleccionado').val());
    console.log('  - #idPacienteSeleccionado:', $('#idPacienteSeleccionado').val());
    console.log('  - #sucursalCita:', $('#sucursalCita').val());
    console.log('  - #especialidadCita:', $('#especialidadCita').val());
    
    console.log('Verificar condiciones de validación:');
    const doctor = $('#doctorCita').val();
    const fecha = $('#fechaCita').val();
    const hora = $('#horaCita').val();
    const slot = window.slotSeleccionado;
    
    console.log('  - Doctor:', !!doctor);
    console.log('  - slotSeleccionado:', !!slot);
    console.log('  - fecha campo:', !!fecha);
    console.log('  - hora campo:', !!hora);
    console.log('  - Condición que falla:', (!slot || !fecha || !hora));
}

// ===== EVENTOS DEL MODAL CANCELAR CITA =====
$(document).ready(function() {
    // Contador de caracteres en tiempo real
    $('#motivoCancelacion').on('input', function() {
        const length = $(this).val().length;
        const contador = $('#contadorCaracteres');
        
        contador.text(`${length}/500 caracteres`);
        
        if (length < 10) {
            contador.removeClass('text-muted text-success').addClass('text-danger');
        } else {
            contador.removeClass('text-danger text-muted').addClass('text-success');
        }
        
        // Habilitar/deshabilitar botón según validación
        const btnConfirmar = $('#btnConfirmarCancelacion');
        if (length >= 10) {
            btnConfirmar.prop('disabled', false);
        } else {
            btnConfirmar.prop('disabled', true);
        }
    });
    
    // Evento para confirmar cancelación
    $('#btnConfirmarCancelacion').on('click', function() {
        procesarCancelacionCita();
    });
    
    // Auto-focus en el textarea cuando se abre el modal
    $('#modalCancelarCita').on('shown.bs.modal', function() {
        $('#motivoCancelacion').focus();
    });
    
    // Validar formulario cuando se envía
    $('#formCancelarCita').on('submit', function(e) {
        e.preventDefault();
        procesarCancelacionCita();
    });
});

function procesarCancelacionCita() {
    const motivo = $('#motivoCancelacion').val().trim();
    const enviarNotificacion = $('#enviarNotificacionCancelacion').is(':checked');
    const idCita = $('#idCitaCancelar').val();
    
    // Validaciones
    if (!motivo) {
        mostrarError('El motivo de cancelación es obligatorio');
        $('#motivoCancelacion').focus();
        return;
    }
    
    if (motivo.length < 10) {
        mostrarError('El motivo debe tener al menos 10 caracteres');
        $('#motivoCancelacion').focus();
        return;
    }
    
    if (!idCita) {
        mostrarError('Error: No se pudo identificar la cita a cancelar');
        return;
    }
    
    // Deshabilitar botón para evitar doble clic
    const $btn = $('#btnConfirmarCancelacion');
    const textoOriginal = $btn.html();
    $btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>Cancelando...');
    
    // Ejecutar cancelación
    ejecutarCancelacion(idCita, motivo, enviarNotificacion, function() {
        // Callback para restaurar botón
        $btn.prop('disabled', false).html(textoOriginal);
    });
}

// Hacer disponible globalmente
window.debugCompleto = debugCompleto;