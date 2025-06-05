<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($submenus)) {
    require_once __DIR__ . '/../../controladores/SubMenusControlador/SubMenusController.php';
    $controller = new SubMenusController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediSys - Gestión de Submenús</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../estilos/gestionsubmenus.css">
</head>
<body>
<?php include __DIR__ . "/../../navbars/header.php"; ?>
<?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

<div class="main-content">
  <div class="container-fluid p-4">
    <!-- Título y botón crear -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="page-title"><i class="bi bi-diagram-3-fill me-2"></i>Gestión de Submenús</h2>
      <?php if ($permisos['puede_crear']): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearSubmenuModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Crear Submenú
        </button>
      <?php endif; ?>
    </div>
    
    <!-- Estadísticas -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-diagram-3-fill fs-1 me-3 text-primary"></i>
            <div>
              <h5 class="card-title mb-0" id="totalSubmenus">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </h5>
              <p class="card-text text-muted">Total de Submenús</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-1 me-3 text-success"></i>
            <div>
              <h5 class="card-title mb-0" id="submenusActivos">
                <div class="spinner-border spinner-border-sm text-success" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </h5>
              <p class="card-text text-muted">Submenús Activos</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-collection-fill fs-1 me-3 text-info"></i>
            <div>
              <h5 class="card-title mb-0" id="menusConSubmenus">
                <div class="spinner-border spinner-border-sm text-info" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </h5>
              <p class="card-text text-muted">Menús con Submenús</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de submenús con buscador y paginación -->
    <div class="card">
      <div class="card-body">
        <!-- Buscador -->
        <div class="row mb-3">
          <div class="col-md-8 col-lg-6 mx-auto">
            <div class="search-container">
              <div class="search-wrapper" id="searchWrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="buscarSubmenu" class="form-control" 
                       placeholder="Buscar por nombre, URL o menú..." 
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
          <table class="table table-striped table-hover align-middle shadow-sm" id="tablaSubmenus">
            <thead>
              <tr>
                <th><i class="bi bi-hash me-1"></i> ID</th>
                <th><i class="bi bi-diagram-3 me-1"></i> Nombre del Submenú</th>
                <th><i class="bi bi-link-45deg me-1"></i> URL</th>
                <th><i class="bi bi-menu-button-wide me-1"></i> Menú Padre</th>
                <th><i class="bi bi-gear me-1"></i> Acciones</th>
              </tr>
            </thead>
            <tbody id="submenus-container">
              <!-- El contenido se cargará dinámicamente -->
            </tbody>
          </table>
        </div>

        <!-- Paginación y conteo de registros -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
          <span id="contadorSubmenus" class="badge bg-primary px-3 py-2">
            <i class="bi bi-diagram-3-fill me-1"></i> Cargando submenús...
          </span>
          
          <nav aria-label="Paginación de submenús">
            <ul id="paginacionSubmenus" class="pagination pagination-sm mb-0">
              <!-- La paginación se generará dinámicamente -->
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Crear Submenú -->
<div class="modal fade" id="crearSubmenuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="formCrearSubmenu">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Crear Submenú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Nombre del Submenú -->
          <div class="col-md-6">
            <label for="nombre_submenu" class="form-label"><i class="bi bi-diagram-3 me-1"></i> Nombre del Submenú</label>
            <input id="nombre_submenu" name="nombre_submenu" type="text" class="form-control" required 
                   placeholder="Ej: Gestión Usuarios, Reportes, etc.">
          </div>
          <!-- URL del Submenú -->
          <div class="col-md-6">
            <label for="url_submenu" class="form-label"><i class="bi bi-link-45deg me-1"></i> URL del Submenú</label>
            <input id="url_submenu" name="url_submenu" type="text" class="form-control" required 
                   placeholder="Ej: vistas/gestion/usuarios.php">
          </div>
          <!-- Menú Padre -->
          <div class="col-12">
            <label for="id_menu" class="form-label"><i class="bi bi-menu-button-wide me-1"></i> Menú Padre</label>
            <select id="id_menu" name="id_menu" class="form-select" required>
              <option value="">Seleccione un menú padre...</option>
              <?php foreach ($menus as $menu): ?>
                <option value="<?= $menu['id_menu'] ?>"><?= htmlspecialchars($menu['nombre_menu']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Crear Submenú
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Submenú -->
<div class="modal fade" id="editarSubmenuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="formEditarSubmenu">
      <input type="hidden" name="id_submenu" id="edit_id">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Editar Submenú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Nombre del Submenú -->
          <div class="col-md-6">
            <label for="edit_nombre_submenu" class="form-label"><i class="bi bi-diagram-3 me-1"></i> Nombre del Submenú</label>
            <input id="edit_nombre_submenu" name="nombre_submenu" type="text" class="form-control" required>
          </div>
          <!-- URL del Submenú -->
          <div class="col-md-6">
            <label for="edit_url_submenu" class="form-label"><i class="bi bi-link-45deg me-1"></i> URL del Submenú</label>
            <input id="edit_url_submenu" name="url_submenu" type="text" class="form-control" required>
          </div>
          <!-- Menú Padre -->
          <div class="col-12">
            <label for="edit_id_menu" class="form-label"><i class="bi bi-menu-button-wide me-1"></i> Menú Padre</label>
            <select id="edit_id_menu" name="id_menu" class="form-select" required>
              <option value="">Seleccione un menú padre...</option>
              <?php foreach ($menus as $menu): ?>
                <option value="<?= $menu['id_menu'] ?>"><?= htmlspecialchars($menu['nombre_menu']) ?></option>
              <?php endforeach; ?>
            </select>
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

<!-- Modal Eliminar Submenú -->
<div class="modal fade" id="eliminarSubmenuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formEliminarSubmenu">
      <input type="hidden" name="id_submenu" id="delete_id">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Eliminar Submenú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>¿Estás seguro?</strong>
        </div>
        <p class="mb-2">¿Deseas eliminar el submenú <strong id="delete_nombre_submenu"></strong>?</p>
        <div class="alert alert-info">
          <i class="bi bi-info-circle-fill me-2"></i>
          <small>Esta acción no se puede deshacer. Todos los permisos relacionados también serán afectados.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-trash me-1"></i> Eliminar Submenú
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
window.gestionSubmenus = {
    submenuId: <?= $id_submenu ?>,
    permisos: <?= json_encode($permisos) ?>
};

// También crear objeto con menus para JavaScript
window.menus = <?= json_encode($menus) ?>;
</script>
<script src="../../js/gestionsubmenus.js"></script>

</body>
</html>