/**
 * Sistema de Gestión de Horarios para Doctores
 * VERSIÓN CORREGIDA para trabajar con UNA sola sucursal por doctor
 */

// Variables globales para horarios
let horariosDoctor = {}; // Estructura: {id_sucursal: [horarios]}
let horarioEditando = null;

// ===== VARIABLES PARA EDICIÓN =====
let modoEdicion = false;
let doctorEditandoId = null;

// Mapeo de días de la semana
const DIAS_SEMANA = {
    1: 'Lunes',
    2: 'Martes', 
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo'
};

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    inicializarEventosHorarios();
    inicializarEventosEdicion();
    console.log('✅ Sistema de horarios inicializado (versión sucursal única)');
});

function inicializarEventosHorarios() {
    // Event listeners principales
    $('#btnAgregarHorario').on('click', abrirModalAgregarHorario);
    $('#btnGuardarHorario').on('click', guardarHorario);
    
    // Validación de horas
    $('#horaInicio, #horaFin').on('change', validarHoras);
    
    // Resetear al abrir modal principal
    $('#crearDoctorModal').on('show.bs.modal', function() {
        limpiarTodosLosHorarios();
    });
}

// ===== FUNCIONES PARA EDICIÓN =====
function inicializarEventosEdicion() {
    // Eventos específicos del modal de edición
    $('#btnAgregarHorarioEditar').on('click', abrirModalAgregarHorarioEditar);
    $('#btnRecargarHorarios').on('click', recargarHorariosDelServidor);
    
    // Evento al abrir modal de edición
    $('#editarDoctorModal').on('show.bs.modal', function() {
        modoEdicion = true;
        console.log('🔄 Modo edición activado');
    });
    
    $('#editarDoctorModal').on('hidden.bs.modal', function() {
        modoEdicion = false;
        doctorEditandoId = null;
        limpiarTodosLosHorarios();
        console.log('🔄 Modo edición desactivado');
    });
}

// ===== AGREGAR HORARIO (CREAR) =====
function abrirModalAgregarHorario() {
    // ✅ NUEVA LÓGICA: Obtener sucursal del select único
    const idSucursal = $('#id_sucursal').val();
    
    if (!idSucursal) {
        Swal.fire({
            icon: 'warning',
            title: 'Sucursal requerida',
            text: 'Primero debe seleccionar una sucursal'
        });
        return;
    }
    
    console.log('➕ Agregando horario para sucursal:', idSucursal);
    
    // Limpiar formulario del modal
    document.getElementById('formHorario').reset();
    horarioEditando = null;
    
    // Almacenar la sucursal actual
    $('#modalHorario').data('sucursal-actual', idSucursal);
    $('#modalHorario').data('modo-edicion', false);
    
    // Cambiar título del modal
    $('#tituloModalHorario').text('Agregar Horario de Atención');
    
    // Mostrar modal
    $('#modalHorario').modal('show');
}

// ===== AGREGAR HORARIO (EDITAR) =====
function abrirModalAgregarHorarioEditar() {
    // ✅ NUEVA LÓGICA: Obtener sucursal del select de edición
    const idSucursal = $('#editarIdSucursal').val(); // Este ID debe coincidir con tu HTML de edición
    
    if (!idSucursal) {
        Swal.fire({
            icon: 'warning',
            title: 'Sucursal requerida',
            text: 'Primero debe seleccionar una sucursal'
        });
        return;
    }
    
    console.log('➕ Agregando horario para edición, sucursal:', idSucursal);
    
    // Limpiar formulario del modal
    document.getElementById('formHorario').reset();
    horarioEditando = null;
    
    // Almacenar la sucursal actual y el modo
    $('#modalHorario').data('sucursal-actual', idSucursal);
    $('#modalHorario').data('modo-edicion', true);
    
    // Cambiar título del modal
    $('#tituloModalHorario').text('Agregar Horario de Atención (Edición)');
    
    // Mostrar modal
    $('#modalHorario').modal('show');
}

// ===== GUARDAR HORARIO =====
function guardarHorario() {
    const form = document.getElementById('formHorario');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const diaSemana = $('#diaSemana').val();
    const horaInicio = $('#horaInicio').val();
    const horaFin = $('#horaFin').val();
    const duracionCita = $('#duracionCita').val() || 30;
    
    // ✅ OBTENER SUCURSAL DEL MODAL
    const idSucursal = $('#modalHorario').data('sucursal-actual');
    const modoEdicionActual = $('#modalHorario').data('modo-edicion') || false;
    
    if (!idSucursal) {
        Swal.fire('Error', 'No se pudo determinar la sucursal', 'error');
        return;
    }
    
    // Validar que las horas sean válidas
    if (horaInicio >= horaFin) {
        Swal.fire('Error', 'La hora de fin debe ser mayor que la hora de inicio', 'error');
        return;
    }
    
    // Crear objeto horario
    const nuevoHorario = {
        dia_semana: parseInt(diaSemana),
        hora_inicio: horaInicio,
        hora_fin: horaFin,
        duracion_cita: parseInt(duracionCita)
    };
    
    // Inicializar array si no existe
    if (!horariosDoctor[idSucursal]) {
        horariosDoctor[idSucursal] = [];
    }
    
    // Validar conflictos
    const conflicto = horariosDoctor[idSucursal].find((h, index) => 
        h.dia_semana === nuevoHorario.dia_semana && 
        horariosSeSuperponen(h, nuevoHorario) &&
        index !== horarioEditando
    );
    
    if (conflicto) {
        Swal.fire('Error', 'El horario se superpone con otro horario existente', 'error');
        return;
    }
    
    if (horarioEditando !== null) {
        // Editar horario existente
        horariosDoctor[idSucursal][horarioEditando] = nuevoHorario;
        console.log('✏️ Horario editado:', nuevoHorario);
    } else {
        // Agregar nuevo horario
        horariosDoctor[idSucursal].push(nuevoHorario);
        console.log('➕ Horario agregado:', nuevoHorario);
    }
    
    // Actualizar vista
    if (modoEdicionActual) {
        mostrarHorariosSucursalEditar(idSucursal);
    } else {
        mostrarHorariosSucursal(idSucursal);
    }
    
    // Cerrar modal
    $('#modalHorario').modal('hide');
    
    Swal.fire({
        icon: 'success',
        title: horarioEditando !== null ? 'Horario actualizado' : 'Horario agregado',
        timer: 1500,
        showConfirmButton: false
    });
}

// ===== MOSTRAR HORARIOS (CREAR) =====
function mostrarHorariosSucursal(idSucursal) {
    const container = $('#horariosContainer');
    
    if (!idSucursal) {
        container.html(`
            <div class="text-center text-muted py-4" id="noHorariosMessage">
                <i class="bi bi-clock-history display-1 text-purple mb-3"></i>
                <h5 class="text-muted">⏰ No hay horarios configurados</h5>
                <p class="mb-0">Haga clic en "Agregar Horario" para comenzar</p>
            </div>
        `);
        return;
    }
    
    const horarios = horariosDoctor[idSucursal] || [];
    
    if (horarios.length === 0) {
        container.html(`
            <div class="text-center text-muted py-4" id="noHorariosMessage">
                <i class="bi bi-clock-history display-1 text-purple mb-3"></i>
                <h5 class="text-muted">⏰ No hay horarios configurados</h5>
                <p class="mb-0">Haga clic en "Agregar Horario" para comenzar</p>
            </div>
        `);
        return;
    }
    
    // Agrupar horarios por día
    const horariosPorDia = {};
    horarios.forEach((horario, index) => {
        const dia = parseInt(horario.dia_semana);
        if (!horariosPorDia[dia]) {
            horariosPorDia[dia] = [];
        }
        horariosPorDia[dia].push({...horario, index});
    });
    
    // Generar HTML por días
    let html = '<div class="row g-2">';
    
    for (let dia = 1; dia <= 7; dia++) {
        const nombreDia = DIAS_SEMANA[dia];
        const horariosDelDia = horariosPorDia[dia] || [];
        
        html += `
            <div class="col-lg-6 col-xl-4 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0 fw-bold">${nombreDia}</h6>
                    </div>
                    <div class="card-body py-2">
        `;
        
        if (horariosDelDia.length === 0) {
            html += `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-clock"></i>
                    <small class="d-block">Sin horarios</small>
                </div>
            `;
        } else {
            horariosDelDia.forEach(horario => {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <strong class="text-primary">${horario.hora_inicio} - ${horario.hora_fin}</strong><br>
                            <small class="text-muted">
                                <i class="bi bi-stopwatch me-1"></i>${horario.duracion_cita} min/cita
                            </small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editarHorario('${idSucursal}', ${horario.index})" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="eliminarHorario('${idSucursal}', ${horario.index})" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    container.html(html);
}

// ===== MOSTRAR HORARIOS (EDITAR) =====
function mostrarHorariosSucursalEditar(idSucursal) {
    const container = $('#editarHorariosContainer');
    
    if (!idSucursal) {
        container.html(`
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history display-6 d-block mb-2"></i>
                <p>Seleccione una sucursal para gestionar horarios</p>
            </div>
        `);
        return;
    }
    
    const horarios = horariosDoctor[idSucursal] || [];
    
    if (horarios.length === 0) {
        container.html(`
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history display-6 d-block mb-2"></i>
                <p>No hay horarios configurados para esta sucursal</p>
                <small>Haga clic en "Agregar Horario" para comenzar</small>
            </div>
        `);
        return;
    }
    
    // Agrupar horarios por día (igual que arriba)
    const horariosPorDia = {};
    horarios.forEach((horario, index) => {
        const dia = parseInt(horario.dia_semana);
        if (!horariosPorDia[dia]) {
            horariosPorDia[dia] = [];
        }
        horariosPorDia[dia].push({...horario, index});
    });
    
    let html = '<div class="row g-2">';
    
    for (let dia = 1; dia <= 7; dia++) {
        const nombreDia = DIAS_SEMANA[dia];
        const horariosDelDia = horariosPorDia[dia] || [];
        
        html += `
            <div class="col-lg-6 col-xl-4 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0 fw-bold">${nombreDia}</h6>
                    </div>
                    <div class="card-body py-2">
        `;
        
        if (horariosDelDia.length === 0) {
            html += `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-clock"></i>
                    <small class="d-block">Sin horarios</small>
                </div>
            `;
        } else {
            horariosDelDia.forEach(horario => {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <strong class="text-primary">${horario.hora_inicio} - ${horario.hora_fin}</strong><br>
                            <small class="text-muted">
                                <i class="bi bi-stopwatch me-1"></i>${horario.duracion_cita} min/cita
                            </small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editarHorarioEdicion('${idSucursal}', ${horario.index})" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="eliminarHorarioEdicion('${idSucursal}', ${horario.index})" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    container.html(html);
}

// ===== EDITAR HORARIO =====
function editarHorario(idSucursal, index) {
    const horario = horariosDoctor[idSucursal][index];
    if (!horario) return;
    
    horarioEditando = index;
    
    // Llenar formulario
    $('#diaSemana').val(horario.dia_semana);
    $('#horaInicio').val(horario.hora_inicio);
    $('#horaFin').val(horario.hora_fin);
    $('#duracionCita').val(horario.duracion_cita);
    
    // Configurar modal
    $('#modalHorario').data('sucursal-actual', idSucursal);
    $('#modalHorario').data('modo-edicion', false);
    $('#tituloModalHorario').text('Editar Horario de Atención');
    
    $('#modalHorario').modal('show');
}

function editarHorarioEdicion(idSucursal, index) {
    const horario = horariosDoctor[idSucursal][index];
    if (!horario) return;
    
    horarioEditando = index;
    
    // Llenar formulario
    $('#diaSemana').val(horario.dia_semana);
    $('#horaInicio').val(horario.hora_inicio);
    $('#horaFin').val(horario.hora_fin);
    $('#duracionCita').val(horario.duracion_cita);
    
    // Configurar modal
    $('#modalHorario').data('sucursal-actual', idSucursal);
    $('#modalHorario').data('modo-edicion', true);
    $('#tituloModalHorario').text('Editar Horario de Atención (Edición)');
    
    $('#modalHorario').modal('show');
}

// ===== ELIMINAR HORARIO =====
function eliminarHorario(idSucursal, index) {
    Swal.fire({
        title: '¿Eliminar horario?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            horariosDoctor[idSucursal].splice(index, 1);
            mostrarHorariosSucursal(idSucursal);
            
            Swal.fire({
                icon: 'success',
                title: 'Horario eliminado',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

function eliminarHorarioEdicion(idSucursal, index) {
    Swal.fire({
        title: '¿Eliminar horario?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            horariosDoctor[idSucursal].splice(index, 1);
            mostrarHorariosSucursalEditar(idSucursal);
            
            Swal.fire({
                icon: 'success',
                title: 'Horario eliminado',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// ===== VALIDACIONES =====
function horariosSeSuperponen(horario1, horario2) {
    if (horario1.dia_semana !== horario2.dia_semana) return false;
    
    const inicio1 = horario1.hora_inicio;
    const fin1 = horario1.hora_fin;
    const inicio2 = horario2.hora_inicio;
    const fin2 = horario2.hora_fin;
    
    return (inicio1 < fin2 && fin1 > inicio2);
}

function validarHoras() {
    const horaInicio = $('#horaInicio').val();
    const horaFin = $('#horaFin').val();
    
    if (horaInicio && horaFin && horaInicio >= horaFin) {
        $('#horaFin')[0].setCustomValidity('La hora de fin debe ser mayor que la hora de inicio');
    } else {
        $('#horaFin')[0].setCustomValidity('');
    }
}

// ===== OBTENER HORARIOS PARA ENVÍO =====
function obtenerHorariosParaEnvio() {
    console.log('📦 === OBTENER HORARIOS PARA ENVÍO ===');
    console.log('Estado horariosDoctor:', horariosDoctor);
    
    const horariosParaEnviar = [];
    
    // Procesar cada sucursal (ahora solo será una)
    Object.keys(horariosDoctor).forEach(idSucursal => {
        const horariosDelaSucursal = horariosDoctor[idSucursal];
        console.log(`🏥 Sucursal ${idSucursal}:`, horariosDelaSucursal);
        
        if (Array.isArray(horariosDelaSucursal)) {
            horariosDelaSucursal.forEach(horario => {
                const horarioParaDB = {
                    id_sucursal: parseInt(idSucursal),
                    dia_semana: parseInt(horario.dia_semana),
                    hora_inicio: horario.hora_inicio,
                    hora_fin: horario.hora_fin,
                    duracion_cita: parseInt(horario.duracion_cita) || 30
                };
                
                horariosParaEnviar.push(horarioParaDB);
                console.log('✅ Horario agregado:', horarioParaDB);
            });
        }
    });
    
    console.log(`📤 Total horarios para enviar: ${horariosParaEnviar.length}`);
    console.log('📋 Horarios finales:', horariosParaEnviar);
    
    return horariosParaEnviar;
}

// ===== LIMPIAR HORARIOS =====
function limpiarTodosLosHorarios() {
    horariosDoctor = {};
    mostrarHorariosSucursal('');
    mostrarHorariosSucursalEditar('');
    console.log('🗑️ Horarios limpiados');
}

function limpiarHorarios() {
    limpiarTodosLosHorarios();
}

// ===== CARGAR HORARIOS EXISTENTES =====
function cargarHorariosExistentesDelServidor(idDoctor) {
    console.log('🔄 Cargando horarios del servidor para doctor:', idDoctor);
    
    if (!window.doctoresConfig || !window.doctoresConfig.submenuId) {
        console.error('❌ Configuración de doctores no disponible');
        return;
    }
    
    $.ajax({
        url: '../../controladores/DoctoresControlador/DoctoresController.php',
        type: 'GET',
        data: {
            action: 'obtenerHorarios',
            id_doctor: idDoctor,
            submenu_id: window.doctoresConfig.submenuId
        },
        dataType: 'json',
        success: function(response) {
            console.log('📥 Respuesta horarios del servidor:', response);
            
            if (response.success && response.data) {
                cargarHorariosExistentes(response.data);
                console.log('✅ Horarios cargados desde el servidor');
            } else {
                console.warn('⚠️ No se encontraron horarios o error en respuesta');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando horarios del servidor:', error);
        }
    });
}

function cargarHorariosExistentes(horariosArray) {
    limpiarTodosLosHorarios();
    
    // Agrupar por sucursal
    horariosArray.forEach(horario => {
        const idSucursal = horario.id_sucursal.toString();
        
        if (!horariosDoctor[idSucursal]) {
            horariosDoctor[idSucursal] = [];
        }
        
        horariosDoctor[idSucursal].push({
            dia_semana: horario.dia_semana,
            hora_inicio: horario.hora_inicio,
            hora_fin: horario.hora_fin,
            duracion_cita: horario.duracion_cita || 30
        });
    });
    
    // Actualizar vista según el modo
    if (modoEdicion) {
        const sucursalActual = $('#editarIdSucursal').val();
        if (sucursalActual) {
            mostrarHorariosSucursalEditar(sucursalActual);
        }
    } else {
        const sucursalActual = $('#id_sucursal').val();
        if (sucursalActual) {
            mostrarHorariosSucursal(sucursalActual);
        }
    }
    
    console.log('📥 Horarios existentes cargados:', horariosDoctor);
}

// ===== RECARGAR HORARIOS =====
function recargarHorariosDelServidor() {
    if (!doctorEditandoId) {
        Swal.fire('Error', 'No se pudo determinar el doctor a recargar', 'error');
        return;
    }
    
    const btnRecargar = $('#btnRecargarHorarios');
    const textoOriginal = btnRecargar.html();
    btnRecargar.html('<i class="bi bi-arrow-clockwise me-1"></i>Recargando...').prop('disabled', true);
    
    try {
        cargarHorariosExistentesDelServidor(doctorEditandoId);
        
        const sucursalActual = $('#editarIdSucursal').val();
        if (sucursalActual) {
            mostrarHorariosSucursalEditar(sucursalActual);
        }
        
        Swal.fire({
            icon: 'success',
            title: 'Horarios recargados',
            timer: 1500,
            showConfirmButton: false
        });
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron recargar los horarios'
        });
    } finally {
        btnRecargar.html(textoOriginal).prop('disabled', false);
    }
}

function iniciarEdicionDoctor(idDoctor) {
    doctorEditandoId = idDoctor;
    modoEdicion = true;
    console.log(`🔄 Iniciando edición del doctor ${idDoctor}`);
}

// ===== EXPORTAR FUNCIONES GLOBALMENTE =====
window.abrirModalAgregarHorario = abrirModalAgregarHorario;
window.editarHorario = editarHorario;
window.eliminarHorario = eliminarHorario;
window.obtenerHorariosParaEnvio = obtenerHorariosParaEnvio;
window.limpiarTodosLosHorarios = limpiarTodosLosHorarios;
window.limpiarHorarios = limpiarHorarios;
window.cargarHorariosExistentes = cargarHorariosExistentes;

// ===== FUNCIONES EXPORTADAS PARA EDICIÓN =====
window.editarHorarioEdicion = editarHorarioEdicion;
window.eliminarHorarioEdicion = eliminarHorarioEdicion;
window.iniciarEdicionDoctor = iniciarEdicionDoctor;
window.cargarHorariosExistentesDelServidor = cargarHorariosExistentesDelServidor;
window.inicializarEventosEdicion = inicializarEventosEdicion;
window.recargarHorariosDelServidor = recargarHorariosDelServidor;

console.log('🎯 Sistema de horarios de doctores cargado completamente (versión sucursal única)');