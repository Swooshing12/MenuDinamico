/**
 * Sistema de Gestión de Especialidades
 * Autor: Sistema MediSys
 * Descripción: CRUD completo para gestión de especialidades médicas
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
    
    $('#limpiarBusqueda').on('click', limpiarBusqueda);
    $('#refrescarTabla').on('click', function() {
        cargarEspecialidadesPaginadas(paginaActual);
    });
    
    // Modal de eliminación
    $('#btnConfirmarEliminar').on('click', confirmarEliminarEspecialidad);
    
    // Editar desde ver detalles
    $('#btnEditarDesdeVer').on('click', function() {
        const idEspecialidad = $(this).data('id-especialidad');
        if (idEspecialidad) {
            $('#verEspecialidadModal').modal('hide');
            setTimeout(() => abrirModalEditar(idEspecialidad), 300);
        }
    });
    
    // Limpiar modales al cerrar
    $('#crearEspecialidadModal').on('hidden.bs.modal', limpiarModalCrear);
    $('#editarEspecialidadModal').on('hidden.bs.modal', limpiarModalEditar);
    
    console.log('✅ Eventos inicializados');
}

function inicializarValidaciones() {
    console.log('✅ Validaciones inicializadas');
    
    // Validación en tiempo real para nombre
    $('#nombre_especialidad, #editarNombreEspecialidad').on('input', function() {
        const valor = $(this).val().trim();
        
        if (valor.length > 0 && valor.length < 3) {
            $(this).addClass('is-invalid');
            mostrarTooltipError($(this), 'Mínimo 3 caracteres');
        } else if (valor.length > 100) {
            $(this).addClass('is-invalid');
            mostrarTooltipError($(this), 'Máximo 100 caracteres');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Contador de caracteres para descripción
    $('#descripcion, #editarDescripcion').on('input', function() {
        const valor = $(this).val();
        const contador = valor.length;
        const maximo = 500;
        
        const parent = $(this).closest('.col-12');
        let contadorElement = parent.find('.contador-caracteres');
        
        if (contadorElement.length === 0) {
            contadorElement = $('<small class="contador-caracteres text-muted float-end"></small>');
            parent.find('.form-text').append(contadorElement);
        }
        
        contadorElement.text(`${contador}/${maximo} caracteres`);
        
        if (contador > maximo) {
            $(this).addClass('is-invalid');
            contadorElement.addClass('text-danger').removeClass('text-muted');
        } else {
            $(this).removeClass('is-invalid');
            contadorElement.removeClass('text-danger').addClass('text-muted');
        }
    });
}

// ===== CRUD PRINCIPAL =====

function crearEspecialidad(event) {
    event.preventDefault();
    
    if (!validarFormularioCrear()) {
        return;
    }
    
    const formData = new FormData($('#formCrearEspecialidad')[0]);
    formData.append('accion', 'crear');
    formData.append('submenu_id', config.submenuId);
    
    // Mostrar loading
    const submitBtn = $('#formCrearEspecialidad button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="bi bi-arrow-clockwise spin"></i> Creando...').prop('disabled', true);
    
    if (config.debug) {
        console.log('📤 Creando especialidad:', Object.fromEntries(formData));
    }
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('📥 Respuesta crear especialidad:', response);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Especialidad creada',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#crearEspecialidadModal').modal('hide');
                    cargarEspecialidadesPaginadas(1);
                    cargarEstadisticas();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX crear especialidad:', {xhr, status, error});
            let mensaje = 'Error de conexión al crear la especialidad';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            }
            
            Swal.fire('Error', mensaje, 'error');
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

function editarEspecialidad(event) {
    event.preventDefault();
    
    if (!validarFormularioEditar()) {
        return;
    }
    
    const formData = new FormData($('#formEditarEspecialidad')[0]);
    formData.append('accion', 'editar');
    formData.append('submenu_id', config.submenuId);
    
    // Mostrar loading
    const submitBtn = $('#formEditarEspecialidad button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="bi bi-arrow-clockwise spin"></i> Guardando...').prop('disabled', true);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('📥 Respuesta editar especialidad:', response);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Especialidad actualizada',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#editarEspecialidadModal').modal('hide');
                    cargarEspecialidadesPaginadas(paginaActual);
                    cargarEstadisticas();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX editar especialidad:', {xhr, status, error});
            Swal.fire('Error', 'Error de conexión al actualizar la especialidad', 'error');
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

// ===== CARGAR Y MOSTRAR DATOS =====

function cargarEspecialidadesPaginadas(pagina = 1) {
    console.log(`📄 Cargando especialidades - Página ${pagina}`);
    
    paginaActual = pagina;
    
    // Mostrar loading
    const container = $('#especialidades-container');
    container.html(`
        <tr>
            <td colspan="4" class="text-center py-5">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Cargando especialidades...</p>
            </td>
        </tr>
    `);
    
    const params = {
        action: 'obtenerPaginadas',
        pagina: pagina,
        limit: registrosPorPagina,
        busqueda: busquedaActual,
        submenu_id: config.submenuId
    };
    
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            console.log('📥 Especialidades cargadas:', response);
            
            if (response.success) {
                mostrarEspecialidades(response.data);
                actualizarPaginacion(response);
                actualizarContador(response);
                
                totalRegistros = response.totalRegistros;
                totalPaginas = response.totalPaginas;
            } else {
                mostrarErrorCarga('Error de datos', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando especialidades:', {xhr, status, error});
            mostrarErrorCarga('Error de conexión', 'No se pudieron cargar las especialidades');
        }
    });
}

function mostrarEspecialidades(especialidades) {
    const container = $('#especialidades-container');
    
    if (!especialidades || especialidades.length === 0) {
        container.html(`
            <tr>
                <td colspan="4" class="text-center py-5">
                    <div class="text-muted">
                        <i class="bi bi-search fs-1"></i>
                        <p class="mt-2 mb-0">No se encontraron especialidades</p>
                        <small>Intente ajustar los filtros de búsqueda</small>
                    </div>
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    
    especialidades.forEach(especialidad => {
        const descripcionCompleta = especialidad.descripcion || 'Sin descripción';
        const descripcionCorta = descripcionCompleta.length > 100 ? 
            descripcionCompleta.substring(0, 100) + '...' : descripcionCompleta;
        
        const totalDoctores = especialidad.total_doctores || 0;
        
        html += `
            <tr data-id-especialidad="${especialidad.id_especialidad}">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="bi bi-hospital text-info fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold">${especialidad.nombre_especialidad}</div>
                            <small class="text-muted">ID: ${especialidad.id_especialidad}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="descripcion-cell" title="${descripcionCompleta}">
                        ${descripcionCorta}
                    </div>
                </td>
                <td>
                    <span class="badge ${totalDoctores > 0 ? 'bg-success' : 'bg-secondary'}">
                        ${totalDoctores} doctor${totalDoctores !== 1 ? 'es' : ''}
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-info" 
                                onclick="verDetallesEspecialidad(${especialidad.id_especialidad})" 
                                title="Ver detalles">
                            <i class="bi bi-eye"></i>
                        </button>
                        
                        ${config.permisos.puede_editar ? 
                            `<button type="button" class="btn btn-sm btn-outline-warning" 
                                    onclick="abrirModalEditar(${especialidad.id_especialidad})" 
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>` : ''
                        }
                        
                        ${config.permisos.puede_eliminar ? 
                            `<button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="abrirModalEliminar(${especialidad.id_especialidad}, '${especialidad.nombre_especialidad.replace(/'/g, "\\'")}', ${totalDoctores})" 
                                    title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `;
    });
    
    container.html(html);
    
    // Inicializar tooltips
    $('[title]').tooltip();
    
    console.log('✅ Especialidades mostradas en tabla');
}

// ===== ACCIONES DE ESPECIALIDAD =====

function verDetallesEspecialidad(idEspecialidad) {
    console.log('👁️ Viendo detalles de especialidad:', idEspecialidad);
    
    // Mostrar loading en el modal
    $('#contenidoVerEspecialidad').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">Cargando información de la especialidad...</p>
        </div>
    `);
    
    $('#verEspecialidadModal').modal('show');
    
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
            if (response.success && response.data) {
                mostrarDetallesEspecialidad(response.data);
                $('#btnEditarDesdeVer').data('id-especialidad', idEspecialidad);
            } else {
                $('#contenidoVerEspecialidad').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error: ${response.message || 'No se pudo cargar la información'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando detalles:', {xhr, status, error});
            $('#contenidoVerEspecialidad').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error de conexión al cargar los detalles
                </div>
            `);
        }
    });
}

function mostrarDetallesEspecialidad(especialidad) {
    const html = `
        <div class="row">
            <div class="col-12">
                <div class="card border-0 bg-light">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-hospital me-2"></i>
                            Información de la Especialidad
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>ID:</strong><br>
                                <span class="badge bg-secondary">${especialidad.id_especialidad}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Nombre:</strong><br>
                                <span class="fs-5 fw-bold text-info">${especialidad.nombre_especialidad}</span>
                            </div>
                            <div class="col-12">
                                <strong>Descripción:</strong><br>
                                <div class="bg-white p-3 rounded border">
                                    ${especialidad.descripcion || '<em class="text-muted">Sin descripción</em>'}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong>Total de Doctores:</strong><br>
                                <span class="badge ${(especialidad.total_doctores || 0) > 0 ? 'bg-success' : 'bg-secondary'} fs-6">
                                    ${especialidad.total_doctores || 0} doctor${(especialidad.total_doctores || 0) !== 1 ? 'es' : ''}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Total de Sucursales:</strong><br>
                                <span class="badge bg-info fs-6">
                                    ${especialidad.total_sucursales || 0} sucursal${(especialidad.total_sucursales || 0) !== 1 ? 'es' : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#contenidoVerEspecialidad').html(html);
}

function abrirModalEditar(idEspecialidad) {
    console.log('✏️ Abriendo modal editar para especialidad:', idEspecialidad);
    
    // Limpiar formulario
    limpiarModalEditar();
    
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
            if (response.success && response.data) {
                llenarFormularioEditar(response.data);
            } else {
                Swal.fire('Error', response.message || 'No se pudo cargar la información de la especialidad', 'error');
                $('#editarEspecialidadModal').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando datos para editar:', {xhr, status, error});
            Swal.fire('Error', 'Error de conexión al cargar los datos', 'error');
            $('#editarEspecialidadModal').modal('hide');
        }
    });
}

function llenarFormularioEditar(especialidad) {
    console.log('📝 Llenando formulario de edición:', especialidad);
    
    $('#editarIdEspecialidad').val(especialidad.id_especialidad);
    $('#editarNombreEspecialidad').val(especialidad.nombre_especialidad);
    $('#editarDescripcion').val(especialidad.descripcion || '');
    
    // Trigger del contador de caracteres
    $('#editarDescripcion').trigger('input');
    
    console.log('✅ Formulario de edición llenado');
}

function abrirModalEliminar(idEspecialidad, nombreEspecialidad, totalDoctores) {
    especialidadSeleccionadaParaEliminar = idEspecialidad;
    
    let mensaje = nombreEspecialidad;
    if (totalDoctores > 0) {
        mensaje += `<br><small class="text-warning">(Tiene ${totalDoctores} doctor${totalDoctores !== 1 ? 'es' : ''} asignado${totalDoctores !== 1 ? 's' : ''})</small>`;
    }
    
    $('#especialidadAEliminar').html(mensaje);
    $('#eliminarEspecialidadModal').modal('show');
}

function confirmarEliminarEspecialidad() {
    if (!especialidadSeleccionadaParaEliminar) {
        return;
    }
    
    const btnConfirmar = $('#btnConfirmarEliminar');
    const textoOriginal = btnConfirmar.html();
    btnConfirmar.html('<i class="bi bi-arrow-clockwise spin"></i> Eliminando...').prop('disabled', true);
    
    $.ajax({
        url: config.baseUrl,
        type: 'POST',
        data: {
            accion: 'eliminar',
            id_especialidad: especialidadSeleccionadaParaEliminar,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Especialidad eliminada',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#eliminarEspecialidadModal').modal('hide');
                    cargarEspecialidadesPaginadas(paginaActual);
                    cargarEstadisticas();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error eliminando especialidad:', {xhr, status, error});
            Swal.fire('Error', 'Error de conexión al eliminar la especialidad', 'error');
        },
        complete: function() {
            btnConfirmar.html(textoOriginal).prop('disabled', false);
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
            if (response.success) {
                actualizarEstadisticas(response.data);
            } else {
                console.error('❌ Error en estadísticas:', response.message);
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
    animarNumero('#totalDoctores', datos.total_doctores || 0);
    
    console.log('✅ Estadísticas actualizadas');
}

function mostrarEstadisticasError() {
    $('#totalEspecialidades').html('<span class="text-danger">Error</span>');
    $('#especialidadesConDoctores').html('<span class="text-danger">Error</span>');
    $('#totalDoctores').html('<span class="text-danger">Error</span>');
}

function animarNumero(selector, objetivo) {
    const elemento = $(selector);
    const actual = parseInt(elemento.text()) || 0;
    
    if (actual === objetivo) return;
    
    let contador = actual;
    const incremento = objetivo > actual ? 1 : -1;
    const tiempo = Math.abs(objetivo - actual) > 20 ? 50 : 100;
    
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
    
    // Agregar eventos
    container.find('.page-link').on('click', function() {
        const pagina = parseInt($(this).data('pagina'));
        if (!isNaN(pagina) && pagina !== paginaActual && !$(this).parent().hasClass('disabled')) {
            cargarEspecialidadesPaginadas(pagina);
        }
    });
}

function actualizarContador(response) {
    const { totalRegistros, mostrando, paginaActual } = response;
    const inicio = ((paginaActual - 1) * registrosPorPagina) + 1;
    const fin = Math.min(inicio + mostrando - 1, totalRegistros);
    
    const texto = totalRegistros > 0 ? 
        `Mostrando ${inicio} a ${fin} de ${totalRegistros} especialidades` :
        'No se encontraron especialidades';
    
    $('#infoRegistros').text(texto);
    $('#contadorEspecialidades').html(`
        <i class="bi bi-hospital me-1"></i>
        ${totalRegistros} especialidades registradas
    `);
}

// ===== BÚSQUEDA Y FILTROS =====

function limpiarBusqueda() {
    $('#busquedaGlobal').val('');
    busquedaActual = '';
    cargarEspecialidadesPaginadas(1);
}

// ===== VALIDACIONES =====

function validarFormularioCrear() {
    const nombre = $('#nombre_especialidad').val().trim();
    
    if (!nombre) {
        Swal.fire('Error', 'El nombre de la especialidad es requerido', 'error');
        $('#nombre_especialidad').focus();
        return false;
    }
    
    if (nombre.length < 3) {
        Swal.fire('Error', 'El nombre debe tener al menos 3 caracteres', 'error');
        $('#nombre_especialidad').focus();
        return false;
    }
    
    if (nombre.length > 100) {
        Swal.fire('Error', 'El nombre no puede exceder 100 caracteres', 'error');
        $('#nombre_especialidad').focus();
        return false;
    }
    
    const descripcion = $('#descripcion').val();
    if (descripcion.length > 500) {
Swal.fire('Error', 'La descripción no puede exceder 500 caracteres', 'error');
       $('#descripcion').focus();
       return false;
   }
   
   return true;
}

function validarFormularioEditar() {
   const nombre = $('#editarNombreEspecialidad').val().trim();
   
   if (!nombre) {
       Swal.fire('Error', 'El nombre de la especialidad es requerido', 'error');
       $('#editarNombreEspecialidad').focus();
       return false;
   }
   
   if (nombre.length < 3) {
       Swal.fire('Error', 'El nombre debe tener al menos 3 caracteres', 'error');
       $('#editarNombreEspecialidad').focus();
       return false;
   }
   
   if (nombre.length > 100) {
       Swal.fire('Error', 'El nombre no puede exceder 100 caracteres', 'error');
       $('#editarNombreEspecialidad').focus();
       return false;
   }
   
   const descripcion = $('#editarDescripcion').val();
   if (descripcion.length > 500) {
       Swal.fire('Error', 'La descripción no puede exceder 500 caracteres', 'error');
       $('#editarDescripcion').focus();
       return false;
   }
   
   return true;
}

// ===== UTILIDADES =====

function limpiarModalCrear() {
   $('#formCrearEspecialidad')[0].reset();
   $('#formCrearEspecialidad .is-invalid').removeClass('is-invalid');
   $('#formCrearEspecialidad .contador-caracteres').remove();
}

function limpiarModalEditar() {
   $('#formEditarEspecialidad')[0].reset();
   $('#formEditarEspecialidad .is-invalid').removeClass('is-invalid');
   $('#formEditarEspecialidad .contador-caracteres').remove();
}

function mostrarErrorCarga(titulo, mensaje) {
   $('#especialidades-container').html(`
       <tr>
           <td colspan="4" class="text-center py-5">
               <div class="text-danger">
                   <i class="bi bi-exclamation-triangle fs-1"></i>
                   <h6 class="mt-2">${titulo}</h6>
                   <p class="text-muted">${mensaje}</p>
                   <button class="btn btn-outline-primary btn-sm" onclick="cargarEspecialidadesPaginadas(paginaActual)">
                       <i class="bi bi-arrow-repeat me-1"></i>
                       Reintentar
                   </button>
               </div>
           </td>
       </tr>
   `);
}

function mostrarTooltipError(elemento, mensaje) {
   elemento.attr('title', mensaje).tooltip('show');
   setTimeout(() => {
       elemento.tooltip('hide');
   }, 3000);
}

function showToast(mensaje, tipo = 'info') {
   const Toast = Swal.mixin({
       toast: true,
       position: 'top-end',
       showConfirmButton: false,
       timer: 3000,
       timerProgressBar: true,
       didOpen: (toast) => {
           toast.addEventListener('mouseenter', Swal.stopTimer);
           toast.addEventListener('mouseleave', Swal.resumeTimer);
       }
   });
   
   Toast.fire({
       icon: tipo,
       title: mensaje
   });
}

// ===== GESTIÓN DE ERRORES GLOBALES =====

$(document).ajaxError(function(event, xhr, settings, thrownError) {
   if (xhr.status === 0) {
       console.error('❌ Error de conexión global');
       Swal.fire({
           icon: 'error',
           title: 'Error de conexión',
           text: 'No se pudo conectar con el servidor. Verifique su conexión a internet.',
           footer: '<small>Si el problema persiste, contacte al administrador</small>'
       });
   } else if (xhr.status === 500) {
       console.error('❌ Error del servidor:', xhr.responseText);
       Swal.fire({
           icon: 'error',
           title: 'Error del servidor',
           text: 'Se produjo un error interno del servidor.',
           footer: '<small>Contacte al administrador si el problema persiste</small>'
       });
   }
});

// ===== FUNCIONES ADICIONALES =====

// Función para exportar especialidades (opcional)
function exportarEspecialidades() {
   Swal.fire({
       title: 'Exportar Especialidades',
       text: '¿En qué formato desea exportar?',
       icon: 'question',
       showCancelButton: true,
       confirmButtonText: 'Excel',
       cancelButtonText: 'PDF',
       showDenyButton: true,
       denyButtonText: 'Cancelar'
   }).then((result) => {
       if (result.isConfirmed) {
           exportarAExcel();
       } else if (result.isDismissed && result.dismiss !== Swal.DismissReason.deny) {
           exportarAPDF();
       }
   });
}

function exportarAExcel() {
   window.open(`${config.baseUrl}?action=exportarExcel&submenu_id=${config.submenuId}`, '_blank');
}

function exportarAPDF() {
   window.open(`${config.baseUrl}?action=exportarPDF&submenu_id=${config.submenuId}`, '_blank');
}

// Función para buscar especialidades por texto específico
function buscarEspecialidadEspecifica() {
   Swal.fire({
       title: 'Búsqueda Específica',
       input: 'text',
       inputLabel: 'Ingrese el nombre de la especialidad',
       inputPlaceholder: 'Ej: Cardiología',
       showCancelButton: true,
       inputValidator: (value) => {
           if (!value) {
               return 'Debe ingresar un texto para buscar';
           }
       }
   }).then((result) => {
       if (result.isConfirmed) {
           $('#busquedaGlobal').val(result.value);
           busquedaActual = result.value;
           cargarEspecialidadesPaginadas(1);
       }
   });
}

// Función para obtener reporte de especialidades
function generarReporte() {
   Swal.fire({
       title: 'Generando reporte...',
       text: 'Por favor espere',
       allowOutsideClick: false,
       didOpen: () => {
           Swal.showLoading();
       }
   });
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'generarReporte',
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           Swal.close();
           
           if (response.success) {
               mostrarReporte(response.data);
           } else {
               Swal.fire('Error', 'No se pudo generar el reporte', 'error');
           }
       },
       error: function() {
           Swal.close();
           Swal.fire('Error', 'Error al generar el reporte', 'error');
       }
   });
}

function mostrarReporte(datos) {
   const html = `
       <div class="row">
           <div class="col-md-6">
               <div class="card border-info">
                   <div class="card-header bg-info text-white">
                       <h6 class="mb-0">Resumen General</h6>
                   </div>
                   <div class="card-body">
                       <ul class="list-unstyled mb-0">
                           <li><strong>Total Especialidades:</strong> ${datos.total_especialidades}</li>
                           <li><strong>Con Doctores:</strong> ${datos.con_doctores}</li>
                           <li><strong>Sin Doctores:</strong> ${datos.total_especialidades - datos.con_doctores}</li>
                           <li><strong>Total Doctores:</strong> ${datos.total_doctores}</li>
                       </ul>
                   </div>
               </div>
           </div>
           <div class="col-md-6">
               <div class="card border-success">
                   <div class="card-header bg-success text-white">
                       <h6 class="mb-0">Métricas</h6>
                   </div>
                   <div class="card-body">
                       <ul class="list-unstyled mb-0">
                           <li><strong>Promedio Doctores/Especialidad:</strong> ${(datos.total_doctores / datos.total_especialidades).toFixed(1)}</li>
                           <li><strong>Cobertura:</strong> ${((datos.con_doctores / datos.total_especialidades) * 100).toFixed(1)}%</li>
                       </ul>
                   </div>
               </div>
           </div>
       </div>
   `;
   
   Swal.fire({
       title: 'Reporte de Especialidades',
       html: html,
       icon: 'info',
       width: 600,
       confirmButtonText: 'Cerrar'
   });
}

// Inicializar tooltips cuando el DOM esté listo
$(document).ready(function() {
   // Inicializar tooltips para elementos dinámicos
   $(document).on('mouseenter', '[title]', function() {
       $(this).tooltip('show');
   });
   
   $(document).on('mouseleave', '[title]', function() {
       $(this).tooltip('hide');
   });
});

console.log('🎯 Sistema de Gestión de Especialidades COMPLETAMENTE CARGADO');