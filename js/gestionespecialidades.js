/**
 * Sistema de Gestión de Especialidades con Sucursales
 * Autor: Sistema MediSys
 * Descripción: CRUD completo para especialidades con asignación de sucursales
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: true,
    submenuId: window.especialidadesConfig?.submenuId || null,
    permisos: window.especialidadesConfig?.permisos || {},
    baseUrl: '../../controladores/EspecialidadesControlador/EspecialidadesController.php'
};

// Variables globales
let paginaActual = 1;
let registrosPorPagina = 10;
let busquedaActual = '';
let totalPaginas = 0;
let totalRegistros = 0;
let especialidadSeleccionadaParaEliminar = null;

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🏥 Iniciando Sistema de Gestión de Especialidades');
    
    if (config.debug) {
        console.log('🔧 Configuración cargada:', config);
    }

    inicializarEventos();
    inicializarValidaciones();
    cargarEspecialidadesPaginadas(1);
    cargarEstadisticas();
    
    console.log('✅ Sistema de especialidades inicializado');
});

// ===== EVENTOS PRINCIPALES =====
function inicializarEventos() {
    console.log('🎯 Inicializando eventos...');
    
    // Formularios principales
    $('#formCrearEspecialidad').on('submit', crearEspecialidad);
    $('#formEditarEspecialidad').on('submit', editarEspecialidad);
    
    // Búsqueda con debounce
    let timeoutBusqueda;
    $('#busquedaGlobal').on('input', function() {
        clearTimeout(timeoutBusqueda);
        const valor = $(this).val().trim();
        
        timeoutBusqueda = setTimeout(() => {
            busquedaActual = valor;
            cargarEspecialidadesPaginadas(1);
        }, 300);
    });
    
    // Controles de tabla
    $('#registrosPorPagina').on('change', function() {
        registrosPorPagina = parseInt($(this).val());
        cargarEspecialidadesPaginadas(1);
    });
    
    $('#refrescarTabla').on('click', function() {
        cargarEspecialidadesPaginadas(paginaActual);
    });
    
    // Paginación
    $(document).on('click', '#paginacion a', function(e) {
        e.preventDefault();
        const pagina = $(this).data('pagina');
        if (pagina && pagina !== paginaActual) {
            cargarEspecialidadesPaginadas(pagina);
        }
    });
    
    // Modal de eliminación
    $('#btnConfirmarEliminar').on('click', confirmarEliminarEspecialidad);
    
    // Eventos de sucursales en modal CREAR
    $('#seleccionarTodasSucursales').on('click', function() {
        $('.sucursal-checkbox').prop('checked', true);
        actualizarContadorSucursales('crear');
    });
    
    $('#deseleccionarTodasSucursales').on('click', function() {
        $('.sucursal-checkbox').prop('checked', false);
        actualizarContadorSucursales('crear');
    });
    
    // Eventos de sucursales en modal EDITAR
    $('#editarSeleccionarTodasSucursales').on('click', function() {
        $('.editar-sucursal-checkbox').prop('checked', true);
        actualizarContadorSucursales('editar');
    });
    
    $('#editarDeseleccionarTodasSucursales').on('click', function() {
        $('.editar-sucursal-checkbox').prop('checked', false);
        actualizarContadorSucursales('editar');
    });
    
    // Cambios en checkboxes individuales
    $(document).on('change', '.sucursal-checkbox', function() {
        actualizarContadorSucursales('crear');
    });
    
    $(document).on('change', '.editar-sucursal-checkbox', function() {
        actualizarContadorSucursales('editar');
    });
    
    // Limpiar modales al cerrarlos
    $('#crearEspecialidadModal').on('hidden.bs.modal', limpiarModalCrear);
    $('#editarEspecialidadModal').on('hidden.bs.modal', limpiarModalEditar);
    
    console.log('✅ Eventos inicializados');
}

// ===== VALIDACIONES =====
function inicializarValidaciones() {
    console.log('📋 Inicializando validaciones...');
    
    // Validación en tiempo real para nombre de especialidad
    $('#nombre_especialidad').on('input', validarNombreEspecialidad);
    $('#editarNombreEspecialidad').on('input', validarNombreEspecialidad);
    
    // Contador de caracteres para descripción
    $('#descripcion').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        $(this).next('.form-text').html(`
            <i class="bi bi-info-circle me-1"></i>
            ${currentLength}/${maxLength} caracteres
        `);
    });
    
    $('#editarDescripcion').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        $(this).next('.form-text').html(`
            <i class="bi bi-info-circle me-1"></i>
            ${currentLength}/${maxLength} caracteres
        `);
    });
    
    console.log('✅ Validaciones inicializadas');
}

function validarNombreEspecialidad() {
    const campo = $(this);
    const valor = campo.val().trim();
    
    // Limpiar validaciones anteriores
    campo.removeClass('is-invalid is-valid');
    campo.next('.invalid-feedback').remove();
    
    if (!valor) {
        return;
    }
    
    // Validaciones básicas
    if (valor.length < 3) {
        mostrarErrorCampo(campo[0], 'El nombre debe tener al menos 3 caracteres');
        return;
    }
    
    if (valor.length > 100) {
        mostrarErrorCampo(campo[0], 'El nombre no puede exceder 100 caracteres');
        return;
    }
    
    // Verificar si ya existe (solo si no estamos editando la misma especialidad)
    const isEditing = campo.closest('#editarEspecialidadModal').length > 0;
    const idExcluir = isEditing ? $('#editarIdEspecialidad').val() : null;
    
    verificarNombreDisponible(valor, idExcluir).then(disponible => {
        if (disponible) {
            campo.removeClass('is-invalid').addClass('is-valid');
        } else {
            mostrarErrorCampo(campo[0], 'Ya existe una especialidad con este nombre');
        }
    });
}

async function verificarNombreDisponible(nombre, idExcluir = null) {
    try {
        const params = new URLSearchParams({
            action: 'verificarNombre',
            nombre: nombre,
            submenu_id: config.submenuId
        });
        
        if (idExcluir) {
            params.append('id_excluir', idExcluir);
        }
        
        const response = await fetch(`${config.baseUrl}?${params}`);
        const result = await response.json();
        
        return result.success ? result.disponible : true;
    } catch (error) {
        console.warn('Error verificando nombre:', error);
        return true; // En caso de error, asumimos que está disponible
    }
}

function mostrarErrorCampo(campo, mensaje) {
    const $campo = $(campo);
    $campo.addClass('is-invalid');
    
    // Remover mensaje anterior
    $campo.next('.invalid-feedback').remove();
    
    // Agregar nuevo mensaje
    $campo.after(`<div class="invalid-feedback">${mensaje}</div>`);
}

// ===== FUNCIONES PRINCIPALES =====

// ===== CARGAR ESPECIALIDADES PAGINADAS =====
function cargarEspecialidadesPaginadas(pagina = 1) {
    paginaActual = pagina;
    
    const parametros = {
        action: 'obtenerEspecialidadesPaginadas',
        pagina: pagina,
        limit: registrosPorPagina,
        busqueda: busquedaActual,
        submenu_id: config.submenuId
    };
    
    if (config.debug) {
        console.log('📥 Cargando especialidades con parámetros:', parametros);
    }
    
    // Mostrar loading
    $('#especialidades-container').html(`
        <tr>
            <td colspan="6" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3 text-muted">Cargando especialidades...</p>
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
                console.log('📥 Respuesta del servidor:', response);
            }
            
            if (response.success) {
                mostrarEspecialidades(response.data);
                actualizarPaginacion(response);
                actualizarInfoTabla(response);
            } else {
                mostrarError('Error al cargar especialidades: ' + response.message);
                mostrarErrorEnTabla();
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX:', {xhr, status, error});
            mostrarError('Error de conexión al cargar especialidades');
            mostrarErrorEnTabla();
        }
    });
}

function mostrarEspecialidades(especialidades) {
    const tbody = $('#especialidades-container');
    
    if (!especialidades || especialidades.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron especialidades</p>
                    ${busquedaActual ? 
                        '<button class="btn btn-outline-secondary btn-sm" onclick="limpiarBusqueda()">Limpiar búsqueda</button>' : 
                        ''
                    }
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    
    especialidades.forEach(especialidad => {
        // Descripción truncada
        const descripcionCorta = especialidad.descripcion ? 
            (especialidad.descripcion.length > 80 ? 
                especialidad.descripcion.substring(0, 80) + '...' : 
                especialidad.descripcion) : 
            '<span class="text-muted fst-italic">Sin descripción</span>';
        
        // Badges de sucursales y doctores
        const badgeSucursales = `
            <span class="badge bg-info">
                <i class="bi bi-building me-1"></i>
                ${especialidad.total_sucursales || 0}
            </span>
        `;
        
        const badgeDoctores = `
            <span class="badge bg-success">
                <i class="bi bi-people me-1"></i>
                ${especialidad.total_doctores || 0}
            </span>
        `;
        
        // Botones de acción
        let botones = '';
        
        // Botón ver (siempre disponible)
        botones += `
            <button class="btn btn-outline-info btn-sm me-1" onclick="verEspecialidad(${especialidad.id_especialidad})" 
                    title="Ver detalles">
                <i class="bi bi-eye"></i>
            </button>
        `;
        
        // Botón editar
        if (config.permisos.puede_editar) {
            botones += `
                <button class="btn btn-outline-warning btn-sm me-1" onclick="abrirModalEditar(${especialidad.id_especialidad})" 
                        title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
            `;
        }
        
        // Botón eliminar
        if (config.permisos.puede_eliminar) {
            botones += `
                <button class="btn btn-outline-danger btn-sm" onclick="confirmarEliminar(${especialidad.id_especialidad}, '${especialidad.nombre_especialidad.replace(/'/g, "\\'")}', ${especialidad.total_doctores || 0})" 
                        title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            `;
        }
        
        html += `
            <tr>
                <td class="text-center">
                    <span class="badge bg-primary">#${especialidad.id_especialidad}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="especialidad-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">${especialidad.nombre_especialidad}</h6>
                            <small class="text-muted">Especialidad médica</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-muted">${descripcionCorta}</span>
                </td>
                <td class="text-center">${badgeSucursales}</td>
                <td class="text-center">${badgeDoctores}</td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        ${botones}
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

function mostrarErrorEnTabla() {
    $('#especialidades-container').html(`
        <tr>
            <td colspan="6" class="text-center py-5">
                <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3"></i>
                <p class="text-muted">Error al cargar los datos</p>
                <button class="btn btn-outline-primary btn-sm" onclick="cargarEspecialidadesPaginadas(${paginaActual})">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                </button>
            </td>
        </tr>
    `);
}

// ===== CREAR ESPECIALIDAD =====
function crearEspecialidad(e) {
    e.preventDefault();
    
    console.log('💾 Creando nueva especialidad...');
    
    if (!validarFormulario('formCrearEspecialidad')) {
        return;
    }
    
    const form = document.getElementById('formCrearEspecialidad');
    const formData = new FormData(form);
    
    // Agregar datos adicionales
    formData.append('action', 'crear');
    formData.append('submenu_id', config.submenuId);
    
    // Obtener sucursales seleccionadas
    const sucursalesSeleccionadas = [];
    $('.sucursal-checkbox:checked').each(function() {
        sucursalesSeleccionadas.push($(this).val());
    });
    
    // Agregar sucursales al FormData
    sucursalesSeleccionadas.forEach(sucursal => {
        formData.append('sucursales[]', sucursal);
    });
    
    const submitBtn = $('#formCrearEspecialidad button[type="submit"]');
    const textoOriginal = submitBtn.html();
    
    // Deshabilitar botón y mostrar loading
    submitBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm me-1"></span>
        Creando...
    `);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('📥 Respuesta crear:', response);
            }
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                $('#crearEspecialidadModal').modal('hide');
                cargarEspecialidadesPaginadas(1);
                cargarEstadisticas();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX crear:', {xhr, status, error});
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

// ===== EDITAR ESPECIALIDAD =====
function abrirModalEditar(idEspecialidad) {
    console.log('🔄 Abriendo modal editar para ID:', idEspecialidad);
    
    // Limpiar formulario
    document.getElementById('formEditarEspecialidad').reset();
    $('.editar-sucursal-checkbox').prop('checked', false);
    
    // Mostrar modal
    $('#editarEspecialidadModal').modal('show');
    
    // Cargar datos de la especialidad
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerPorId',
            id: idEspecialidad,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('📥 Datos de especialidad:', response);
            }
            
            if (response.success && response.data) {
                const especialidad = response.data;
                
                // Llenar formulario
                $('#editarIdEspecialidad').val(especialidad.id_especialidad);
                $('#editarNombreEspecialidad').val(especialidad.nombre_especialidad);
                $('#editarDescripcion').val(especialidad.descripcion || '');
                
                // Marcar sucursales asignadas
                if (especialidad.sucursales && Array.isArray(especialidad.sucursales)) {
                    especialidad.sucursales.forEach(sucursal => {
                        $(`#editar_sucursal_${sucursal.id_sucursal}`).prop('checked', true);
                    });
                }
                
                actualizarContadorSucursales('editar');
                console.log('✅ Datos cargados en modal de edición');
            } else {
                Swal.fire('Error', response.message || 'No se pudo cargar la información', 'error');
                $('#editarEspecialidadModal').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando datos:', {xhr, status, error});
            Swal.fire('Error', 'Error de conexión al cargar los datos', 'error');
            $('#editarEspecialidadModal').modal('hide');
        }
    });
}

function editarEspecialidad(e) {
    e.preventDefault();
    
    console.log('💾 Editando especialidad...');
    
    if (!validarFormulario('formEditarEspecialidad')) {
        return;
    }
    
    const form = document.getElementById('formEditarEspecialidad');
    const formData = new FormData(form);
    
    // Agregar datos adicionales
    formData.append('action', 'editar');
    formData.append('submenu_id', config.submenuId);
    
    // Obtener sucursales seleccionadas
    const sucursalesSeleccionadas = [];
    $('.editar-sucursal-checkbox:checked').each(function() {
        sucursalesSeleccionadas.push($(this).val());
    });
    
    // Agregar sucursales al FormData
    sucursalesSeleccionadas.forEach(sucursal => {
        formData.append('sucursales[]', sucursal);
    });
    
    const submitBtn = $('#formEditarEspecialidad button[type="submit"]');
    const textoOriginal = submitBtn.html();
    
    // Deshabilitar botón y mostrar loading
    submitBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm me-1"></span>
        Guardando...
    `);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('📥 Respuesta editar:', response);
            }
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                $('#editarEspecialidadModal').modal('hide');
                cargarEspecialidadesPaginadas(paginaActual);
                cargarEstadisticas();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX editar:', {xhr, status, error});
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

// ===== VER ESPECIALIDAD =====
function verEspecialidad(idEspecialidad) {
    console.log('👁️ Viendo detalles de especialidad ID:', idEspecialidad);
    
    $('#contenidoVerEspecialidad').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando detalles...</p>
        </div>
    `);
    
    $('#verEspecialidadModal').modal('show');
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerPorId',
            id: idEspecialidad,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const especialidad = response.data;
                
                let html = `
                    <div class="row g-4">
                        <!-- Información básica -->
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="especialidad-icon bg-primary text-white rounded-circle me-3">
                                            <i class="bi bi-hospital"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-1">${especialidad.nombre_especialidad}</h4>
                                            <span class="badge bg-primary">ID: ${especialidad.id_especialidad}</span>
                                        </div>
                                    </div>
                                    
                                    ${especialidad.descripcion ? `
                                        <div class="mt-3">
                                            <h6 class="text-muted mb-2">Descripción:</h6>
                                            <p class="mb-0">${especialidad.descripcion}</p>
                                        </div>
                                    ` : '<p class="text-muted fst-italic">Sin descripción disponible</p>'}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success bg-opacity-10">
                                    <h6 class="mb-0 text-success">
                                        <i class="bi bi-people me-2"></i>Doctores
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-1">${especialidad.total_doctores || 0}</h3>
                                    <small class="text-muted">Doctores especializados</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info bg-opacity-10">
                                    <h6 class="mb-0 text-info">
                                        <i class="bi bi-building me-2"></i>Sucursales
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <h3 class="text-info mb-1">${especialidad.total_sucursales || 0}</h3>
                                    <small class="text-muted">Sucursales asignadas</small>
                                </div>
                            </div>
                        </div>
                `;
                
                // Mostrar sucursales si las hay
                if (especialidad.sucursales && especialidad.sucursales.length > 0) {
                    html += `
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-building me-2"></i>
                                        Sucursales Asignadas (${especialidad.sucursales.length})
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                    `;
                    
                    especialidad.sucursales.forEach(sucursal => {
                        html += `
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-building text-primary me-2"></i>
                                    <div>
                                        <small class="fw-bold">${sucursal.nombre_sucursal}</small><br>
                                        <small class="text-muted">${sucursal.direccion}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Esta especialidad no está asignada a ninguna sucursal.
                            </div>
                        </div>
                    `;
                }
                
                html += '</div>';
                
                $('#contenidoVerEspecialidad').html(html);
            } else {
                $('#contenidoVerEspecialidad').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error al cargar los detalles de la especialidad.
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando detalles:', {xhr, status, error});
            $('#contenidoVerEspecialidad').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-wifi-off me-2"></i>
                    Error de conexión. No se pudieron cargar los detalles.
                </div>
            `);
        }
    });
}

// ===== ELIMINAR ESPECIALIDAD =====
function confirmarEliminar(idEspecialidad, nombreEspecialidad, totalDoctores) {
    especialidadSeleccionadaParaEliminar = idEspecialidad;
    
    $('#nombreEspecialidadEliminar').text(nombreEspecialidad);
    
    // Advertencia especial si tiene doctores
    if (totalDoctores > 0) {
        $('#eliminarEspecialidadModal .modal-body').append(`
            <div class="alert alert-danger mt-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>¡Atención!</strong> Esta especialidad tiene ${totalDoctores} doctor(es) asignado(s). 
                No se puede eliminar mientras tenga doctores asociados.
            </div>
        `);
        $('#btnConfirmarEliminar').prop('disabled', true).text('No se puede eliminar');
    } else {
        $('#btnConfirmarEliminar').prop('disabled', false).html(`
            <i class="bi bi-trash me-1"></i>Eliminar Especialidad
        `);
    }
    
    $('#eliminarEspecialidadModal').modal('show');
}

function confirmarEliminarEspecialidad() {
   if (!especialidadSeleccionadaParaEliminar) return;
   
   console.log('🗑️ Eliminando especialidad ID:', especialidadSeleccionadaParaEliminar);
   
   const submitBtn = $('#btnConfirmarEliminar');
   const textoOriginal = submitBtn.html();
   
   // Deshabilitar botón y mostrar loading
   submitBtn.prop('disabled', true).html(`
       <span class="spinner-border spinner-border-sm me-1"></span>
       Eliminando...
   `);
   
   $.ajax({
       url: config.baseUrl,
       type: 'POST',
       data: {
           action: 'eliminar',
           id: especialidadSeleccionadaParaEliminar,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (config.debug) {
               console.log('📥 Respuesta eliminar:', response);
           }
           
           if (response.success) {
               Swal.fire({
                   icon: 'success',
                   title: '¡Eliminado!',
                   text: response.message,
                   timer: 2000,
                   showConfirmButton: false
               });
               
               $('#eliminarEspecialidadModal').modal('hide');
               cargarEspecialidadesPaginadas(paginaActual);
               cargarEstadisticas();
           } else {
               Swal.fire({
                   icon: 'error',
                   title: 'Error',
                   text: response.message
               });
           }
       },
       error: function(xhr, status, error) {
           console.error('❌ Error AJAX eliminar:', {xhr, status, error});
           Swal.fire({
               icon: 'error',
               title: 'Error de conexión',
               text: 'No se pudo conectar con el servidor. Intente nuevamente.'
           });
       },
       complete: function() {
           // Rehabilitar botón
           submitBtn.prop('disabled', false).html(textoOriginal);
           especialidadSeleccionadaParaEliminar = null;
       }
   });
}

// ===== ESTADÍSTICAS =====
function cargarEstadisticas() {
   console.log('📊 Cargando estadísticas...');
   
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
               actualizarEstadisticas(response.data);
           } else {
               console.warn('❌ Error en estadísticas:', response.message);
               mostrarEstadisticasError();
           }
       },
       error: function(xhr, status, error) {
           console.error('❌ Error AJAX estadísticas:', {xhr, status, error});
           mostrarEstadisticasError();
       }
   });
}

function actualizarEstadisticas(datos) {
   console.log('📊 Actualizando estadísticas:', datos);
   
   // Animación de números
   animarNumero('#totalEspecialidades', datos.total_especialidades || 0);
   animarNumero('#especialidadesConDoctores', datos.con_doctores || 0);
   animarNumero('#sucursalesActivas', datos.sucursales_activas || 0);
   animarNumero('#totalDoctores', datos.total_doctores || 0);
   
   console.log('✅ Estadísticas actualizadas');
}

function mostrarEstadisticasError() {
   $('#totalEspecialidades').html('<span class="text-danger">Error</span>');
   $('#especialidadesConDoctores').html('<span class="text-danger">Error</span>');
   $('#sucursalesActivas').html('<span class="text-danger">Error</span>');
   $('#totalDoctores').html('<span class="text-danger">Error</span>');
}

function animarNumero(selector, objetivo) {
   const elemento = $(selector);
   const actual = parseInt(elemento.text()) || 0;
   
   if (actual === objetivo) return;
   
   let contador = actual;
   const incremento = objetivo > actual ? 1 : -1;
   const tiempo = Math.abs(objetivo - actual) > 20 ? 30 : 80;
   
   const timer = setInterval(() => {
       contador += incremento;
       elemento.text(contador);
       
       if (contador === objetivo) {
           clearInterval(timer);
       }
   }, tiempo);
}

// ===== PAGINACIÓN =====
function actualizarPaginacion(response) {
   const container = $('#paginacion');
   const { paginaActual, totalPaginas } = response;
   
   totalPaginas = response.totalPaginas;
   
   if (totalPaginas <= 1) {
       container.empty();
       return;
   }
   
   let html = '';
   
   // Botón anterior
   html += `
       <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
           <a class="page-link" href="javascript:void(0)" data-pagina="${paginaActual - 1}">
               <i class="bi bi-chevron-left"></i>
           </a>
       </li>
   `;
   
   // Páginas
   const inicioRango = Math.max(1, paginaActual - 2);
   const finRango = Math.min(totalPaginas, paginaActual + 2);
   
   if (inicioRango > 1) {
       html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-pagina="1">1</a></li>`;
       if (inicioRango > 2) {
           html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
       }
   }
   
   for (let i = inicioRango; i <= finRango; i++) {
       html += `
           <li class="page-item ${i === paginaActual ? 'active' : ''}">
               <a class="page-link" href="javascript:void(0)" data-pagina="${i}">${i}</a>
           </li>
       `;
   }
   
   if (finRango < totalPaginas) {
       if (finRango < totalPaginas - 1) {
           html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
       }
       html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-pagina="${totalPaginas}">${totalPaginas}</a></li>`;
   }
   
   // Botón siguiente
   html += `
       <li class="page-item ${paginaActual >= totalPaginas ? 'disabled' : ''}">
           <a class="page-link" href="javascript:void(0)" data-pagina="${paginaActual + 1}">
               <i class="bi bi-chevron-right"></i>
           </a>
       </li>
   `;
   
   container.html(html);
}

function actualizarInfoTabla(response) {
   const { paginaActual, totalPaginas, totalRegistros, registrosPorPagina } = response;
   
   const inicio = ((paginaActual - 1) * registrosPorPagina) + 1;
   const fin = Math.min(paginaActual * registrosPorPagina, totalRegistros);
   
   $('#infoRegistros').html(`
       <i class="bi bi-info-circle me-1"></i>
       Mostrando ${inicio} a ${fin} de ${totalRegistros} especialidades
   `);
   
   $('#infoPaginacion').html(`
       Página ${paginaActual} de ${totalPaginas}
   `);
}

// ===== FUNCIONES DE SUCURSALES =====
function actualizarContadorSucursales(tipo) {
   const selector = tipo === 'crear' ? '.sucursal-checkbox' : '.editar-sucursal-checkbox';
   const seleccionadas = $(`${selector}:checked`).length;
   const total = $(selector).length;
   
   // Aquí podrías agregar un contador visual si quisieras
   console.log(`📍 Sucursales seleccionadas (${tipo}): ${seleccionadas}/${total}`);
}

// ===== FUNCIONES DE UTILIDAD =====
function validarFormulario(formId) {
   const form = document.getElementById(formId);
   if (!form) return false;
   
   let esValido = true;
   
   // Limpiar validaciones anteriores
   $(form).find('.is-invalid').removeClass('is-invalid');
   $(form).find('.invalid-feedback').remove();
   
   // Validar campos requeridos
   const camposRequeridos = form.querySelectorAll('input[required], select[required], textarea[required]');
   
   camposRequeridos.forEach(campo => {
       if (!campo.value.trim()) {
           mostrarErrorCampo(campo, 'Este campo es requerido');
           esValido = false;
       }
   });
   
   // Validar que al menos una sucursal esté seleccionada
   const checkboxSelector = formId === 'formCrearEspecialidad' ? '.sucursal-checkbox' : '.editar-sucursal-checkbox';
   const sucursalesSeleccionadas = $(`${checkboxSelector}:checked`).length;
   
   if (sucursalesSeleccionadas === 0) {
       Swal.fire({
           icon: 'warning',
           title: 'Sucursales requeridas',
           text: 'Debe asignar al menos una sucursal a la especialidad'
       });
       esValido = false;
   }
   
   return esValido;
}

function limpiarModalCrear() {
   console.log('🧹 Limpiando modal crear...');
   
   // Limpiar formulario
   document.getElementById('formCrearEspecialidad').reset();
   
   // Desmarcar checkboxes
   $('.sucursal-checkbox').prop('checked', false);
   
   // Limpiar validaciones
   $('#formCrearEspecialidad .is-invalid').removeClass('is-invalid');
   $('#formCrearEspecialidad .invalid-feedback').remove();
   $('#formCrearEspecialidad .is-valid').removeClass('is-valid');
   
   actualizarContadorSucursales('crear');
}

function limpiarModalEditar() {
   console.log('🧹 Limpiando modal editar...');
   
   // Limpiar formulario
   document.getElementById('formEditarEspecialidad').reset();
   
   // Desmarcar checkboxes
   $('.editar-sucursal-checkbox').prop('checked', false);
   
   // Limpiar validaciones
   $('#formEditarEspecialidad .is-invalid').removeClass('is-invalid');
   $('#formEditarEspecialidad .invalid-feedback').remove();
   $('#formEditarEspecialidad .is-valid').removeClass('is-valid');
   
   actualizarContadorSucursales('editar');
}

function limpiarBusqueda() {
   $('#busquedaGlobal').val('');
   busquedaActual = '';
   cargarEspecialidadesPaginadas(1);
}

function mostrarError(mensaje) {
   Swal.fire({
       icon: 'error',
       title: 'Error',
       text: mensaje,
       timer: 3000,
       showConfirmButton: false
   });
}

// ===== FUNCIONES EXPORTADAS (para uso global) =====
window.verEspecialidad = verEspecialidad;
window.abrirModalEditar = abrirModalEditar;
window.confirmarEliminar = confirmarEliminar;
window.limpiarBusqueda = limpiarBusqueda;

console.log('🎯 JavaScript de especialidades cargado completamente');