<?php
// Si no se han cargado los datos, usar el controlador
if (!isset($usuarios)) {
    require_once __DIR__ . '/../../controladores/UsuariosControlador/UsuariosController.php';
    $controller = new UsuariosController();
    $controller->index();
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediSys - Gestión de Usuarios</title>
  <!-- En el <head> de tu vista -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../../estilos/gestionusuarios.css">
</head>
<body>
<?php include __DIR__ . "/../../navbars/header.php"; ?>
<?php include __DIR__ . "/../../navbars/sidebar.php"; ?>

<div class="main-content">
  <div class="container-fluid p-4">
    <!-- Título y botón crear -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="page-title"><i class="bi bi-people-fill me-2"></i>Gestión de Usuarios</h2>
      <?php if ($permisos['puede_crear']): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
          <i class="bi bi-person-plus-fill me-1"></i> Crear Usuario
        </button>
      <?php endif; ?>
    </div>
    
    <!-- Estadísticas -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-people-fill fs-1 me-3 text-primary"></i>
            <div>
              <h5 class="card-title mb-0"><?= count($usuarios) ?></h5>
              <p class="card-text text-muted">Total de Usuarios</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-person-check-fill fs-1 me-3 text-success"></i>
            <div>
              <h5 class="card-title mb-0"><?= count(array_filter($usuarios, function($u) { return $u['id_estado'] == 1; })) ?></h5>
              <p class="card-text text-muted">Usuarios Activos</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-person-x-fill fs-1 me-3 text-danger"></i>
            <div>
              <h5 class="card-title mb-0"><?= count(array_filter($usuarios, function($u) { return $u['id_estado'] != 1; })) ?></h5>
              <p class="card-text text-muted">Usuarios Inactivos/Bloqueados/Pendientes</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center flex-wrap">
          <label class="me-2 fw-bold"><i class="bi bi-funnel-fill me-1"></i> Filtrar:</label>
          <div class="btn-group">
            <a href="?submenu_id=<?= $id_submenu ?>&filtro=todos" 
              class="btn btn-sm btn-outline-secondary <?= $filtro === 'todos' ? 'active' : '' ?>">
              Todos
            </a>
            <a href="?submenu_id=<?= $id_submenu ?>&filtro=activos" 
              class="btn btn-sm btn-outline-success <?= $filtro === 'activos' ? 'active' : '' ?>">
              Activos
            </a>
            <a href="?submenu_id=<?= $id_submenu ?>&filtro=inactivos" 
              class="btn btn-sm btn-outline-danger <?= $filtro === 'inactivos' ? 'active' : '' ?>">
              Inactivos
            </a>
            <a href="?submenu_id=<?= $id_submenu ?>&filtro=bloqueados" 
              class="btn btn-sm btn-outline-warning <?= $filtro === 'bloqueados' ? 'active' : '' ?>">
              Bloqueados
            </a>
            <a href="?submenu_id=<?= $id_submenu ?>&filtro=pendientes" 
              class="btn btn-sm btn-outline-primary <?= $filtro === 'pendientes' ? 'active' : '' ?>">
              Pendientes
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de usuarios con buscador y paginación -->
<div class="card">
  <div class="card-body">
    <!-- Buscador -->
    <div class="row mb-3">
      <div class="col-md-8 col-lg-6 mx-auto">
        <div class="search-container">
          <div class="search-wrapper" id="searchWrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="buscarUsuario" class="form-control" 
                   placeholder="Buscar por cédula, nombre, apellido o correo..." 
                   autocomplete="off">
            <button class="btn" type="button" id="limpiarBusqueda" title="Limpiar búsqueda">
              <i class="bi bi-x-circle"></i>
            </button>
            <div class="search-results-badge" id="searchResultsBadge"></div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle shadow-sm" id="tablaUsuarios">
        <thead>
          <tr>
            <th><i class="bi bi-fingerprint me-1"></i> Cédula</th>
            <th><i class="bi bi-person-badge me-1"></i> Usuario</th>
            <th><i class="bi bi-person-vcard me-1"></i> Nombres</th>
            <th><i class="bi bi-person-vcard me-1"></i> Apellidos</th>
            <th><i class="bi bi-gender-ambiguous me-1"></i> Sexo</th>
            <th><i class="bi bi-globe me-1"></i> Nacionalidad</th>
            <th><i class="bi bi-envelope me-1"></i> Correo</th>
            <th><i class="bi bi-shield-lock me-1"></i> Rol</th>
            <th><i class="bi bi-toggle-on me-1"></i> Estado</th>
            <th><i class="bi bi-gear me-1"></i> Acciones</th>
          </tr>
        </thead>
        <tbody id="usuarios-container">
          <!-- El contenido se cargará dinámicamente -->
        </tbody>
      </table>
    </div>

    <!-- Paginación y conteo de registros -->
    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
      <span id="contadorUsuarios" class="badge bg-primary px-3 py-2">
        <i class="bi bi-people-fill me-1"></i> Cargando usuarios...
      </span>
      
      <nav aria-label="Paginación de usuarios">
        <ul id="paginacionUsuarios" class="pagination pagination-sm mb-0">
          <!-- La paginación se generará dinámicamente -->
        </ul>
      </nav>
    </div>
  </div>
</div>
<!-- Modal Crear Usuario -->
<div class="modal fade" id="crearUsuarioModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" id="formCrearUsuario">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Crear Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Cédula -->
          <div class="col-md-4">
            <label for="cedula" class="form-label"><i class="bi bi-fingerprint me-1"></i> Cédula</label>
            <div class="input-group">
              <input id="cedula" name="cedula" type="text" class="form-control" required>
              <button type="button" class="btn btn-outline-secondary" id="btnBuscarCedula">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
          <!-- Nombres / Apellidos -->
          <div class="col-md-4">
            <label for="nombres" class="form-label"><i class="bi bi-person-fill me-1"></i> Nombres</label>
            <input id="nombres" name="nombres" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label for="apellidos" class="form-label"><i class="bi bi-person-fill me-1"></i> Apellidos</label>
            <input id="apellidos" name="apellidos" class="form-control" required>
          </div>
          <!-- Sexo / Nacionalidad -->
          <div class="col-md-4">
            <label for="sexo" class="form-label"><i class="bi bi-gender-ambiguous me-1"></i> Sexo</label>
            <select id="sexo" name="sexo" class="form-select" required>
              <option value="">Seleccione...</option>
              <option value="M">Masculino</option>
              <option value="F">Femenino</option>
              <option value="O">Otro</option>
            </select>
          </div>
          <div class="col-md-8">
            <label for="nacionalidadSelect" class="form-label"><i class="bi bi-globe2 me-1"></i> Nacionalidad</label>
            <div class="input-group">
              <span class="input-group-text has-icon"><i class="bi bi-globe2"></i></span>
              <input type="hidden" id="nacionalidadHidden" name="nacionalidad" value="">
              <select id="nacionalidadSelect" class="form-select text-dark bg-light" required>
                <option value="">Seleccione nacionalidad</option>
              </select>
            </div>
          </div>
          <!-- Usuario / Correo -->
          <div class="col-md-6">
            <label for="username" class="form-label"><i class="bi bi-person-badge me-1"></i> Usuario</label>
            <input id="username" name="username" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="correo" class="form-label"><i class="bi bi-envelope-fill me-1"></i> Correo</label>
            <input id="correo" type="email" name="correo" class="form-control" required>
          </div>
          <!-- Contraseña / Rol -->
          <div class="col-md-6">
            <label for="password" class="form-label"><i class="bi bi-key-fill me-1"></i> Contraseña</label>
            <input id="password" type="password" name="password" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="rol" class="form-label"><i class="bi bi-shield-lock me-1"></i> Rol</label>
            <select id="rol" name="rol" class="form-select" required>
              <option value="">Seleccione un rol...</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
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
          <i class="bi bi-save me-1"></i> Crear Usuario
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" id="formEditarUsuario">
      <input type="hidden" name="id_usuario" id="edit_id">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Cédula -->
          <div class="col-md-4">
            <label for="edit_cedula" class="form-label"><i class="bi bi-fingerprint me-1"></i> Cédula</label>
            <input id="edit_cedula" name="cedula" type="text" class="form-control" required>
          </div>
          <!-- Nombres / Apellidos -->
          <div class="col-md-4">
            <label for="edit_nombres" class="form-label"><i class="bi bi-person-fill me-1"></i> Nombres</label>
            <input id="edit_nombres" name="nombres" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label for="edit_apellidos" class="form-label"><i class="bi bi-person-fill me-1"></i> Apellidos</label>
            <input id="edit_apellidos" name="apellidos" class="form-control" required>
          </div>
          <!-- Sexo / Nacionalidad -->
          <div class="col-md-4">
            <label for="edit_sexo" class="form-label"><i class="bi bi-gender-ambiguous me-1"></i> Sexo</label>
            <select id="edit_sexo" name="sexo" class="form-select" required>
              <option value="M">Masculino</option>
              <option value="F">Femenino</option>
              <option value="O">Otro</option>
            </select>
          </div>
          <div class="col-md-8">
            <label for="edit_nacionalidadSelect" class="form-label"><i class="bi bi-globe2 me-1"></i> Nacionalidad</label>
            <div class="input-group">
              <span class="input-group-text has-icon"><i class="bi bi-globe2"></i></span>
              <select id="edit_nacionalidadSelect" name="nacionalidad" class="form-select" required>
                <option></option>
              </select>
            </div>
          </div>
          <!-- Usuario / Correo -->
          <div class="col-md-6">
            <label for="edit_username" class="form-label"><i class="bi bi-person-badge me-1"></i> Usuario</label>
            <input id="edit_username" name="username" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="edit_correo" class="form-label"><i class="bi bi-envelope-fill me-1"></i> Correo</label>
            <input id="edit_correo" type="email" name="correo" class="form-control" required>
          </div>
          <!-- Nueva Contraseña / Rol / Estado -->
          <div class="col-md-4">
            <label for="edit_password" class="form-label"><i class="bi bi-key-fill me-1"></i> Nueva Contraseña</label>
            <input id="edit_password" type="password" name="password" class="form-control" placeholder="Dejar vacío para mantener actual">
            <small class="form-text text-muted">Dejar vacío si no desea cambiar la contraseña</small>
          </div>
          <div class="col-md-4">
            <label for="edit_rol" class="form-label"><i class="bi bi-shield-lock me-1"></i> Rol</label>
            <select id="edit_rol" name="rol" class="form-select" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label for="edit_estado" class="form-label"><i class="bi bi-toggle-on me-1"></i> Estado</label>
            <select id="edit_estado" name="estado" class="form-select" required>
              <option value="1">✅ Activo</option>
              <option value="2">🚫 Bloqueado</option>
              <option value="3">⏳ Pendiente</option>
              <option value="4">❌ Inactivo</option>
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

<!-- Modal Eliminar Usuario -->
<div class="modal fade" id="eliminarUsuarioModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formEliminarUsuario">
      <input type="hidden" name="id_usuario" id="delete_id">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-person-x me-1"></i> Desactivar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>¿Estás seguro?</strong>
        </div>
        <p class="mb-2">¿Deseas desactivar al usuario <strong id="delete_username"></strong>?</p>
        <div class="alert alert-info">
          <i class="bi bi-info-circle-fill me-2"></i>
          <small>El usuario será marcado como inactivo pero no se eliminará permanentemente del sistema.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-trash me-1"></i> Desactivar Usuario
        </button>
      </div>
    </form>
  </div>
</div>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>

<!-- Script personalizado -->
<script>
// Pasar SOLO datos esenciales a JavaScript
window.gestionUsuarios = {
    submenuId: <?= $id_submenu ?>,
    permisos: <?= json_encode($permisos) ?>
};

// También crear objeto con roles para JavaScript
window.roles = <?= json_encode($roles) ?>;
</script>
<script src="../../js/gestionusuarios.js"></script>

</body>
</html>