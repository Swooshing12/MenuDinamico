$(document).ready(function() {
    // Configuración inicial
    const config = {
        submenuId: window.gestionUsuarios?.submenuId || 0,
        permisos: window.gestionUsuarios?.permisos || {},
        debug: window.gestionUsuarios?.debug || false,
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
    
    console.log('Inicializando gestión de usuarios...', config);
    
    // Verificar Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('ERROR: Bootstrap no está cargado correctamente');
    }
    
    // Inicializar componentes
    inicializarValidaciones();
    cargarPaises();
    configurarEventos();
    configurarBusqueda();
    cargarUsuariosPaginados(1);

    // Configurar búsqueda en tiempo real
    // Configurar búsqueda en tiempo real - VERSIÓN CORREGIDA
// ⭐ BÚSQUEDA SIMPLIFICADA - Sin timeouts complejos
function configurarBusqueda() {
    const inputBusqueda = $('#buscarUsuario');
    const btnLimpiar = $('#limpiarBusqueda');
    
    console.log('🔍 Configurando búsqueda...');
    
    // ⭐ BÚSQUEDA CON DEBOUNCE SIMPLE
    let searchTimeout;
    
    inputBusqueda.on('input keyup', function(e) {
        const termino = $(this).val().trim();
        config.busqueda.termino = termino;
        
        // Limpiar timeout anterior
        clearTimeout(searchTimeout);
        
        // ⭐ BÚSQUEDA CON DELAY CORTO PERO EFECTIVO
        searchTimeout = setTimeout(() => {
            console.log('🚀 Buscando:', termino);
            cargarUsuariosPaginados(1);
        }, 400); // 400ms es suficiente
    });
    
    // Limpiar búsqueda
    btnLimpiar.on('click', function() {
        console.log('🗑️ Limpiando búsqueda...');
        inputBusqueda.val('');
        config.busqueda.termino = '';
        clearTimeout(searchTimeout);
        cargarUsuariosPaginados(1);
    });
    
    // ESC para limpiar
    inputBusqueda.on('keydown', function(e) {
        if (e.key === 'Escape') {
            $(this).val('');
            config.busqueda.termino = '';
            clearTimeout(searchTimeout);
            cargarUsuariosPaginados(1);
        }
    });
}
    // Validaciones de entrada
    function inicializarValidaciones() {
        ['cedula', 'edit_cedula'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15);
                });
            }
        });
        
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', function() {
                if (this.value.length > 0 && this.value.length < 6) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        const editPasswordField = document.getElementById('edit_password');
        if (editPasswordField) {
            editPasswordField.addEventListener('input', function() {
                if (this.value.length > 0 && this.value.length < 6) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
    }
    
    // Cargar países y configurar Select2
    function cargarPaises() {
        fetch("https://restcountries.com/v2/all?fields=name,alpha2Code,flag,demonym")
            .then(r => {
                if (!r.ok) throw new Error(`Error HTTP: ${r.status}`);
                return r.json();
            })
            .then(data => {
                const paises = data.filter(c => c.demonym).map(c => ({
                    code: c.alpha2Code,
                    name: c.name,
                    gentilicio: c.name.toLowerCase() === 'ecuador' ? 'Ecuadorean' : c.demonym,
                    flag: c.flag
                })).sort((a, b) => a.gentilicio.localeCompare(b.gentilicio));

                if (config.debug) {
                    console.log(`Países cargados: ${paises.length}`);
                }

                ['nacionalidadSelect', 'edit_nacionalidadSelect'].forEach(id => {
                    const $sel = $(`#${id}`);
                    if ($sel.length) {
                        $sel.find('option:not(:first)').remove();
                        
                        paises.forEach(p => {
                            $sel.append(new Option(`${p.gentilicio} (${p.name})`, p.gentilicio))
                                .find(`option[value="${p.gentilicio}"]`)
                                .attr('data-flag', p.flag);
                        });
                        
                        try {
                            $sel.select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Seleccione nacionalidad',
                                dropdownParent: $sel.closest('.modal'),
                                templateResult: formatCountry,
                                templateSelection: formatCountry,
                                minimumResultsForSearch: 0,
                                width: '100%',
                                language: 'es'
                            });
                        } catch (error) {
                            console.error('Error inicializando Select2:', error);
                            $sel.removeClass('select2');
                        }
                    }
                });
            })
            .catch(e => {
                console.error('Error cargando países:', e);
                Swal.fire('Error', 'No se pudieron cargar los países. Por favor, recarga la página.', 'error');
            });
    }
    
    function formatCountry(state) {
        if (!state.id) return state.text;
        const flag = $(state.element).data('flag');
        if (flag) {
            return $(`<span><img src="${flag}" style="width:20px;margin-right:8px;"/>${state.text}</span>`);
        }
        return state.text;
    }
    
    // Configurar eventos
    function configurarEventos() {
        $('#btnBuscarCedula').on('click', buscarPorCedula);
        $('#formCrearUsuario').on('submit', crearUsuario);
        $('#formEditarUsuario').on('submit', editarUsuario);
        $('#formEliminarUsuario').on('submit', eliminarUsuario);
        $('#editarUsuarioModal').on('show.bs.modal', cargarDatosEdicion);
        $('#eliminarUsuarioModal').on('show.bs.modal', cargarDatosEliminacion);
        
        $('#crearUsuarioModal').on('hidden.bs.modal', function() {
            limpiarFormulario('formCrearUsuario');
        });
        
        $('#editarUsuarioModal').on('hidden.bs.modal', function() {
            limpiarFormulario('formEditarUsuario');
        });
        
        if (config.debug) {
            $('#debugInfo').removeClass('d-none');
        }
    }
    
    // Función para cargar usuarios paginados con búsqueda
    // VERSIÓN SIMPLIFICADA Y SUAVE para cargarUsuariosPaginados
function cargarUsuariosPaginados(pagina = 1) {
    console.log('🔄 CARGANDO PÁGINA:', pagina);
    
    const container = $('#usuarios-container');
    
    // ⭐ TRANSICIÓN SIMPLE Y SUAVE - Sin clases complejas
    container.css({
        'opacity': '0.6',
        'pointer-events': 'none'
    });
    
    // Loading simple y elegante
    container.html(`
        <tr>
            <td colspan="10" class="text-center py-4">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="spinner-border text-primary me-3" role="status" style="width: 2rem; height: 2rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <span class="text-muted fs-6">
                        ${config.busqueda.termino ? 
                            `Buscando "${config.busqueda.termino}"...` : 
                            'Cargando usuarios...'
                        }
                    </span>
                </div>
            </td>
        </tr>
    `);
    
    config.paginacion.paginaActual = pagina;
    
    const urlParams = new URLSearchParams(window.location.search);
    const filtro = urlParams.get('filtro') || 'todos';
    
    $.ajax({
        url: '../../controladores/UsuariosControlador/UsuariosController.php',
        type: 'GET',
        data: {
            action: 'obtenerUsuariosPaginados',
            pagina: pagina,
            limit: config.paginacion.registrosPorPagina,
            filtro: filtro,
            busqueda: config.busqueda.termino,
            submenu_id: config.submenuId
        },
        dataType: 'json',
        success: function(response) {
            console.log('✅ RESPUESTA:', response);
            
            if (response.success) {
                config.paginacion.totalRegistros = response.totalRegistros;
                config.paginacion.totalPaginas = response.totalPaginas;
                config.paginacion.paginaActual = response.paginaActual;
                
                // ⭐ MOSTRAR RESULTADOS CON TRANSICIÓN SUAVE
                mostrarUsuariosPaginados(response.data);
                actualizarContador(response.totalRegistros, response.mostrando);
                generarPaginacion(response.paginaActual, response.totalPaginas);
                
                // ⭐ RESTAURAR VISIBILIDAD SUAVEMENTE
                setTimeout(() => {
                    container.css({
                        'opacity': '1',
                        'pointer-events': 'auto'
                    });
                }, 100);
                
            } else {
                mostrarErrorPaginacion('No se pudieron cargar los usuarios', response.message || 'Error desconocido');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ ERROR AJAX:', { xhr, status, error });
            mostrarErrorPaginacion('Error de conexión', 'No se pudo establecer conexión con el servidor');
        }
    });
}

    
   // ⭐ VERSIÓN SIMPLIFICADA de mostrarUsuariosPaginados
function mostrarUsuariosPaginados(usuarios) {
    const container = $('#usuarios-container');
    container.empty();
    
    if (!usuarios || usuarios.length === 0) {
        let mensaje = 'No se encontraron usuarios';
        if (config.busqueda.termino) {
            mensaje = `No se encontraron usuarios que coincidan con: "${config.busqueda.termino}"`;
        }
        
        container.html(`
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="alert alert-info mb-0" style="margin: 0 auto; max-width: 400px;">
                        <i class="bi bi-info-circle me-2"></i> ${mensaje}
                    </div>
                </td>
            </tr>
        `);
        return;
    }
    
    // ⭐ GENERAR FILAS DIRECTAMENTE - Sin animaciones complejas
    usuarios.forEach(function(u) {
        let estadoBadge = '';
        switch (parseInt(u.id_estado)) {
            case 1:
                estadoBadge = '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Activo</span>';
                break;
            case 2:
                estadoBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i> Bloqueado</span>';
                break;
            case 3:
                estadoBadge = '<span class="badge bg-info text-dark"><i class="bi bi-hourglass-split me-1"></i> Pendiente</span>';
                break;
            case 4:
                estadoBadge = '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill me-1"></i> Inactivo</span>';
                break;
            default:
                estadoBadge = '<span class="badge bg-dark"><i class="bi bi-question-circle-fill me-1"></i> Desconocido</span>';
        }
        
        let rolNombre = 'Sin rol';
        if (window.roles && window.roles.length > 0) {
            const rol = window.roles.find(r => r.id_rol == u.id_rol);
            if (rol) {
                rolNombre = escapeHtml(rol.nombre_rol);
            }
        }
        
        container.append(`
            <tr>
                <td>${escapeHtml(u.cedula)}</td>
                <td><i class="bi bi-person-fill"></i> ${escapeHtml(u.username)}</td>
                <td>${escapeHtml(u.nombres)}</td>
                <td>${escapeHtml(u.apellidos)}</td>
                <td>${u.sexo === 'M' ? '<i class="bi bi-gender-male text-primary"></i> M' : '<i class="bi bi-gender-female text-danger"></i> F'}</td>
                <td>
                    <span class="nacionalidad-banderita" data-nacionalidad="${escapeHtml(u.nacionalidad)}">
                        ${escapeHtml(u.nacionalidad)}
                    </span>
                </td>
                <td><i class="bi bi-envelope-fill"></i> ${escapeHtml(u.correo)}</td>
                <td><span class="badge bg-primary">${rolNombre}</span></td>
                <td>${estadoBadge}</td>
                <td>
                    <div class="btn-group">
                        ${config.permisos.puede_editar ? `
                        <button class="btn btn-sm btn-warning me-1 btn-editar"
                                data-bs-toggle="modal" data-bs-target="#editarUsuarioModal"
                                data-id="${u.id_usuario}"
                                data-cedula="${escapeHtml(u.cedula)}"
                                data-username="${escapeHtml(u.username)}"
                                data-nombres="${escapeHtml(u.nombres)}"
                                data-apellidos="${escapeHtml(u.apellidos)}"
                                data-sexo="${escapeHtml(u.sexo)}"
                                data-nacionalidad="${escapeHtml(u.nacionalidad)}"
                                data-correo="${escapeHtml(u.correo)}"
                                data-rol="${u.id_rol}"
                                data-estado="${u.id_estado}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        ` : ''}
                        ${config.permisos.puede_eliminar ? `
                        <button class="btn btn-sm btn-danger btn-eliminar"
                                data-bs-toggle="modal" data-bs-target="#eliminarUsuarioModal"
                                data-id="${u.id_usuario}"
                                data-username="${escapeHtml(u.username)}">
                            <i class="bi bi-person-x-fill"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `);
    });
    
    cargarBanderas();
}

    
    // Función para actualizar el contador de registros
    function actualizarContador(total, mostrando) {
    let texto = `<i class="bi bi-people-fill me-1"></i> Mostrando ${mostrando} de ${total} usuarios`;
    
    if (config.busqueda.termino) {
        texto += ` <span class="badge bg-info ms-2">
            <i class="bi bi-search me-1"></i>Filtrado por: "${config.busqueda.termino}"
        </span>`;
    }
    
    $('#contadorUsuarios').html(texto);
}
    // Función para generar la paginación (SIEMPRE VISIBLE)
function generarPaginacion(paginaActual, totalPaginas) {
    console.log('🔍 DEBUG PAGINACIÓN:', { 
        paginaActual: paginaActual, 
        totalPaginas: totalPaginas
    });
    
    const container = $('#paginacionUsuarios');
    container.empty();
    
    // Convertir a números para asegurar comparaciones correctas
    paginaActual = parseInt(paginaActual);
    totalPaginas = parseInt(totalPaginas);
    
    // ⭐ CAMBIO PRINCIPAL: Asegurar que siempre haya al menos 1 página
    if (totalPaginas < 1) {
        totalPaginas = 1;
    }
    
    console.log('📊 Paginación actualizada:', { paginaActual, totalPaginas });
    
    // ⭐ ELIMINAR ESTA CONDICIÓN - Ahora SIEMPRE se muestra la paginación
    // if (totalPaginas <= 1) {
    //     return;
    // }
    
    console.log('✅ Generando botones de paginación...');
    
    // Botón Anterior
    container.append(`
        <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-pagina="${paginaActual - 1}" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `);
    
    // Determinar rango de páginas a mostrar (máximo 5)
    let startPage = Math.max(1, paginaActual - 2);
    let endPage = Math.min(totalPaginas, startPage + 4);
    
    // Ajustar el rango si estamos cerca del final
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    // Mostrar primera página si no está en el rango
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
    
    // Mostrar última página si no está en el rango
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
    
    // Agregar eventos a los botones de paginación
    container.find('.page-link').on('click', function() {
        const pagina = parseInt($(this).data('pagina'));
        if (!isNaN(pagina) && pagina !== paginaActual && !$(this).parent().hasClass('disabled')) {
            cargarUsuariosPaginados(pagina);
        }
    });
    
    console.log('🎉 Paginación completada');
}
    
    // Función para mostrar errores de paginación
    function mostrarErrorPaginacion(titulo, mensaje) {
        $('#usuarios-container').html(`
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>${titulo}:</strong> ${mensaje}
                    </div>
                </td>
            </tr>
        `);
        
        $('#paginacionUsuarios').empty();
        $('#contadorUsuarios').html(`
            <i class="bi bi-exclamation-circle me-1"></i> 
            Error al cargar usuarios
        `);
    }
    
    // Buscar datos por cédula
    function buscarPorCedula() {
        const cedula = $('#cedula').val().trim();
        if (!cedula) {
            return Swal.fire('Error', 'Por favor, ingresa una cédula', 'error');
        }
        
        fetch(`../../controladores/obtenerDatos.php?cedula=${cedula}`)
            .then(r => {
                if (!r.ok) throw new Error(`Error HTTP: ${r.status}`);
                return r.json();
            })
            .then(json => {
                if (config.debug) {
                    console.log('Respuesta de búsqueda por cédula:', json);
                }
                
                if (json.estado !== 'OK' || !json.resultado?.length) {
                    return Swal.fire('Error', 'No se encontraron datos para la cédula ingresada.', 'error');
                }
                
                const c = json.resultado[0];
                const palabras = c.nombre.split(' ');
                
                $('#apellidos').val(palabras.slice(0, 2).join(' '));
                $('#nombres').val(palabras.slice(2).join(' '));
                $('#cedula').val(c.cedula).prop('readonly', true);
                
                if (c.condicionCiudadano.toUpperCase() === 'CIUDADANO') {
                    $('#nacionalidadHidden').val('Ecuadorean');
                    $('#nacionalidadSelect').val('Ecuadorean').trigger('change').prop('disabled', true);
                    
                    if (config.debug) {
                        console.log('Nacionalidad seleccionada:', $('#nacionalidadSelect').val());
                        console.log('Valor en campo oculto:', $('#nacionalidadHidden').val());
                    }
                }
                                
                Swal.fire('Éxito', 'Datos encontrados y actualizados.', 'success');
            })
            .catch(err => {
                console.error('Error buscando cédula:', err);
                Swal.fire('Error', 'No se pudieron obtener los datos. Intente nuevamente.', 'error');
            });
    }
    
    // Crear usuario
    function crearUsuario(e) {
        e.preventDefault();
        
        if (!validarFormulario('formCrearUsuario')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'crear');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('Datos a enviar (crear):');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        }
        
        $.ajax({
            url: '../../controladores/UsuariosControlador/UsuariosController.php',
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
                        title: 'Usuario creado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#crearUsuarioModal').modal('hide');
                        cargarUsuariosPaginados(1);
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
                console.error('Error en la petición AJAX (crear):', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
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
    
    // Editar usuario
    function editarUsuario(e) {
        e.preventDefault();
        
        if (!validarFormulario('formEditarUsuario')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'editar');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('Datos a enviar (editar):');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        }
        
        $.ajax({
            url: '../../controladores/UsuariosControlador/UsuariosController.php',
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
                        title: 'Usuario actualizado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#editarUsuarioModal').modal('hide');
                        cargarUsuariosPaginados(config.paginacion.paginaActual);
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
                console.error('Error en la petición AJAX (editar):', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
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
    
    // Eliminar usuario
    function eliminarUsuario(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'eliminar');
        formData.append('submenu_id', config.submenuId);
        
        if (config.debug) {
            console.log('Datos a enviar (eliminar):');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        }
        
        $.ajax({
            url: '../../controladores/UsuariosControlador/UsuariosController.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (config.debug) {
                    console.log('Respuesta del servidor (eliminar):', response);
                }
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario desactivado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#eliminarUsuarioModal').modal('hide');
                        cargarUsuariosPaginados(config.paginacion.paginaActual);
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
                console.error('Error en la petición AJAX (eliminar):', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
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
            modal.find('#edit_cedula').val(btn.dataset.cedula);
            modal.find('#edit_username').val(btn.dataset.username);
            modal.find('#edit_nombres').val(btn.dataset.nombres);
            modal.find('#edit_apellidos').val(btn.dataset.apellidos);
            modal.find('#edit_sexo').val(btn.dataset.sexo);
            
            const nacionalidadSelect = modal.find('#edit_nacionalidadSelect');
            if (nacionalidadSelect.hasClass('select2-hidden-accessible')) {
                nacionalidadSelect.val(btn.dataset.nacionalidad).trigger('change');
            } else {
                nacionalidadSelect.val(btn.dataset.nacionalidad);
            }
            
            modal.find('#edit_correo').val(btn.dataset.correo);
            modal.find('#edit_rol').val(btn.dataset.rol);
            modal.find('#edit_estado').val(btn.dataset.estado);
            modal.find('#edit_password').val('');
            
            if (config.debug) {
               console.log('Datos cargados en modal de edición:', btn.dataset);
           }
       } catch (error) {
           console.error('Error cargando datos de edición:', error);
       }
   }
   
   // Cargar datos en modal de eliminación
   function cargarDatosEliminacion(e) {
       const btn = e.relatedTarget;
       if (!btn) return;
       
       try {
           const modal = $(this);
           modal.find('#delete_id').val(btn.dataset.id);
           modal.find('#delete_username').text(btn.dataset.username);
           
           if (config.debug) {
               console.log('Datos cargados en modal de eliminación:', {
                   id: btn.dataset.id,
                   username: btn.dataset.username
               });
           }
       } catch (error) {
           console.error('Error cargando datos de eliminación:', error);
       }
   }
   
   // Cargar banderas en la tabla
   function cargarBanderas() {
       fetch("https://restcountries.com/v2/all?fields=name,alpha2Code,flag,demonym")
           .then(res => {
               if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);
               return res.json();
           })
           .then(data => {
               const paises = data.map(p => ({
                   nombre: p.name.toLowerCase(),
                   flag: p.flag,
                   demonym: p.demonym
               }));

               document.querySelectorAll('.nacionalidad-banderita').forEach(span => {
                   try {
                       const nacionalidad = span.dataset.nacionalidad.toLowerCase();
                       const pais = paises.find(p => p.demonym && p.demonym.toLowerCase() === nacionalidad);

                       if (pais) {
                           span.innerHTML = `<img src="${pais.flag}" alt="${pais.nombre}" style="width: 20px; height: 15px; margin-right: 5px;"> ${pais.demonym}`;
                       } else {
                           span.innerHTML += ' <span title="No se encontró bandera">🌐</span>';
                       }
                   } catch (error) {
                       console.error('Error procesando bandera:', error, span);
                   }
               });
           })
           .catch(err => {
               console.error('Error cargando banderas:', err);
               // No mostrar error al usuario - no es crítico
           });
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
           } else {
               field.classList.remove('is-invalid');
           }
       });
       
       // Validación especial para contraseña en edición
       if (formId === 'formEditarUsuario') {
           const passwordField = form.querySelector('#edit_password');
           if (passwordField && passwordField.value.length > 0 && passwordField.value.length < 6) {
               passwordField.classList.add('is-invalid');
               isValid = false;
           }
       }
       
       if (!isValid) {
           Swal.fire({
               icon: 'error',
               title: 'Error de validación',
               text: 'Por favor, completa todos los campos requeridos correctamente',
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
           
           // Limpiar select2
           try {
               $(form).find('.select2-hidden-accessible').val(null).trigger('change');
           } catch (error) {
               console.error('Error limpiando campos Select2:', error);
           }
           
           // Habilitar campos deshabilitados
           $(form).find('input, select').prop('disabled', false).prop('readonly', false);
           
           // Limpiar clases de validación
           $(form).find('.is-invalid').removeClass('is-invalid');
       }
   }
   
   // Función de escape HTML para prevenir XSS
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