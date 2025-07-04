/**
 * Sistema de Gestión de Doctores
 * Autor: Sistema MediSys
 * Descripción: CRUD completo para gestión de doctores con múltiples relaciones
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: true,
    submenuId: window.doctoresConfig?.submenuId || null,
    permisos: window.doctoresConfig?.permisos || {},
    especialidades: window.doctoresConfig?.especialidades || [],
    sucursales: window.doctoresConfig?.sucursales || [],
    baseUrl: '../../controladores/DoctoresControlador/DoctoresController.php'
};

// Variables globales
let paginaActual = 1;
let registrosPorPagina = 10;
let busquedaActual = '';
let filtroEstadoActual = '';
let filtroEspecialidadActual = '';
let filtroSucursalActual = '';
let totalPaginas = 0;
let totalRegistros = 0;
let passwordGenerada = '';

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🏥 Iniciando Sistema de Gestión de Doctores');
    
    if (config.debug) {
        console.log('Config:', config);
    }

    inicializarEventos();
    cargarDoctoresPaginados(1);
    cargarEstadisticas();
    generarPasswordInicial();
    $.getScript('../../js/nacionalidades.js', function() {
    console.log('✅ Script de nacionalidades para doctores cargado');
}).fail(function() {
    console.error('❌ Error cargando script de nacionalidades para doctores');
});
});

// ===== EVENTOS =====
function inicializarEventos() {
    // Formularios
    $('#formCrearDoctor').on('submit', crearDoctor);
    $('#formEditarDoctor').on('submit', editarDoctor);
    
    // Búsqueda con debounce mejorado
    let timeoutBusqueda;
    $('#busquedaGlobal').on('input', function() {
        clearTimeout(timeoutBusqueda);
        const valor = $(this).val().trim();
        
        timeoutBusqueda = setTimeout(() => {
            busquedaActual = valor;
            cargarDoctoresPaginados(1);
        }, 300);
    });
    
    // Filtros
    $('#filtroEstado').on('change', function() {
        filtroEstadoActual = $(this).val();
        cargarDoctoresPaginados(1);
    });
    
    $('#filtroEspecialidad').on('change', function() {
        filtroEspecialidadActual = $(this).val();
        cargarDoctoresPaginados(1);
    });
    
    $('#filtroSucursal').on('change', function() {
        filtroSucursalActual = $(this).val();
        cargarDoctoresPaginados(1);
    });
    
    $('#registrosPorPagina').on('change', function() {
        registrosPorPagina = parseInt($(this).val());
        cargarDoctoresPaginados(1);
    });
    
    // Botones de control
    $('#limpiarFiltros').on('click', limpiarFiltros);
    $('#refrescarTabla').on('click', function() {
        cargarDoctoresPaginados(paginaActual);
        cargarEstadisticas();
    });
    
    // Generador de contraseña
    $('#generarPassword').on('click', generarNuevaPassword);
    
    // Validaciones en tiempo real
    $('#cedula').on('blur', validarCedula);
    $('#username').on('blur', validarUsername);
    $('#correo').on('blur', validarCorreo);
    
    $('#editarCedula').on('blur', function() { validarCedula.call(this, true); });
    $('#editarUsername').on('blur', function() { validarUsername.call(this, true); });
    $('#editarCorreo').on('blur', function() { validarCorreo.call(this, true); });
    
    // Auto-generar username basado en nombres
    $('#nombres, #apellidos').on('input', generarUsernameAutomatico);
    $('#editarNombres, #editarApellidos').on('input', function() {
        generarUsernameAutomatico.call(this, true);
    });
    
    // Resetear formularios al cerrar modales
    $('.modal').on('hidden.bs.modal', function() {
        const form = $(this).find('form')[0];
        if (form) {
            form.reset();
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback').remove();
            $(form).find('input[type="checkbox"]').prop('checked', false);
        }
        
        // Regenerar contraseña en crear
        if ($(this).attr('id') === 'crearDoctorModal') {
            generarPasswordInicial();
        }
    });
    
    if (config.debug) {
        console.log('✅ Eventos inicializados correctamente');
    }
}

// ===== FUNCIONES PRINCIPALES =====

/**
 * Cargar doctores paginados
 */
function cargarDoctoresPaginados(pagina = 1) {
    paginaActual = pagina;
    
    const parametros = {
        action: 'obtenerDoctoresPaginados',
        pagina: pagina,
        limit: registrosPorPagina,
        busqueda: busquedaActual,
        submenu_id: config.submenuId
    };
    
    if (filtroEstadoActual !== '') {
        parametros.estado = filtroEstadoActual;
    }
    if (filtroEspecialidadActual !== '') {
        parametros.especialidad = filtroEspecialidadActual;
    }
    if (filtroSucursalActual !== '') {
        parametros.sucursal = filtroSucursalActual;
    }
    
    if (config.debug) {
        console.log('Cargando doctores con parámetros:', parametros);
    }
    
    // Mostrar loading
    $('#tablaDoctoresBody').html(`
        <tr>
            <td colspan="8" class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3 text-muted">Cargando doctores...</p>
            </td>
        </tr>
    `);
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: parametros,
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('Respuesta del servidor:', response);
            }
            
            if (response.success) {
                mostrarDoctores(response.data);
                actualizarPaginacion(response);
                actualizarInfoTabla(response);
            } else {
                mostrarError('Error al cargar doctores: ' + response.message);
                mostrarErrorEnTabla();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', {xhr, status, error});
            mostrarError('Error de conexión al cargar doctores');
            mostrarErrorEnTabla();
        }
    });
}

/**
 * Mostrar error en la tabla
 */
function mostrarErrorEnTabla() {
    $('#tablaDoctoresBody').html(`
        <tr>
            <td colspan="8" class="text-center py-5">
                <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3"></i>
                <p class="text-muted">Error al cargar los datos</p>
                <button class="btn btn-outline-success btn-sm" onclick="cargarDoctoresPaginados(${paginaActual})">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                </button>
            </td>
        </tr>
    `);
}

/**
 * Mostrar doctores en la tabla
 */
function mostrarDoctores(doctores) {
    const tbody = $('#tablaDoctoresBody');
    
    if (!doctores || doctores.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron doctores</p>
                    ${busquedaActual || filtroEstadoActual || filtroEspecialidadActual || filtroSucursalActual ? 
                        '<button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">Limpiar filtros</button>' : 
                        ''
                    }
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    
    doctores.forEach(doctor => {
        // Estado badge
        let estadoBadge = '';
        switch(parseInt(doctor.id_estado)) {
            case 1:
                estadoBadge = '<span class="badge bg-success badge-estado">✅ Activo</span>';
                break;
            case 2:
                estadoBadge = '<span class="badge bg-danger badge-estado">🚫 Bloqueado</span>';
                break;
            case 3:
                estadoBadge = '<span class="badge bg-warning badge-estado">⏳ Pendiente</span>';
                break;
            case 4:
                estadoBadge = '<span class="badge bg-secondary badge-estado">❌ Inactivo</span>';
                break;
            default:
                estadoBadge = '<span class="badge bg-light text-dark badge-estado">❓ Desconocido</span>';
        }
        
        // Información personal
        const informacionPersonal = `
            <div class="d-flex flex-column small">
                <span><i class="bi bi-card-text me-1 text-muted"></i><strong>CI:</strong> ${doctor.cedula}</span>
                <span><i class="bi bi-person-circle me-1 text-muted"></i><strong>User:</strong> ${doctor.username}</span>
                <span><i class="bi bi-envelope me-1 text-muted"></i>${doctor.correo}</span>
            </div>
        `;
        
        // Especialidad con título
        const especialidadInfo = `
            <div class="d-flex flex-column">
                <span class="fw-bold text-primary">${doctor.nombre_especialidad}</span>
                ${doctor.titulo_profesional ? 
                    `<small class="text-muted"><i class="bi bi-mortarboard me-1"></i>${doctor.titulo_profesional}</small>` : 
                    '<small class="text-muted">Sin título especificado</small>'
                }
            </div>
        `;
        
        // Sucursales (simplificado para la tabla)
        const sucursalesInfo = `
            <span class="badge bg-info">
                <i class="bi bi-building me-1"></i>${doctor.total_sucursales || 0} sucursal(es)
            </span>
        `;
        
        // Estadísticas
        const estadisticas = `
            <div class="d-flex flex-column small">
                <span class="text-primary">
                    <i class="bi bi-calendar-check me-1"></i>${doctor.total_citas || 0} citas
                </span>
                <span class="text-success">
                    <i class="bi bi-people me-1"></i>Activo desde ${doctor.fecha_creacion ? new Date(doctor.fecha_creacion).getFullYear() : 'N/A'}
                </span>
            </div>
        `;
        
        // Botones de acción según permisos
        let botones = '';
        
        // Botón ver (siempre disponible)
        botones += `
            <button class="btn btn-outline-info btn-sm" onclick="verDoctor(${doctor.id_doctor})" 
                    title="Ver detalles">
                <i class="bi bi-eye"></i>
            </button>
        `;
        
        // Botón editar
        if (config.permisos.puede_editar) {
            botones += `
                <button class="btn btn-outline-primary btn-sm" onclick="abrirModalEditar(${doctor.id_doctor})" 
                        title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
            `;
        }
        
        // Botón cambiar estado
        if (config.permisos.puede_editar) {
            const estadoActual = parseInt(doctor.id_estado);
            let proximoEstado, estadoTexto, estadoIcono, estadoColor;
            
            switch(estadoActual) {
                case 1: // Activo -> Bloquear
                    proximoEstado = 2;
                    estadoTexto = 'Bloquear';
                    estadoIcono = 'shield-x';
                    estadoColor = 'outline-warning';
                    break;
                case 2: // Bloqueado -> Activar
                    proximoEstado = 1;
                    estadoTexto = 'Activar';
                    estadoIcono = 'shield-check';
                    estadoColor = 'outline-success';
                    break;
                case 3: // Pendiente -> Activar
                    proximoEstado = 1;
                    estadoTexto = 'Activar';
                    estadoIcono = 'check-circle';
                    estadoColor = 'outline-success';
                    break;
                case 4: // Inactivo -> Activar
                    proximoEstado = 1;
                    estadoTexto = 'Activar';
                    estadoIcono = 'arrow-clockwise';
                    estadoColor = 'outline-success';
                    break;
                default:
                    proximoEstado = 1;
                    estadoTexto = 'Activar';
                    estadoIcono = 'question';
                    estadoColor = 'outline-secondary';
            }
            
            botones += `
                <button class="btn ${estadoColor} btn-sm" onclick="cambiarEstado(${doctor.id_doctor}, ${proximoEstado})" 
                        title="${estadoTexto}">
                    <i class="bi bi-${estadoIcono}"></i>
                </button>
            `;
        }
        
        // Botón eliminar
        if (config.permisos.puede_eliminar) {
            botones += `
                <button class="btn btn-outline-danger btn-sm" onclick="eliminarDoctor(${doctor.id_doctor})" 
                        title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            `;
        }
        
        html += `
            <tr>
                <td><strong class="text-primary">#${doctor.id_doctor}</strong></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                             style="width: 40px; height: 40px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <strong>${doctor.nombres} ${doctor.apellidos}</strong>
                            <br>
                            <small class="text-muted">${doctor.sexo === 'M' ? 'Masculino' : 'Femenino'} • ${doctor.nacionalidad}</small>
                        </div>
                    </div>
                </td>
                <td>${informacionPersonal}</td>
                <td>${especialidadInfo}</td>
                <td>${sucursalesInfo}</td>
                <td>${estadoBadge}</td>
                <td>${estadisticas}</td>
                <td class="text-center">
                    <div class="btn-group-vertical" role="group">
                        ${botones}
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

/**
 * Crear nuevo doctor
 */
// ===== CREAR DOCTOR CON HORARIOS - VERSIÓN CORREGIDA =====
function crearDoctor(e) {
    e.preventDefault();
    
    if (!validarFormulario('formCrearDoctor')) {
        return;
    }
    
    // Obtener sucursales seleccionadas
    const sucursalesSeleccionadas = [];
    $('#sucursalesCrear input[type="checkbox"]:checked').each(function() {
        sucursalesSeleccionadas.push($(this).val());
    });
    
    // Validar que tenga al menos una sucursal
    if (sucursalesSeleccionadas.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sucursales requeridas',
            text: 'Debe seleccionar al menos una sucursal para el doctor'
        });
        return;
    }
    
    // 🕒 OBTENER HORARIOS - VERSIÓN MEJORADA
    console.log('📦 === DEBUG ANTES DE OBTENER HORARIOS ===');
    console.log('horariosDoctor global:', window.horariosDoctor);
    
    let horarios = [];
    if (typeof window.obtenerHorariosParaEnvio === 'function') {
        horarios = window.obtenerHorariosParaEnvio();
        console.log('✅ Horarios obtenidos:', horarios);
    } else {
        console.log('❌ Función obtenerHorariosParaEnvio no disponible');
    }
    
    // Validar que tenga al menos un horario
    if (horarios.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Horarios requeridos',
            text: 'Debe configurar al menos un horario para el doctor',
            footer: '<small>Seleccione una sucursal y agregue horarios de atención</small>'
        });
        return;
    }
    
    console.log(`📋 Total horarios a enviar: ${horarios.length}`);
    
    // Crear FormData
    const formData = new FormData(this);
    formData.append('action', 'crear');
    formData.append('submenu_id', config.submenuId);
    
    // Agregar sucursales al FormData
    sucursalesSeleccionadas.forEach(suc => {
        formData.append('sucursales[]', suc);
    });
    
    // 🔥 AGREGAR HORARIOS COMO JSON
    const horariosJson = JSON.stringify(horarios);
    formData.append('horarios', horariosJson);
    
    console.log('📤 JSON de horarios enviado:', horariosJson);
    
    if (config.debug) {
        console.log('📦 === DATOS A ENVIAR ===');
        for (let pair of formData.entries()) {
            console.log(`${pair[0]}: ${pair[1]}`);
        }
        console.log('🏥 Sucursales:', sucursalesSeleccionadas);
        console.log('🕒 Horarios:', horarios);
    }
    
    // Deshabilitar botón de envío
    const submitBtn = $(this).find('button[type="submit"]');
    const textoOriginal = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Creando doctor...');
    
    $.ajax({
        url: config.baseUrl,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('📥 Respuesta del servidor:', response);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Doctor registrado!',
                    html: `
                        <div class="text-start">
                            <p><strong>Doctor creado exitosamente</strong></p>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-person-check text-success me-1"></i> Información personal guardada</li>
                                <li><i class="bi bi-building text-primary me-1"></i> ${sucursalesSeleccionadas.length} sucursal(es) asignada(s)</li>
                                <li><i class="bi bi-clock text-info me-1"></i> ${horarios.length} horario(s) configurado(s)</li>
                            </ul>
                            <small class="text-muted">${response.message}</small>
                        </div>
                    `,
                    timer: 4000,
                    showConfirmButton: true,
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    $('#crearDoctorModal').modal('hide');
                    cargarDoctoresPaginados(1);
                    cargarEstadisticas();
                    
                    // Limpiar horarios
                    if (typeof window.limpiarTodosLosHorarios === 'function') {
                        window.limpiarTodosLosHorarios();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al crear doctor',
                    text: response.message || 'Error desconocido'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX:', {status, error, response: xhr.responseText});
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Intente nuevamente.'
            });
        },
        complete: function() {
            // Rehabilitar botón
            submitBtn.prop('disabled', false).html(textoOriginal);
        }
    });
}

/**
* Abrir modal para editar doctor CON HORARIOS
*/
function abrirModalEditar(idDoctor) {
    console.log('🔄 === INICIANDO EDICIÓN DEL DOCTOR ===');
    console.log('Doctor ID:', idDoctor);
    
    // Inicializar edición
    if (typeof window.iniciarEdicionDoctor === 'function') {
        window.iniciarEdicionDoctor(idDoctor);
    }
    
    // Limpiar formulario
    document.getElementById('formEditarDoctor').reset();
    
    // Mostrar modal
    $('#editarDoctorModal').modal('show');
    
    // Cargar datos del doctor
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerPorId',
            id: idDoctor,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            console.log('📥 Respuesta obtenerPorId:', response);
            
            if (response.success && response.data) {
                const doctor = response.data;
                console.log('✅ Datos del doctor cargados:', doctor);
                
                // Llenar el formulario
                $('#editarIdDoctor').val(doctor.id_doctor);
                $('#editarCedula').val(doctor.cedula);
                $('#editarUsername').val(doctor.username);
                $('#editarNombres').val(doctor.nombres);
                $('#editarApellidos').val(doctor.apellidos);
                $('#editarSexo').val(doctor.sexo);
                $('#editarNacionalidad').val(doctor.nacionalidad);
                $('#editarIdEstado').val(doctor.id_estado);
                $('#editarCorreo').val(doctor.correo);
                $('#editarIdEspecialidad').val(doctor.id_especialidad);
                $('#editarTituloProfesional').val(doctor.titulo_profesional);
                
                // Marcar sucursales asignadas
                $('#sucursalesEditar input[type="checkbox"]').prop('checked', false);
                if (doctor.sucursales && Array.isArray(doctor.sucursales)) {
                    doctor.sucursales.forEach(sucursal => {
                        $(`#editar_sucursal_${sucursal.id_sucursal}`).prop('checked', true);
                    });
                    console.log('✅ Sucursales marcadas:', doctor.sucursales.length);
                }
                
                // 🕒 CARGAR HORARIOS PASO A PASO
                setTimeout(async () => {
                    console.log('🕒 === INICIANDO CARGA DE HORARIOS ===');
                    
                    // 1. Sincronizar sucursales para horarios
                    if (typeof window.sincronizarSucursalesEdicion === 'function') {
                        console.log('🔄 Sincronizando sucursales...');
                        window.sincronizarSucursalesEdicion();
                    }
                    
                    // 2. Esperar un poco más y cargar horarios
                    setTimeout(async () => {
                        console.log('📅 Cargando horarios del servidor...');
                        
                        if (typeof window.cargarHorariosExistentesDelServidor === 'function') {
                            try {
                                const horarios = await window.cargarHorariosExistentesDelServidor(idDoctor);
                                console.log('📅 Horarios cargados:', horarios);
                                
                                if (horarios && horarios.length > 0) {
                                    // Si hay una sucursal seleccionada, mostrar sus horarios
                                    const sucursalActual = $('#editarSucursalHorarios').val();
                                    if (sucursalActual) {
                                        console.log(`📅 Mostrando horarios para sucursal: ${sucursalActual}`);
                                        if (typeof window.mostrarHorariosSucursalEditar === 'function') {
                                            window.mostrarHorariosSucursalEditar(sucursalActual);
                                        }
                                    }
                                }
                            } catch (error) {
                                console.error('❌ Error cargando horarios:', error);
                            }
                        } else {
                            console.error('❌ Función cargarHorariosExistentesDelServidor no disponible');
                        }
                    }, 500);
                }, 300);
                
            } else {
                console.error('❌ Error en respuesta:', response);
                Swal.fire('Error', response.message || 'No se pudo cargar la información del doctor', 'error');
                $('#editarDoctorModal').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX cargando datos:', {xhr, status, error});
            Swal.fire('Error', 'Error de conexión al cargar los datos', 'error');
            $('#editarDoctorModal').modal('hide');
        }
    });
}

/**
* Editar doctor CON HORARIOS
*/
function editarDoctor(e) {
   e.preventDefault();
   
   if (!validarFormulario('formEditarDoctor')) {
       return;
   }
   
   // Obtener sucursales seleccionadas
   const sucursalesSeleccionadas = [];
   $('#sucursalesEditar input[type="checkbox"]:checked').each(function() {
       sucursalesSeleccionadas.push($(this).val());
   });
   
   // Validar que tenga al menos una sucursal
   if (sucursalesSeleccionadas.length === 0) {
       Swal.fire({
           icon: 'warning',
           title: 'Sucursales requeridas',
           text: 'Debe seleccionar al menos una sucursal para el doctor'
       });
       return;
   }
   
   // 🕒 OBTENER HORARIOS ACTUALIZADOS
   console.log('📦 === DEBUG HORARIOS EN EDICIÓN ===');
   
   let horarios = [];
   if (typeof window.obtenerHorariosParaEnvio === 'function') {
       horarios = window.obtenerHorariosParaEnvio();
       console.log('✅ Horarios obtenidos para actualizar:', horarios);
   } else {
       console.log('❌ Función obtenerHorariosParaEnvio no disponible');
   }
   
   console.log(`📋 Total horarios a actualizar: ${horarios.length}`);
   
   // Crear FormData
   const formData = new FormData(this);
   formData.append('action', 'actualizar');
   formData.append('submenu_id', config.submenuId);
   
   // Agregar sucursales al FormData
   sucursalesSeleccionadas.forEach(suc => {
       formData.append('sucursales[]', suc);
   });
   
   // 🔥 AGREGAR HORARIOS COMO JSON
   const horariosJson = JSON.stringify(horarios);
   formData.append('horarios', horariosJson);
   
   console.log('📤 JSON de horarios enviado (edición):', horariosJson);
   
   if (config.debug) {
       console.log('📦 === DATOS A ENVIAR (EDICIÓN) ===');
       for (let pair of formData.entries()) {
           console.log(`${pair[0]}: ${pair[1]}`);
       }
       console.log('🏥 Sucursales:', sucursalesSeleccionadas);
       console.log('🕒 Horarios:', horarios);
   }
   
   // Deshabilitar botón de envío
   const submitBtn = $(this).find('button[type="submit"]');
   const textoOriginal = submitBtn.html();
   submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Actualizando...');
   
   $.ajax({
       url: config.baseUrl,
       method: 'POST',
       data: formData,
       processData: false,
       contentType: false,
       dataType: 'json',
       success: function(response) {
           console.log('📥 Respuesta del servidor (edición):', response);
           
           if (response.success) {
               Swal.fire({
                   icon: 'success',
                   title: '¡Doctor actualizado!',
                   html: `
                       <div class="text-start">
                           <p><strong>Doctor actualizado exitosamente</strong></p>
                           <ul class="list-unstyled">
                               <li><i class="bi bi-person-check text-success me-1"></i> Información personal actualizada</li>
                               <li><i class="bi bi-building text-primary me-1"></i> ${sucursalesSeleccionadas.length} sucursal(es) asignada(s)</li>
                               <li><i class="bi bi-clock text-info me-1"></i> ${horarios.length} horario(s) configurado(s)</li>
                           </ul>
                           <small class="text-muted">${response.message}</small>
                       </div>
                   `,
                   timer: 4000,
                   showConfirmButton: true,
                   confirmButtonText: 'Entendido'
               }).then(() => {
                   $('#editarDoctorModal').modal('hide');
                   cargarDoctoresPaginados(paginaActual);
                   cargarEstadisticas();
               });
           } else {
               Swal.fire({
                   icon: 'error',
                   title: 'Error al actualizar doctor',
                   text: response.message || 'Error desconocido'
               });
           }
       },
       error: function(xhr, status, error) {
           console.error('❌ Error AJAX (edición):', {status, error, response: xhr.responseText});
           Swal.fire({
               icon: 'error',
               title: 'Error de conexión',
               text: 'No se pudo conectar con el servidor. Intente nuevamente.'
           });
       },
       complete: function() {
           // Rehabilitar botón
           submitBtn.prop('disabled', false).html(textoOriginal);
       }
   });
}
/**
 * Marcar sucursales seleccionadas en edición
 */
function marcarSucursalesSeleccionadas(sucursalesAsignadas) {
    // Limpiar todas las selecciones
    $('#sucursalesEditar input[type="checkbox"]').prop('checked', false);
    
    // Marcar las sucursales asignadas
    if (sucursalesAsignadas && sucursalesAsignadas.length > 0) {
        sucursalesAsignadas.forEach(suc => {
            $(`#sucursalesEditar input[value="${suc.id_sucursal}"]`).prop('checked', true);
        });
    }
}


/**
 * Ver detalles de doctor
 */
function verDoctor(idDoctor) {
    if (config.debug) {
        console.log('Viendo detalles de doctor:', idDoctor);
    }
    
    // Mostrar loading en el modal
    $('#contenidoVerDoctor').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando información...</p>
        </div>
    `);
    
    $('#verDoctorModal').modal('show');
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerPorId',
            id: idDoctor,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const doctor = response.data;
                mostrarDetallesDoctor(doctor);
            } else {
                $('#contenidoVerDoctor').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error al cargar la información del doctor
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando detalles:', {xhr, status, error});
            $('#contenidoVerDoctor').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error de conexión al cargar los detalles
                </div>
            `);
        }
    });
}

/**
 * Mostrar detalles del doctor en el modal
 */
function mostrarDetallesDoctor(doctor) {
    let estadoBadge = '';
    switch(parseInt(doctor.id_estado)) {
        case 1:
            estadoBadge = '<span class="badge bg-success fs-6">✅ Activo</span>';
            break;
        case 2:
            estadoBadge = '<span class="badge bg-danger fs-6">🚫 Bloqueado</span>';
            break;
        case 3:
            estadoBadge = '<span class="badge bg-warning fs-6">⏳ Pendiente</span>';
            break;
        case 4:
            estadoBadge = '<span class="badge bg-secondary fs-6">❌ Inactivo</span>';
            break;
        default:
            estadoBadge = '<span class="badge bg-light text-dark fs-6">❓ Desconocido</span>';
    }
    
    // Generar lista de sucursales
    let sucursalesHtml = '';
    if (doctor.sucursales && doctor.sucursales.length > 0) {
        sucursalesHtml = doctor.sucursales.map(suc => 
            `<span class="badge bg-info me-2 mb-2">
                <i class="bi bi-building me-1"></i>${suc.nombre_sucursal}
             </span>`
        ).join('');
    } else {
        sucursalesHtml = '<span class="text-muted">No hay sucursales asignadas</span>';
    }
    
    const html = `
        <div class="row g-3">
            <!-- Información Personal -->
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success bg-opacity-10">
                        <h6 class="mb-0 text-success">
                            <i class="bi bi-person me-2"></i>
                            Información Personal
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID:</strong> #${doctor.id_doctor}</p>
                                <p><strong>Nombres:</strong> ${doctor.nombres} ${doctor.apellidos}</p>
                                <p><strong>Cédula:</strong> ${doctor.cedula}</p>
                                <p><strong>Sexo:</strong> ${doctor.sexo === 'M' ? 'Masculino' : 'Femenino'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Usuario:</strong> ${doctor.username}</p>
                                <p><strong>Email:</strong> 
                                    <a href="mailto:${doctor.correo}" class="text-decoration-none">
                                        <i class="bi bi-envelope me-1"></i>${doctor.correo}
                                    </a>
                                </p>
                                <p><strong>Nacionalidad:</strong> ${doctor.nacionalidad}</p>
                                <p><strong>Estado:</strong> ${estadoBadge}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información Médica -->
            <div class="col-md-6">
                <div class="card border-primary h-100">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h6 class="mb-0 text-primary">
                            <i class="bi bi-journal-medical me-2"></i>
                            Información Médica
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Especialidad:</strong><br>
                           <span class="badge bg-primary">${doctor.nombre_especialidad}</span></p>
                       <p><strong>Título Profesional:</strong><br>
                          ${doctor.titulo_profesional || '<span class="text-muted">No especificado</span>'}
                       </p>
                   </div>
               </div>
           </div>
           
           <!-- Estadísticas -->
           <div class="col-md-6">
               <div class="card border-warning h-100">
                   <div class="card-header bg-warning bg-opacity-10">
                       <h6 class="mb-0 text-warning">
                           <i class="bi bi-graph-up me-2"></i>
                           Estadísticas
                       </h6>
                   </div>
                   <div class="card-body">
                       <div class="row text-center">
                           <div class="col-6">
                               <h4 class="text-primary mb-1">${doctor.total_citas || 0}</h4>
                               <small class="text-muted">Citas atendidas</small>
                           </div>
                           <div class="col-6">
                               <h4 class="text-success mb-1">${doctor.total_sucursales || 0}</h4>
                               <small class="text-muted">Sucursales asignadas</small>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
           
           <!-- Sucursales Asignadas -->
           <div class="col-12">
               <div class="card border-info">
                   <div class="card-header bg-info bg-opacity-10">
                       <h6 class="mb-0 text-info">
                           <i class="bi bi-building me-2"></i>
                           Sucursales Asignadas
                       </h6>
                   </div>
                   <div class="card-body">
                       ${sucursalesHtml}
                   </div>
               </div>
           </div>
       </div>
   `;
   
   $('#contenidoVerDoctor').html(html);
}

/**
* Cambiar estado de doctor
*/
function cambiarEstado(idDoctor, nuevoEstado) {
   const estadosTexto = {
       1: 'activar',
       2: 'bloquear',
       3: 'poner en estado pendiente',
       4: 'desactivar'
   };
   
   const estadosTitulo = {
       1: 'Activar Doctor',
       2: 'Bloquear Doctor', 
       3: 'Estado Pendiente',
       4: 'Desactivar Doctor'
   };
   
   const estadoTexto = estadosTexto[nuevoEstado] || 'cambiar estado';
   const estadoTitulo = estadosTitulo[nuevoEstado] || 'Cambiar Estado';
   
   Swal.fire({
       title: estadoTitulo,
       text: `¿Está seguro que desea ${estadoTexto} a este doctor?`,
       icon: 'question',
       showCancelButton: true,
       confirmButtonColor: nuevoEstado === 1 ? '#28a745' : nuevoEstado === 2 ? '#dc3545' : '#ffc107',
       cancelButtonColor: '#6c757d',
       confirmButtonText: `Sí, ${estadoTexto}`,
       cancelButtonText: 'Cancelar'
   }).then((result) => {
       if (result.isConfirmed) {
           $.ajax({
               url: config.baseUrl,
               method: 'POST',
               data: {
                   action: 'cambiarEstado',
                   id: idDoctor,
                   estado: nuevoEstado,
                   submenu_id: config.submenuId
               },
               dataType: 'json',
               success: function(response) {
                   if (config.debug) {
                       console.log('Respuesta cambiar estado:', response);
                   }
                   
                   if (response.success) {
                       Swal.fire({
                           icon: 'success',
                           title: 'Estado actualizado',
                           text: response.message,
                           timer: 2000,
                           showConfirmButton: false,
                           toast: true,
                           position: 'top-end'
                       });
                       cargarDoctoresPaginados(paginaActual);
                       cargarEstadisticas();
                   } else {
                       Swal.fire({
                           icon: 'error',
                           title: 'Error',
                           text: response.message || 'Error al cambiar estado'
                       });
                   }
               },
               error: function(xhr, status, error) {
                   console.error('Error cambiando estado:', {xhr, status, error});
                   Swal.fire({
                       icon: 'error',
                       title: 'Error de conexión',
                       text: 'No se pudo cambiar el estado'
                   });
               }
           });
       }
   });
}

/**
* Eliminar doctor
*/
function eliminarDoctor(idDoctor) {
   Swal.fire({
       title: '⚠️ Eliminar Doctor',
       text: '¿Está seguro que desea eliminar este doctor? Esta acción desactivará su cuenta permanentemente.',
       icon: 'warning',
       showCancelButton: true,
       confirmButtonColor: '#dc3545',
       cancelButtonColor: '#6c757d',
       confirmButtonText: 'Sí, eliminar',
       cancelButtonText: 'Cancelar',
       reverseButtons: true
   }).then((result) => {
       if (result.isConfirmed) {
           $.ajax({
               url: config.baseUrl,
               method: 'POST',
               data: {
                   action: 'eliminar',
                   id: idDoctor,
                   submenu_id: config.submenuId
               },
               dataType: 'json',
               success: function(response) {
                   if (config.debug) {
                       console.log('Respuesta eliminar:', response);
                   }
                   
                   if (response.success) {
                       Swal.fire({
                           icon: 'success',
                           title: 'Doctor eliminado',
                           text: response.message,
                           timer: 2000,
                           showConfirmButton: false
                       });
                       cargarDoctoresPaginados(paginaActual);
                       cargarEstadisticas();
                   } else {
                       Swal.fire({
                           icon: 'error',
                           title: 'No se pudo eliminar',
                           text: response.message || 'Error al eliminar doctor'
                       });
                   }
               },
               error: function(xhr, status, error) {
                   console.error('Error eliminando doctor:', {xhr, status, error});
                   Swal.fire({
                       icon: 'error',
                       title: 'Error de conexión',
                       text: 'No se pudo eliminar el doctor'
                   });
               }
           });
       }
   });
}

// ===== FUNCIONES DE CONTRASEÑAS =====

/**
* Generar contraseña inicial
*/
function generarPasswordInicial() {
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'generarPassword',
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               passwordGenerada = response.password;
               $('#passwordDisplay').text(passwordGenerada);
           }
       },
       error: function() {
           // Fallback: generar contraseña simple
           passwordGenerada = 'MediSys' + Math.floor(Math.random() * 10000);
           $('#passwordDisplay').text(passwordGenerada);
       }
   });
}

/**
* Generar nueva contraseña manualmente
*/
function generarNuevaPassword() {
   const btn = $('#generarPassword');
   const originalIcon = btn.html();
   
   btn.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'generarPassword',
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               passwordGenerada = response.password;
               $('#passwordDisplay').text(passwordGenerada);
               
               // Animación de éxito
               $('#passwordDisplay').addClass('bg-success text-white');
               setTimeout(() => {
                   $('#passwordDisplay').removeClass('bg-success text-white');
               }, 1000);
           } else {
               mostrarError('Error al generar contraseña');
           }
       },
       error: function() {
           mostrarError('Error de conexión al generar contraseña');
       },
       complete: function() {
           btn.html(originalIcon).prop('disabled', false);
       }
   });
}

// ===== FUNCIONES DE VALIDACIÓN =====

/**
* Generar username automático
*/
function generarUsernameAutomatico(esEdicion = false) {
   const isEditing = esEdicion || $(this).closest('#editarDoctorModal').length > 0;
   
   let nombres, apellidos;
   
   if (isEditing) {
       nombres = $('#editarNombres').val().trim();
       apellidos = $('#editarApellidos').val().trim();
   } else {
       nombres = $('#nombres').val().trim();
       apellidos = $('#apellidos').val().trim();
   }
   
   if (nombres && apellidos) {
       // Tomar primera parte del nombre y apellido
       const primerNombre = nombres.split(' ')[0].toLowerCase();
       const primerApellido = apellidos.split(' ')[0].toLowerCase();
       
       // Remover acentos y caracteres especiales
       const username = (primerNombre + '.' + primerApellido)
           .normalize("NFD")
           .replace(/[\u0300-\u036f]/g, "")
           .replace(/[^a-z.]/g, "");
       
       if (isEditing) {
           $('#editarUsername').val(username);
       } else {
           $('#username').val(username);
       }
   }
}

/**
* Validar cédula
*/
function validarCedula(esEdicion = false) {
   const campo = $(this);
   const cedula = campo.val().trim();
   
   // Limpiar validaciones anteriores
   campo.removeClass('is-invalid is-valid');
   campo.next('.invalid-feedback').remove();
   
   if (!cedula) {
       return;
   }
   
   // Validación básica de formato
   if (!/^\d{10}$/.test(cedula)) {
       mostrarErrorCampo(campo[0], 'La cédula debe tener exactamente 10 dígitos');
       return;
   }
   
   // Verificar si ya existe
   const isEditing = esEdicion || campo.closest('#editarDoctorModal').length > 0;
   const idExcluir = isEditing ? $('#editarIdDoctor').val() : null;
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'verificarCedula',
           cedula: cedula,
           id_excluir: idExcluir,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               if (response.existe) {
                   mostrarErrorCampo(campo[0], 'Ya existe un doctor con esta cédula');
               } else {
                   campo.removeClass('is-invalid').addClass('is-valid');
               }
           }
       },
       error: function() {
           console.warn('No se pudo verificar la cédula');
       }
   });
}

/**
* Validar username
*/
function validarUsername(esEdicion = false) {
   const campo = $(this);
   const username = campo.val().trim();
   
   // Limpiar validaciones anteriores
   campo.removeClass('is-invalid is-valid');
   campo.next('.invalid-feedback').remove();
   
   if (!username) {
       return;
   }
   
   // Validación básica de formato
   if (username.length < 3) {
       mostrarErrorCampo(campo[0], 'El username debe tener al menos 3 caracteres');
       return;
   }
   
   if (!/^[a-zA-Z0-9._-]+$/.test(username)) {
       mostrarErrorCampo(campo[0], 'Solo se permiten letras, números, puntos, guiones y guiones bajos');
       return;
   }
   
   // Verificar si ya existe
   const isEditing = esEdicion || campo.closest('#editarDoctorModal').length > 0;
   const idExcluir = isEditing ? $('#editarIdDoctor').val() : null;
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'verificarUsername',
           username: username,
           id_excluir: idExcluir,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               if (response.existe) {
                   mostrarErrorCampo(campo[0], 'Ya existe un usuario con este nombre de usuario');
               } else {
                   campo.removeClass('is-invalid').addClass('is-valid');
               }
           }
       },
       error: function() {
           console.warn('No se pudo verificar el username');
       }
   });
}

/**
* Validar correo
*/
function validarCorreo(esEdicion = false) {
   const campo = $(this);
   const correo = campo.val().trim();
   
   // Limpiar validaciones anteriores
   campo.removeClass('is-invalid is-valid');
   campo.next('.invalid-feedback').remove();
   
   if (!correo) {
       return;
   }
   
   // Validación básica de formato
   const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   if (!emailRegex.test(correo)) {
       mostrarErrorCampo(campo[0], 'Formato de correo electrónico inválido');
       return;
   }
   
   // Verificar si ya existe
   const isEditing = esEdicion || campo.closest('#editarDoctorModal').length > 0;
   const idExcluir = isEditing ? $('#editarIdDoctor').val() : null;
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'verificarCorreo',
           correo: correo,
           id_excluir: idExcluir,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               if (response.existe) {
                   mostrarErrorCampo(campo[0], 'Ya existe un usuario con este correo electrónico');
               } else {
                   campo.removeClass('is-invalid').addClass('is-valid');
               }
           }
       },
       error: function() {
           console.warn('No se pudo verificar el correo');
       }
   });
}

/**
* Validar formulario completo
*/
function validarFormulario(formId) {
   const form = document.getElementById(formId);
   if (!form) return false;
   
   let esValido = true;
   
   // Limpiar validaciones anteriores
   $(form).find('.is-invalid').removeClass('is-invalid');
   $(form).find('.invalid-feedback').remove();
   
   // Validar campos requeridos
   const camposRequeridos = form.querySelectorAll('input[required], select[required]');
   
   camposRequeridos.forEach(campo => {
       if (!campo.value.trim()) {
           mostrarErrorCampo(campo, 'Este campo es requerido');
           esValido = false;
       }
   });
   
   // Validar que al menos una sucursal esté seleccionada
   const sucursalesSeleccionadas = $(form).find('input[name="sucursales[]"]:checked').length;
   if (sucursalesSeleccionadas === 0) {
       Swal.fire({
           icon: 'warning',
           title: 'Sucursales requeridas',
           text: 'Debe asignar al menos una sucursal al doctor'
       });
       esValido = false;
   }
   
   return esValido;
}

/**
* Mostrar error en campo específico
*/
function mostrarErrorCampo(campo, mensaje) {
   const $campo = $(campo);
   $campo.addClass('is-invalid');
   
   // Remover mensaje anterior si existe
   $campo.next('.invalid-feedback').remove();
   
   // Agregar nuevo mensaje
   $campo.after(`<div class="invalid-feedback">${mensaje}</div>`);
}

// ===== FUNCIONES DE ESTADÍSTICAS =====

/**
* Cargar estadísticas
*/
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
           if (response.success && response.data) {
               const datos = response.data;
               
               // Actualizar las tarjetas con animación
               animarContador('#total-activos', datos.doctores_activos || 0);
               animarContador('#total-especialidades', datos.especialidades_cubiertas || 0);
               animarContador('#total-sucursales', datos.sucursales_con_doctores || 0);
               animarContador('#total-doctores', datos.total_doctores || 0);
               
               if (config.debug) {
                   console.log('Estadísticas actualizadas:', datos);
               }
           } else {
               console.warn('No se pudieron cargar las estadísticas');
               $('#total-activos, #total-especialidades, #total-sucursales, #total-doctores').text('--');
           }
       },
       error: function(xhr, status, error) {
           console.error('Error cargando estadísticas:', {xhr, status, error});
           $('#total-activos, #total-especialidades, #total-sucursales, #total-doctores').text('--');
       }
   });
}

/**
* Animar contador en las estadísticas
*/
function animarContador(selector, valorFinal) {
   const elemento = $(selector);
   const valorInicial = 0;
   const duracion = 1000;
   const incremento = valorFinal / (duracion / 50);
   
   let valorActual = valorInicial;
   
   const timer = setInterval(() => {
       valorActual += incremento;
       if (valorActual >= valorFinal) {
           valorActual = valorFinal;
           clearInterval(timer);
       }
       elemento.text(Math.floor(valorActual));
   }, 50);
}

// ===== FUNCIONES DE PAGINACIÓN =====

/**
* Actualizar información de la tabla
*/
function actualizarInfoTabla(response) {
   const inicio = ((response.paginaActual - 1) * registrosPorPagina) + 1;
   const fin = Math.min(inicio + response.mostrando - 1, response.totalRegistros);
   
   let info = '';
   if (response.totalRegistros > 0) {
       info = `Mostrando <strong>${inicio}</strong> a <strong>${fin}</strong> de <strong>${response.totalRegistros}</strong> doctores`;
       
       if (busquedaActual || filtroEstadoActual || filtroEspecialidadActual || filtroSucursalActual) {
           info += ` (filtrados)`;
       }
   } else {
       info = 'No se encontraron doctores';
       if (busquedaActual || filtroEstadoActual || filtroEspecialidadActual || filtroSucursalActual) {
           info += ' con los filtros aplicados';
       }
   }
   
   $('#infoTabla span').html(info);
   $('#infoRegistros').html(info);
   
   // Actualizar variables globales
   totalPaginas = response.totalPaginas;
   totalRegistros = response.totalRegistros;
}

/**
* Actualizar paginación
*/
function actualizarPaginacion(response) {
   const paginacion = $('#paginacion');
   
   if (response.totalPaginas <= 1) {
       paginacion.empty();
       return;
   }
   
   let html = '';
   
   // Botón anterior
   const anteriorDisabled = response.paginaActual === 1 ? 'disabled' : '';
   html += `
       <li class="page-item ${anteriorDisabled}">
           <a class="page-link" href="#" ${response.paginaActual > 1 ? `onclick="cargarDoctoresPaginados(${response.paginaActual - 1})"` : ''} aria-label="Anterior">
               <i class="bi bi-chevron-left"></i>
           </a>
       </li>
   `;
   
   // Páginas numeradas
   const maxPaginas = window.innerWidth > 768 ? 7 : 5;
   let inicio = Math.max(1, response.paginaActual - Math.floor(maxPaginas / 2));
   let fin = Math.min(response.totalPaginas, inicio + maxPaginas - 1);
   
   if (fin - inicio + 1 < maxPaginas) {
       inicio = Math.max(1, fin - maxPaginas + 1);
   }
   
   // Primera página si no está en el rango
   if (inicio > 1) {
       html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarDoctoresPaginados(1)">1</a></li>`;
       if (inicio > 2) {
           html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
       }
   }
   
   // Páginas del rango
   for (let i = inicio; i <= fin; i++) {
       const active = i === response.paginaActual ? 'active' : '';
       html += `
           <li class="page-item ${active}">
               <a class="page-link" href="#" onclick="cargarDoctoresPaginados(${i})">${i}</a>
           </li>
       `;
   }
   
   // Última página si no está en el rango
   if (fin < response.totalPaginas) {
       if (fin < response.totalPaginas - 1) {
           html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
       }
       html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarDoctoresPaginados(${response.totalPaginas})">${response.totalPaginas}</a></li>`;
   }
   
   // Botón siguiente
   const siguienteDisabled = response.paginaActual === response.totalPaginas ? 'disabled' : '';
   html += `
       <li class="page-item ${siguienteDisabled}">
           <a class="page-link" href="#" ${response.paginaActual < response.totalPaginas ? `onclick="cargarDoctoresPaginados(${response.paginaActual + 1})"` : ''} aria-label="Siguiente">
               <i class="bi bi-chevron-right"></i>
           </a>
       </li>
   `;
   
   paginacion.html(html);
}

// ===== FUNCIONES AUXILIARES =====

/**
* Limpiar filtros
*/
function limpiarFiltros() {
   $('#busquedaGlobal').val('');
   $('#filtroEstado').val('');
   $('#filtroEspecialidad').val('');
   $('#filtroSucursal').val('');
   $('#registrosPorPagina').val('10');
   
   busquedaActual = '';
   filtroEstadoActual = '';
   filtroEspecialidadActual = '';
   filtroSucursalActual = '';
   registrosPorPagina = 10;
   
   cargarDoctoresPaginados(1);
   
   Swal.fire({
       icon: 'info',
       title: 'Filtros limpiados',
       text: 'Se han restablecido todos los filtros',
       timer: 1500,
       showConfirmButton: false,
       toast: true,
       position: 'top-end'
   });
}

/**
* Mostrar mensaje de error
*/
function mostrarError(mensaje) {
   Swal.fire({
       icon: 'error',
       title: 'Error',
       text: mensaje
   });
}

/**
* Mostrar mensaje de éxito
*/
function mostrarExito(mensaje) {
   Swal.fire({
       icon: 'success',
       title: 'Éxito',
       text: mensaje,
       timer: 2000,
       showConfirmButton: false
   });
}

// ===== FUNCIONES GLOBALES PARA ONCLICK =====
window.cargarDoctoresPaginados = cargarDoctoresPaginados;
window.abrirModalEditar = abrirModalEditar;
window.verDoctor = verDoctor;
window.cambiarEstado = cambiarEstado;
window.eliminarDoctor = eliminarDoctor;
window.limpiarFiltros = limpiarFiltros;

// ===== DEBUG INFO =====
if (config.debug) {
   console.log('🏥 Sistema de Gestión de Doctores cargado correctamente');
   console.log('Configuración:', config);
   
   window.doctoresDebug = {
       config,
       cargarDoctores: () => cargarDoctoresPaginados(paginaActual),
       cargarEstadisticas,
       variables: () => ({
           paginaActual,
           registrosPorPagina,
           busquedaActual,
           filtroEstadoActual,
           filtroEspecialidadActual,
           filtroSucursalActual,
           totalPaginas,
           totalRegistros,
           passwordGenerada
       })
   };
}