/**
 * Sistema de Gestión de Horarios para Doctores
 * Compatible con tabla doctor_horarios
 */

// Variables globales para horarios
let horariosDoctor = {}; // Estructura: {id_sucursal: [horarios]}
let horarioEditando = null;

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
    console.log('✅ Sistema de horarios inicializado');
});

function inicializarEventosHorarios() {
    // Event listeners principales
    $('#btnAgregarHorario').on('click', abrirModalAgregarHorario);
    $('#btnGuardarHorario').on('click', guardarHorario);
    $('#sucursalHorarios').on('change', cambiarSucursalHorarios);
    
    // Validación de horas
    $('#horaInicio, #horaFin').on('change', validarHoras);
    
    // Sincronizar sucursales seleccionadas
    $('input[name="sucursales[]"]').on('change', sincronizarSucursalesHorarios);
    
    // Resetear al abrir modal principal
    $('#crearDoctorModal').on('show.bs.modal', function() {
        limpiarTodosLosHorarios();
    });
}

// ===== GESTIÓN DE SUCURSALES =====
function sincronizarSucursalesHorarios() {
    const sucursalesSeleccionadas = $('input[name="sucursales[]"]:checked');
    const selectSucursalHorarios = $('#sucursalHorarios');
    const sucursalActual = selectSucursalHorarios.val();
    
    // Limpiar opciones existentes excepto la primera
    selectSucursalHorarios.find('option:not(:first)').remove();
    
    // Agregar sucursales seleccionadas
    sucursalesSeleccionadas.each(function() {
        const idSucursal = $(this).val();
        const nombreSucursal = $(this).next('label').find('strong').text();
        selectSucursalHorarios.append(new Option(nombreSucursal, idSucursal));
    });
    
    // Restaurar selección si aún es válida
    if (sucursalActual && sucursalesSeleccionadas.filter(`[value="${sucursalActual}"]`).length > 0) {
        selectSucursalHorarios.val(sucursalActual);
        mostrarHorariosSucursal(sucursalActual);
    } else {
        selectSucursalHorarios.val('');
        mostrarHorariosSucursal('');
    }
}

function cambiarSucursalHorarios() {
    const idSucursal = $('#sucursalHorarios').val();
    mostrarHorariosSucursal(idSucursal);
}

// ===== MOSTRAR HORARIOS =====
function mostrarHorariosSucursal(idSucursal) {
    const container = $('#horariosContainer');
    
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
                <p>No hay horarios configurados</p>
                <small>Haga clic en "Agregar Horario" para comenzar</small>
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
                            <button class="btn btn-outline-primary" onclick="editarHorario(${horario.index})" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="eliminarHorario(${horario.index})" title="Eliminar">
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

// ===== MODAL DE HORARIO =====
function abrirModalAgregarHorario() {
    const idSucursal = $('#sucursalHorarios').val();
    
    if (!idSucursal) {
        Swal.fire({
            icon: 'warning',
            title: 'Seleccione una sucursal',
            text: 'Debe seleccionar una sucursal antes de agregar horarios'
        });
        return;
    }
    
    horarioEditando = null;
    $('#tituloModalHorario').text('Agregar Horario');
    $('#formHorario')[0].reset();
    $('#modalHorario').modal('show');
}

function editarHorario(index) {
    const idSucursal = $('#sucursalHorarios').val();
    const horarios = horariosDoctor[idSucursal] || [];
    const horario = horarios[index];
    
    if (!horario) return;
    
    horarioEditando = index;
    $('#tituloModalHorario').text('Editar Horario');
    $('#diaSemana').val(horario.dia_semana);
    $('#horaInicio').val(horario.hora_inicio);
    $('#horaFin').val(horario.hora_fin);
    $('#duracionCita').val(horario.duracion_cita);
    $('#modalHorario').modal('show');
}

function guardarHorario() {
    const idSucursal = $('#sucursalHorarios').val();
    const diaSemana = $('#diaSemana').val();
    const horaInicio = $('#horaInicio').val();
    const horaFin = $('#horaFin').val();
    const duracionCita = $('#duracionCita').val();
    
    // Validaciones
    if (!diaSemana || !horaInicio || !horaFin) {
        Swal.fire({
            icon: 'error',
            title: 'Campos requeridos',
            text: 'Complete todos los campos obligatorios'
        });
        return;
    }
    
    if (horaInicio >= horaFin) {
        Swal.fire({
            icon: 'error',
            title: 'Horario inválido',
            text: 'La hora de inicio debe ser menor que la hora de fin'
        });
        return;
    }
    
    // Verificar solapamientos
    if (!verificarSolapamientos(idSucursal, diaSemana, horaInicio, horaFin, horarioEditando)) {
        return;
    }
    
    // Crear objeto horario con formato correcto para la BD
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
    
    // Agregar o editar
    if (horarioEditando !== null) {
        horariosDoctor[idSucursal][horarioEditando] = nuevoHorario;
        Swal.fire({
            icon: 'success',
            title: 'Horario actualizado',
            timer: 1500,
            showConfirmButton: false
        });
    } else {
        horariosDoctor[idSucursal].push(nuevoHorario);
        Swal.fire({
            icon: 'success',
            title: 'Horario agregado',
            timer: 1500,
            showConfirmButton: false
        });
    }
    
    // Actualizar vista y cerrar modal
    mostrarHorariosSucursal(idSucursal);
    $('#modalHorario').modal('hide');
    
    console.log('📅 Horarios actualizados:', horariosDoctor);
}

function eliminarHorario(index) {
    const idSucursal = $('#sucursalHorarios').val();
    
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

// ===== VALIDACIONES =====
function verificarSolapamientos(idSucursal, dia, horaInicio, horaFin, indexExcluir = null) {
    const horarios = horariosDoctor[idSucursal] || [];
    
    for (let i = 0; i < horarios.length; i++) {
        if (i === indexExcluir) continue;
        
        const horario = horarios[i];
        if (parseInt(horario.dia_semana) === parseInt(dia)) {
            // Verificar solapamiento
            if ((horaInicio >= horario.hora_inicio && horaInicio < horario.hora_fin) ||
                (horaFin > horario.hora_inicio && horaFin <= horario.hora_fin) ||
                (horaInicio <= horario.hora_inicio && horaFin >= horario.hora_fin)) {
                
                Swal.fire({
                    icon: 'error',
                    title: 'Horario solapado',
                    text: `Ya existe un horario de ${horario.hora_inicio} a ${horario.hora_fin} el ${DIAS_SEMANA[dia]}`
                });
                return false;
            }
        }
    }
    return true;
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
    
    // Procesar cada sucursal
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
    $('#sucursalHorarios').val('');
    mostrarHorariosSucursal('');
    console.log('🗑️ Horarios limpiados');
}

// ===== CARGAR HORARIOS EXISTENTES (para edición) =====
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
    
    // Actualizar vista si hay sucursal seleccionada
    const sucursalActual = $('#sucursalHorarios').val();
    if (sucursalActual) {
        mostrarHorariosSucursal(sucursalActual);
    }
    
    console.log('📥 Horarios existentes cargados:', horariosDoctor);
}

// Exportar funciones globalmente
window.abrirModalAgregarHorario = abrirModalAgregarHorario;
window.editarHorario = editarHorario;
window.eliminarHorario = eliminarHorario;
window.obtenerHorariosParaEnvio = obtenerHorariosParaEnvio;
window.limpiarTodosLosHorarios = limpiarTodosLosHorarios;
window.cargarHorariosExistentes = cargarHorariosExistentes;