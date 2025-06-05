$(document).ready(function() {
    // Configuración inicial
    const config = {
        submenuId: window.gestionMenus?.submenuId || 0,
        permisos: window.gestionMenus?.permisos || {},
        debug: window.gestionMenus?.debug || false,
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
    
    console.log('🚀 Inicializando gestión de menús...', config);
    
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
    cargarMenusPaginados(1);

    // Configurar búsqueda en tiempo real
    function configurarBusqueda() {
        const inputBusqueda = $('#buscarMenu');
        const btnLimpiar = $('#limpiarBusqueda');
        
        console.log('🔍 Configurando búsqueda...');
        
        let searchTimeout;
        
        // Búsqueda en tiempo real
        inputBusqueda.on('input keyup', function(e) {
            const termino = $(this).val().trim();
            config.busqueda.termino = termino;
            
            console.log('🔍 Búsqueda:', termino);
            
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                console.log('🚀 Ejecutando búsqueda para:', termino);
                cargarMenusPaginados(1);
            }, 400);
        });
        
        // Limpiar búsqueda
        btnLimpiar.on('click', function() {
            console.log('🗑️ Limpiando búsqueda...');
            inputBusqueda.val('');
            config.busqueda.termino = '';
            clearTimeout(searchTimeout);
            cargarMenusPaginados(1);
        });
        
        // ESC para limpiar
        inputBusqueda.on('keydown', function(e) {
            if (e.key === 'Escape') {
                $(this).val('');
                config.busqueda.termino = '';
                clearTimeout(searchTimeout);
                cargarMenusPaginados(1);
            }
        });
    }

    // En gestionmenus.js, agregar estas funciones:

// Cargar estadísticas dinámicamente
function cargarEstadisticas() {
    console.log('📊 Cargando estadísticas...');
    
    $.ajax({
        url: '../../controladores/MenusControlador/MenusController.php',
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
    $('#totalMenus').html(`
        <span class="counter-animation" data-target="${datos.total_menus}">0</span>
    `);
    
    $('#menusActivos').html(`
        <span class="counter-animation" data-target="${datos.menus_activos}">0</span>
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
    $('#totalMenus').html('<span class="text-danger">Error</span>');
    $('#menusActivos').html('<span class="text-danger">Error</span>');
}

// Modificar las funciones de CRUD para actualizar estadísticas:

// En crearMenu - después del éxito:
if (response.success) {
    Swal.fire({
        icon: 'success',
        title: 'Menú creado',
        text: response.message,
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        $('#crearMenuModal').modal('hide');
        cargarMenusPaginados(1);
        cargarEstadisticas(); // ⭐ AGREGAR ESTA LÍNEA
    });
}

// En editarMenu - después del éxito:
if (response.success) {
    Swal.fire({
        icon: 'success',
        title: 'Menú actualizado',
        text: response.message,
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        $('#editarMenuModal').modal('hide');
        cargarMenusPaginados(config.paginacion.paginaActual);
        cargarEstadisticas(); // ⭐ AGREGAR ESTA LÍNEA
    });
}

// En eliminarMenu - después del éxito:
if (response.success) {
    Swal.fire({
        icon: 'success',
        title: 'Menú eliminado',
        text: response.message,
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        $('#eliminarMenuModal').modal('hide');
        cargarMenusPaginados(config.paginacion.paginaActual);
        cargarEstadisticas(); // ⭐ AGREGAR ESTA LÍNEA
    });
}

// Modificar la inicialización:
$(document).ready(function() {
    // ... código existente ...
    
    // Inicializar componentes
    inicializarValidaciones();
    configurarEventos();
    configurarBusqueda();
    cargarEstadisticas(); // ⭐ AGREGAR ESTA LÍNEA
    cargarMenusPaginados(1);
});

    // Validaciones de entrada
    function inicializarValidaciones() {
        // Validación para nombres de menús (solo letras, números, espacios y algunos caracteres especiales)
        ['nombre_menu', 'edit_nombre_menu'].forEach(id => {
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
        $('#formCrearMenu').on('submit', crearMenu);
        $('#formEditarMenu').on('submit', editarMenu);
        $('#formEliminarMenu').on('submit', eliminarMenu);
        $('#editarMenuModal').on('show.bs.modal', cargarDatosEdicion);
        $('#eliminarMenuModal').on('show.bs.modal', cargarDatosEliminacion);
        
        $('#crearMenuModal').on('hidden.bs.modal', function() {
            limpiarFormulario('formCrearMenu');
        });
        
        $('#editarMenuModal').on('hidden.bs.modal', function() {
            limpiarFormulario('formEditarMenu');
        });
        
        if (config.debug) {
            console.log('✅ Eventos configurados correctamente');
        }
    }
    
    // Función para cargar menús paginados
    function cargarMenusPaginados(pagina = 1) {
        console.log('🔄 CARGANDO PÁGINA:', pagina);
        
        const container = $('#menus-container');
        
        // Transición suave
        container.css({
            'opacity': '0.6',
            'pointer-events': 'none'
        });
        
        // Loading elegante
        container.html(`
            <tr>
                <td colspan="3" class="text-center py-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="spinner-border text-primary me-3" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span class="text-muted fs-6">
                            ${config.busqueda.termino ? 
                                `Buscando "${config.busqueda.termino}"...` : 
                                'Cargando menús...'
                            }
                        </span>
                    </div>
                </td>
            </tr>
        `);
        
        config.paginacion.paginaActual = pagina;
        
        $.ajax({
            url: '../../controladores/MenusControlador/MenusController.php',
            type: 'GET',
            data: {
                action: 'obtenerMenusPaginados',
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
                    
                    mostrarMenusPaginados(response.data);
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
                    mostrarErrorPaginacion('No se pudieron cargar los menús', response.message || 'Error desconocido');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ ERROR AJAX:', { xhr, status, error });
                mostrarErrorPaginacion('Error de conexión', 'No se pudo establecer conexión con el servidor');
            }
        });
    }
    
    // Función para mostrar menús en la tabla
    function mostrarMenusPaginados(menus) {
        const container = $('#menus-container');
        container.empty();
        
        if (!menus || menus.length === 0) {
            let mensaje = 'No se encontraron menús';
            if (config.busqueda.termino) {
                mensaje = `No se encontraron menús que coincidan con: "${config.busqueda.termino}"`;
            }
            
            container.html(`
                <tr>
                    <td colspan="3" class="text-center py-4">
                        <div class="alert alert-info mb-0" style="margin: 0 auto; max-width: 400px;">
                            <i class="bi bi-info-circle me-2"></i> ${mensaje}
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        // Generar filas
        menus.forEach(function(menu) {
            container.append(`
                <tr>
                    <td><span class="badge bg-secondary">${escapeHtml(menu.id_menu)}</span></td>
                    <td>
                        <i class="bi bi-menu-button-wide me-2 text-primary"></i>
                        <strong>${escapeHtml(menu.nombre_menu)}</strong>
                    </td>
                    <td>
                        <div class="btn-group">
                            ${config.permisos.puede_editar ? `
                            <button class="btn btn-sm btn-warning me-1 btn-editar"
                                    data-bs-toggle="modal" data-bs-target="#editarMenuModal"
                                    data-id="${menu.id_menu}"
                                    data-nombre="${escapeHtml(menu.nombre_menu)}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            ` : ''}
                            ${config.permisos.puede_eliminar ? `
                            <button class="btn btn-sm btn-danger btn-eliminar"
                                    data-bs-toggle="modal" data-bs-target="#eliminarMenuModal"
                                    data-id="${menu.id_menu}"
                                    data-nombre="${escapeHtml(menu.nombre_menu)}">
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
        let texto = `<i class="bi bi-menu-button-wide-fill me-1"></i> Mostrando ${mostrando} de ${total} menús`;
        
        if (config.busqueda.termino) {
            texto += ` <span class="badge bg-info ms-2">
                <i class="bi bi-search me-1"></i>Filtrado por: "${config.busqueda.termino}"
            </span>`;
        }
        
        $('#contadorMenus').html(texto);
    }
    
    // Función para generar la paginación
    function generarPaginacion(paginaActual, totalPaginas) {
        console.log('📄 Generando paginación:', { paginaActual, totalPaginas });
        
        const container = $('#paginacionMenus');
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
                cargarMenusPaginados(pagina);
            }
        });
    }
    
    // Función para mostrar errores de paginación
    function mostrarErrorPaginacion(titulo, mensaje) {
        $('#menus-container').html(`
            <tr>
                <td colspan="3" class="text-center py-4">
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>${titulo}:</strong> ${mensaje}
                    </div>
                </td>
            </tr>
        `);
        
        $('#paginacionMenus').empty();
        $('#contadorMenus').html(`
            <i class="bi bi-exclamation-circle me-1"></i> 
            Error al cargar menús
        `);
    }
    
    // Crear menú
    function crearMenu(e) {
        e.preventDefault();
        
        if (!validarFormulario('formCrearMenu')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'crear');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('📝 Creando menú:', formData.get('nombre_menu'));
        }
        
        $.ajax({
            url: '../../controladores/MenusControlador/MenusController.php',
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
                        title: 'Menú creado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#crearMenuModal').modal('hide');
                        cargarMenusPaginados(1);
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
    
    // Editar menú
    function editarMenu(e) {
        e.preventDefault();
        
        if (!validarFormulario('formEditarMenu')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'editar');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('✏️ Editando menú ID:', formData.get('id_menu'));
        }
        
        $.ajax({
            url: '../../controladores/MenusControlador/MenusController.php',
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
                        title: 'Menú actualizado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#editarMenuModal').modal('hide');
                        cargarMenusPaginados(config.paginacion.paginaActual);
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
    
    // Eliminar menú
    function eliminarMenu(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'eliminar');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('🗑️ Eliminando menú ID:', formData.get('id_menu'));
        }
        
        $.ajax({
            url: '../../controladores/MenusControlador/MenusController.php',
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
                        title: 'Menú eliminado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#eliminarMenuModal').modal('hide');
                        cargarMenusPaginados(config.paginacion.paginaActual);
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
            modal.find('#edit_id').val(btn.dataset.id);
            modal.find('#edit_nombre_menu').val(btn.dataset.nombre);
            
            if (config.debug) {
                console.log('📝 Datos cargados para edición:', {
                    id: btn.dataset.id,
                    nombre: btn.dataset.nombre
                });
            }
        } catch (error) {
            console.error('❌ Error cargando datos de edición:', error);
        }
    }
    
    // Cargar datos en modal de eliminación
    function cargarDatosEliminacion(e) {
        const btn = e.relatedTarget;
        if (!btn) return;
        
        try {
            const modal = $(this);
            modal.find('#delete_id').val(btn.dataset.id);
            modal.find('#delete_nombre_menu').text(btn.dataset.nombre);
            
            if (config.debug) {
                console.log('🗑️ Datos cargados para eliminación:', {
                    id: btn.dataset.id,
                    nombre: btn.dataset.nombre
                });
            }
        } catch (error) {
            console.error('❌ Error cargando datos de eliminación:', error);
        }
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
        
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'El nombre del menú debe tener al menos 3 caracteres',
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