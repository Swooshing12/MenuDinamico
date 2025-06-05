<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($menus)) {
    require_once __DIR__ . '/../../controladores/MenusControlador/MenusController.php';
    $controller = new MenusController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediSys - Gestión de Menús</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../estilos/gestionmenus.css">
</head>
<body>
<?php include __DIR__ . "/../../navbars/header.php"; ?>
<?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

<div class="main-content">
  <div class="container-fluid p-4">
    <!-- Título y botón crear -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="page-title"><i class="bi bi-menu-button-wide-fill me-2"></i>Gestión de Menús</h2>
      <?php if ($permisos['puede_crear']): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearMenuModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Crear Menú
        </button>
      <?php endif; ?>
    </div>
    
    <!-- Estadísticas DINÁMICAS -->
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center">
        <i class="bi bi-menu-button-wide-fill fs-1 me-3 text-primary"></i>
        <div>
          <h5 class="card-title mb-0" id="totalMenus">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </h5>
          <p class="card-text text-muted">Total de Menús</p>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center">
        <i class="bi bi-check-circle-fill fs-1 me-3 text-success"></i>
        <div>
          <h5 class="card-title mb-0" id="menusActivos">
            <div class="spinner-border spinner-border-sm text-success" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </h5>
          <p class="card-text text-muted">Menús Activos</p>
        </div>
      </div>
    </div>
  </div>
</div>

    <!-- Tabla de menús con buscador y paginación -->
    <div class="card">
      <div class="card-body">
        <!-- Buscador -->
        <div class="row mb-3">
          <div class="col-md-8 col-lg-6 mx-auto">
            <div class="search-container">
              <div class="search-wrapper" id="searchWrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="buscarMenu" class="form-control" 
                       placeholder="Buscar menú por nombre..." 
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
          <table class="table table-striped table-hover align-middle shadow-sm" id="tablaMenus">
            <thead>
              <tr>
                <th><i class="bi bi-hash me-1"></i> ID</th>
                <th><i class="bi bi-menu-button-wide me-1"></i> Nombre del Menú</th>
                <th><i class="bi bi-gear me-1"></i> Acciones</th>
              </tr>
            </thead>
            <tbody id="menus-container">
              <!-- El contenido se cargará dinámicamente -->
            </tbody>
          </table>
        </div>

        <!-- Paginación y conteo de registros -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
          <span id="contadorMenus" class="badge bg-primary px-3 py-2">
            <i class="bi bi-menu-button-wide-fill me-1"></i> Cargando menús...
          </span>
          
          <nav aria-label="Paginación de menús">
            <ul id="paginacionMenus" class="pagination pagination-sm mb-0">
              <!-- La paginación se generará dinámicamente -->
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Crear Menú -->
<div class="modal fade" id="crearMenuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formCrearMenu">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Crear Menú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="nombre_menu" class="form-label"><i class="bi bi-menu-button-wide me-1"></i> Nombre del Menú</label>
          <input id="nombre_menu" name="nombre_menu" type="text" class="form-control" required 
                 placeholder="Ej: Administración, Pacientes, etc.">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Crear Menú
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Menú -->
<div class="modal fade" id="editarMenuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formEditarMenu">
      <input type="hidden" name="id_menu" id="edit_id">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Editar Menú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="edit_nombre_menu" class="form-label"><i class="bi bi-menu-button-wide me-1"></i> Nombre del Menú</label>
          <input id="edit_nombre_menu" name="nombre_menu" type="text" class="form-control" required>
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

<!-- Modal Eliminar Menú -->
<div class="modal fade" id="eliminarMenuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formEliminarMenu">
      <input type="hidden" name="id_menu" id="delete_id">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Eliminar Menú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>¿Estás seguro?</strong>
        </div>
        <p class="mb-2">¿Deseas eliminar el menú <strong id="delete_nombre_menu"></strong>?</p>
        <div class="alert alert-info">
          <i class="bi bi-info-circle-fill me-2"></i>
          <small>Esta acción no se puede deshacer. Todos los submenús relacionados también serán afectados.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-trash me-1"></i> Eliminar Menú
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script personalizado -->
<script>
// Pasar datos esenciales a JavaScript
window.gestionMenus = {
    submenuId: <?= $id_submenu ?>,
    permisos: <?= json_encode($permisos) ?>
};
</script>
<script src="../../js/gestionmenus.js"></script>

</body>
</html>