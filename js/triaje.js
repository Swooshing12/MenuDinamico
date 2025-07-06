/**
 * Sistema de Gestión de Triaje - JavaScript
 * Autor: Sistema MediSys
 * Descripción: Manejo completo de triaje para enfermeros
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: window.triajeConfig?.debug || true,
    baseUrl: window.triajeConfig?.baseUrl || '../../controladores/EnfermeriaControlador/EnfermeriaController.php',
    permisos: window.triajeConfig?.permisos || {},
    submenuId: window.triajeConfig?.submenuId || null,
    idEnfermero: window.triajeConfig?.idEnfermero || null
};

// Variables globales
let citasDelDia = [];
let citaSeleccionada = null;
let modoEdicion = false;
let triajeActual = null;

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🏥 Iniciando Sistema de Triaje');
    
    if (config.debug) {
        console.log('🔧 Configuración cargada:', config);
    }

    inicializarEventos();
    inicializarFlatpickr();
    cargarCitasDelDia();
    
    console.log('✅ Sistema de triaje inicializado');
});

// ===== EVENTOS PRINCIPALES =====
function inicializarEventos() {
    console.log('🎯 Inicializando eventos...');
    
    // Eventos de fecha
    $('#fechaTriaje').on('change', cargarCitasDelDia);
    
    // Eventos de botones principales
    $('#btnRefrescar').on('click', cargarCitasDelDia);
    $('#btnEstadisticas').on('click', mostrarEstadisticas);
    
    // Eventos de filtros
    $('#filtroEstado').on('change', filtrarCitas);
    
    // Eventos del formulario de triaje
    $('#formTriaje').on('submit', guardarTriaje);
    $('#peso, #talla').on('input', calcularIMC);
    $('#temperatura, #frecuenciaCardiaca, #saturacionOxigeno').on('blur', validarSignosVitales);
    
    // Eventos de modales
    $('#modalTriaje').on('hidden.bs.modal', limpiarFormularioTriaje);
    $('#btnEditarTriaje').on('click', editarTriaje);
    
    console.log('✅ Eventos inicializados');
}

function inicializarFlatpickr() {
    flatpickr("#fechaTriaje", {
        locale: "es",
        dateFormat: "Y-m-d",
        defaultDate: "today",
        maxDate: new Date().fp_incr(7) // Máximo 7 días en el futuro
    });
}

// ===== BUSCADOR POR CÉDULA CORREGIDO =====
function buscarPorCedula(cedula) {
    console.log('🔍 Buscando por cédula:', cedula);
    
    // Limpiar espacios y validar
    cedula = cedula.trim();
    
    if (!cedula) {
        mostrarTodasLasCitas();
        ocultarResultadosBusqueda();
        return;
    }
    
    if (cedula.length < 3) {
        mostrarAlerta('Ingrese al menos 3 dígitos para buscar', 'warning');
        return;
    }
    
    // Validar que solo sean números
    if (!/^\d+$/.test(cedula)) {
        mostrarAlerta('La cédula debe contener solo números', 'warning');
        return;
    }
    
    // ✅ CORRECCIÓN: Convertir cedula_paciente a string antes de usar includes
    const citasEncontradas = citasDelDia.filter(cita => {
        if (!cita.cedula_paciente) return false;
        
        // Convertir a string para poder usar includes
        const cedulaString = String(cita.cedula_paciente);
        return cedulaString.includes(cedula);
    });
    
    console.log('🔍 Citas encontradas:', citasEncontradas.length);
    
    if (citasEncontradas.length > 0) {
        mostrarCitas(citasEncontradas);
        mostrarResultadosBusqueda(citasEncontradas.length, cedula);
    } else {
        mostrarCitasVacias();
        mostrarResultadosBusqueda(0, cedula);
    }
}

// ===== FUNCIÓN DE FILTROS CORREGIDA =====
function filtrarCitas() {
    const estadoSeleccionado = $('#filtroEstado').val();
    const cedulaBusqueda = $('#buscarCedula').val();
    
    let citasFiltradas = citasDelDia;
    
    // Primero filtrar por cédula si existe búsqueda
    if (cedulaBusqueda && cedulaBusqueda.length >= 3) {
        citasFiltradas = citasFiltradas.filter(cita => {
            if (!cita.cedula_paciente) return false;
            
            // ✅ CORRECCIÓN: Convertir a string
            const cedulaString = String(cita.cedula_paciente);
            return cedulaString.includes(cedulaBusqueda);
        });
    }
    
    // Luego filtrar por estado
    if (estadoSeleccionado) {
        if (estadoSeleccionado === 'Pendiente') {
            citasFiltradas = citasFiltradas.filter(c => !c.tiene_triaje);
        } else if (estadoSeleccionado === 'Completado') {
            citasFiltradas = citasFiltradas.filter(c => c.tiene_triaje && c.estado_triaje === 'Completado');
        } else if (estadoSeleccionado === 'Urgente') {
            citasFiltradas = citasFiltradas.filter(c => c.tiene_triaje && c.estado_triaje === 'Urgente');
        } else if (estadoSeleccionado === 'Critico') {
            citasFiltradas = citasFiltradas.filter(c => c.tiene_triaje && c.estado_triaje === 'Critico');
        }
    }
    
    mostrarCitas(citasFiltradas);
    
    // Mostrar resultados si hay búsqueda activa
    if (cedulaBusqueda && cedulaBusqueda.length >= 3) {
        mostrarResultadosBusqueda(citasFiltradas.length, cedulaBusqueda);
    }
}

// ===== FUNCIÓN AUXILIAR PARA BÚSQUEDA MEJORADA =====
function buscarEnCitas(termino) {
    if (!termino || termino.length < 3) {
        return citasDelDia;
    }
    
    const terminoLower = termino.toLowerCase();
    
    return citasDelDia.filter(cita => {
        // Búsqueda por cédula (convertir a string)
        if (cita.cedula_paciente) {
            const cedulaString = String(cita.cedula_paciente);
            if (cedulaString.includes(termino)) {
                return true;
            }
        }
        
        // Búsqueda por nombre
        if (cita.nombres_paciente && cita.nombres_paciente.toLowerCase().includes(terminoLower)) {
            return true;
        }
        
        // Búsqueda por apellido
        if (cita.apellidos_paciente && cita.apellidos_paciente.toLowerCase().includes(terminoLower)) {
            return true;
        }
        
        // Búsqueda por motivo
        if (cita.motivo && cita.motivo.toLowerCase().includes(terminoLower)) {
            return true;
        }
        
        return false;
    });
}

// ===== EVENTO DE BÚSQUEDA MEJORADO =====
$(document).ready(function() {
    // Búsqueda en tiempo real con debounce
    let timeoutBusqueda;
    $('#buscarCedula').on('input', function() {
        const cedula = $(this).val();
        
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            if (cedula.length >= 3) {
                buscarPorCedula(cedula);
            } else if (cedula.length === 0) {
                limpiarBusqueda();
            }
        }, 500); // 500ms de delay
    });
    
    // Botón de búsqueda manual
    $('#btnBuscarCedula').on('click', function() {
        const cedula = $('#buscarCedula').val();
        buscarPorCedula(cedula);
    });
    
    // Botón limpiar
    $('#btnLimpiarBusqueda').on('click', limpiarBusqueda);
    
    // Búsqueda con Enter
    $('#buscarCedula').on('keypress', function(e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            buscarPorCedula($(this).val());
        }
    });
    
    // Solo permitir números en el campo cédula
    $('#buscarCedula').on('keypress', function(e) {
        // Permitir: backspace, delete, tab, escape, enter, y números
        if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
            // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        // Asegurar que sea solo números (0-9)
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    
    // ✅ CORREGIR COMBINAR BÚSQUEDA CON FILTROS
    $('#filtroEstado').on('change', function() {
        // Siempre aplicar filtros
        filtrarCitas();
    });
});

// ===== CARGAR DATOS =====
function cargarCitasDelDia() {
    const fecha = $('#fechaTriaje').val();
    
    if (config.debug) {
        console.log('📅 Cargando citas para fecha:', fecha);
    }
    
    // Mostrar loading
    mostrarLoading(true);
    
    $.ajax({
        url: config.baseUrl,
        method: 'GET',
        data: {
            action: 'obtenerCitasPendientes',
            fecha: fecha
        },
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('📋 Respuesta citas:', response);
            }
            
            if (response.success) {
                citasDelDia = response.data;
                mostrarCitas(citasDelDia);
                actualizarEstadisticasRapidas(citasDelDia);
            } else {
                console.error('❌ Error:', response.message);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al cargar las citas'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX:', error);
            manejarErrorAjax(xhr, status, error);
        },
        complete: function() {
            mostrarLoading(false);
        }
    });
}

// ===== FUNCIONES AUXILIARES PARA BÚSQUEDA =====

function mostrarTodasLasCitas() {
    mostrarCitas(citasDelDia);
    ocultarResultadosBusqueda();
    $('#contadorResultados').text(citasDelDia.length);
}

function mostrarResultadosBusqueda(cantidad, cedula) {
    const contenedor = $('#resultadosBusqueda');
    
    if (cantidad > 0) {
        const html = `
            <div class="alert alert-success alert-sm">
                <i class="bi bi-check-circle me-2"></i>
                <strong>✅ ${cantidad} paciente(s) encontrado(s)</strong> con cédula que contiene "${cedula}"
                <button type="button" class="btn-close btn-sm ms-2" onclick="limpiarBusqueda()"></button>
            </div>
        `;
        contenedor.html(html).show();
    } else {
        const html = `
            <div class="alert alert-warning alert-sm">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>❌ No se encontraron pacientes</strong> con cédula que contiene "${cedula}"
                <button type="button" class="btn-close btn-sm ms-2" onclick="limpiarBusqueda()"></button>
            </div>
        `;
        contenedor.html(html).show();
    }
    
    // Actualizar contador
    $('#contadorResultados').text(cantidad);
}

function ocultarResultadosBusqueda() {
    $('#resultadosBusqueda').hide().empty();
}

function limpiarBusqueda() {
    $('#buscarCedula').val('');
    $('#filtroEstado').val('');
    mostrarTodasLasCitas();
    console.log('🧹 Búsqueda limpiada');
}

function mostrarCitasVacias() {
    $('#sinCitas').removeClass('d-none');
    $('#tablaCitas').addClass('d-none');
    $('#sinResultados').removeClass('d-none');
    
    $('#sinResultados').html(`
        <div class="text-center py-5">
            <i class="bi bi-search fs-1 text-muted mb-3"></i>
            <h5 class="text-muted">No se encontraron resultados</h5>
            <p class="text-muted">No hay pacientes con esa cédula para la fecha seleccionada</p>
            <button class="btn btn-outline-primary" onclick="limpiarBusqueda()">
                <i class="bi bi-arrow-left me-1"></i>
                Ver todas las citas
            </button>
        </div>
    `);
}

function mostrarAlerta(mensaje, tipo = 'info') {
    const iconos = {
        'success': 'bi-check-circle',
        'warning': 'bi-exclamation-triangle',
        'error': 'bi-x-circle',
        'info': 'bi-info-circle'
    };
    
    const html = `
        <div class="alert alert-${tipo} alert-dismissible fade show alert-sm">
            <i class="${iconos[tipo]} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#resultadosBusqueda').html(html).show();
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        $('#resultadosBusqueda .alert').fadeOut();
    }, 3000);
}

// ===== FUNCIÓN MOSTRAR CITAS MEJORADA =====
function mostrarCitas(citas) {
    const tbody = $('#cuerpoTablaCitas');
    
    if (!citas || citas.length === 0) {
        $('#tablaCitas').addClass('d-none');
        $('#sinCitas').removeClass('d-none');
        $('#contadorResultados').text('0');
        return;
    }
    
    // Mostrar tabla y ocultar mensaje vacío
    $('#tablaCitas').removeClass('d-none');
    $('#sinCitas').addClass('d-none');
    $('#sinResultados').addClass('d-none');
    
    let html = '';
    
    citas.forEach(cita => {
        html += crearFilaCita(cita);
    });
    
    tbody.html(html);
    $('#contadorResultados').text(citas.length);
    
    if (config.debug) {
        console.log('✅ Mostrando', citas.length, 'citas en la tabla');
    }
}

function crearFilaCita(cita) {
    const hora = new Date(cita.fecha_hora).toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    const nombreCompleto = `${cita.nombres_paciente} ${cita.apellidos_paciente}`;
    const doctorCompleto = `Dr. ${cita.nombres_doctor} ${cita.apellidos_doctor}`;
    
    // Estado del triaje
    let estadoTriaje, badgeClass, urgenciaInfo;
    
    if (cita.tiene_triaje) {
        // Usar estado_triaje de la base de datos
        const estado = cita.estado_triaje_display || cita.estado_triaje || 'Completado';
        estadoTriaje = `<span class="badge badge-completado">✅ ${estado}</span>`;
        badgeClass = 'estado-completado';
        urgenciaInfo = obtenerInfoUrgencia(cita.nivel_urgencia || 1);
    } else {
        estadoTriaje = '<span class="badge badge-pendiente">⏳ Pendiente</span>';
        badgeClass = 'estado-pendiente';
        urgenciaInfo = '<span class="text-muted">-</span>';
    }
    
    // Botones de acción
    let botones;
    if (cita.tiene_triaje) {
        botones = `
            <button class="btn btn-sm btn-info me-1" onclick="verTriaje(${cita.id_cita})" title="Ver triaje">
                <i class="bi bi-eye"></i>
            </button>
            <button class="btn btn-sm btn-warning" onclick="editarTriageModal(${cita.id_cita})" title="Editar triaje">
                <i class="bi bi-pencil"></i>
            </button>
        `;
    } else {
        botones = `
            <button class="btn btn-sm btn-primary btn-triaje" onclick="realizarTriaje(${cita.id_cita})" title="Realizar triaje">
                <i class="bi bi-clipboard2-pulse"></i> Triaje
            </button>
        `;
    }
    
    return `
        <tr class="${badgeClass}" data-cita-id="${cita.id_cita}">
            <td><strong>${hora}</strong></td>
            <td>${nombreCompleto}</td>
            <td><code>${cita.cedula_paciente}</code></td>
            <td>${doctorCompleto}</td>
            <td><span class="badge bg-secondary">${cita.nombre_especialidad}</span></td>
            <td>${estadoTriaje}</td>
            <td>${urgenciaInfo}</td>
            <td>${botones}</td>
        </tr>
    `;
}

function obtenerInfoUrgencia(nivel) {
    const niveles = {
        1: '<span class="urgencia-1">🟢 Bajo</span>',
        2: '<span class="urgencia-2">🟡 Medio</span>',
        3: '<span class="urgencia-3">🟠 Alto</span>',
        4: '<span class="urgencia-4">🔴 Crítico</span>'
    };
    
    return niveles[nivel] || '<span class="text-muted">-</span>';
}

// ===== FUNCIÓN PARA ACTUALIZAR CONTADORES =====
function actualizarContadoresRapidos(citas) {
    const total = citas.length;
    const pendientes = citas.filter(c => !c.tiene_triaje).length;
    const completados = citas.filter(c => c.tiene_triaje).length;
    const urgentes = citas.filter(c => c.tiene_triaje && (c.nivel_urgencia >= 3)).length;
    
    $('#totalCitas').text(total);
    $('#citasPendientes').text(pendientes);
    $('#triageCompletados').text(completados);
    $('#urgentes').text(urgentes);
    
    if (config.debug) {
        console.log(`📊 Contadores - Total: ${total}, Pendientes: ${pendientes}, Completados: ${completados}, Urgentes: ${urgentes}`);
    }
}

// ===== AGREGAR CSS PARA LOS ESTADOS =====
const estilosAdicionales = `
<style>
.badge-completado { background-color: #28a745 !important; color: white; }
.badge-pendiente { background-color: #ffc107 !important; color: black; }
.estado-completado { background-color: rgba(40, 167, 69, 0.1); }
.estado-pendiente { background-color: rgba(255, 193, 7, 0.1); }
.urgencia-1 { color: #28a745; font-weight: bold; }
.urgencia-2 { color: #ffc107; font-weight: bold; }
.urgencia-3 { color: #fd7e14; font-weight: bold; }
.urgencia-4 { color: #dc3545; font-weight: bold; }
.alert-sm { padding: 0.5rem 0.75rem; font-size: 0.875rem; }
</style>
`;

// Agregar estilos al head
$(document).ready(function() {
    $('head').append(estilosAdicionales);
});
function obtenerInfoUrgencia(nivel) {
    const niveles = {
        1: '<span class="urgencia-1">🟢 Bajo</span>',
        2: '<span class="urgencia-2">🟡 Medio</span>',
        3: '<span class="urgencia-3">🟠 Alto</span>',
        4: '<span class="urgencia-4">🔴 Crítico</span>'
    };
    
    return niveles[nivel] || '<span class="text-muted">-</span>';
}

// ===== REALIZAR TRIAJE =====
function realizarTriaje(idCita) {
    citaSeleccionada = citasDelDia.find(c => c.id_cita == idCita);
    modoEdicion = false;
    
    if (!citaSeleccionada) {
        console.error('❌ Cita no encontrada:', idCita);
        return;
    }
    
    if (config.debug) {
        console.log('🏥 Realizando triaje para cita:', citaSeleccionada);
    }
    
    // Llenar información del paciente
    $('#nombrePacienteTriaje').text(`${citaSeleccionada.nombres_paciente} ${citaSeleccionada.apellidos_paciente}`);
    $('#cedulaPacienteTriaje').text(citaSeleccionada.cedula_paciente);
    $('#doctorTriaje').text(`Dr. ${citaSeleccionada.nombres_doctor} ${citaSeleccionada.apellidos_doctor}`);
    $('#especialidadTriaje').text(citaSeleccionada.nombre_especialidad);
    $('#idCitaTriaje').val(citaSeleccionada.id_cita);
    
    // Limpiar formulario
    limpiarFormularioTriaje();
    
    // Cambiar título del modal
    $('#modalTriaje .modal-title').html('<i class="bi bi-clipboard2-pulse me-2"></i>Realizar Triaje');
    
    // Mostrar modal
    $('#modalTriaje').modal('show');
}

function guardarTriaje(e) {
    e.preventDefault();
    
    if (!validarFormularioTriaje()) {
        return;
    }
    
    const formData = new FormData($('#formTriaje')[0]);
    formData.append('action', modoEdicion ? 'actualizarTriaje' : 'crearTriaje');
    
    if (modoEdicion && triajeActual) {
        formData.append('id_triaje', triajeActual.id_triage);
    }
    
    if (config.debug) {
        console.log('💾 Guardando triaje:', Object.fromEntries(formData));
    }
    
    // Mostrar loading en el botón
    const btnSubmit = $('#formTriaje button[type="submit"]');
    const textoOriginal = btnSubmit.html();
    btnSubmit.html('<i class="bi bi-hourglass-split"></i> Guardando...').prop('disabled', true);
    
    $.ajax({
        url: config.baseUrl,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('💾 Respuesta guardar:', response);
            }
            
            if (response.success) {
                // Mostrar éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Triaje guardado!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Mostrar alertas si las hay
                if (response.alertas && response.alertas.length > 0) {
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Signos vitales anómalos',
                            html: response.alertas.map(alerta => `• ${alerta}`).join('<br>'),
                            confirmButtonText: 'Entendido'
                        });
                    }, 2500);
                }
                
                // Cerrar modal y recargar
                $('#modalTriaje').modal('hide');
                cargarCitasDelDia();
                
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al guardar el triaje'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error guardando triaje:', error);
            manejarErrorAjax(xhr, status, error);
        },
        complete: function() {
            // Restaurar botón
            btnSubmit.html(textoOriginal).prop('disabled', false);
        }
    });
}

// ===== VER TRIAJE =====
function verTriaje(idCita) {
    if (config.debug) {
        console.log('👁️ Viendo triaje para cita:', idCita);
    }
    
    $.ajax({
        url: config.baseUrl,
        method: 'GET',
        data: {
            action: 'obtenerTriajePorCita',
            id_cita: idCita
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarTriajeCompleto(response.data);
                triajeActual = response.data;
                citaSeleccionada = citasDelDia.find(c => c.id_cita == idCita);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'No se pudo cargar el triaje'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando triaje:', error);
            manejarErrorAjax(xhr, status, error);
        }
    });
}

function mostrarTriajeCompleto(triaje) {
    const contenido = `
        <div class="row">
            <div class="col-12 mb-3">
                <div class="alert alert-info">
                    <h6><i class="bi bi-person-fill me-2"></i>Paciente: ${triaje.nombres_paciente} ${triaje.apellidos_paciente}</h6>
                    <p class="mb-0"><strong>Realizado por:</strong> ${triaje.nombres} ${triaje.apellidos_enfermero}</p>
                    <p class="mb-0"><strong>Fecha:</strong> ${new Date(triaje.fecha_hora).toLocaleString('es-ES')}</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="bi bi-heart-pulse me-2"></i>Signos Vitales</h6>
                <table class="table table-sm">
                    <tr><td><strong>Temperatura:</strong></td><td>${triaje.temperatura || '-'} °C</td></tr>
                    <tr><td><strong>Presión Arterial:</strong></td><td>${triaje.presion_arterial || '-'}</td></tr>
                    <tr><td><strong>Freq. Cardíaca:</strong></td><td>${triaje.frecuencia_cardiaca || '-'} lpm</td></tr>
                    <tr><td><strong>Freq. Respiratoria:</strong></td><td>${triaje.frecuencia_respiratoria || '-'} rpm</td></tr>
                    <tr><td><strong>Sat. Oxígeno:</strong></td><td>${triaje.saturacion_oxigeno || '-'} %</td></tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6 class="text-success"><i class="bi bi-speedometer2 me-2"></i>Medidas Corporales</h6>
                <table class="table table-sm">
                    <tr><td><strong>Peso:</strong></td><td>${triaje.peso || '-'} kg</td></tr>
                    <tr><td><strong>Talla:</strong></td><td>${triaje.talla || '-'} cm</td></tr>
                    <tr><td><strong>IMC:</strong></td><td>${triaje.imc || '-'} ${triaje.categoria_imc ? `(${triaje.categoria_imc})` : ''}</td></tr>
                </table>
                
                <h6 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Evaluación</h6>
                <p><strong>Nivel de Urgencia:</strong> ${obtenerInfoUrgencia(triaje.nivel_urgencia)}</p>
            </div>
        </div>
        
        ${triaje.observaciones ? `
        <div class="row">
            <div class="col-12">
                <h6 class="text-secondary"><i class="bi bi-chat-left-text me-2"></i>Observaciones</h6>
                <div class="alert alert-light">${triaje.observaciones}</div>
            </div>
        </div>
        ` : ''}
    `;
    
    $('#contenidoTriaje').html(contenido);
    $('#modalVerTriaje').modal('show');
}

// ===== EDITAR TRIAJE =====
function editarTriaje() {
    if (!triajeActual || !citaSeleccionada) {
        console.error('❌ No hay triaje para editar');
        return;
    }
    
    modoEdicion = true;
    
    // Cerrar modal de ver
    $('#modalVerTriaje').modal('hide');
    
    // Esperar a que se cierre completamente
    setTimeout(() => {
        // Llenar formulario con datos actuales
        $('#nombrePacienteTriaje').text(`${citaSeleccionada.nombres_paciente} ${citaSeleccionada.apellidos_paciente}`);
        $('#cedulaPacienteTriaje').text(citaSeleccionada.cedula_paciente);
        $('#doctorTriaje').text(`Dr. ${citaSeleccionada.nombres_doctor} ${citaSeleccionada.apellidos_doctor}`);
        $('#especialidadTriaje').text(citaSeleccionada.nombre_especialidad);
        $('#idCitaTriaje').val(citaSeleccionada.id_cita);
        
        // Llenar datos del triaje
        $('#temperatura').val(triajeActual.temperatura || '');
        $('#presionArterial').val(triajeActual.presion_arterial || '');
        $('#frecuenciaCardiaca').val(triajeActual.frecuencia_cardiaca || '');
        $('#frecuenciaRespiratoria').val(triajeActual.frecuencia_respiratoria || '');
        $('#saturacionOxigeno').val(triajeActual.saturacion_oxigeno || '');
        $('#peso').val(triajeActual.peso || '');
        $('#talla').val(triajeActual.talla || '');
        $('#nivelUrgencia').val(triajeActual.nivel_urgencia || '');
        $('#observaciones').val(triajeActual.observaciones || '');
        
        // Calcular IMC si hay datos
        if (triajeActual.peso && triajeActual.talla) {
            calcularIMC();
        }
        
        // Cambiar título del modal
        $('#modalTriaje .modal-title').html('<i class="bi bi-pencil me-2"></i>Editar Triaje');
        
        // Mostrar modal
        $('#modalTriaje').modal('show');
    }, 500);
}

function editarTriageModal(idCita) {
    verTriaje(idCita);
}

// ===== CÁLCULOS Y VALIDACIONES =====
function calcularIMC() {
    const peso = parseFloat($('#peso').val());
    const talla = parseInt($('#talla').val());
    
    if (peso && talla && peso > 0 && talla > 0) {
        const alturaMetros = talla / 100;
        const imc = peso / (alturaMetros * alturaMetros);
        const imcRedondeado = Math.round(imc * 100) / 100;
        
        $('#imc').val(imcRedondeado);
        
        // Determinar categoría
        let categoria, claseCSS;
        if (imc < 18.5) {
            categoria = 'Bajo peso';
            claseCSS = 'imc-bajo-peso';
        } else if (imc < 25) {
            categoria = 'Normal';
            claseCSS = 'imc-normal';
        } else if (imc < 30) {
            categoria = 'Sobrepeso';
            claseCSS = 'imc-sobrepeso';
        } else {
            categoria = 'Obesidad';
            claseCSS = 'imc-obesidad';
        }
        
        $('#categoriaIMC').text(categoria).removeClass().addClass(`input-group-text ${claseCSS}`);
    } else {
        $('#imc').val('');
        $('#categoriaIMC').text('-').removeClass().addClass('input-group-text');
    }
}

function validarSignosVitales() {
    const signos = {
        temperatura: $('#temperatura').val(),
        frecuencia_cardiaca: $('#frecuenciaCardiaca').val(),
        saturacion_oxigeno: $('#saturacionOxigeno').val()
    };
    
    if (!signos.temperatura && !signos.frecuencia_cardiaca && !signos.saturacion_oxigeno) {
        $('#alertasSignosVitales').empty();
        return;
    }
    
    $.ajax({
        url: config.baseUrl,
        method: 'POST',
        data: {
            action: 'validarSignosVitales',
            ...signos
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.tiene_alertas) {
                mostrarAlertasSignosVitales(response.alertas);
            } else {
                $('#alertasSignosVitales').empty();
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error validando signos:', error);
        }
    });
}

function mostrarAlertasSignosVitales(alertas) {
    if (!alertas || alertas.length === 0) {
        $('#alertasSignosVitales').empty();
        return;
    }
    
    const alertasHTML = alertas.map(alerta => `
        <div class="alert alert-warning alerta-signos alerta-advertencia" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Atención:</strong> ${alerta}
        </div>
    `).join('');
    
    $('#alertasSignosVitales').html(alertasHTML);
}

function validarFormularioTriaje() {
    const nivelUrgencia = $('#nivelUrgencia').val();
    
    if (!nivelUrgencia) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Debe seleccionar un nivel de urgencia'
        });
        $('#nivelUrgencia').focus();
        return false;
    }
    
    return true;
}

// ===== ESTADÍSTICAS =====
function actualizarEstadisticasRapidas(citas) {
    const total = citas.length;
    const pendientes = citas.filter(c => !c.tiene_triaje).length;
    const completados = citas.filter(c => c.tiene_triaje).length;
    const urgentes = citas.filter(c => c.tiene_triaje && (c.nivel_urgencia >= 3)).length;
    
    $('#totalCitas').text(total);
    $('#citasPendientes').text(pendientes);
    $('#triageCompletados').text(completados);
    $('#urgentes').text(urgentes);
}

function mostrarEstadisticas() {
    $.ajax({
        url: config.baseUrl,
        method: 'GET',
        data: {
            action: 'obtenerEstadisticas'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarModalEstadisticas(response.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar las estadísticas'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando estadísticas:', error);
            manejarErrorAjax(xhr, status, error);
        }
    });
}

function mostrarModalEstadisticas(stats) {
    const contenido = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">📊 Resumen General (últimos 30 días)</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td><strong>Total Triajes:</strong></td><td>${stats.total_triajes || 0}</td></tr>
                            <tr><td><strong>Temperatura Promedio:</strong></td><td>${stats.temperatura_promedio ? parseFloat(stats.temperatura_promedio).toFixed(1) + '°C' : '-'}</td></tr>
                            <tr><td><strong>IMC Promedio:</strong></td><td>${stats.imc_promedio ? parseFloat(stats.imc_promedio).toFixed(1) : '-'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">🚨 Distribución por Urgencia</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td>🟢 Baja:</td><td>${stats.urgencia_baja || 0}</td></tr>
                            <tr><td>🟡 Media:</td><td>${stats.urgencia_media || 0}</td></tr>
                            <tr><td>🟠 Alta:</td><td>${stats.urgencia_alta || 0}</td></tr>
                            <tr><td>🔴 Crítica:</td><td>${stats.urgencia_critica || 0}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#contenidoEstadisticas').html(contenido);
    $('#modalEstadisticas').modal('show');
}

// ===== FILTROS =====
function filtrarCitas() {
    const estadoSeleccionado = $('#filtroEstado').val();
    
    let citasFiltradas = citasDelDia;
    
    if (estadoSeleccionado) {
        if (estadoSeleccionado === 'Pendiente') {
            citasFiltradas = citasDelDia.filter(c => !c.tiene_triaje);
        } else if (estadoSeleccionado === 'Triaje Completado') {
            citasFiltradas = citasDelDia.filter(c => c.tiene_triaje);
        } else if (estadoSeleccionado === 'Triaje Urgente') {
            citasFiltradas = citasDelDia.filter(c => c.tiene_triaje && (c.nivel_urgencia >= 3));
        }
    }
    
    mostrarCitas(citasFiltradas);
}

// ===== UTILIDADES =====
function limpiarFormularioTriaje() {
    $('#formTriaje')[0].reset();
    $('#imc').val('');
    $('#categoriaIMC').text('-').removeClass().addClass('input-group-text');
    $('#alertasSignosVitales').empty();
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        $('#loadingCitas').removeClass('d-none');
        $('#tablaCitas, #sinCitas').addClass('d-none');
    } else {
        $('#loadingCitas').addClass('d-none');
    }
}

function manejarErrorAjax(xhr, status, error) {
    console.error('❌ Error AJAX:', { xhr, status, error });
    
    let mensaje = 'Error de conexión. Por favor, intenta nuevamente.';
    
    if (xhr.status === 403) {
        mensaje = 'No tienes permisos para realizar esta acción.';
    } else if (xhr.status === 404) {
        mensaje = 'Recurso no encontrado.';
    } else if (xhr.status === 500) {
        mensaje = 'Error interno del servidor.';
    }
    
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        footer: config.debug ? `<small>Detalles técnicos: ${error}</small>` : ''
    });
}

// ===== FUNCIONES GLOBALES (para botones en HTML) =====
window.realizarTriaje = realizarTriaje;
window.verTriaje = verTriaje;
window.editarTriageModal = editarTriageModal;