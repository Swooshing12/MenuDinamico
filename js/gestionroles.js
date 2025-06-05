$(document).ready(function() {
    // Configuración inicial
    const config = {
        submenuId: window.gestionRoles?.submenuId || 0,
        permisos: window.gestionRoles?.permisos || {},
        debug: window.gestionRoles?.debug || false,
        paginacion: {
            paginaActual: 1,
            registrosPorPagina: 10,
            totalPaginas: 1,
            totalRegistros: 0
        },
        busqueda: {
            termino: '',
            timeout: null
        }
    };
    
    console.log('🚀 Inicializando gestión de roles...', config);
    
    // Verificar Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está cargado correctamente');
    } else {
        console.log('✅ Bootstrap cargado correctamente');
    }
    
    // Inicializar componentes
    inicializarValidaciones();
    configurarEventos();
    configurarBusqueda();
    cargarEstadisticas();
    cargarRolesPaginados(1);

    // Configurar búsqueda en tiempo real
    function configurarBusqueda() {
        const inputBusqueda = $('#buscarRol');
        const btnLimpiar = $('#limpiarBusqueda');
        
        console.log('🔍 Configurando búsqueda...');
        
        let searchTimeout;
        
        // Búsqueda en tiempo real por nombre de rol
        inputBusqueda.on('input keyup', function(e) {
            const termino = $(this).val().trim();
            config.busqueda.termino = termino;
            
            console.log('🔍 Búsqueda por nombre de rol:', termino);
            
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                console.log('🚀 Ejecutando búsqueda para:', termino);
                cargarRolesPaginados(1);
            }, 400);
        });
        
        // Limpiar búsqueda
        btnLimpiar.on('click', function() {
            console.log('🗑️ Limpiando búsqueda...');
            inputBusqueda.val('');
            config.busqueda.termino = '';
            clearTimeout(searchTimeout);
            cargarRolesPaginados(1);
        });
        
        // ESC para limpiar
        inputBusqueda.on('keydown', function(e) {
            if (e.key === 'Escape') {
                $(this).val('');
                config.busqueda.termino = '';
                clearTimeout(searchTimeout);
                cargarRolesPaginados(1);
            }
        });
    }

    // Validaciones de entrada
    function inicializarValidaciones() {
        // Validación para nombres de roles
        ['nombre_rol', 'edit_nombre_rol'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', function() {
                    // Permitir letras, números, espacios, guiones y algunos caracteres especiales
                    this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-_()]/g, '');
                    
                    if (this.value.length > 0 && this.value.length < 3) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            }
        });
    }
    
    // Configurar eventos
    function configurarEventos() {
        $('#formCrearRol').on('submit', crearRol);
        $('#formEditarRol').on('submit', editarRol);
        $('#formEliminarRol').on('submit', eliminarRol);
        $('#editarRolModal').on('show.bs.modal', cargarDatosEdicion);
        $('#eliminarRolModal').on('show.bs.modal', cargarDatosEliminacion);
        $('#verPermisosModal').on('show.bs.modal', cargarPermisosDetalle);
        
        // Eventos para modales
        $('#crearRolModal').on('shown.bs.modal', function() {
            cargarEstructuraPermisos('permisos-container');
            $('#nombre_rol').focus();
        });
        
        $('#crearRolModal').on('hidden.bs.modal', function() {
            limpiarFormulario('formCrearRol');
        });
        
        $('#editarRolModal').on('hidden.bs.modal', function() {
            limpiarFormulario('formEditarRol');
        });
        
        if (config.debug) {
            console.log('✅ Eventos configurados correctamente');
        }
    }
    
    // Cargar estadísticas dinámicamente
    function cargarEstadisticas() {
        console.log('📊 Cargando estadísticas...');
        
        $.ajax({
            url: '../../controladores/RolesControlador/RolesController.php',
            type: 'GET',
            data: {
                action: 'obtenerEstadisticas',
                submenu_id: config.submenuId
            },
            dataType: 'json',
            success: function(response) {
                console.log('✅ Estadísticas recibidas:', response);
                
                if (response.success) {
                    actualizarEstadisticas(response.data);
                } else {
                    console.error('❌ Error en estadísticas:', response.message);
                    mostrarEstadisticasError();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX estadísticas:', { xhr, status, error });
                mostrarEstadisticasError();
            }
        });
    }

    // Actualizar las estadísticas en la vista
    function actualizarEstadisticas(datos) {
        console.log('📊 Actualizando estadísticas:', datos);
        
        // Actualizar con animación suave
        $('#totalRoles').html(`
            <span class="counter-animation" data-target="${datos.total_roles}">0</span>
        `);
        
        $('#rolesActivos').html(`
            <span class="counter-animation" data-target="${datos.roles_con_usuarios}">0</span>
        `);
        
        $('#permisosAsignados').html(`
            <span class="counter-animation" data-target="${datos.permisos_asignados}">0</span>
        `);
        
        // Animar contadores
        $('.counter-animation').each(function() {
            const $this = $(this);
            const target = parseInt($this.data('target'));
            
            $({ counter: 0 }).animate({
                counter: target
            }, {
                duration: 1000,
                easing: 'swing',
                step: function() {
                    $this.text(Math.ceil(this.counter));
                },
                complete: function() {
                    $this.text(target);
                }
            });
        });
    }

    // Mostrar error en estadísticas
    function mostrarEstadisticasError() {
        $('#totalRoles').html('<span class="text-danger">Error</span>');
        $('#rolesActivos').html('<span class="text-danger">Error</span>');
        $('#permisosAsignados').html('<span class="text-danger">Error</span>');
    }
    
    // Función para cargar roles paginados
    function cargarRolesPaginados(pagina = 1) {
        console.log('🔄 CARGANDO PÁGINA:', pagina);
        
        const container = $('#roles-container');
        
        // Transición suave
        container.css({
            'opacity': '0.6',
            'pointer-events': 'none'
        });
        
        // Loading elegante
        container.html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="spinner-border text-primary me-3" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span class="text-muted fs-6">
                            ${config.busqueda.termino ? 
                                `Buscando "${config.busqueda.termino}"...` : 
                                'Cargando roles...'
                            }
                        </span>
                    </div>
                </td>
            </tr>
        `);
        
        config.paginacion.paginaActual = pagina;
        
        $.ajax({
            url: '../../controladores/RolesControlador/RolesController.php',
            type: 'GET',
            data: {
                action: 'obtenerRolesPaginados',
                pagina: pagina,
                limit: config.paginacion.registrosPorPagina,
                busqueda: config.busqueda.termino,
                submenu_id: config.submenuId
            },
            dataType: 'json',
            success: function(response) {
                console.log('✅ RESPUESTA DEL SERVIDOR:', response);
                
                if (response.success) {
                    config.paginacion.totalRegistros = response.totalRegistros;
                    config.paginacion.totalPaginas = response.totalPaginas;
                    config.paginacion.paginaActual = response.paginaActual;
                    
                    mostrarRolesPaginados(response.data);
                    actualizarContador(response.totalRegistros, response.mostrando);
                    generarPaginacion(response.paginaActual, response.totalPaginas);
                    
                    // Restaurar visibilidad
                    setTimeout(() => {
                        container.css({
                            'opacity': '1',
                            'pointer-events': 'auto'
                        });
                    }, 100);
                    
                } else {
                    mostrarErrorPaginacion('No se pudieron cargar los roles', response.message || 'Error desconocido');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ ERROR AJAX:', { xhr, status, error });
                mostrarErrorPaginacion('Error de conexión', 'No se pudo establecer conexión con el servidor');
            }
        });
    }
    
    // Función para mostrar roles en la tabla
    function mostrarRolesPaginados(roles) {
        const container = $('#roles-container');
        container.empty();
        
        if (!roles || roles.length === 0) {
            let mensaje = 'No se encontraron roles';
            if (config.busqueda.termino) {
                mensaje = `No se encontraron roles que coincidan con: "${config.busqueda.termino}"`;
            }
            
            container.html(`
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="alert alert-info mb-0" style="margin: 0 auto; max-width: 400px;">
                            <i class="bi bi-info-circle me-2"></i> ${mensaje}
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        // Generar filas
        roles.forEach(function(rol) {
            container.append(`
                <tr>
                    <td><span class="badge bg-secondary">${escapeHtml(rol.id_rol)}</span></td>
                    <td>
                        <i class="bi bi-shield-lock me-2 text-primary"></i>
                        <strong>${escapeHtml(rol.nombre_rol)}</strong>
                    </td>
                    <td>
                        <span class="badge ${rol.usuarios_asignados > 0 ? 'bg-success' : 'bg-secondary'}">
                            <i class="bi bi-people me-1"></i>${rol.usuarios_asignados || 0}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-info">
                            <i class="bi bi-gear me-1"></i>${rol.permisos_count || 0} permisos
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>${rol.fecha_creacion_formatted || 'N/A'}
                        </small>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info me-1 btn-ver-permisos"
                                    data-bs-toggle="modal" data-bs-target="#verPermisosModal"
                                    data-id="${rol.id_rol}"
                                    data-nombre="${escapeHtml(rol.nombre_rol)}"
                                    title="Ver permisos">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${config.permisos.puede_editar ? `
                            <button class="btn btn-sm btn-warning me-1 btn-editar"
                                    data-bs-toggle="modal" data-bs-target="#editarRolModal"
                                    data-id="${rol.id_rol}"
                                    data-nombre="${escapeHtml(rol.nombre_rol)}"
                                    title="Editar rol">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            ` : ''}
                            ${config.permisos.puede_eliminar ? `
                            <button class="btn btn-sm btn-danger btn-eliminar"
                                    data-bs-toggle="modal" data-bs-target="#eliminarRolModal"
                                    data-id="${rol.id_rol}"
                                    data-nombre="${escapeHtml(rol.nombre_rol)}"
                                    data-usuarios="${rol.usuarios_asignados || 0}"
                                    title="Eliminar rol">
                                <i class="bi bi-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `);
        });
    }
    
    // Función para actualizar el contador
    function actualizarContador(total, mostrando) {
        let texto = `<i class="bi bi-shield-lock-fill me-1"></i> Mostrando ${mostrando} de ${total} roles`;
        
        if (config.busqueda.termino) {
            texto += ` <span class="badge bg-info ms-2">
                <i class="bi bi-search me-1"></i>Filtrado por: "${config.busqueda.termino}"
            </span>`;
        }
        
        $('#contadorRoles').html(texto);
    }
    
    // Función para generar la paginación
    function generarPaginacion(paginaActual, totalPaginas) {
        console.log('📄 Generando paginación:', { paginaActual, totalPaginas });
        
        const container = $('#paginacionRoles');
        container.empty();
        
        paginaActual = parseInt(paginaActual);
        totalPaginas = parseInt(totalPaginas);
        
        if (totalPaginas < 1) {
            totalPaginas = 1;
        }
        
        // Botón Anterior
        container.append(`
            <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" data-pagina="${paginaActual - 1}" aria-label="Anterior">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `);
        
        // Determinar rango de páginas
        let startPage = Math.max(1, paginaActual - 2);
        let endPage = Math.min(totalPaginas, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        // Primera página si no está en el rango
        if (startPage > 1) {
            container.append(`
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" data-pagina="1">1</a>
                </li>
            `);
            if (startPage > 2) {
                container.append(`
                    <li class="page-item disabled">
                        <a class="page-link" href="javascript:void(0)">...</a>
                    </li>
                `);
            }
        }
        
        // Páginas en el rango
        for (let i = startPage; i <= endPage; i++) {
            container.append(`
                <li class="page-item ${i === paginaActual ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" data-pagina="${i}">${i}</a>
                </li>
            `);
        }
        
        // Última página si no está en el rango
        if (endPage < totalPaginas) {
            if (endPage < totalPaginas - 1) {
                container.append(`
                    <li class="page-item disabled">
                        <a class="page-link" href="javascript:void(0)">...</a>
                    </li>
                `);
            }
            container.append(`
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" data-pagina="${totalPaginas}">${totalPaginas}</a>
                </li>
            `);
        }
        
        // Botón Siguiente
        container.append(`
            <li class="page-item ${paginaActual >= totalPaginas ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" data-pagina="${paginaActual + 1}" aria-label="Siguiente">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `);
        
        // Eventos de paginación
        container.find('.page-link').on('click', function() {
            const pagina = parseInt($(this).data('pagina'));
            if (!isNaN(pagina) && pagina !== paginaActual && !$(this).parent().hasClass('disabled')) {
                cargarRolesPaginados(pagina);
            }
        });
    }
    
    // Función para mostrar errores de paginación
    function mostrarErrorPaginacion(titulo, mensaje) {
        $('#roles-container').html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>${titulo}:</strong> ${mensaje}
                    </div>
                </td>
            </tr>
        `);
        
        $('#paginacionRoles').empty();
        $('#contadorRoles').html(`
            <i class="bi bi-exclamation-circle me-1"></i> 
            Error al cargar roles
        `);
    }
    
    // Cargar estructura de permisos para asignar
    function cargarEstructuraPermisos(containerId) {
        const container = $(`#${containerId}`);
        
        container.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando estructura de permisos...</p>
            </div>
        `);
        
        $.ajax({
            url: '../../controladores/RolesControlador/RolesController.php',
            type: 'GET',
            data: {
                action: 'obtenerEstructuraPermisos',
                submenu_id: config.submenuId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarEstructuraPermisos(response.data, containerId);
                } else {
                    container.html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Error al cargar la estructura de permisos: ${response.message}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error cargando estructura de permisos:', error);
                container.html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error de conexión al cargar permisos
                    </div>
                `);
            }
        });
    }
    
    // Mostrar estructura de permisos
    function mostrarEstructuraPermisos(estructura, containerId, permisosActuales = {}) {
        const container = $(`#${containerId}`);
        let html = '';
        
        estructura.forEach(menu => {
            html += `
                <div class="menu-section mb-4">
                    <div class="form-check menu-check">
                        <input class="form-check-input menu-toggle" type="checkbox" 
                               id="menu_${menu.id_menu}" data-menu-id="${menu.id_menu}">
                        <label class="form-check-label fw-bold text-success fs-5" for="menu_${menu.id_menu}">
                            <i class="bi bi-folder-fill me-2"></i>${escapeHtml(menu.nombre_menu)}
                        </label>
                    </div>
                    
                    <div class="submenu-section ms-4 mt-3 d-none" id="submenus_${menu.id_menu}">
            `;
            
            if (menu.submenus && menu.submenus.length > 0) {
                menu.submenus.forEach(submenu => {
                    const permisosSubmenu = permisosActuales[submenu.id_submenu] || {};
                    
                    html += `
                        <div class="submenu-item mb-3 p-3 border rounded bg-light">
                            <div class="form-check">
                                <input class="form-check-input submenu-toggle" type="checkbox" 
                                       id="submenu_${submenu.id_submenu}" 
                                       data-submenu-id="${submenu.id_submenu}"
                                       ${permisosSubmenu.tiene_acceso ? 'checked' : ''}>
                                <label class="form-check-label fw-semibold text-primary" 
                                       for="submenu_${submenu.id_submenu}">
                                    <i class="bi bi-diagram-3 me-2"></i>${escapeHtml(submenu.nombre_submenu)}
                                </label>
                            </div>
                            
                            <div class="permisos-actions ms-4 mt-2 ${permisosSubmenu.tiene_acceso ? '' : 'd-none'}" 
                                 id="actions_${submenu.id_submenu}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="permisos[${submenu.id_submenu}][]" 
                                                   value="crear" 
                                                   id="crear_${submenu.id_submenu}"
                                                   ${permisosSubmenu.puede_crear ? 'checked' : ''}>
                                            <label class="form-check-label text-success" 
                                                   for="crear_${submenu.id_submenu}">
                                                <i class="bi bi-plus-circle me-1"></i>Crear
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="permisos[${submenu.id_submenu}][]" 
                                                   value="editar" 
                                                   id="editar_${submenu.id_submenu}"
                                                   ${permisosSubmenu.puede_editar ? 'checked' : ''}>
                                            <label class="form-check-label text-warning" 
                                                   for="editar_${submenu.id_submenu}">
                                                <i class="bi bi-pencil me-1"></i>Editar
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="permisos[${submenu.id_submenu}][]" 
                                                   value="eliminar" 
                                                   id="eliminar_${submenu.id_submenu}"
                                                   ${permisosSubmenu.puede_eliminar ? 'checked' : ''}>
                                            <label class="form-check-label text-danger" 
                                                   for="eliminar_${submenu.id_submenu}">
                                                <i class="bi bi-trash me-1"></i>Eliminar
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Este menú no tiene submenús configurados.
                    </div>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
        });
        
        container.html(html);
        
        // Configurar eventos de checkboxes
        configurarEventosPermisos(containerId);
    }
    
    // Configurar eventos para checkboxes de permisos
    function configurarEventosPermisos(containerId) {
        const container = $(`#${containerId}`);
        
        // Toggle menús
        container.find('.menu-toggle').on('change', function() {
            const menuId = $(this).data('menu-id');
            const submenuSection = $(`#submenus_${menuId}`);
            
            if ($(this).is(':checked')) {
                submenuSection.removeClass('d-none');
            } else {
                submenuSection.addClass('d-none');
                // Desmarcar todos los submenús de este menú
                submenuSection.find('.submenu-toggle').prop('checked', false).trigger('change');
            }
        });
        
        // Toggle submenús
        container.find('.submenu-toggle').on('change', function() {
            const submenuId = $(this).data('submenu-id');
            const actionsSection = $(`#actions_${submenuId}`);
            
            if ($(this).is(':checked')) {
                actionsSection.removeClass('d-none');
            } else {
                actionsSection.addClass('d-none');
                // Desmarcar todas las acciones de este submenú
                actionsSection.find('input[type="checkbox"]').prop('checked', false);
            }
        });
        
        // Verificar menús padre cuando se marcan submenús
        container.find('.submenu-toggle').on('change', function() {
            const menuSection = $(this).closest('.menu-section');
            const menuToggle = menuSection.find('.menu-toggle');
            const submenuToggles = menuSection.find('.submenu-toggle');
            const checkedSubmenus = submenuToggles.filter(':checked');
            
            if (checkedSubmenus.length > 0) {
                menuToggle.prop('checked', true);
                menuSection.find('.submenu-section').removeClass('d-none');
            } else if (checkedSubmenus.length === 0) {
                // Solo desmarcar el menú si no hay submenús marcados
                // menuToggle.prop('checked', false);
            }
        });
    }
    
    // Crear rol
    function crearRol(e) {
        e.preventDefault();
        
        if (!validarFormulario('formCrearRol')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'crear');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('📝 Creando rol:', formData.get('nombre_rol'));
        }
        
        $.ajax({
            url: '../../controladores/RolesControlador/RolesController.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Respuesta crear:', response);
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rol creado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#crearRolModal').modal('hide');
                        cargarRolesPaginados(1);
                        cargarEstadisticas();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error desconocido en el servidor'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error crear:', { xhr, status, error });
                
                let errorMsg = 'Error de conexión. Por favor, intenta nuevamente.';
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.message) {
                        errorMsg = jsonResponse.message;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        errorMsg = 'Error del servidor: ' + xhr.responseText.substring(0, 200);
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error en la operación',
                    text: errorMsg
                });
            }
        });
    }
    
    // Editar rol
    function editarRol(e) {
        e.preventDefault();
        
        if (!validarFormulario('formEditarRol')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'editar');
        formData.append('submenu_id', config.submenuId);
       
       if (config.debug) {
           console.log('✏️ Editando rol ID:', formData.get('id_rol'));
       }
       
       $.ajax({
           url: '../../controladores/RolesControlador/RolesController.php',
           method: 'POST',
           data: formData,
           processData: false,
           contentType: false,
           dataType: 'json',
           success: function(response) {
               console.log('✅ Respuesta editar:', response);
               
               if (response.success) {
                   Swal.fire({
                       icon: 'success',
                       title: 'Rol actualizado',
                       text: response.message,
                       timer: 2000,
                       showConfirmButton: false
                   }).then(() => {
                       $('#editarRolModal').modal('hide');
                       cargarRolesPaginados(config.paginacion.paginaActual);
                       cargarEstadisticas();
                   });
               } else {
                   Swal.fire({
                       icon: 'error',
                       title: 'Error',
                       text: response.message || 'Error desconocido en el servidor'
                   });
               }
           },
           error: function(xhr, status, error) {
               console.error('❌ Error editar:', { xhr, status, error });
               
               let errorMsg = 'Error de conexión. Por favor, intenta nuevamente.';
               try {
                   const jsonResponse = JSON.parse(xhr.responseText);
                   if (jsonResponse.message) {
                       errorMsg = jsonResponse.message;
                   }
               } catch (e) {
                   if (xhr.responseText) {
                       errorMsg = 'Error del servidor: ' + xhr.responseText.substring(0, 200);
                   }
               }
               
               Swal.fire({
                   icon: 'error',
                   title: 'Error en la operación',
                   text: errorMsg
               });
           }
       });
   }
   
   // Eliminar rol
   function eliminarRol(e) {
       e.preventDefault();
       
       const formData = new FormData(this);
       formData.append('action', 'eliminar');
       formData.append('submenu_id', config.submenuId);
       
       if (config.debug) {
           console.log('🗑️ Eliminando rol ID:', formData.get('id_rol'));
       }
       
       $.ajax({
           url: '../../controladores/RolesControlador/RolesController.php',
           method: 'POST',
           data: formData,
           processData: false,
           contentType: false,
           dataType: 'json',
           success: function(response) {
               console.log('✅ Respuesta eliminar:', response);
               
               if (response.success) {
                   Swal.fire({
                       icon: 'success',
                       title: 'Rol eliminado',
                       text: response.message,
                       timer: 2000,
                       showConfirmButton: false
                   }).then(() => {
                       $('#eliminarRolModal').modal('hide');
                       cargarRolesPaginados(config.paginacion.paginaActual);
                       cargarEstadisticas();
                   });
               } else {
                   Swal.fire({
                       icon: 'error',
                       title: 'Error',
                       text: response.message || 'Error desconocido en el servidor'
                   });
               }
           },
           error: function(xhr, status, error) {
               console.error('❌ Error eliminar:', { xhr, status, error });
               
               let errorMsg = 'Error de conexión. Por favor, intenta nuevamente.';
               try {
                   const jsonResponse = JSON.parse(xhr.responseText);
                   if (jsonResponse.message) {
                       errorMsg = jsonResponse.message;
                   }
               } catch (e) {
                   if (xhr.responseText) {
                       errorMsg = 'Error del servidor: ' + xhr.responseText.substring(0, 200);
                   }
               }
               
               Swal.fire({
                   icon: 'error',
                   title: 'Error en la operación',
                   text: errorMsg
               });
           }
       });
   }
   
   // Cargar datos en modal de edición
   function cargarDatosEdicion(e) {
       const btn = e.relatedTarget;
       if (!btn) return;
       
       try {
           const modal = $(this);
           const idRol = btn.dataset.id;
           const nombreRol = btn.dataset.nombre;
           
           modal.find('#edit_id').val(idRol);
           modal.find('#edit_nombre_rol').val(nombreRol);
           
           // Cargar permisos actuales del rol
           cargarPermisosParaEdicion(idRol);
           
           if (config.debug) {
               console.log('📝 Datos cargados para edición:', {
                   id: idRol,
                   nombre: nombreRol
               });
           }
       } catch (error) {
           console.error('❌ Error cargando datos de edición:', error);
       }
   }
   
   // Cargar permisos actuales para edición
   function cargarPermisosParaEdicion(idRol) {
       const container = $('#edit-permisos-container');
       
       container.html(`
           <div class="text-center py-4">
               <div class="spinner-border text-primary" role="status">
                   <span class="visually-hidden">Cargando...</span>
               </div>
               <p class="mt-2 text-muted">Cargando permisos actuales...</p>
           </div>
       `);
       
       $.ajax({
           url: '../../controladores/RolesControlador/RolesController.php',
           type: 'GET',
           data: {
               action: 'obtenerPermisosPorRol',
               id_rol: idRol,
               submenu_id: config.submenuId
           },
           dataType: 'json',
           success: function(response) {
               if (response.success) {
                   // Convertir los permisos a formato esperado
                   const permisosActuales = {};
                   response.data.forEach(menu => {
                       menu.submenus.forEach(submenu => {
                           permisosActuales[submenu.id_submenu] = {
                               tiene_acceso: submenu.puede_crear || submenu.puede_editar || submenu.puede_eliminar,
                               puede_crear: submenu.puede_crear,
                               puede_editar: submenu.puede_editar,
                               puede_eliminar: submenu.puede_eliminar
                           };
                       });
                   });
                   
                   mostrarEstructuraPermisos(response.data, 'edit-permisos-container', permisosActuales);
               } else {
                   container.html(`
                       <div class="alert alert-danger">
                           <i class="bi bi-exclamation-triangle me-2"></i>
                           Error al cargar permisos: ${response.message}
                       </div>
                   `);
               }
           },
           error: function(xhr, status, error) {
               console.error('Error cargando permisos para edición:', error);
               container.html(`
                   <div class="alert alert-danger">
                       <i class="bi bi-exclamation-triangle me-2"></i>
                       Error de conexión al cargar permisos
                   </div>
               `);
           }
       });
   }
   
   // Cargar datos en modal de eliminación
   function cargarDatosEliminacion(e) {
       const btn = e.relatedTarget;
       if (!btn) return;
       
       try {
           const modal = $(this);
           const idRol = btn.dataset.id;
           const nombreRol = btn.dataset.nombre;
           const usuariosAsignados = parseInt(btn.dataset.usuarios) || 0;
           
           modal.find('#delete_id').val(idRol);
           modal.find('#delete_nombre_rol').text(nombreRol);
           
           // Mostrar/ocultar advertencia de usuarios asignados
           const warningElement = modal.find('#usuarios-asignados-warning');
           const submitButton = modal.find('button[type="submit"]');
           
           if (usuariosAsignados > 0) {
               modal.find('#cantidad-usuarios').text(usuariosAsignados);
               warningElement.show();
               submitButton.prop('disabled', true).addClass('disabled');
           } else {
               warningElement.hide();
               submitButton.prop('disabled', false).removeClass('disabled');
           }
           
           if (config.debug) {
               console.log('🗑️ Datos cargados para eliminación:', {
                   id: idRol,
                   nombre: nombreRol,
                   usuarios: usuariosAsignados
               });
           }
       } catch (error) {
           console.error('❌ Error cargando datos de eliminación:', error);
       }
   }
   
   // Cargar permisos detallados para visualización
   function cargarPermisosDetalle(e) {
       const btn = e.relatedTarget;
       if (!btn) return;
       
       try {
           const modal = $(this);
           const idRol = btn.dataset.id;
           const nombreRol = btn.dataset.nombre;
           const container = modal.find('#permisos-detalle-container');
           
           modal.find('.modal-title').html(`
               <i class="bi bi-eye me-1"></i>Permisos del Rol: <strong class="text-info">${escapeHtml(nombreRol)}</strong>
           `);
           
           container.html(`
               <div class="text-center py-4">
                   <div class="spinner-border text-primary" role="status">
                       <span class="visually-hidden">Cargando...</span>
                   </div>
                   <p class="mt-2 text-muted">Cargando permisos detallados...</p>
               </div>
           `);
           
           $.ajax({
               url: '../../controladores/RolesControlador/RolesController.php',
               type: 'GET',
               data: {
                   action: 'obtenerPermisosPorRol',
                   id_rol: idRol,
                   submenu_id: config.submenuId
               },
               dataType: 'json',
               success: function(response) {
                   if (response.success) {
                       mostrarPermisosDetalle(response.data, container);
                   } else {
                       container.html(`
                           <div class="alert alert-danger">
                               <i class="bi bi-exclamation-triangle me-2"></i>
                               Error al cargar permisos: ${response.message}
                           </div>
                       `);
                   }
               },
               error: function(xhr, status, error) {
                   console.error('Error cargando permisos detallados:', error);
                   container.html(`
                       <div class="alert alert-danger">
                           <i class="bi bi-exclamation-triangle me-2"></i>
                           Error de conexión al cargar permisos
                       </div>
                   `);
               }
           });
           
           if (config.debug) {
               console.log('👁️ Visualizando permisos del rol:', {
                   id: idRol,
                   nombre: nombreRol
               });
           }
       } catch (error) {
           console.error('❌ Error cargando permisos detallados:', error);
       }
   }
   
   // Mostrar permisos detallados en formato de solo lectura
   function mostrarPermisosDetalle(permisos, container) {
       let html = '';
       let totalPermisos = 0;
       
       if (!permisos || permisos.length === 0) {
           html = `
               <div class="alert alert-warning">
                   <i class="bi bi-exclamation-triangle me-2"></i>
                   Este rol no tiene permisos asignados.
               </div>
           `;
       } else {
           permisos.forEach(menu => {
               const submenusConPermisos = menu.submenus.filter(s => 
                   s.puede_crear || s.puede_editar || s.puede_eliminar
               );
               
               if (submenusConPermisos.length > 0) {
                   html += `
                       <div class="card mb-3">
                           <div class="card-header bg-primary text-white">
                               <h6 class="mb-0">
                                   <i class="bi bi-folder-fill me-2"></i>${escapeHtml(menu.nombre_menu)}
                               </h6>
                           </div>
                           <div class="card-body">
                               <div class="row">
                   `;
                   
                   submenusConPermisos.forEach(submenu => {
                       totalPermisos++;
                       const permisosBadges = [];
                       
                       if (submenu.puede_crear) {
                           permisosBadges.push('<span class="badge bg-success me-1"><i class="bi bi-plus-circle me-1"></i>Crear</span>');
                       }
                       if (submenu.puede_editar) {
                           permisosBadges.push('<span class="badge bg-warning me-1"><i class="bi bi-pencil me-1"></i>Editar</span>');
                       }
                       if (submenu.puede_eliminar) {
                           permisosBadges.push('<span class="badge bg-danger me-1"><i class="bi bi-trash me-1"></i>Eliminar</span>');
                       }
                       
                       html += `
                           <div class="col-md-6 mb-3">
                               <div class="border rounded p-3 bg-light">
                                   <h6 class="text-primary mb-2">
                                       <i class="bi bi-diagram-3 me-1"></i>${escapeHtml(submenu.nombre_submenu)}
                                   </h6>
                                   <div>
                                       ${permisosBadges.join('')}
                                   </div>
                               </div>
                           </div>
                       `;
                   });
                   
                   html += `
                               </div>
                           </div>
                       </div>
                   `;
               }
           });
           
           // Resumen de permisos
           if (totalPermisos > 0) {
               html = `
                   <div class="alert alert-info mb-3">
                       <i class="bi bi-info-circle me-2"></i>
                       <strong>Total de módulos con permisos: ${totalPermisos}</strong>
                   </div>
               ` + html;
           }
       }
       
       container.html(html);
   }
   
   // Validar formulario
   function validarFormulario(formId) {
       const form = document.getElementById(formId);
       if (!form) return false;
       
       const requiredFields = form.querySelectorAll('[required]');
       let isValid = true;
       
       requiredFields.forEach(field => {
           if (!field.value.trim()) {
               field.classList.add('is-invalid');
               isValid = false;
           } else if (field.value.trim().length < 3) {
               field.classList.add('is-invalid');
               isValid = false;
           } else {
               field.classList.remove('is-invalid');
           }
       });
       
       // Validar que al menos un permiso esté seleccionado (solo para crear/editar)
       if (formId === 'formCrearRol' || formId === 'formEditarRol') {
           const permisosSeleccionados = form.querySelectorAll('input[name^="permisos"]:checked');
           if (permisosSeleccionados.length === 0) {
               Swal.fire({
                   icon: 'warning',
                   title: 'Permisos requeridos',
                   text: 'Debe asignar al menos un permiso al rol',
                   timer: 3000,
                   showConfirmButton: false
               });
               isValid = false;
           }
       }
       
       if (!isValid) {
           Swal.fire({
               icon: 'error',
               title: 'Error de validación',
               text: 'Por favor, completa todos los campos correctamente',
               timer: 3000,
               showConfirmButton: false
           });
       }
       
       return isValid;
   }
   
   // Limpiar formularios
   function limpiarFormulario(formId) {
       const form = document.getElementById(formId);
       if (form) {
           form.reset();
           $(form).find('.is-invalid').removeClass('is-invalid');
           
           // Limpiar contenedores de permisos
           if (formId === 'formCrearRol') {
               $('#permisos-container').html(`
                   <div class="text-center py-4">
                       <div class="spinner-border text-primary" role="status">
                           <span class="visually-hidden">Cargando permisos...</span>
                       </div>
                       <p class="mt-2 text-muted">Cargando estructura de permisos...</p>
                   </div>
               `);
           } else if (formId === 'formEditarRol') {
               $('#edit-permisos-container').empty();
           }
       }
   }
   
   // Función de escape HTML
   function escapeHtml(text) {
       if (!text) return '';
       const map = {
           '&': '&amp;',
           '<': '&lt;',
           '>': '&gt;',
           '"': '&quot;',
           "'": '&#039;'
       };
       return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
   }
});