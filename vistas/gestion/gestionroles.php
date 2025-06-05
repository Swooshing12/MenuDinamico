<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($roles)) {
    require_once __DIR__ . '/../../controladores/RolesControlador/RolesController.php';
    $controller = new RolesController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediSys - Gestión de Roles</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../estilos/gestionroles.css">
</head>
<body>
<?php include __DIR__ . "/../../navbars/header.php"; ?>
<?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

<div class="main-content">
  <div class="container-fluid p-4">
    <!-- Título y botón crear -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="page-title"><i class="bi bi-shield-lock-fill me-2"></i>Gestión de Roles</h2>
      <?php if ($permisos['puede_crear']): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearRolModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Crear Rol
        </button>
      <?php endif; ?>
    </div>
    
    <!-- Estadísticas -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-shield-lock-fill fs-1 me-3 text-primary"></i>
            <div>
              <h5 class="card-title mb-0" id="totalRoles">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </h5>
              <p class="card-text text-muted">Total de Roles</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-people-fill fs-1 me-3 text-success"></i>
            <div>
              <h5 class="card-title mb-0" id="rolesActivos">
                <div class="spinner-border spinner-border-sm text-success" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </h5>
              <p class="card-text text-muted">Roles con Usuarios</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-gear-fill fs-1 me-3 text-info"></i>
            <div>
              <h5 class="card-title mb-0" id="permisosAsignados">
                <div class="spinner-border spinner-border-sm text-info" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </h5>
              <p class="card-text text-muted">Permisos Configurados</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de roles con buscador y paginación -->
    <div class="card">
      <div class="card-body">
        <!-- Buscador -->
        <div class="row mb-3">
          <div class="col-md-8 col-lg-6 mx-auto">
            <div class="search-container">
              <div class="search-wrapper" id="searchWrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="buscarRol" class="form-control" 
                       placeholder="Buscar rol por nombre..." 
                       autocomplete="off">
                <button class="btn" type="button" id="limpiarBusqueda" title="Limpiar búsqueda">
                  <i class="bi bi-x-circle"></i>
                </button>
                <div class="search-results-badge" id="searchResultsBadge"></div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="table-responsive table-container">
          <table class="table table-striped table-hover align-middle shadow-sm" id="tablaRoles">
            <thead>
              <tr>
                <th><i class="bi bi-hash me-1"></i> ID</th>
                <th><i class="bi bi-shield-lock me-1"></i> Nombre del Rol</th>
                <th><i class="bi bi-people me-1"></i> Usuarios Asignados</th>
                <th><i class="bi bi-gear me-1"></i> Permisos</th>
                <th><i class="bi bi-calendar me-1"></i> Fecha Creación</th>
                <th><i class="bi bi-tools me-1"></i> Acciones</th>
              </tr>
            </thead>
            <tbody id="roles-container">
              <!-- El contenido se cargará dinámicamente -->
            </tbody>
          </table>
        </div>

        <!-- Paginación y conteo de registros -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
          <span id="contadorRoles" class="badge bg-primary px-3 py-2">
            <i class="bi bi-shield-lock-fill me-1"></i> Cargando roles...
          </span>
          
          <nav aria-label="Paginación de roles">
            <ul id="paginacionRoles" class="pagination pagination-sm mb-0">
              <!-- La paginación se generará dinámicamente -->
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Crear Rol -->
<div class="modal fade" id="crearRolModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" id="formCrearRol">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Crear Nuevo Rol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Nombre del Rol -->
          <div class="col-12">
            <label for="nombre_rol" class="form-label">
              <i class="bi bi-shield-lock me-1"></i> Nombre del Rol
            </label>
            <input id="nombre_rol" name="nombre_rol" type="text" class="form-control" required 
                   placeholder="Ej: Administrador, Doctor, Enfermero, etc.">
          </div>
          
          <!-- Asignación de Permisos -->
          <div class="col-12">
            <hr>
            <h5 class="mb-3"><i class="bi bi-list-check me-2 text-secondary"></i>Asignar Menús y Permisos</h5>
            
            <div id="permisos-container">
              <!-- Los menús y submenús se cargarán dinámicamente aquí -->
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Cargando permisos...</span>
                </div>
                <p class="mt-2 text-muted">Cargando estructura de permisos...</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Crear Rol
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Rol -->
<div class="modal fade" id="editarRolModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" id="formEditarRol">
      <input type="hidden" name="id_rol" id="edit_id">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Editar Rol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Nombre del Rol -->
          <div class="col-12">
            <label for="edit_nombre_rol" class="form-label">
              <i class="bi bi-shield-lock me-1"></i> Nombre del Rol
            </label>
            <input id="edit_nombre_rol" name="nombre_rol" type="text" class="form-control" required>
          </div>
          
          <!-- Edición de Permisos -->
          <div class="col-12">
            <hr>
            <h5 class="mb-3"><i class="bi bi-list-check me-2 text-secondary"></i>Modificar Permisos</h5>
            
            <div id="edit-permisos-container">
              <!-- Los permisos actuales se cargarán aquí -->
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-save me-1"></i> Guardar Cambios
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Eliminar Rol -->
<div class="modal fade" id="eliminarRolModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formEliminarRol">
      <input type="hidden" name="id_rol" id="delete_id">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Eliminar Rol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>¿Estás seguro?</strong>
        </div>
        <p class="mb-2">¿Deseas eliminar el rol <strong id="delete_nombre_rol"></strong>?</p>
        <div class="alert alert-info">
          <i class="bi bi-info-circle-fill me-2"></i>
          <small>Esta acción no se puede deshacer. Todos los usuarios con este rol perderán sus permisos.</small>
        </div>
        <div class="alert alert-danger" id="usuarios-asignados-warning" style="display: none;">
          <i class="bi bi-exclamation-circle-fill me-2"></i>
          <small>Este rol tiene <span id="cantidad-usuarios"></span> usuario(s) asignado(s). Reasígnelos antes de eliminar.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-trash me-1"></i> Eliminar Rol
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ver Permisos -->
<div class="modal fade" id="verPermisosModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bi bi-eye me-1"></i> Permisos del Rol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="permisos-detalle-container">
          <!-- Los permisos detallados se mostrarán aquí -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script personalizado -->
<script>
// Pasar datos esenciales a JavaScript
window.gestionRoles = {
    submenuId: <?= $id_submenu ?>,
    permisos: <?= json_encode($permisos) ?>
};

// También crear objeto con menús para JavaScript
window.menus = <?= json_encode($menus) ?>;
window.submenus = <?= json_encode($submenus) ?>;
</script>
<script src="../../js/gestionroles.js"></script>

</body>
</html>