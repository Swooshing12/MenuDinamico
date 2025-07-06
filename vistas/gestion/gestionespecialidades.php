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
    <title>Gestión de Especialidades | MediSys</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link href="../../estilos/gestionespecialidades.css" rel="stylesheet">
</head>
<body>
    <!-- Incluir navegación -->
    <?php include "../../navbars/header.php"; ?>
    <?php include "../../navbars/sidebar.php"; ?>

    <!-- Contenido principal -->
    <main class="dashboard-container">
        <div class="container-fluid p-4">
            <!-- Header con estadísticas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="fw-bold text-primary mb-2">
                                <i class="bi bi-hospital me-2"></i>
                                Gestión de Especialidades
                            </h1>
                            <p class="text-muted mb-0">Administra las especialidades médicas y sus sucursales asignadas</p>
                        </div>
                        
                        <?php if ($permisos['puede_crear']): ?>
                        <button class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#crearEspecialidadModal">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nueva Especialidad
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de estadísticas -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card estadistica-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="estadistica-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3">
                                    <i class="bi bi-hospital"></i>
                                </div>
                                <div>
                                    <h3 class="estadistica-numero mb-1" id="totalEspecialidades">0</h3>
                                    <p class="text-muted mb-0 small">Total Especialidades</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card estadistica-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="estadistica-icon bg-success bg-opacity-10 text-success rounded-circle me-3">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div>
                                    <h3 class="estadistica-numero mb-1" id="especialidadesConDoctores">0</h3>
                                    <p class="text-muted mb-0 small">Con Doctores</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card estadistica-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="estadistica-icon bg-info bg-opacity-10 text-info rounded-circle me-3">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <h3 class="estadistica-numero mb-1" id="sucursalesActivas">0</h3>
                                    <p class="text-muted mb-0 small">Sucursales Activas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card estadistica-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="estadistica-icon bg-warning bg-opacity-10 text-warning rounded-circle me-3">
                                    <i class="bi bi-person-heart"></i>
                                </div>
                                <div>
                                    <h3 class="estadistica-numero mb-1" id="totalDoctores">0</h3>
                                    <p class="text-muted mb-0 small">Total Doctores</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel principal -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-list-ul me-2 text-primary"></i>
                                        Listado de Especialidades
                                    </h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-end gap-2">
                                        <div class="input-group" style="max-width: 300px;">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-search text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" 
                                                   id="busquedaGlobal" placeholder="Buscar especialidades...">
                                        </div>
                                        <button class="btn btn-outline-secondary" id="refrescarTabla" title="Refrescar">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <!-- Información de la tabla -->
                            <div class="px-3 py-2 bg-light border-bottom">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="dataTables_info text-muted" id="infoRegistros">
                                            <span><i class="bi bi-info-circle me-1"></i>Cargando información...</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <label class="text-muted small">Mostrar:</label>
                                            <select class="form-select form-select-sm" id="registrosPorPagina" style="width: auto;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla responsive -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="tablaEspecialidades">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="60" class="text-center">
                                                <i class="bi bi-hash me-1"></i>ID
                                            </th>
                                            <th>
                                                <i class="bi bi-hospital me-1"></i>Especialidad
                                            </th>
                                            <th>
                                                <i class="bi bi-chat-text me-1"></i>Descripción
                                            </th>
                                            <th width="120" class="text-center">
                                                <i class="bi bi-building me-1"></i>Sucursales
                                            </th>
                                            <th width="100" class="text-center">
                                                <i class="bi bi-people me-1"></i>Doctores
                                            </th>
                                            <th width="150" class="text-center">
                                                <i class="bi bi-gear me-1"></i>Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="especialidades-container">
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <p class="text-muted mt-2 mb-0">Cargando especialidades...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Footer con paginación -->
                        <div class="card-footer bg-white border-0">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="text-muted small" id="infoPaginacion">
                                        <!-- Se llena dinámicamente -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <nav aria-label="Paginación de especialidades" class="d-flex justify-content-end">
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
        </div>
    </main>

    <!-- ========== MODALES ========== -->

    <!-- Modal Crear Especialidad CON SUCURSALES -->
    <div class="modal fade" id="crearEspecialidadModal" tabindex="-1" aria-labelledby="crearEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="crearEspecialidadModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Crear Nueva Especialidad
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form id="formCrearEspecialidad">
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Información básica -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Información de la Especialidad
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
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
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="descripcion" name="descripcion" 
                                              placeholder="Descripción" style="height: 100px" maxlength="500"></textarea>
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

                            <!-- Asignación de sucursales -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-building me-2"></i>
                                    Asignación de Sucursales
                                </h6>
                                <p class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Selecciona en qué sucursales estará disponible esta especialidad
                                </p>
                            </div>
                            
                            <div class="col-12">
                                <div class="row" id="sucursalesContainer">
                                    <?php foreach ($sucursales as $sucursal): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card sucursal-card h-100">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input sucursal-checkbox" type="checkbox" 
                                                           value="<?= $sucursal['id_sucursal'] ?>" 
                                                           id="sucursal_<?= $sucursal['id_sucursal'] ?>"
                                                           name="sucursales[]">
                                                    <label class="form-check-label w-100" for="sucursal_<?= $sucursal['id_sucursal'] ?>">
                                                        <div class="d-flex align-items-start">
                                                            <div class="me-3">
                                                                <div class="sucursal-icon bg-primary bg-opacity-10 text-primary rounded-circle">
                                                                    <i class="bi bi-building"></i>
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($sucursal['nombre_sucursal']) ?></h6>
                                                                <p class="text-muted small mb-1">
                                                                    <i class="bi bi-geo-alt me-1"></i>
                                                                    <?= htmlspecialchars($sucursal['direccion']) ?>
                                                                </p>
                                                                <p class="text-muted small mb-0">
                                                                    <i class="bi bi-telephone me-1"></i>
                                                                    <?= htmlspecialchars($sucursal['telefono']) ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="seleccionarTodasSucursales">
                                            <i class="bi bi-check-all me-1"></i>
                                            Seleccionar Todas
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="deseleccionarTodasSucursales">
                                            <i class="bi bi-x-square me-1"></i>
                                            Deseleccionar Todas
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            Crear Especialidad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Especialidad CON SUCURSALES -->
    <div class="modal fade" id="editarEspecialidadModal" tabindex="-1" aria-labelledby="editarEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editarEspecialidadModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Especialidad
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form id="formEditarEspecialidad">
                    <input type="hidden" id="editarIdEspecialidad" name="id_especialidad">
                    
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Información básica -->
                            <div class="col-12">
                                <h6 class="text-warning border-bottom pb-2 mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Información de la Especialidad
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="editarNombreEspecialidad" name="nombre_especialidad" 
                                           placeholder="Nombre de la especialidad" required maxlength="100">
                                    <label for="editarNombreEspecialidad">
                                        <i class="bi bi-hospital me-1"></i>
                                        Nombre de la Especialidad *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="editarDescripcion" name="descripcion" 
                                              placeholder="Descripción" style="height: 100px" maxlength="500"></textarea>
                                    <label for="editarDescripcion">
                                        <i class="bi bi-chat-text me-1"></i>
                                        Descripción
                                    </label>
                                </div>
                            </div>

                            <!-- Asignación de sucursales para editar -->
                            <div class="col-12">
                                <h6 class="text-warning border-bottom pb-2 mb-3">
                                    <i class="bi bi-building me-2"></i>
                                    Asignación de Sucursales
                                </h6>
                            </div>
                            
                            <div class="col-12">
                                <div class="row" id="editarSucursalesContainer">
                                    <?php foreach ($sucursales as $sucursal): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card sucursal-card h-100">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input editar-sucursal-checkbox" type="checkbox" 
                                                           value="<?= $sucursal['id_sucursal'] ?>" 
                                                           id="editar_sucursal_<?= $sucursal['id_sucursal'] ?>"
                                                           name="sucursales[]">
                                                    <label class="form-check-label w-100" for="editar_sucursal_<?= $sucursal['id_sucursal'] ?>">
                                                        <div class="d-flex align-items-start">
                                                            <div class="me-3">
                                                                <div class="sucursal-icon bg-warning bg-opacity-10 text-warning rounded-circle">
                                                                    <i class="bi bi-building"></i>
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($sucursal['nombre_sucursal']) ?></h6>
                                                                <p class="text-muted small mb-1">
                                                                    <i class="bi bi-geo-alt me-1"></i>
                                                                    <?= htmlspecialchars($sucursal['direccion']) ?>
                                                                </p>
                                                                <p class="text-muted small mb-0">
                                                                    <i class="bi bi-telephone me-1"></i>
                                                                    <?= htmlspecialchars($sucursal['telefono']) ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-warning btn-sm" id="editarSeleccionarTodasSucursales">
                                            <i class="bi bi-check-all me-1"></i>
                                            Seleccionar Todas
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="editarDeseleccionarTodasSucursales">
                                            <i class="bi bi-x-square me-1"></i>
                                            Deseleccionar Todas
                                        </button>
                                    </div>
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
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="verEspecialidadModalLabel">
                        <i class="bi bi-eye me-2"></i>
                        Detalles de la Especialidad
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="contenidoVerEspecialidad">
                    <!-- Se llena dinámicamente -->
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="eliminarEspecialidadModal" tabindex="-1" aria-labelledby="eliminarEspecialidadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="eliminarEspecialidadModalLabel">
                        <i class="bi bi-trash me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                    </div>
                    
                    <p class="mb-3">¿Estás seguro de que deseas eliminar la especialidad <strong id="nombreEspecialidadEliminar"></strong>?</p>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <small>Esta especialidad se eliminará de todas las sucursales asignadas.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                        <i class="bi bi-trash me-1"></i>
                        Eliminar Especialidad
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Configuración para JavaScript -->
    <script>
        window.especialidadesConfig = {
            submenuId: <?= json_encode($id_submenu) ?>,
            permisos: <?= json_encode($permisos) ?>
        };
    </script>
    
    <!-- Script personalizado -->
    <script src="../../js/gestionespecialidades.js"></script>
</body>
</html>