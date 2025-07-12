/**
 * JavaScript para Consulta de Citas de Pacientes
 * Sistema MediSys - Módulo Pacientes
 */

class ConsultaCitasApp {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.filtros = {};
        this.isLoading = false;
        this.debounceTimeout = null;
        
        this.init();
    }
    
    /**
     * Inicializar la aplicación
     */
    init() {
        this.setupEventListeners();
        this.setupDatePickers();
        this.cargarHistorialInicial();
        this.cargarDatosIniciales();
        this.setupTooltips();
        this.setupModals();
    }
    
    /**
 * Configurar event listeners - CORREGIDO
 */
setupEventListeners() {
    // Búsqueda en tiempo real
    $('#busquedaGeneral').on('input', (e) => {
        clearTimeout(this.debounceTimeout);
        this.debounceTimeout = setTimeout(() => {
            this.buscarCitas(e.target.value);
        }, 500);
    });
    
    // Filtros
    $('#filtroEstado, #filtroTipoCita, #filtroEspecialidad').on('change', () => {
        this.aplicarFiltros();
    });
    
    // Búsqueda por fechas
    $('#btnBuscarFechas').on('click', () => {
        this.buscarPorFechas();
    });
    
    // Limpiar filtros
    $('#btnLimpiarFiltros').on('click', () => {
        this.limpiarFiltros();
    });
    
    // Cambiar items por página
    $('#itemsPorPagina').on('change', (e) => {
        this.itemsPerPage = parseInt(e.target.value);
        this.currentPage = 1;
        this.cargarHistorial();
    });
    
    // Refresh
    $('#btnRefresh').on('click', () => {
        this.refrescarDatos();
    });
    
    // Modal de detalle - CORREGIDO
    $(document).on('click', '.btn-ver-detalle', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Obtener el ID de cita del elemento clicado o sus padres
        let idCita = null;
        const target = $(e.target);
        
        // Buscar en el elemento actual
        if (target.hasClass('btn-ver-detalle')) {
            idCita = target.data('id-cita');
        }
        // Buscar en el botón padre si es un ícono
        else if (target.closest('.btn-ver-detalle').length) {
            idCita = target.closest('.btn-ver-detalle').data('id-cita');
        }
        
        console.log('ID Cita capturado:', idCita); // Debug
        
        if (idCita && idCita !== '' && idCita !== undefined) {
            this.mostrarDetalleCita(idCita);
        } else {
            console.error('No se pudo obtener el ID de la cita');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo obtener la información de la cita',
                confirmButtonColor: '#ef476f'
            });
        }
    });
    
    // Exportar datos
    $('#btnExportarPDF, #btnExportarExcel').on('click', (e) => {
        const formato = e.target.id.includes('PDF') ? 'pdf' : 'excel';
        this.exportarDatos(formato);
    });
    
    // Responsive: colapsar filtros en móvil
    $('#toggleFiltros').on('click', () => {
        $('#filtrosContainer').toggleClass('show');
    });
    
    // Limpiar búsqueda
    $('#btnLimpiarBusqueda').on('click', () => {
        $('#busquedaGeneral').val('').trigger('input');
    });
}
    
    /**
     * Configurar date pickers
     */
    setupDatePickers() {
        // Configurar fechas con restricciones
        const hoy = new Date().toISOString().split('T')[0];
        const hace6Meses = new Date();
        hace6Meses.setMonth(hace6Meses.getMonth() - 6);
        const fechaMinima = hace6Meses.toISOString().split('T')[0];
        
        $('#fechaDesde, #fechaHasta').attr({
            'max': hoy,
            'min': fechaMinima
        });
        
        // Auto-completar fecha hasta cuando se selecciona fecha desde
        $('#fechaDesde').on('change', function() {
            const fechaDesde = $(this).val();
            if (fechaDesde && !$('#fechaHasta').val()) {
                $('#fechaHasta').val(hoy);
            }
        });
        
        // Validar que fecha hasta >= fecha desde
        $('#fechaHasta').on('change', function() {
            const fechaDesde = $('#fechaDesde').val();
            const fechaHasta = $(this).val();
            
            if (fechaDesde && fechaHasta && fechaHasta < fechaDesde) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fechas inválidas',
                    text: 'La fecha hasta debe ser mayor o igual a la fecha desde',
                    confirmButtonColor: '#0077b6'
                });
                $(this).val(fechaDesde);
            }
        });
    }
    
    /**
     * Configurar tooltips
     */
    setupTooltips() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
    
    /**
     * Configurar modals
     */
    setupModals() {
        // Modal de detalle cita
        $('#modalDetalleCita').on('hidden.bs.modal', () => {
            $('#detalleContent').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
        });
    }
    
    /**
     * Cargar datos iniciales
     */
    cargarDatosIniciales() {
        this.cargarProximasCitas();
        this.cargarEstadisticas();
        this.cargarEspecialidades();
    }
    
    /**
     * Cargar historial inicial
     */
    cargarHistorialInicial() {
        this.mostrarLoading();
        this.cargarHistorial();
    }
    
    /**
     * Cargar historial de citas con paginación
     */
    async cargarHistorial() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.mostrarLoading();
        
        try {
            const response = await $.ajax({
                url: '../../controladores/PacientesControlador/PacientesController.php',
                method: 'POST',
                data: {
                    accion: 'obtener_historial',
                    pagina: this.currentPage,
                    por_pagina: this.itemsPerPage,
                    ...this.filtros
                },
                dataType: 'json'
            });
            
            if (response.success) {
                this.renderizarCitas(response.data.citas);
                this.renderizarPaginacion(response.data.paginacion);
                this.actualizarResumen(response.data.resumen);
            } else {
                throw new Error(response.error || 'Error al cargar las citas');
            }
            
        } catch (error) {
            console.error('Error cargando historial:', error);
            this.mostrarError('Error al cargar el historial de citas');
            $('#citasContainer').html(this.getTemplateError());
        } finally {
            this.isLoading = false;
            this.ocultarLoading();
        }
    }
    
    /**
     * Renderizar lista de citas
     */
    renderizarCitas(citas) {
        const container = $('#citasContainer');
        
        if (!citas || citas.length === 0) {
            container.html(this.getTemplateNoCitas());
            return;
        }
        
        let html = '';
        citas.forEach(cita => {
            html += this.getTemplateCita(cita);
        });
        
        container.html(html);
        
        // Animar entrada
        $('.cita-card').each((index, element) => {
            $(element).css('opacity', '0').delay(index * 100).animate({opacity: 1}, 300);
        });
        
        // Re-inicializar tooltips
        this.setupTooltips();
    }
    
    /**
     * Template para tarjeta de cita
     */
    getTemplateCita(cita) {
        const estadoBadge = this.getEstadoBadge(cita.estado);
        const tipoBadge = this.getTipoBadge(cita.tipo_cita);
        const iconoEspecialidad = this.getIconoEspecialidad(cita.nombre_especialidad);
        
        return `
            <div class="col-12 mb-3">
                <div class="card cita-card h-100 shadow-sm hover-card" data-id-cita="${cita.id_cita}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Columna 1: Fecha y hora -->
                            <div class="col-lg-3 col-md-4 mb-3 mb-md-0">
                                <div class="fecha-container">
                                    <div class="fecha-principal">
                                        <i class="bi bi-calendar-event text-primary me-2"></i>
                                        <strong>${cita.fecha_formateada}</strong>
                                    </div>
                                    <div class="hora-cita">
                                        <i class="bi bi-clock text-muted me-1"></i>
                                        ${cita.hora_formateada}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna 2: Doctor y especialidad -->
                            <div class="col-lg-4 col-md-4 mb-3 mb-md-0">
                                <div class="doctor-info">
                                    <div class="doctor-nombre">
                                        <i class="bi ${iconoEspecialidad} text-info me-2"></i>
                                        <strong>${cita.doctor_nombre}</strong>
                                    </div>
                                    <div class="especialidad">
                                        ${cita.nombre_especialidad}
                                    </div>
                                    <div class="sucursal text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        ${cita.nombre_sucursal}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna 3: Motivo y badges -->
                            <div class="col-lg-3 col-md-4 mb-3 mb-md-0">
                                <div class="motivo-container">
                                    <div class="motivo-texto">
                                        <strong>Motivo:</strong>
                                        <span class="text-muted">${cita.motivo}</span>
                                    </div>
                                    <div class="badges mt-2">
                                        ${estadoBadge}
                                        ${tipoBadge}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna 4: Acciones -->
                            <div class="col-lg-2 text-end">
                                <div class="acciones-cita">
                                    <button class="btn btn-outline-primary btn-sm btn-ver-detalle" 
                                            data-id-cita="${cita.id_cita}"
                                            data-bs-toggle="tooltip" 
                                            title="Ver detalles completos">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-lg-inline ms-1">Ver</span>
                                    </button>
                                    
                                    ${cita.enlace_virtual ? `
                                        <button class="btn btn-outline-success btn-sm mt-1" 
                                                onclick="window.open('${cita.enlace_virtual}', '_blank')"
                                                data-bs-toggle="tooltip" 
                                                title="Unirse a videollamada">
                                            <i class="bi bi-camera-video"></i>
                                        </button>
                                    ` : ''}
                                    
                                    ${cita.triaje_observaciones ? `
                                        <span class="badge bg-info ms-1" 
                                              data-bs-toggle="tooltip" 
                                              title="Triaje realizado">
                                            <i class="bi bi-heart-pulse"></i>
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información adicional colapsable en móvil -->
                        <div class="additional-info d-lg-none mt-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="bi bi-person-badge me-1"></i>
                                        ${cita.titulo_profesional || 'Médico'}
                                    </small>
                                </div>
                                <div class="col-6 text-end">
                                    <small class="text-muted">
                                        Creada: ${this.formatearFechaCorta(cita.fecha_creacion)}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar paginación
     */
    renderizarPaginacion(paginacion) {
        const container = $('#paginacionContainer');
        
        if (paginacion.total_paginas <= 1) {
            container.html('');
            return;
        }
        
        let html = `
            <nav aria-label="Paginación de citas">
                <ul class="pagination justify-content-center">
                    <li class="page-item ${!paginacion.tiene_anterior ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-pagina="1">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item ${!paginacion.tiene_anterior ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-pagina="${paginacion.pagina_actual - 1}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
        `;
        
        // Páginas numéricas
        const startPage = Math.max(1, paginacion.pagina_actual - 2);
        const endPage = Math.min(paginacion.total_paginas, paginacion.pagina_actual + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === paginacion.pagina_actual ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                </li>
            `;
        }
        
        html += `
                    <li class="page-item ${!paginacion.tiene_siguiente ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-pagina="${paginacion.pagina_actual + 1}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item ${!paginacion.tiene_siguiente ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-pagina="${paginacion.total_paginas}">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="text-center mt-2">
                <small class="text-muted">
                    Mostrando página ${paginacion.pagina_actual} de ${paginacion.total_paginas} 
                    (${paginacion.total_registros} registros total)
                </small>
            </div>
        `;
        
        container.html(html);
        
        // Event listener para paginación
        $('.page-link').on('click', (e) => {
            e.preventDefault();
            const pagina = parseInt($(e.target).closest('.page-link').data('pagina'));
            
            if (pagina && pagina !== this.currentPage && pagina >= 1 && pagina <= paginacion.total_paginas) {
                this.currentPage = pagina;
                this.cargarHistorial();
                
                // Scroll suave al top
                $('html, body').animate({
                    scrollTop: $('#citasContainer').offset().top - 100
                }, 500);
            }
        });
    }
    
    /**
     * Buscar citas por texto
     */
    async buscarCitas(termino) {
        if (termino.length < 3 && termino.length > 0) {
            return; // Esperar al menos 3 caracteres
        }
        
        if (termino.length === 0) {
            // Si está vacío, recargar todo
            this.filtros.busqueda = undefined;
            delete this.filtros.busqueda;
            this.currentPage = 1;
            this.cargarHistorial();
            return;
        }
        
        this.filtros.busqueda = termino;
        this.currentPage = 1;
        this.cargarHistorial();
        
        // Actualizar indicador de búsqueda
        this.actualizarIndicadorBusqueda(termino);
    }
    
    /**
     * Buscar por rango de fechas
     */
    async buscarPorFechas() {
        const fechaDesde = $('#fechaDesde').val();
        const fechaHasta = $('#fechaHasta').val();
        
        if (!fechaDesde || !fechaHasta) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas requeridas',
                text: 'Por favor selecciona ambas fechas para la búsqueda',
                confirmButtonColor: '#0077b6'
            });
            return;
        }
        
        this.mostrarLoading();
        
        try {
            const response = await $.ajax({
                url: '../../controladores/PacientesControlador/PacientesController.php',
                method: 'POST',
                data: {
                    accion: 'buscar_por_fechas',
                    fecha_inicio: fechaDesde,
                    fecha_fin: fechaHasta
                },
                dataType: 'json'
            });
            
            if (response.success) {
                this.renderizarCitas(response.data.citas);
                $('#paginacionContainer').html(''); // Limpiar paginación
                
                // Mostrar resumen de búsqueda
                this.mostrarResumenBusqueda(response.data.rango);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Búsqueda completada',
                    text: `Se encontraron ${response.data.rango.total_encontradas} citas en el rango seleccionado`,
                    timer: 3000,
                    showConfirmButton: false
                });
                
            } else {
                throw new Error(response.error);
            }
            
        } catch (error) {
            console.error('Error en búsqueda por fechas:', error);
            this.mostrarError('Error al buscar por fechas');
        } finally {
            this.ocultarLoading();
        }
    }
    
    /**
     * Aplicar filtros
     */
    aplicarFiltros() {
        const estado = $('#filtroEstado').val();
        const tipoCita = $('#filtroTipoCita').val();
        const especialidad = $('#filtroEspecialidad').val();
        
        // Actualizar filtros
        this.filtros = {};
        
        if (estado) this.filtros.estado = estado;
        if (tipoCita) this.filtros.tipo_cita = tipoCita;
        if (especialidad) this.filtros.especialidad = especialidad;
        
        // Mantener búsqueda si existe
        const busqueda = $('#busquedaGeneral').val();
        if (busqueda && busqueda.length >= 3) {
            this.filtros.busqueda = busqueda;
        }
        
        this.currentPage = 1;
        this.cargarHistorial();
        
        // Actualizar indicadores visuales
        this.actualizarIndicadoresFiltros();
    }
    
    /**
     * Limpiar todos los filtros
     */
    limpiarFiltros() {
        $('#busquedaGeneral').val('');
        $('#filtroEstado').val('');
        $('#filtroTipoCita').val('');
        $('#filtroEspecialidad').val('');
        $('#fechaDesde').val('');
        $('#fechaHasta').val('');
        
        this.filtros = {};
        this.currentPage = 1;
        
        this.cargarHistorial();
        this.limpiarIndicadores();
        
        // Feedback visual
        const btn = $('#btnLimpiarFiltros');
        const iconOriginal = btn.html();
        btn.html('<i class="bi bi-check"></i> Limpiado');
        setTimeout(() => {
            btn.html(iconOriginal);
        }, 1500);
    }
    
    /**
     * Mostrar detalle de cita en modal
     */
    async mostrarDetalleCita(idCita) {
        const modal = $('#modalDetalleCita');
        const content = $('#detalleContent');
        
        // Mostrar modal con loading
        content.html(this.getTemplateLoading());
        modal.modal('show');
        
        try {
            const response = await $.ajax({
                url: '../../controladores/PacientesControlador/PacientesController.php',
                method: 'POST',
                data: {
                    accion: 'obtener_detalle_cita',
                    id_cita: idCita
                },
                dataType: 'json'
            });
            
            if (response.success) {
                content.html(this.getTemplateDetalleCita(response.data));
                
                // Inicializar componentes del modal
                this.setupModalComponents();
                
            } else {
                throw new Error(response.error);
            }
            
        } catch (error) {
            console.error('Error cargando detalle:', error);
            content.html(this.getTemplateError('No se pudo cargar el detalle de la cita'));
        }
    }
    
    /**
     * Cargar próximas citas (widget)
     */
    async cargarProximasCitas() {
        try {
            const response = await $.ajax({
                url: '../../controladores/PacientesControlador/PacientesController.php',
                method: 'POST',
                data: {
                    accion: 'obtener_proximas_citas',
                    limite: 3
                },
                dataType: 'json'
            });
            
            if (response.success) {
                this.renderizarProximasCitas(response.data.proximas_citas);
            }
            
        } catch (error) {
            console.error('Error cargando próximas citas:', error);
        }
    }
    
    /**
 * Renderizar widget de especialidades visitadas - MÉTODO FALTANTE
 */
renderizarEspecialidadesWidget(especialidades) {
    // Este método puede ir en un widget adicional si lo necesitas
    // Por ahora solo lo agregamos para evitar el error
    console.log('Especialidades visitadas:', especialidades);
}
    /**
 * Cargar estadísticas del paciente - CORREGIDO
 */
async cargarEstadisticas() {
    try {
        const response = await $.ajax({
            url: '../../controladores/PacientesControlador/PacientesController.php',
            method: 'POST',
            data: {
                accion: 'obtener_estadisticas'
            },
            dataType: 'json'
        });
        
        if (response.success) {
            this.renderizarEstadisticas(response.data.estadisticas);
            // Comentado temporalmente hasta implementar el widget
            // this.renderizarEspecialidadesWidget(response.data.especialidades_visitadas);
        }
        
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

    
    /**
     * Cargar especialidades para filtro
     */
    async cargarEspecialidades() {
        try {
            const response = await $.ajax({
                url: '../../controladores/PacientesControlador/PacientesController.php',
                method: 'POST',
                data: {
                    accion: 'obtener_especialidades'
                },
                dataType: 'json'
            });
            
            if (response.success) {
                this.llenarSelectEspecialidades(response.data);
            }
            
        } catch (error) {
            console.error('Error cargando especialidades:', error);
        }
    }
    
    /**
     * Refrescar todos los datos
     */
    refrescarDatos() {
        const btn = $('#btnRefresh');
        const iconOriginal = btn.find('i').attr('class');
        
        // Animación de refresh
        btn.find('i').attr('class', 'bi bi-arrow-clockwise').addClass('spin');
        btn.prop('disabled', true);
        
        // Recargar datos
        this.cargarHistorial();
        this.cargarDatosIniciales();
        
        setTimeout(() => {
            btn.find('i').attr('class', iconOriginal).removeClass('spin');
            btn.prop('disabled', false);
        }, 1000);
    }
    
    // ===== MÉTODOS DE RENDERIZADO =====
    
    /**
     * Renderizar próximas citas widget
     */
    renderizarProximasCitas(citas) {
        const container = $('#proximasCitasWidget');
        
        if (!citas || citas.length === 0) {
            container.html(`
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x fs-2"></i>
                    <p class="mt-2">No tienes citas próximas</p>
                </div>
            `);
            return;
        }
        
        let html = '';
        citas.forEach(cita => {
            html += `
                <div class="proxima-cita-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${cita.doctor_nombre}</h6>
                            <small class="text-muted">${cita.nombre_especialidad}</small>
                            <div class="mt-1">
                                <small>
                                    <i class="bi bi-calendar me-1"></i>
                                    ${this.formatearFechaCorta(cita.fecha_hora)}
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            ${this.getEstadoBadge(cita.estado)}
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.html(html);
    }
    
    /**
 * Renderizar estadísticas - CORREGIDO
 */
renderizarEstadisticas(stats) {
    // Actualizar contadores
    $('#totalCitas').text(stats.total_citas || 0);
    $('#citasCompletadas').text(stats.citas_completadas || 0);
    $('#citasPendientes').text(stats.citas_pendientes || 0);
    $('#citasVirtuales').text(stats.citas_virtuales || 0);
    
    // Actualizar porcentajes con animación
    this.animarPorcentaje('#porcentajeCompletadas', stats.porcentaje_completadas || 0);
    this.animarPorcentaje('#porcentajePendientes', stats.porcentaje_pendientes || 0);
    this.animarPorcentaje('#porcentajeVirtuales', stats.porcentaje_virtuales || 0);
    
    // Actualizar barras de progreso
    this.actualizarBarraProgreso('#progressCompletadas', stats.porcentaje_completadas || 0);
    this.actualizarBarraProgreso('#progressPendientes', stats.porcentaje_pendientes || 0);
    this.actualizarBarraProgreso('#progressVirtuales', stats.porcentaje_virtuales || 0);
}

    
    // ===== MÉTODOS AUXILIARES =====
    
    /**
     * Obtener badge de estado
     */
    getEstadoBadge(estado) {
        const badges = {
            'Pendiente': '<span class="badge bg-warning text-dark">Pendiente</span>',
            'Confirmada': '<span class="badge bg-info">Confirmada</span>',
            'Completada': '<span class="badge bg-success">Completada</span>',
            'Cancelada': '<span class="badge bg-danger">Cancelada</span>',
            'No Asistio': '<span class="badge bg-secondary">No Asistió</span>'
        };
        
        return badges[estado] || '<span class="badge bg-secondary">Sin Estado</span>';
    }
    
    /**
     * Obtener badge de tipo
     */
    getTipoBadge(tipo) {
        const badges = {
            'presencial': '<span class="badge bg-primary">Presencial</span>',
            'virtual': '<span class="badge bg-success">Virtual</span>'
        };
        
        return badges[tipo] || '';
    }
    
    /**
     * Obtener icono de especialidad
     */
    getIconoEspecialidad(especialidad) {
        const iconos = {
            'Cardiología': 'bi-heart-pulse',
            'Neurología': 'bi-brain',
            'Dermatología': 'bi-person',
            'Pediatría': 'bi-people',
            'Ginecología': 'bi-gender-female',
            'Oftalmología': 'bi-eye',
            'Psiquiatría': 'bi-chat-heart',
            'Medicina General': 'bi-heart'
        };
        
        return iconos[especialidad] || 'bi-hospital';
    }
    
    /**
     * Formatear fecha corta
     */
    formatearFechaCorta(fecha) {
        const d = new Date(fecha);
        return d.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Mostrar loading
     */
    mostrarLoading() {
        $('#loadingIndicator').show();
    }
    
    /**
     * Ocultar loading
     */
    ocultarLoading() {
        $('#loadingIndicator').hide();
    }
    
    /**
     * Mostrar mensaje de error
     */
    mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonColor: '#ef476f'
        });
    }
    
    /**
     * Template de carga
     */
    getTemplateLoading() {
        return `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando datos...</p>
            </div>
        `;
    }
    
   /**
    * Template de error
    */
   getTemplateError(mensaje = 'Error al cargar los datos') {
       return `
           <div class="text-center p-5">
               <div class="error-container">
                   <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                   <h5 class="mt-3 text-danger">¡Oops! Algo salió mal</h5>
                   <p class="text-muted">${mensaje}</p>
                   <button class="btn btn-outline-primary" onclick="location.reload()">
                       <i class="bi bi-arrow-clockwise me-1"></i>
                       Intentar de nuevo
                   </button>
               </div>
           </div>
       `;
   }
   
   /**
    * Template cuando no hay citas
    */
   getTemplateNoCitas() {
       return `
           <div class="text-center p-5">
               <div class="no-data-container">
                   <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                   <h4 class="mt-3 text-muted">No se encontraron citas</h4>
                   <p class="text-muted">
                       ${Object.keys(this.filtros).length > 0 ? 
                           'Intenta modificar los filtros de búsqueda' : 
                           'Aún no tienes citas registradas en el sistema'
                       }
                   </p>
                   ${Object.keys(this.filtros).length > 0 ? 
                       '<button class="btn btn-outline-primary" id="btnLimpiarFiltrosNoData"><i class="bi bi-funnel me-1"></i>Limpiar filtros</button>' : 
                       ''
                   }
               </div>
           </div>
       `;
   }
   
   /**
    * Template detalle completo de cita
    */
   getTemplateDetalleCita(cita) {
       return `
           <div class="detalle-cita-container">
               <!-- Header del detalle -->
               <div class="detalle-header mb-4">
                   <div class="row">
                       <div class="col-md-8">
                           <h4 class="text-primary mb-2">
                               <i class="bi bi-calendar-event me-2"></i>
                               Detalle de Cita Médica
                           </h4>
                           <p class="text-muted mb-0">
                               <strong>ID:</strong> #${cita.id_cita} | 
                               <strong>Creada:</strong> ${this.formatearFechaCorta(cita.fecha_creacion)}
                           </p>
                       </div>
                       <div class="col-md-4 text-end">
                           <div class="status-badges">
                               ${this.getEstadoBadge(cita.estado)}
                               ${this.getTipoBadge(cita.tipo_cita)}
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Información principal -->
               <div class="row mb-4">
                   <!-- Columna izquierda: Fecha y Doctor -->
                   <div class="col-md-6">
                       <div class="card border-0 bg-light h-100">
                           <div class="card-body">
                               <h6 class="card-title text-primary">
                                   <i class="bi bi-calendar-heart me-2"></i>
                                   Información de la Cita
                               </h6>
                               
                               <div class="info-item mb-3">
                                   <strong>Fecha y Hora:</strong>
                                   <div class="ms-3">
                                       <i class="bi bi-calendar-date text-info me-1"></i>
                                       ${cita.fecha_formateada}
                                       <br>
                                       <i class="bi bi-clock text-info me-1"></i>
                                       ${cita.hora_formateada}
                                   </div>
                               </div>
                               
                               <div class="info-item mb-3">
                                   <strong>Motivo de Consulta:</strong>
                                   <div class="ms-3 text-muted">
                                       ${cita.motivo}
                                   </div>
                               </div>
                               
                               ${cita.notas ? `
                                   <div class="info-item">
                                       <strong>Notas:</strong>
                                       <div class="ms-3 text-muted">
                                           ${cita.notas}
                                       </div>
                                   </div>
                               ` : ''}
                           </div>
                       </div>
                   </div>
                   
                   <!-- Columna derecha: Doctor y Especialidad -->
                   <div class="col-md-6">
                       <div class="card border-0 bg-light h-100">
                           <div class="card-body">
                               <h6 class="card-title text-primary">
                                   <i class="bi bi-person-hearts me-2"></i>
                                   Médico Tratante
                               </h6>
                               
                               <div class="doctor-card">
                                   <div class="doctor-avatar mb-3">
                                       <div class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                                            style="width: 60px; height: 60px; border-radius: 50%; font-size: 24px;">
                                           <i class="bi ${this.getIconoEspecialidad(cita.nombre_especialidad)}"></i>
                                       </div>
                                   </div>
                                   
                                   <div class="doctor-info">
                                       <h5 class="mb-1">${cita.doctor_nombre}</h5>
                                       <p class="text-muted mb-2">${cita.titulo_profesional || 'Médico Especialista'}</p>
                                       
                                       <div class="especialidad-badge mb-2">
                                           <span class="badge bg-info fs-6">
                                               <i class="bi ${this.getIconoEspecialidad(cita.nombre_especialidad)} me-1"></i>
                                               ${cita.nombre_especialidad}
                                           </span>
                                       </div>
                                       
                                       ${cita.doctor_correo ? `
                                           <div class="contact-info">
                                               <small class="text-muted">
                                                   <i class="bi bi-envelope me-1"></i>
                                                   ${cita.doctor_correo}
                                               </small>
                                           </div>
                                       ` : ''}
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Información de la sucursal -->
               <div class="card border-0 bg-light mb-4">
                   <div class="card-body">
                       <h6 class="card-title text-primary">
                           <i class="bi bi-building me-2"></i>
                           Ubicación de la Cita
                       </h6>
                       
                       <div class="row">
                           <div class="col-md-6">
                               <div class="sucursal-info">
                                   <h6 class="mb-2">${cita.nombre_sucursal}</h6>
                                   <div class="info-item mb-2">
                                       <i class="bi bi-geo-alt text-danger me-2"></i>
                                       <span>${cita.sucursal_direccion}</span>
                                   </div>
                                   ${cita.sucursal_telefono ? `
                                       <div class="info-item mb-2">
                                           <i class="bi bi-telephone text-success me-2"></i>
                                           <span>${cita.sucursal_telefono}</span>
                                       </div>
                                   ` : ''}
                               </div>
                           </div>
                           <div class="col-md-6">
                               ${cita.horario_atencion ? `
                                   <div class="horario-info">
                                       <strong>Horario de Atención:</strong>
                                       <div class="ms-3 text-muted">
                                           ${cita.horario_atencion}
                                       </div>
                                   </div>
                               ` : ''}
                               
                               ${cita.enlace_virtual ? `
                                   <div class="virtual-info mt-3">
                                       <button class="btn btn-success" onclick="window.open('${cita.enlace_virtual}', '_blank')">
                                           <i class="bi bi-camera-video me-2"></i>
                                           Unirse a Videollamada
                                       </button>
                                       ${cita.sala_virtual ? `
                                           <div class="mt-2">
                                               <small class="text-muted">
                                                   Sala: ${cita.sala_virtual}
                                               </small>
                                           </div>
                                       ` : ''}
                                   </div>
                               ` : ''}
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Información del Triaje -->
               ${cita.id_triage ? this.getTemplateTriaje(cita) : ''}

               <!-- Consultas Médicas -->
               ${cita.consultas && cita.consultas.length > 0 ? this.getTemplateConsultas(cita.consultas) : ''}

               <!-- Recetas Médicas -->
               ${cita.recetas && cita.recetas.length > 0 ? this.getTemplateRecetas(cita.recetas) : ''}

               <!-- Acciones adicionales -->
               <div class="acciones-detalle mt-4 pt-3 border-top">
                   <div class="row">
                       <div class="col-md-6">
                           <button class="btn btn-outline-primary" onclick="window.print()">
                               <i class="bi bi-printer me-2"></i>
                               Imprimir Detalle
                           </button>
                       </div>
                       <div class="col-md-6 text-end">
                           <button class="btn btn-primary" data-bs-dismiss="modal">
                               <i class="bi bi-check-lg me-2"></i>
                               Cerrar
                           </button>
                       </div>
                   </div>
               </div>
           </div>
       `;
   }
   
   /**
    * Template para información de triaje
    */
   getTemplateTriaje(cita) {
       if (!cita.id_triage) return '';
       
       const nivelUrgencia = this.getNivelUrgenciaBadge(cita.nivel_urgencia);
       const estadoTriaje = this.getEstadoTriajeBadge(cita.estado_triaje);
       
       return `
           <div class="card border-0 bg-light mb-4">
               <div class="card-body">
                   <h6 class="card-title text-primary">
                       <i class="bi bi-heart-pulse me-2"></i>
                       Información de Triaje
                   </h6>
                   
                   <div class="row">
                       <div class="col-md-6">
                           <div class="triaje-info">
                               <div class="mb-3">
                                   <strong>Estado del Triaje:</strong>
                                   <div class="ms-3">
                                       ${estadoTriaje}
                                       ${nivelUrgencia}
                                   </div>
                               </div>
                               
                               <div class="mb-3">
                                   <strong>Realizado por:</strong>
                                   <div class="ms-3 text-muted">
                                       ${cita.enfermero_nombre || 'Personal de enfermería'}
                                   </div>
                               </div>
                               
                               <div class="mb-3">
                                   <strong>Fecha y Hora:</strong>
                                   <div class="ms-3 text-muted">
                                       ${this.formatearFechaCorta(cita.triaje_fecha)}
                                   </div>
                               </div>
                           </div>
                       </div>
                       
                       <div class="col-md-6">
                           <div class="signos-vitales">
                               <strong>Signos Vitales:</strong>
                               <div class="signos-grid mt-2">
                                   ${cita.temperatura ? `
                                       <div class="signo-item">
                                           <i class="bi bi-thermometer text-danger me-1"></i>
                                           <strong>Temperatura:</strong> ${cita.temperatura}°C
                                       </div>
                                   ` : ''}
                                   
                                   ${cita.presion_arterial ? `
                                       <div class="signo-item">
                                           <i class="bi bi-heart text-danger me-1"></i>
                                           <strong>Presión:</strong> ${cita.presion_arterial} mmHg
                                       </div>
                                   ` : ''}
                                   
                                   ${cita.frecuencia_cardiaca ? `
                                       <div class="signo-item">
                                           <i class="bi bi-activity text-primary me-1"></i>
                                           <strong>Frecuencia Cardíaca:</strong> ${cita.frecuencia_cardiaca} bpm
                                       </div>
                                   ` : ''}
                                   
                                   ${cita.saturacion_oxigeno ? `
                                       <div class="signo-item">
                                           <i class="bi bi-lungs text-info me-1"></i>
                                           <strong>Sat. Oxígeno:</strong> ${cita.saturacion_oxigeno}%
                                       </div>
                                   ` : ''}
                                   
                                   ${cita.peso && cita.talla ? `
                                       <div class="signo-item">
                                           <i class="bi bi-person text-success me-1"></i>
                                           <strong>Peso/Talla:</strong> ${cita.peso}kg / ${cita.talla}cm
                                       </div>
                                   ` : ''}
                                   
                                   ${cita.imc ? `
                                       <div class="signo-item">
                                           <i class="bi bi-calculator text-warning me-1"></i>
                                           <strong>IMC:</strong> ${cita.imc}
                                       </div>
                                   ` : ''}
                               </div>
                           </div>
                       </div>
                   </div>
                   
                   ${cita.triaje_observaciones ? `
                       <div class="mt-3">
                           <strong>Observaciones del Triaje:</strong>
                           <div class="bg-white p-3 rounded mt-2">
                               ${cita.triaje_observaciones}
                           </div>
                       </div>
                   ` : ''}
               </div>
           </div>
       `;
   }
   

   /**
 * Obtener descripción del estado
 */
getEstadoDescripcion(estado) {
    const descripciones = {
        'Pendiente': '<small class="text-muted"><i class="bi bi-clock me-1"></i>Esperando confirmación</small>',
        'Confirmada': '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Cita confirmada</small>',
        'Completada': '<small class="text-success"><i class="bi bi-check-all me-1"></i>Consulta realizada</small>',
        'Cancelada': '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Cita cancelada</small>',
        'No Asistio': '<small class="text-secondary"><i class="bi bi-person-x me-1"></i>Paciente no asistió</small>'
    };
    
    return descripciones[estado] || '';
}

/**
 * Template cuando no hay triaje
 */
getTemplateNoTriaje(estado) {
    if (estado === 'Pendiente' || estado === 'Confirmada') {
        return `
            <div class="card border-0 bg-light mb-4">
                <div class="card-body text-center py-4">
                    <i class="bi bi-heart-pulse text-muted" style="font-size: 2rem;"></i>
                    <h6 class="mt-2 text-muted">Triaje Pendiente</h6>
                    <p class="text-muted mb-0">El triaje se realizará cuando llegues a la cita</p>
                </div>
            </div>
        `;
    }
    
    return `
        <div class="card border-0 bg-light mb-4">
            <div class="card-body text-center py-4">
                <i class="bi bi-info-circle text-info" style="font-size: 2rem;"></i>
                <h6 class="mt-2 text-muted">Sin Información de Triaje</h6>
                <p class="text-muted mb-0">No se registró triaje para esta cita</p>
            </div>
        </div>
    `;
}

/**
 * Template para consultas médicas - CON DEBUG
 */
getTemplateConsultas(consultas) {
    console.log('Renderizando consultas:', consultas); // Debug
    
    if (!consultas || consultas.length === 0) {
        console.log('No hay consultas para mostrar'); // Debug
        return '';
    }
    
    let html = `
        <div class="card border-0 bg-light mb-4">
            <div class="card-body">
                <h6 class="card-title text-primary">
                    <i class="bi bi-clipboard-heart me-2"></i>
                    Consultas Médicas Realizadas (${consultas.length})
                </h6>
    `;
    
    consultas.forEach((consulta, index) => {
        console.log(`Procesando consulta ${index + 1}:`, consulta); // Debug
        
        html += `
            <div class="consulta-item ${index > 0 ? 'border-top pt-3' : ''} mb-3">
                <div class="consulta-header mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1 text-primary">
                                <i class="bi bi-file-medical me-1"></i>
                                Consulta del ${consulta.fecha_consulta ? this.formatearFechaCorta(consulta.fecha_consulta) : 'Fecha no disponible'}
                            </h6>
                            <small class="text-muted">Dr. ${consulta.medico_nombre || 'Médico no especificado'}</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i>
                                Completada
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="consulta-details">
                    <!-- Motivo de Consulta -->
                    ${consulta.motivo_consulta ? `
                        <div class="detail-section mb-3">
                            <h6 class="detail-title">
                                <i class="bi bi-clipboard-plus text-info me-2"></i>
                                Motivo de Consulta
                            </h6>
                            <div class="detail-content bg-white p-3 rounded border-start border-info border-3">
                                ${consulta.motivo_consulta}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Sintomatología -->
                    ${consulta.sintomatologia ? `
                        <div class="detail-section mb-3">
                            <h6 class="detail-title">
                                <i class="bi bi-thermometer text-warning me-2"></i>
                                Sintomatología
                            </h6>
                            <div class="detail-content bg-white p-3 rounded border-start border-warning border-3">
                                ${consulta.sintomatologia}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Diagnóstico -->
                    ${consulta.diagnostico ? `
                        <div class="detail-section mb-3">
                            <h6 class="detail-title">
                                <i class="bi bi-search text-primary me-2"></i>
                                Diagnóstico
                            </h6>
                            <div class="detail-content bg-white p-3 rounded border-start border-primary border-3">
                                <strong>${consulta.diagnostico}</strong>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Tratamiento -->
                    ${consulta.tratamiento ? `
                        <div class="detail-section mb-3">
                            <h6 class="detail-title">
                                <i class="bi bi-heart-pulse text-success me-2"></i>
                                Tratamiento Prescrito
                            </h6>
                            <div class="detail-content bg-white p-3 rounded border-start border-success border-3">
                                ${consulta.tratamiento}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Observaciones -->
                    ${consulta.observaciones ? `
                        <div class="detail-section mb-3">
                            <h6 class="detail-title">
                                <i class="bi bi-chat-text text-secondary me-2"></i>
                                Observaciones Médicas
                            </h6>
                            <div class="detail-content bg-white p-3 rounded border-start border-secondary border-3">
                                ${consulta.observaciones}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Fecha de Seguimiento -->
                    ${consulta.fecha_seguimiento ? `
                        <div class="detail-section mb-3">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-calendar-check me-2"></i>
                                <strong>Próxima cita de seguimiento:</strong> 
                                ${this.formatearFechaCorta(consulta.fecha_seguimiento)}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}
/**
 * Template cuando no hay consultas médicas
 */
getTemplateNoConsultas(estado) {
    if (estado === 'Pendiente' || estado === 'Confirmada') {
        return `
            <div class="card border-0 bg-light mb-4">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clipboard-heart text-primary" style="font-size: 2rem;"></i>
                    <h6 class="mt-2 text-muted">Consulta Médica Pendiente</h6>
                    <p class="text-muted mb-0">La consulta se realizará en la fecha programada</p>
                </div>
            </div>
        `;
    }
    
    return `
        <div class="card border-0 bg-light mb-4">
            <div class="card-body text-center py-4">
                <i class="bi bi-clipboard-x text-muted" style="font-size: 2rem;"></i>
                <h6 class="mt-2 text-muted">Sin Consulta Médica</h6>
                <p class="text-muted mb-0">No se registró consulta médica para esta cita</p>
            </div>
        </div>
    `;
}

/**
 * Template cuando no hay recetas
 */
getTemplateNoRecetas(estado) {
    if (estado === 'Pendiente' || estado === 'Confirmada') {
        return `
            <div class="card border-0 bg-light mb-4">
                <div class="card-body text-center py-4">
                    <i class="bi bi-prescription2 text-info" style="font-size: 2rem;"></i>
                    <h6 class="mt-2 text-muted">Recetas Pendientes</h6>
                    <p class="text-muted mb-0">Las recetas se generarán durante la consulta</p>
                </div>
        </div>
        `;
    }
    
    return `
        <div class="card border-0 bg-light mb-4">
            <div class="card-body text-center py-4">
                <i class="bi bi-prescription text-muted" style="font-size: 2rem;"></i>
                <h6 class="mt-2 text-muted">Sin Recetas</h6>
                <p class="text-muted mb-0">No se prescribieron medicamentos en esta cita</p>
            </div>
        </div>
    `;
}

/**
 * Template específico según el estado de la cita
 */
getTemplateEstadoEspecifico(cita) {
    switch (cita.estado) {
        case 'Pendiente':
            return `
                <div class="card border-warning mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="bi bi-clock me-2"></i>
                            Cita Pendiente de Confirmación
                        </h6>
                        <p class="mb-2">Tu cita está programada pero aún no ha sido confirmada por el centro médico.</p>
                        <div class="alert alert-warning mb-0">
                            <strong>Próximos pasos:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Espera la confirmación del centro médico</li>
                                <li>Recibirás una notificación cuando sea confirmada</li>
                                <li>Prepara tu documentación médica</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            
        case 'Confirmada':
            return `
                <div class="card border-success mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="bi bi-check-circle me-2"></i>
                            Cita Confirmada
                        </h6>
                        <p class="mb-2">Tu cita ha sido confirmada. Te esperamos en la fecha y hora programada.</p>
                        <div class="alert alert-success mb-0">
                            <strong>Recordatorios importantes:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Llega 15 minutos antes de tu cita</li>
                                <li>Trae tu documento de identidad</li>
                                <li>Porta mascarilla si es requerido</li>
                                ${cita.tipo_cita === 'virtual' ? '<li>Verifica tu conexión a internet</li>' : ''}
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            
        case 'Completada':
            return `
                <div class="card border-primary mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="bi bi-check-all me-2"></i>
                            Consulta Completada
                        </h6>
                        <p class="mb-2">Tu consulta médica ha sido completada exitosamente.</p>
                        <div class="alert alert-info mb-0">
                            <strong>Información disponible:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Consulta médica realizada</li>
                                <li>Diagnóstico y tratamiento registrado</li>
                                <li>Recetas médicas (si aplica)</li>
                                <li>Seguimiento programado (si aplica)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            
        case 'Cancelada':
            return `
                <div class="card border-danger mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            Cita Cancelada
                        </h6>
                        <p class="mb-2">Esta cita ha sido cancelada.</p>
                        <div class="alert alert-danger mb-0">
                            <strong>¿Necesitas reprogramar?</strong>
                            <p class="mb-0 mt-2">Puedes solicitar una nueva cita contactando al centro médico o a través del sistema.</p>
                        </div>
                    </div>
                </div>
            `;
            
        case 'No Asistio':
            return `
                <div class="card border-secondary mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-secondary">
                            <i class="bi bi-person-x me-2"></i>
                            Ausencia Registrada
                        </h6>
                        <p class="mb-2">Se registró que no asististe a esta cita.</p>
                        <div class="alert alert-secondary mb-0">
                            <strong>¿Quieres reprogramar?</strong>
                            <p class="mb-0 mt-2">Puedes solicitar una nueva cita contactando al centro médico.</p>
                        </div>
                    </div>
                </div>
            `;
            
        default:
            return '';
    }
}
   /**
 * Template para consultas médicas - CAMPOS CORREGIDOS
 */
/**
 * Template detalle completo de cita - ADAPTADO SEGÚN ESTADO
 */
getTemplateDetalleCita(cita) {
    return `
        <div class="detalle-cita-container">
            <!-- Header del detalle -->
            <div class="detalle-header mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="text-primary mb-2">
                            <i class="bi bi-calendar-event me-2"></i>
                            Detalle de Cita Médica
                        </h4>
                        <p class="text-muted mb-0">
                            <strong>ID:</strong> #${cita.id_cita} | 
                            <strong>Creada:</strong> ${this.formatearFechaCorta(cita.fecha_creacion)}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="status-badges">
                            ${this.getEstadoBadge(cita.estado)}
                            ${this.getTipoBadge(cita.tipo_cita)}
                        </div>
                        <div class="mt-2">
                            ${this.getEstadoDescripcion(cita.estado)}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información principal -->
            <div class="row mb-4">
                <!-- Columna izquierda: Fecha y Doctor -->
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary">
                                <i class="bi bi-calendar-heart me-2"></i>
                                Información de la Cita
                            </h6>
                            
                            <div class="info-item mb-3">
                                <strong>Fecha y Hora:</strong>
                                <div class="ms-3">
                                    <i class="bi bi-calendar-date text-info me-1"></i>
                                    ${cita.fecha_formateada}
                                    <br>
                                    <i class="bi bi-clock text-info me-1"></i>
                                    ${cita.hora_formateada}
                                    ${cita.cita_pasada ? '<span class="badge bg-secondary ms-2">Pasada</span>' : '<span class="badge bg-success ms-2">Próxima</span>'}
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <strong>Motivo de Consulta:</strong>
                                <div class="ms-3 text-muted">
                                    ${cita.motivo}
                                </div>
                            </div>
                            
                            ${cita.notas ? `
                                <div class="info-item">
                                    <strong>Notas:</strong>
                                    <div class="ms-3 text-muted">
                                        ${cita.notas}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Columna derecha: Doctor y Especialidad -->
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary">
                                <i class="bi bi-person-hearts me-2"></i>
                                Médico Tratante
                            </h6>
                            
                            <div class="doctor-card">
                                <div class="doctor-avatar mb-3">
                                    <div class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px; border-radius: 50%; font-size: 24px;">
                                        <i class="bi ${this.getIconoEspecialidad(cita.nombre_especialidad)}"></i>
                                    </div>
                                </div>
                                
                                <div class="doctor-info">
                                    <h5 class="mb-1">${cita.doctor_nombre}</h5>
                                    <p class="text-muted mb-2">${cita.titulo_profesional || 'Médico Especialista'}</p>
                                    
                                    <div class="especialidad-badge mb-2">
                                        <span class="badge bg-info fs-6">
                                            <i class="bi ${this.getIconoEspecialidad(cita.nombre_especialidad)} me-1"></i>
                                            ${cita.nombre_especialidad}
                                        </span>
                                    </div>
                                    
                                    ${cita.doctor_correo ? `
                                        <div class="contact-info">
                                            <small class="text-muted">
                                                <i class="bi bi-envelope me-1"></i>
                                                ${cita.doctor_correo}
                                            </small>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de la sucursal -->
            <div class="card border-0 bg-light mb-4">
                <div class="card-body">
                    <h6 class="card-title text-primary">
                        <i class="bi bi-building me-2"></i>
                        Ubicación de la Cita
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="sucursal-info">
                                <h6 class="mb-2">${cita.nombre_sucursal}</h6>
                                <div class="info-item mb-2">
                                    <i class="bi bi-geo-alt text-danger me-2"></i>
                                    <span>${cita.sucursal_direccion}</span>
                                </div>
                                ${cita.sucursal_telefono ? `
                                    <div class="info-item mb-2">
                                        <i class="bi bi-telephone text-success me-2"></i>
                                        <span>${cita.sucursal_telefono}</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="col-md-6">
                            ${cita.horario_atencion ? `
                                <div class="horario-info">
                                    <strong>Horario de Atención:</strong>
                                    <div class="ms-3 text-muted">
                                        ${cita.horario_atencion}
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${cita.enlace_virtual ? `
                                <div class="virtual-info mt-3">
                                    <button class="btn btn-success" onclick="window.open('${cita.enlace_virtual}', '_blank')">
                                        <i class="bi bi-camera-video me-2"></i>
                                        Unirse a Videollamada
                                    </button>
                                    ${cita.sala_virtual ? `
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Sala: ${cita.sala_virtual}
                                            </small>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Triaje (disponible para cualquier estado si existe) -->
            ${cita.id_triage ? this.getTemplateTriaje(cita) : this.getTemplateNoTriaje(cita.estado)}

            <!-- Consultas Médicas (solo si existen) -->
            ${cita.consultas && cita.consultas.length > 0 ? this.getTemplateConsultas(cita.consultas) : this.getTemplateNoConsultas(cita.estado)}

            <!-- Recetas Médicas (solo si existen) -->
            ${cita.recetas && cita.recetas.length > 0 ? this.getTemplateRecetas(cita.recetas) : this.getTemplateNoRecetas(cita.estado)}

            <!-- Estado específico de la cita -->
            ${this.getTemplateEstadoEspecifico(cita)}

            <!-- Acciones adicionales -->
            <div class="acciones-detalle mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>
                            Imprimir Detalle
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="bi bi-check-lg me-2"></i>
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}


   
   /**
    * Obtener badge de nivel de urgencia
    */
   getNivelUrgenciaBadge(nivel) {
       const badges = {
           1: '<span class="badge bg-success">Baja</span>',
           2: '<span class="badge bg-info">Normal</span>',
           3: '<span class="badge bg-warning text-dark">Media</span>',
           4: '<span class="badge bg-danger">Alta</span>',
           5: '<span class="badge bg-dark">Crítica</span>'
       };
       
       return badges[nivel] || '<span class="badge bg-secondary">Sin Clasificar</span>';
   }
   
   /**
    * Obtener badge de estado de triaje
    */
   getEstadoTriajeBadge(estado) {
       const badges = {
           'Completado': '<span class="badge bg-success">Completado</span>',
           'Urgente': '<span class="badge bg-warning text-dark">Urgente</span>',
           'Critico': '<span class="badge bg-danger">Crítico</span>',
           'Pendiente_Atencion': '<span class="badge bg-info">Pendiente Atención</span>'
       };
       
       return badges[estado] || '<span class="badge bg-secondary">Sin Estado</span>';
   }
   
   /**
    * Animar porcentaje
    */
   animarPorcentaje(selector, valor) {
       const elemento = $(selector);
       $({porcentaje: 0}).animate({porcentaje: valor}, {
           duration: 1500,
           easing: 'swing',
           step: function() {
               elemento.text(Math.ceil(this.porcentaje) + '%');
           }
       });
   }
   
   /**
    * Actualizar barra de progreso
    */
   actualizarBarraProgreso(selector, porcentaje) {
       $(selector).css('width', '0%').animate({
           width: porcentaje + '%'
       }, 1500);
   }
   
   /**
    * Llenar select de especialidades
    */
   llenarSelectEspecialidades(especialidades) {
       const select = $('#filtroEspecialidad');
       select.html('<option value="">Todas las especialidades</option>');
       
       especialidades.forEach(esp => {
           select.append(`<option value="${esp.id_especialidad}">${esp.nombre_especialidad}</option>`);
       });
   }
   
   /**
    * Actualizar resumen
    */
   actualizarResumen(resumen) {
       $('#resumenTotal').text(resumen.total_citas);
       $('#resumenFiltros').toggle(resumen.filtros_aplicados);
   }
   
   /**
    * Actualizar indicador de búsqueda
    */
   actualizarIndicadorBusqueda(termino) {
       const indicador = $('#indicadorBusqueda');
       if (termino) {
           indicador.html(`<small class="text-muted">Buscando: "${termino}"</small>`).show();
       } else {
           indicador.hide();
       }
   }
   
   /**
    * Actualizar indicadores de filtros
    */
   actualizarIndicadoresFiltros() {
       const filtrosActivos = Object.keys(this.filtros).length;
       const badge = $('#filtrosActivosBadge');
       
       if (filtrosActivos > 0) {
           badge.text(filtrosActivos).show();
       } else {
           badge.hide();
       }
   }
   
   /**
    * Limpiar indicadores
    */
   limpiarIndicadores() {
       $('#indicadorBusqueda').hide();
       $('#filtrosActivosBadge').hide();
       $('#resumenFiltros').hide();
   }
   
   /**
    * Setup componentes del modal
    */
   setupModalComponents() {
       // Re-inicializar tooltips en el modal
       $('#modalDetalleCita [data-bs-toggle="tooltip"]').tooltip();
       
       // Configurar tabs si existen
       $('#modalDetalleCita .nav-tabs a').on('click', function(e) {
           e.preventDefault();
           $(this).tab('show');
       });
   }
   
   /**
    * Exportar datos (placeholder)
    */
   exportarDatos(formato) {
       // Esta funcionalidad se puede implementar en el backend
       Swal.fire({
           icon: 'info',
           title: 'Función en desarrollo',
           text: `La exportación a ${formato.toUpperCase()} estará disponible próximamente`,
           confirmButtonColor: '#0077b6'
       });
   }
}

// ===== CSS ADICIONAL PARA ANIMACIONES =====
const estilosAdicionales = `
<style>
   .hover-card {
       transition: all 0.3s ease;
       cursor: pointer;
   }
   
   .hover-card:hover {
       transform: translateY(-5px);
       box-shadow: 0 8px 25px rgba(0,119,182,0.15) !important;
   }
   
   .cita-card {
       border-left: 4px solid #0077b6;
       opacity: 0;
   }
   
   .spin {
       animation: spin 1s linear infinite;
   }
   
   @keyframes spin {
       from { transform: rotate(0deg); }
       to { transform: rotate(360deg); }
   }
   
   .signos-grid .signo-item {
       margin-bottom: 8px;
       padding: 5px 0;
       border-bottom: 1px solid #eee;
   }
   
   .signos-grid .signo-item:last-child {
       border-bottom: none;
   }
   
   .proxima-cita-item {
       transition: all 0.2s ease;
       border: 1px solid #e0e0e0 !important;
   }
   
   .proxima-cita-item:hover {
       border-color: #0077b6 !important;
       background-color: rgba(0,119,182,0.05) !important;
   }
   
   .avatar-circle {
       box-shadow: 0 4px 8px rgba(0,0,0,0.1);
   }
   
   .error-container, .no-data-container {
       padding: 3rem 1rem;
   }
   
   @media (max-width: 768px) {
       .cita-card .card-body {
           padding: 1rem;
       }
       
       .fecha-container {
           margin-bottom: 1rem;
       }
       
       .doctor-info {
           margin-bottom: 1rem;
       }
       
       .acciones-cita {
           margin-top: 1rem;
       }
   }
   
   .loading-overlay {
       position: fixed;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       background: rgba(0,0,0,0.5);
       display: flex;
       justify-content: center;
       align-items: center;
       z-index: 9999;
   }
</style>
`;

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
   // Agregar estilos adicionales
   $('head').append(estilosAdicionales);
   
   // Inicializar la aplicación
   window.consultaCitasApp = new ConsultaCitasApp();
   
   // Event listener global para limpiar filtros desde template no-data
   $(document).on('click', '#btnLimpiarFiltrosNoData', function() {
       window.consultaCitasApp.limpiarFiltros();
   });
   
   
   
   console.log('🏥 MediSys - Consulta de Citas inicializada correctamente');
});