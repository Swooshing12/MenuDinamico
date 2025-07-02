/**
 * Sistema de Gestión de Sucursales con Especialidades
 * Autor: Sistema MediSys
 * Descripción: CRUD completo para gestión de sucursales médicas con especialidades
 */

// ===== CONFIGURACIÓN GLOBAL =====
const config = {
    debug: true,
    submenuId: window.sucursalesConfig?.submenuId || null,
    permisos: window.sucursalesConfig?.permisos || {},
    especialidades: window.sucursalesConfig?.especialidades || [],
    baseUrl: '../../controladores/SucursalesControlador/SucursalesController.php'
};

// Variables globales
let paginaActual = 1;
let registrosPorPagina = 10;
let busquedaActual = '';
let filtroEstadoActual = '';
let totalPaginas = 0;
let totalRegistros = 0;

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🏥 Iniciando Sistema de Gestión de Sucursales');
    
    if (config.debug) {
        console.log('Config:', config);
    }

    inicializarEventos();
    cargarSucursalesPaginadas(1);
    cargarEstadisticas();
    cargarEspecialidadesEnFormularios();
});

// ===== EVENTOS =====
function inicializarEventos() {
    // Formularios
    $('#formCrearSucursal').on('submit', crearSucursal);
    $('#formEditarSucursal').on('submit', editarSucursal);
    
    // Búsqueda con debounce mejorado
    let timeoutBusqueda;
    $('#busquedaGlobal').on('input', function() {
        clearTimeout(timeoutBusqueda);
        const valor = $(this).val().trim();
        
        timeoutBusqueda = setTimeout(() => {
            busquedaActual = valor;
            cargarSucursalesPaginadas(1);
        }, 300);
    });
    
    // Filtros
    $('#filtroEstado').on('change', function() {
        filtroEstadoActual = $(this).val();
        cargarSucursalesPaginadas(1);
    });
    
    $('#registrosPorPagina').on('change', function() {
        registrosPorPagina = parseInt($(this).val());
        cargarSucursalesPaginadas(1);
    });
    
    // Botones de control
    $('#limpiarFiltros').on('click', limpiarFiltros);
    $('#refrescarTabla').on('click', function() {
        cargarSucursalesPaginadas(paginaActual);
        cargarEstadisticas();
    });
    
    // Validaciones en tiempo real
    $('#nombreSucursal, #editarNombreSucursal').on('blur', validarNombreSucursal);
    
    // Resetear formularios al cerrar modales
    $('.modal').on('hidden.bs.modal', function() {
        const form = $(this).find('form')[0];
        if (form) {
            form.reset();
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback').remove();
            // Limpiar checkboxes de especialidades
            $(form).find('input[type="checkbox"]').prop('checked', false);
        }
    });
    
    if (config.debug) {
        console.log('✅ Eventos inicializados correctamente');
    }
}

// ===== FUNCIONES DE ESPECIALIDADES =====

/**
 * Cargar especialidades en los formularios
 */
function cargarEspecialidadesEnFormularios() {
    if (!config.especialidades || config.especialidades.length === 0) {
        $('#especialidadesCrear, #especialidadesEditar').html(`
            <div class="col-12 text-center">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No hay especialidades disponibles
                </div>
            </div>
        `);
        return;
    }
    
    const htmlEspecialidades = generarHtmlEspecialidades(config.especialidades);
    $('#especialidadesCrear').html(htmlEspecialidades);
    $('#especialidadesEditar').html(htmlEspecialidades);
    
    if (config.debug) {
        console.log('✅ Especialidades cargadas en formularios:', config.especialidades.length);
    }
}

/**
 * Generar HTML para las especialidades
 */
function generarHtmlEspecialidades(especialidades) {
    let html = '';
    
    especialidades.forEach(especialidad => {
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="especialidad-item">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               value="${especialidad.id_especialidad}" 
                               name="especialidades[]" 
                               id="esp_${especialidad.id_especialidad}">
                        <label class="form-check-label" for="esp_${especialidad.id_especialidad}">
                            <strong>${especialidad.nombre_especialidad}</strong>
                            <br>
                            <small class="text-muted">${especialidad.descripcion || 'Sin descripción'}</small>
                        </label>
                    </div>
                </div>
            </div>
        `;
    });
    
    return html;
}

/**
 * Marcar especialidades seleccionadas en edición
 */
function marcarEspecialidadesSeleccionadas(especialidadesAsignadas) {
    // Limpiar todas las selecciones
    $('#especialidadesEditar input[type="checkbox"]').prop('checked', false);
    
    // Marcar las especialidades asignadas
    if (especialidadesAsignadas && especialidadesAsignadas.length > 0) {
        especialidadesAsignadas.forEach(esp => {
            $(`#especialidadesEditar input[value="${esp.id_especialidad}"]`).prop('checked', true);
        });
    }
}

// ===== FUNCIONES PRINCIPALES =====

/**
 * Cargar sucursales paginadas
 */
function cargarSucursalesPaginadas(pagina = 1) {
    paginaActual = pagina;
    
    const parametros = {
        action: 'obtenerSucursalesPaginadas',
        pagina: pagina,
        limit: registrosPorPagina,
        busqueda: busquedaActual,
        submenu_id: config.submenuId
    };
    
    if (filtroEstadoActual !== '') {
        parametros.estado = filtroEstadoActual;
    }
    
    if (config.debug) {
        console.log('Cargando sucursales con parámetros:', parametros);
    }
    
    // Mostrar loading
    $('#tablaSucursalesBody').html(`
        <tr>
            <td colspan="9" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3 text-muted">Cargando sucursales...</p>
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
                mostrarSucursales(response.data);
                actualizarPaginacion(response);
                actualizarInfoTabla(response);
            } else {
                mostrarError('Error al cargar sucursales: ' + response.message);
                mostrarErrorEnTabla();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', {xhr, status, error});
            mostrarError('Error de conexión al cargar sucursales');
            mostrarErrorEnTabla();
        }
    });
}

/**
 * Mostrar error en la tabla
 */
function mostrarErrorEnTabla() {
    $('#tablaSucursalesBody').html(`
        <tr>
            <td colspan="9" class="text-center py-5">
                <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3"></i>
                <p class="text-muted">Error al cargar los datos</p>
                <button class="btn btn-outline-primary btn-sm" onclick="cargarSucursalesPaginadas(${paginaActual})">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                </button>
            </td>
        </tr>
    `);
}

/**
 * Mostrar sucursales en la tabla
 */
function mostrarSucursales(sucursales) {
    const tbody = $('#tablaSucursalesBody');
    
    if (!sucursales || sucursales.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="9" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron sucursales</p>
                    ${busquedaActual || filtroEstadoActual ? 
                        '<button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">Limpiar filtros</button>' : 
                        ''
                    }
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    
    sucursales.forEach(sucursal => {
        const estadoBadge = sucursal.estado == 1 
            ? '<span class="badge bg-success badge-estado">✅ Activa</span>'
            : '<span class="badge bg-danger badge-estado">❌ Inactiva</span>';
            
        // Contacto
        const telefono = `<a href="tel:${sucursal.telefono}" class="text-decoration-none d-block">
                            <i class="bi bi-telephone me-1"></i>${sucursal.telefono}
                          </a>`;
        const email = sucursal.email ? 
                     `<a href="mailto:${sucursal.email}" class="text-decoration-none d-block small">
                        <i class="bi bi-envelope me-1"></i>${sucursal.email}
                      </a>` : 
                     '<small class="text-muted">Sin email</small>';
        
        // Dirección truncada
        const direccionCorta = sucursal.direccion && sucursal.direccion.length > 40 
            ? sucursal.direccion.substring(0, 40) + '...'
            : sucursal.direccion || '';
            
        // Horario truncado
        const horarioCorto = sucursal.horario_atencion && sucursal.horario_atencion.length > 30
            ? sucursal.horario_atencion.substring(0, 30) + '...'
            : sucursal.horario_atencion || '<small class="text-muted">No especificado</small>';
            
        // Especialidades
        const especialidadesInfo = `
            <small class="text-primary">
                <i class="bi bi-journal-medical me-1"></i>
                ${sucursal.total_especialidades || 0} especialidades
            </small>
        `;
        
        // Estadísticas
        const estadisticas = `
            <div class="d-flex flex-column small">
                <span class="text-primary">
                    <i class="bi bi-people me-1"></i>${sucursal.total_doctores || 0} doctores
                </span>
                <span class="text-success">
                    <i class="bi bi-calendar-check me-1"></i>${sucursal.citas_hoy || 0} citas hoy
                </span>
            </div>
        `;
        
        // Botones de acción según permisos
        let botones = '';
        
        // Botón ver (siempre disponible)
        botones += `
            <button class="btn btn-outline-info btn-sm" onclick="verSucursal(${sucursal.id_sucursal})" 
                    title="Ver detalles">
                <i class="bi bi-eye"></i>
            </button>
        `;
        
        // Botón editar
        if (config.permisos.puede_editar) {
            botones += `
                <button class="btn btn-outline-primary btn-sm" onclick="abrirModalEditar(${sucursal.id_sucursal})" 
                        title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
            `;
        }
        
        // Botón cambiar estado
        if (config.permisos.puede_editar) {
            const estadoTexto = sucursal.estado == 1 ? 'Desactivar' : 'Activar';
            const estadoIcono = sucursal.estado == 1 ? 'toggle-off' : 'toggle-on';
            const estadoColor = sucursal.estado == 1 ? 'outline-warning' : 'outline-success';
            
            botones += `
                <button class="btn ${estadoColor} btn-sm" onclick="cambiarEstado(${sucursal.id_sucursal}, ${sucursal.estado == 1 ? 0 : 1})" 
                        title="${estadoTexto}">
                    <i class="bi bi-${estadoIcono}"></i>
                </button>
            `;
        }
        
        // Botón eliminar
        if (config.permisos.puede_eliminar) {
            botones += `
                <button class="btn btn-outline-danger btn-sm" onclick="eliminarSucursal(${sucursal.id_sucursal})" 
                        title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            `;
        }
        
        html += `
            <tr>
                <td><strong class="text-primary">#${sucursal.id_sucursal}</strong></td>
                <td>
                    <strong>${sucursal.nombre_sucursal}</strong>
                </td>
                <td>
                    <span title="${sucursal.direccion || ''}" class="d-block">
                        <i class="bi bi-geo-alt me-1 text-muted"></i>${direccionCorta}
                    </span>
                </td>
                <td>
                    ${telefono}
                    ${email}
                </td>
                <td>
                    <small title="${sucursal.horario_atencion || ''}">${horarioCorto}</small>
                </td>
                <td>${especialidadesInfo}</td>
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
 * Crear nueva sucursal
 */
function crearSucursal(e) {
    e.preventDefault();
    
    if (!validarFormulario('formCrearSucursal')) {
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'crear');
    formData.append('submenu_id', config.submenuId);
    
    // Obtener especialidades seleccionadas
    const especialidadesSeleccionadas = [];
    $('#especialidadesCrear input[type="checkbox"]:checked').each(function() {
        especialidadesSeleccionadas.push($(this).val());
    });
    
    // Agregar especialidades al FormData
    especialidadesSeleccionadas.forEach(esp => {
        formData.append('especialidades[]', esp);
    });
    
    if (config.debug) {
        console.log('Datos a enviar (crear):');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        console.log('Especialidades seleccionadas:', especialidadesSeleccionadas);
    }
    
    // Deshabilitar botón de envío
    const submitBtn = $(this).find('button[type="submit"]');
    const textoOriginal = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Creando...');
    
    $.ajax({
        url: config.baseUrl,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('Respuesta del servidor (crear):', response);
            }
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Sucursal creada!',
                    text: response.message,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    $('#crearSucursalModal').modal('hide');
                    cargarSucursalesPaginadas(1);
                    cargarEstadisticas();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al crear',
                    text: response.message || 'Error desconocido'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en la petición AJAX (crear):', {status, error, response: xhr.responseText});
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
 * Abrir modal para editar sucursal
 */
function abrirModalEditar(idSucursal) {
    if (config.debug) {
        console.log('Abriendo modal editar para sucursal:', idSucursal);
    }
    
    // Limpiar formulario
    document.getElementById('formEditarSucursal').reset();
    
    // Cargar datos de la sucursal
    $.ajax({
        url: config.baseUrl,
        type: 'GET',
        data: {
            action: 'obtenerPorId',
            id: idSucursal,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const sucursal = response.data;
                
                // Llenar el formulario
                $('#editarIdSucursal').val(sucursal.id_sucursal);
                $('#editarNombreSucursal').val(sucursal.nombre_sucursal);
                $('#editarDireccionSucursal').val(sucursal.direccion);
                $('#editarTelefonoSucursal').val(sucursal.telefono);
                $('#editarEmailSucursal').val(sucursal.email || '');
                $('#editarHorarioAtencion').val(sucursal.horario_atencion || '');
                $('#editarEstadoSucursal').val(sucursal.estado);
                
                // Marcar especialidades asignadas
                marcarEspecialidadesSeleccionadas(sucursal.especialidades || []);
                
                // Abrir modal
                $('#editarSucursalModal').modal('show');
                
                if (config.debug) {
                    console.log('Datos cargados para editar:', sucursal);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar la información de la sucursal'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando sucursal:', {xhr, status, error});
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo cargar la información'
            });
        }
    });
}

/**
 * Editar sucursal
 */
function editarSucursal(e) {
    e.preventDefault();
    
    if (!validarFormulario('formEditarSucursal')) {
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'editar');
    formData.append('submenu_id', config.submenuId);
    
    // Obtener especialidades seleccionadas
    const especialidadesSeleccionadas = [];
    $('#especialidadesEditar input[type="checkbox"]:checked').each(function() {
        especialidadesSeleccionadas.push($(this).val());
    });
    
    // Agregar especialidades al FormData
    especialidadesSeleccionadas.forEach(esp => {
        formData.append('especialidades[]', esp);
    });
    
    if (config.debug) {
        console.log('Datos a enviar (editar):');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        console.log('Especialidades seleccionadas:', especialidadesSeleccionadas);
    }
    
    // Deshabilitar botón de envío
    const submitBtn = $(this).find('button[type="submit"]');
    const textoOriginal = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Guardando...');
    
    $.ajax({
        url: config.baseUrl,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (config.debug) {
                console.log('Respuesta del servidor (editar):', response);
            }
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Sucursal actualizada!',
                    text: response.message,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    $('#editarSucursalModal').modal('hide');
                    cargarSucursalesPaginadas(paginaActual);
                    cargarEstadisticas();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: response.message || 'Error desconocido'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en la petición AJAX (editar):', {status, error, response: xhr.responseText});
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

// ===== FUNCIONES DE PAGINACIÓN MEJORADAS =====

/**
 * Actualizar información de la tabla
 */
function actualizarInfoTabla(response) {
    const inicio = ((response.paginaActual - 1) * registrosPorPagina) + 1;
    const fin = Math.min(inicio + response.mostrando - 1, response.totalRegistros);
    
    let info = '';
    if (response.totalRegistros > 0) {
        info = `Mostrando <strong>${inicio}</strong> a <strong>${fin}</strong> de <strong>${response.totalRegistros}</strong> sucursales`;
        
        if (busquedaActual) {
            info += ` (filtrado de ${response.totalRegistros} registros totales)`;
        }
    } else {
        info = 'No se encontraron sucursales';
        if (busquedaActual || filtroEstadoActual) {
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
 * Actualizar paginación mejorada
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
            <a class="page-link" href="#" ${response.paginaActual > 1 ? `onclick="cargarSucursalesPaginadas(${response.paginaActual - 1})"` : ''} aria-label="Anterior">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Páginas numeradas - mostrar más páginas en pantallas grandes
    const maxPaginas = window.innerWidth > 768 ? 7 : 5;
    let inicio = Math.max(1, response.paginaActual - Math.floor(maxPaginas / 2));
    let fin = Math.min(response.totalPaginas, inicio + maxPaginas - 1);
    
    // Ajustar si estamos cerca del final
    if (fin - inicio + 1 < maxPaginas) {
        inicio = Math.max(1, fin - maxPaginas + 1);
    }
    
    // Primera página si no está en el rango
    if (inicio > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarSucursalesPaginadas(1)">1</a></li>`;
        if (inicio > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    // Páginas del rango
    for (let i = inicio; i <= fin; i++) {
        const active = i === response.paginaActual ? 'active' : '';
        html += `
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="cargarSucursalesPaginadas(${i})">${i}</a>
            </li>
        `;
    }
    
    // Última página si no está en el rango
    if (fin < response.totalPaginas) {
        if (fin < response.totalPaginas - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarSucursalesPaginadas(${response.totalPaginas})">${response.totalPaginas}</a></li>`;
    }
    
    // Botón siguiente
    const siguienteDisabled = response.paginaActual === response.totalPaginas ? 'disabled' : '';
    html += `
        <li class="page-item ${siguienteDisabled}">
            <a class="page-link" href="#" ${response.paginaActual < response.totalPaginas ? `onclick="cargarSucursalesPaginadas(${response.paginaActual + 1})"` : ''} aria-label="Siguiente">
            <i class="bi bi-chevron-right"></i>
           </a>
       </li>
   `;
   
   paginacion.html(html);
}

// ===== FUNCIONES AUXILIARES MEJORADAS =====

/**
* Limpiar filtros
*/
function limpiarFiltros() {
   $('#busquedaGlobal').val('');
   $('#filtroEstado').val('');
   $('#registrosPorPagina').val('10');
   
   busquedaActual = '';
   filtroEstadoActual = '';
   registrosPorPagina = 10;
   
   cargarSucursalesPaginadas(1);
   
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
* Ver detalles de sucursal con especialidades
*/
function verSucursal(idSucursal) {
   if (config.debug) {
       console.log('Viendo detalles de sucursal:', idSucursal);
   }
   
   // Mostrar loading en el modal
   $('#contenidoVerSucursal').html(`
       <div class="text-center py-4">
           <div class="spinner-border text-primary" role="status">
               <span class="visually-hidden">Cargando...</span>
           </div>
           <p class="mt-2 text-muted">Cargando información...</p>
       </div>
   `);
   
   $('#verSucursalModal').modal('show');
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'obtenerPorId',
           id: idSucursal,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success && response.data) {
               const sucursal = response.data;
               mostrarDetallesSucursal(sucursal);
           } else {
               $('#contenidoVerSucursal').html(`
                   <div class="alert alert-danger">
                       <i class="bi bi-exclamation-triangle me-2"></i>
                       Error al cargar la información de la sucursal
                   </div>
               `);
           }
       },
       error: function(xhr, status, error) {
           console.error('Error cargando detalles:', {xhr, status, error});
           $('#contenidoVerSucursal').html(`
               <div class="alert alert-danger">
                   <i class="bi bi-exclamation-triangle me-2"></i>
                   Error de conexión al cargar los detalles
               </div>
           `);
       }
   });
}

/**
* Mostrar detalles de la sucursal en el modal con especialidades
*/
function mostrarDetallesSucursal(sucursal) {
   const estadoBadge = sucursal.estado == 1 
       ? '<span class="badge bg-success fs-6">✅ Activa</span>'
       : '<span class="badge bg-danger fs-6">❌ Inactiva</span>';
   
   // Generar lista de especialidades
   let especialidadesHtml = '';
   if (sucursal.especialidades && sucursal.especialidades.length > 0) {
       especialidadesHtml = sucursal.especialidades.map(esp => 
           `<span class="badge bg-primary me-2 mb-2">${esp.nombre_especialidad}</span>`
       ).join('');
   } else {
       especialidadesHtml = '<span class="text-muted">No hay especialidades asignadas</span>';
   }
   
   const html = `
       <div class="row g-3">
           <!-- Información Principal -->
           <div class="col-12">
               <div class="card border-primary">
                   <div class="card-header bg-primary bg-opacity-10">
                       <h6 class="mb-0 text-primary">
                           <i class="bi bi-building me-2"></i>
                           Información Principal
                       </h6>
                   </div>
                   <div class="card-body">
                       <div class="row">
                           <div class="col-md-6">
                               <p><strong>ID:</strong> #${sucursal.id_sucursal}</p>
                               <p><strong>Nombre:</strong> ${sucursal.nombre_sucursal}</p>
                               <p><strong>Estado:</strong> ${estadoBadge}</p>
                           </div>
                           <div class="col-md-6">
                               <p><strong>Teléfono:</strong> 
                                   <a href="tel:${sucursal.telefono}" class="text-decoration-none">
                                       <i class="bi bi-telephone me-1"></i>${sucursal.telefono}
                                   </a>
                               </p>
                               <p><strong>Email:</strong> 
                                   ${sucursal.email ? 
                                       `<a href="mailto:${sucursal.email}" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>${sucursal.email}</a>` : 
                                       '<span class="text-muted">No especificado</span>'
                                   }
                               </p>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
           
           <!-- Ubicación y Horarios -->
           <div class="col-md-6">
               <div class="card border-info h-100">
                   <div class="card-header bg-info bg-opacity-10">
                       <h6 class="mb-0 text-info">
                           <i class="bi bi-geo-alt me-2"></i>
                           Ubicación
                       </h6>
                   </div>
                   <div class="card-body">
                       <p class="mb-0">
                           <i class="bi bi-pin-map me-1"></i>
                           ${sucursal.direccion || 'No especificada'}
                       </p>
                   </div>
               </div>
           </div>
           
           <div class="col-md-6">
               <div class="card border-warning h-100">
                   <div class="card-header bg-warning bg-opacity-10">
                       <h6 class="mb-0 text-warning">
                           <i class="bi bi-clock me-2"></i>
                           Horarios de Atención
                       </h6>
                   </div>
                   <div class="card-body">
                       <p class="mb-0">
                           <i class="bi bi-calendar-week me-1"></i>
                           ${sucursal.horario_atencion || 'No especificados'}
                       </p>
                   </div>
               </div>
           </div>
           
           <!-- Especialidades -->
           <div class="col-12">
               <div class="card border-success">
                   <div class="card-header bg-success bg-opacity-10">
                       <h6 class="mb-0 text-success">
                           <i class="bi bi-journal-medical me-2"></i>
                           Especialidades Disponibles
                       </h6>
                   </div>
                   <div class="card-body">
                       ${especialidadesHtml}
                   </div>
               </div>
           </div>
           
           <!-- Estadísticas -->
           <div class="col-12">
               <div class="card border-info">
                   <div class="card-header bg-info bg-opacity-10">
                       <h6 class="mb-0 text-info">
                           <i class="bi bi-graph-up me-2"></i>
                           Estadísticas
                       </h6>
                   </div>
                   <div class="card-body">
                       <div class="row text-center">
                           <div class="col-md-4">
                               <div class="border-end pe-3">
                                   <h4 class="text-primary mb-1">${sucursal.total_doctores || 0}</h4>
                                   <small class="text-muted">Doctores asignados</small>
                               </div>
                           </div>
                           <div class="col-md-4">
                               <div class="border-end pe-3">
                                   <h4 class="text-success mb-1">${sucursal.total_especialidades || 0}</h4>
                                   <small class="text-muted">Especialidades disponibles</small>
                               </div>
                           </div>
                           <div class="col-md-4">
                               <h4 class="text-warning mb-1">${sucursal.citas_hoy || 0}</h4>
                               <small class="text-muted">Citas programadas hoy</small>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   `;
   
   $('#contenidoVerSucursal').html(html);
}

/**
* Cambiar estado de sucursal
*/
function cambiarEstado(idSucursal, nuevoEstado) {
   const estadoTexto = nuevoEstado ? 'activar' : 'desactivar';
   const estadoTitulo = nuevoEstado ? 'Activar Sucursal' : 'Desactivar Sucursal';
   
   Swal.fire({
       title: estadoTitulo,
       text: `¿Está seguro que desea ${estadoTexto} esta sucursal?`,
       icon: 'question',
       showCancelButton: true,
       confirmButtonColor: nuevoEstado ? '#198754' : '#ffc107',
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
                   id: idSucursal,
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
                       cargarSucursalesPaginadas(paginaActual);
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
* Eliminar sucursal
*/
function eliminarSucursal(idSucursal) {
   Swal.fire({
       title: '⚠️ Eliminar Sucursal',
       text: '¿Está seguro que desea eliminar esta sucursal? Esta acción la desactivará permanentemente.',
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
                   id: idSucursal,
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
                           title: 'Sucursal eliminada',
                           text: response.message,
                           timer: 2000,
                           showConfirmButton: false
                       });
                       cargarSucursalesPaginadas(paginaActual);
                       cargarEstadisticas();
                   } else {
                       Swal.fire({
                           icon: 'error',
                           title: 'No se pudo eliminar',
                           text: response.message || 'Error al eliminar sucursal'
                       });
                   }
               },
               error: function(xhr, status, error) {
                   console.error('Error eliminando sucursal:', {xhr, status, error});
                   Swal.fire({
                       icon: 'error',
                       title: 'Error de conexión',
                       text: 'No se pudo eliminar la sucursal'
                   });
               }
           });
       }
   });
}

/**
* Cargar estadísticas mejoradas
*/
function cargarEstadisticas() {
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'obtenerResumenActividad',
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success && response.data) {
               const datos = response.data;
               
               // Calcular estadísticas
               let totalActivas = 0;
               let totalCitasHoy = 0;
               let totalDoctores = 0;
               let totalEspecialidades = 0;
               
               datos.forEach(sucursal => {
                   totalActivas++; // Solo sucursales activas en el resumen
                   totalCitasHoy += parseInt(sucursal.citas_hoy) || 0;
                   totalDoctores += parseInt(sucursal.doctores) || 0;
                   totalEspecialidades += parseInt(sucursal.especialidades) || 0;
               });
               
               // Actualizar las tarjetas con animación
               animarContador('#total-activas', totalActivas);
               animarContador('#citas-hoy', totalCitasHoy);
               animarContador('#total-doctores', totalDoctores);
               animarContador('#total-especialidades', totalEspecialidades);
               
               if (config.debug) {
                   console.log('Estadísticas actualizadas:', {
                       totalActivas,
                       totalCitasHoy,
                       totalDoctores,
                       totalEspecialidades
                   });
               }
           } else {
               console.warn('No se pudieron cargar las estadísticas');
               $('#total-activas, #citas-hoy, #total-doctores, #total-especialidades').text('--');
           }
       },
       error: function(xhr, status, error) {
           console.error('Error cargando estadísticas:', {xhr, status, error});
           // Poner valores por defecto
           $('#total-activas, #citas-hoy, #total-doctores, #total-especialidades').text('--');
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

// ===== FUNCIONES DE VALIDACIÓN =====

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
   const camposRequeridos = form.querySelectorAll('input[required], select[required], textarea[required]');
   
   camposRequeridos.forEach(campo => {
       if (!campo.value.trim()) {
           mostrarErrorCampo(campo, 'Este campo es requerido');
           esValido = false;
       }
   });
   
   // Validaciones específicas
   const nombreSucursal = form.querySelector('input[name="nombre_sucursal"]');
   if (nombreSucursal && nombreSucursal.value.trim()) {
       if (nombreSucursal.value.trim().length < 3) {
           mostrarErrorCampo(nombreSucursal, 'El nombre debe tener al menos 3 caracteres');
           esValido = false;
       }
   }
   
   const telefono = form.querySelector('input[name="telefono"]');
   if (telefono && telefono.value.trim()) {
       if (!validarTelefono(telefono.value)) {
           mostrarErrorCampo(telefono, 'Formato de teléfono inválido');
           esValido = false;
       }
   }
   
   const email = form.querySelector('input[name="email"]');
   if (email && email.value.trim()) {
       if (!validarEmail(email.value)) {
           mostrarErrorCampo(email, 'Formato de email inválido');
           esValido = false;
       }
   }
   
   const direccion = form.querySelector('textarea[name="direccion"]');
   if (direccion && direccion.value.trim()) {
       if (direccion.value.trim().length < 10) {
           mostrarErrorCampo(direccion, 'La dirección debe ser más específica (mínimo 10 caracteres)');
           esValido = false;
       }
   }
   
   return esValido;
}

/**
* Validar nombre de sucursal en tiempo real
*/
function validarNombreSucursal() {
   const campo = $(this);
   const nombre = campo.val().trim();
   
   // Limpiar validaciones anteriores
   campo.removeClass('is-invalid is-valid');
   campo.next('.invalid-feedback').remove();
   
   if (!nombre) {
       return;
   }
   
   if (nombre.length < 3) {
       mostrarErrorCampo(campo[0], 'El nombre debe tener al menos 3 caracteres');
       return;
   }
   
   // Verificar si ya existe (solo en crear o si cambió en editar)
   const isEditing = campo.closest('#editarSucursalModal').length > 0;
   const idExcluir = isEditing ? $('#editarIdSucursal').val() : null;
   
   $.ajax({
       url: config.baseUrl,
       type: 'GET',
       data: {
           action: 'verificarNombre',
           nombre: nombre,
           id_excluir: idExcluir,
           submenu_id: config.submenuId
       },
       dataType: 'json',
       success: function(response) {
           if (response.success) {
               if (response.existe) {
                   mostrarErrorCampo(campo[0], 'Ya existe una sucursal con este nombre');
               } else {
                   campo.removeClass('is-invalid').addClass('is-valid');
               }
           }
       },
       error: function() {
           console.warn('No se pudo verificar el nombre de sucursal');
       }
   });
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

/**
* Validar formato de teléfono
*/
function validarTelefono(telefono) {
   // Permitir números, espacios, guiones y paréntesis
   const regex = /^[\d\s\-\(\)\+]{7,20}$/;
   return regex.test(telefono.trim());
}

/**
* Validar formato de email
*/
function validarEmail(email) {
   const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   return regex.test(email.trim());
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
// Estas funciones necesitan estar en el scope global para los onclick en HTML

window.cargarSucursalesPaginadas = cargarSucursalesPaginadas;
window.abrirModalEditar = abrirModalEditar;
window.verSucursal = verSucursal;
window.cambiarEstado = cambiarEstado;
window.eliminarSucursal = eliminarSucursal;
window.limpiarFiltros = limpiarFiltros;

// ===== DEBUG INFO =====
if (config.debug) {
   console.log('🏥 Sistema de Gestión de Sucursales con Especialidades cargado correctamente');
   console.log('Configuración:', config);
   
   // Exponer funciones útiles para debugging
   window.sucursalesDebug = {
       config,
       cargarSucursales: () => cargarSucursalesPaginadas(paginaActual),
       cargarEstadisticas,
       variables: () => ({
           paginaActual,
           registrosPorPagina,
           busquedaActual,
           filtroEstadoActual,
           totalPaginas,
           totalRegistros
       })
   };
}
            