<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($especialidades)) {
    require_once __DIR__ . '/../../controladores/EspecialidadesControlador/EspecialidadesController.php';
    $controller = new EspecialidadesController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSys - Gestión de Especialidades</title>
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../estilos/gestionespecialidades.css">
    
    <style>
        .card-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }
        
        .estadisticas-card {
            border-left: 4px solid #17a2b8;
            transition: transform 0.2s;
        }
        
        .estadisticas-card:hover {
            transform: translateY(-2px);
        }
        
        .tabla-especialidades .btn {
            margin: 0 2px;
        }
        
        .descripcion-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .spin { 
            animation: spin 1s linear infinite; 
        }
        
        @keyframes spin { 
            from { transform: rotate(0deg); } 
            to { transform: rotate(360deg); } 
        }
    </style>
</head>

<body>
    <?php include __DIR__ . "/../../navbars/header.php"; ?>
    <?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h3 text-info mb-1">
                                <i class="bi bi-hospital me-2"></i>
                                Gestión de Especialidades Médicas
                            </h2>
                            <p class="text-muted mb-0">Administre las especialidades médicas del sistema</p>
                        </div>
                        
                        <?php if ($permisos['puede_crear']): ?>
                        <button type="button" class="btn btn-info btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#crearEspecialidadModal">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nueva Especialidad
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 estadisticas-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-gradient rounded-circle p-3">
                                    <i class="bi bi-hospital-fill text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Especialidades</div>
                                <div class="fw-bold fs-4 text-info" id="totalEspecialidades">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 estadisticas-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-gradient rounded-circle p-3">
                                    <i class="bi bi-people-fill text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Con Doctores</div>
                                <div class="fw-bold fs-4 text-success" id="especialidadesConDoctores">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 estadisticas-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-gradient rounded-circle p-3">
                                    <i class="bi bi-person-hearts text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Doctores</div>
                                <div class="fw-bold fs-4 text-primary" id="totalDoctores">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-0">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-funnel me-2"></i>
                        Filtros y Búsqueda
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Búsqueda Global -->
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="busquedaGlobal" 
                                       placeholder="Buscar por nombre o descripción...">
                                <button class="btn btn-outline-secondary" type="button" id="limpiarBusqueda">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-lg-3">
                            <select class="form-select" id="registrosPorPagina">
                                <option value="10">10 por página</option>
                                <option value="25">25 por página</option>
                                <option value="50">50 por página</option>
                                <option value="100">100 por página</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-3">
                            <button type="button" class="btn btn-outline-primary w-100" id="refrescarTabla">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Refrescar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>
                        Lista de Especialidades
                    </h6>
                    <div class="text-muted small" id="contadorEspecialidades">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 tabla-especialidades">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0" style="width: 30%">
                                        <i class="bi bi-hospital me-1"></i>
                                        Especialidad
                                    </th>
                                    <th class="border-0" style="width: 50%">
                                        <i class="bi bi-chat-text me-1"></i>
                                        Descripción
                                    </th>
                                    <th class="border-0" style="width: 10%">
                                        <i class="bi bi-people me-1"></i>
                                        Doctores
                                    </th>
                                    <th class="border-0 text-center" style="width: 10%">
                                        <i class="bi bi-gear me-1"></i>
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="especialidades-container">
                                <!-- Se llena dinámicamente via AJAX -->
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="spinner-border text-info" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <p class="text-muted mt-2 mb-0">Cargando especialidades...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination Footer -->
                <div class="card-footer bg-white border-0">
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center text-md-start">
                            <div class="dataTables_info text-muted" id="infoRegistros">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Paginación de especialidades" class="d-flex justify-content-center justify-content-md-end">
                                <ul class="pagination pagination-sm mb-0" id="paginacion">
                                    <!-- Se llena dinámicamente -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MODALES ========== -->

    <!-- Modal Crear Especialidad -->
    <div class="modal fade" id="crearEspecialidadModal" tabindex="-1" aria-labelledby="crearEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="crearEspecialidadModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Crear Nueva Especialidad
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <form id="formCrearEspecialidad">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nombre_especialidad" name="nombre_especialidad" 
                                           placeholder="Nombre de la especialidad" required maxlength="100">
                                    <label for="nombre_especialidad">
                                        <i class="bi bi-hospital me-1"></i>
                                        Nombre de la Especialidad *
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Máximo 100 caracteres
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="descripcion" name="descripcion" 
                                              placeholder="Descripción" style="height: 120px" maxlength="500"></textarea>
                                    <label for="descripcion">
                                        <i class="bi bi-chat-text me-1"></i>
                                        Descripción
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Máximo 500 caracteres (opcional)
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-check-circle me-1"></i>
                            Crear Especialidad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Especialidad -->
    <div class="modal fade" id="editarEspecialidadModal" tabindex="-1" aria-labelledby="editarEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editarEspecialidadModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Especialidad
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <form id="formEditarEspecialidad">
                    <input type="hidden" id="editarIdEspecialidad" name="id_especialidad">
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="editarNombreEspecialidad" name="nombre_especialidad" 
                                           placeholder="Nombre de la especialidad" required maxlength="100">
                                    <label for="editarNombreEspecialidad">
                                        <i class="bi bi-hospital me-1"></i>
                                        Nombre de la Especialidad *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="editarDescripcion" name="descripcion" 
                                              placeholder="Descripción" style="height: 120px" maxlength="500"></textarea>
                                    <label for="editarDescripcion">
                                        <i class="bi bi-chat-text me-1"></i>
                                        Descripción
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle me-1"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="verEspecialidadModal" tabindex="-1" aria-labelledby="verEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="verEspecialidadModalLabel">
                        <i class="bi bi-eye me-2"></i>
                        Detalles de la Especialidad
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <div class="modal-body" id="contenidoVerEspecialidad">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-muted">Cargando información...</p>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEditarDesdeVer">
                        <i class="bi bi-pencil-square me-1"></i>
                        Editar Especialidad
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Especialidad -->
    <div class="modal fade" id="eliminarEspecialidadModal" tabindex="-1" aria-labelledby="eliminarEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="eliminarEspecialidadModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-danger border-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>¡Atención!</strong> Esta acción eliminará permanentemente la especialidad del sistema.
                    </div>
                    
                    <div class="row">
                        <div class="col-12 text-center">
                            <i class="bi bi-hospital-fill text-danger" style="font-size: 4rem;"></i>
                            <h6 class="mt-3">¿Está seguro de eliminar esta especialidad?</h6>
                            <p class="text-muted mb-0" id="especialidadAEliminar">
                                <!-- Se llena dinámicamente -->
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                        <i class="bi bi-trash me-1"></i>
                        Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== SCRIPTS ========== -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap Bundle -->
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>
    
    <!-- Script de configuración -->
    <script>
        // Configuración global para el módulo de especialidades
        window.especialidadesConfig = {
            permisos: <?php echo json_encode($permisos); ?>,
            submenuId: <?php echo $id_submenu; ?>
        };
        
        console.log('🔧 Configuración de especialidades:', window.especialidadesConfig);
    </script>
    
    <!-- Script principal de especialidades -->
    <script src="../../js/gestionespecialidades.js"></script>
</body>
</html>