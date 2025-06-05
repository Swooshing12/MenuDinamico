<?php
// listarroles.php
session_start();
require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../modelos/Roles.php";
require_once __DIR__ . "/../../modelos/Permisos.php";

// 1) Validar login
if (!isset($_SESSION['id_rol'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

include __DIR__ . "/../../navbars/header.php";
include __DIR__ . "/../../navbars/sidebar.php"; 


$rolesModel    = new Roles();
$permisosModel = new Permisos();
$id_rol        = $_SESSION['id_rol'];

// 2) Fijamos el ID del submenú "Gestión de Roles" (número real en tu BD)
const SUBMENU_GESTION_ROLES = 16;  

// 3) Obtener permisos usando siempre este submenu
$permisos = $permisosModel->obtenerPermisos($id_rol, SUBMENU_GESTION_ROLES);

// —— PROCESAR CREAR —— 
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='create') {
    if (!$permisos['puede_crear']) {
        echo "<script>Swal.fire('⚠️','No tienes permiso para crear roles','warning');</script>";
        exit;
    }
    $name = trim($_POST['nombre_rol']);
    if ($name==='') {
        echo "<script>Swal.fire('❌','El nombre no puede estar vacío','error');</script>";
        exit;
    }
    if ($rolesModel->existeRolPorNombre($name)) {
        echo "<script>Swal.fire('❌','Ya existe un rol con ese nombre','error');</script>";
        exit;
    }
    $rolesModel->crearRol($name);
    echo "<script>
      Swal.fire('✅','Rol creado','success').then(()=>{
        window.location='listarroles.php?submenu_id=" . SUBMENU_GESTION_ROLES . "';
      });
    </script>";
    exit;
}

// —— PROCESAR ACTUALIZAR PERMISOS —— 
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='update') {
    if (!$permisos['puede_editar']) {
        echo "<script>Swal.fire('⚠️','No tienes permiso para editar roles','warning');</script>";
        exit;
    }
    $roleId = (int)$_POST['role_id'];
    $perms = [];
    foreach ($_POST['subm'] ?? [] as $sm) {
        $sm = (int)$sm;
        $perms[$sm] = [
            'crear'   => isset($_POST["crear_{$sm}"]),
            'editar'  => isset($_POST["editar_{$sm}"]),
            'eliminar'=> isset($_POST["eliminar_{$sm}"]),
        ];
    }
    $rolesModel->actualizarPermisosRol($roleId, $perms);
    echo "<script>
      Swal.fire('✅','Permisos actualizados','success').then(()=>{
        window.location='listarroles.php?submenu_id=" . SUBMENU_GESTION_ROLES . "';
      });
    </script>";
    exit;
}

// —— PROCESAR ELIMINAR —— 
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    if (!$permisos['puede_eliminar']) {
        echo "<script>Swal.fire('⚠️','No tienes permiso para eliminar roles','warning');</script>";
        exit;
    }
    $rolesModel->eliminarRol((int)$_GET['delete']);
    echo "<script>
      Swal.fire('✅','Rol eliminado','success').then(()=>{
        window.location='listarroles.php?submenu_id=" . SUBMENU_GESTION_ROLES . "';
      });
    </script>";
    exit;
}

// 4) Obtener todos los roles para mostrar
$allRoles = $rolesModel->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listar Roles</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-shield-lock-fill text-success"></i> Gestión de Roles</h1>
    <?php if($permisos['puede_crear']): ?>
      <a href="gestionroles.php?submenu_id=<?= SUBMENU_GESTION_ROLES ?>"
         class="btn btn-outline-primary">
        <i class="bi bi-plus-circle"></i> Crear Nuevo Rol
      </a>
    <?php endif; ?>
  </div>

  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>Rol</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($allRoles as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['nombre_rol']) ?></td>
          <td class="text-center">
            <?php if($permisos['puede_editar']): ?>
              <button class="btn btn-warning btn-sm me-2"
                      data-bs-toggle="modal"
                      data-bs-target="#modalEdit"
                      data-role="<?= $r['id_rol'] ?>"
                      data-name="<?= htmlspecialchars($r['nombre_rol']) ?>">
                <i class="bi bi-pencil-square"></i> Editar
              </button>
            <?php endif; ?>

            <?php if($permisos['puede_eliminar']): ?>
              <button class="btn btn-danger btn-sm"
                onclick="Swal.fire({
                  title:'¿Eliminar rol?',
                  text:'Se perderán todos sus permisos',
                  icon:'warning',
                  showCancelButton:true
                }).then(r=> {
                  if(r.isConfirmed) {
                    location='listarroles.php?delete=<?= $r['id_rol'] ?>&submenu_id=<?= SUBMENU_GESTION_ROLES ?>';
                  }
                })">
                <i class="bi bi-trash"></i> Eliminar
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal CREAR -->
<div class="modal fade" id="modalCreate" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Crear Rol</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nombre del Rol</label>
        <input name="nombre_rol" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal EDITAR PERMISOS -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="role_id" id="role_id">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-gear"></i> Editar Permisos</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="permsContainer" class="row gx-4 gy-3">
          <div class="col-12 text-center">Cargando permisos…</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
  const modalEdit = document.getElementById('modalEdit');
  modalEdit.addEventListener('show.bs.modal', e => {
    const btn       = e.relatedTarget;
    const roleId    = btn.dataset.role;
    const roleName  = btn.dataset.name;
    // Título dinámico
    document.querySelector('#modalEdit .modal-title').innerHTML =
      `<i class="bi bi-gear"></i> Editar Permisos: <strong>${roleName}</strong>`;
    document.getElementById('role_id').value = roleId;

    const cont = document.getElementById('permsContainer');
    cont.innerHTML = `<div class="col-12 text-center py-3">
                        <i class="bi bi-arrow-clockwise spin"></i> Cargando…
                      </div>`;

    fetch(`obtener_permisos_rol.php?id=${roleId}`)
      .then(r => r.json())
      .then(data => {
        let html = '';
        data.forEach(menu => {
          html += `<div class="col-12"><h5><i class="bi bi-folder"></i> ${menu.nombre_menu}</h5>`;
          menu.submenus.forEach(s => {
            const sid = s.id_submenu;
            html += `
              <div class="form-check ms-4 mb-2">
                <input class="form-check-input" type="checkbox" name="subm[]" value="${sid}" id="sm${sid}" checked>
                <label class="form-check-label text-dark" for="sm${sid}">
                  <i class="bi bi-file-earmark-text"></i> ${s.nombre_submenu}
                </label>
                <div class="ms-5 d-flex gap-3">
                  ${['crear','editar','eliminar'].map(p => `
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox"
                        name="${p}_${sid}" id="${p}_${sid}" ${s['puede_'+p]? 'checked':''}>
                      <label class="form-check-label">${p.charAt(0).toUpperCase()+p.slice(1)}</label>
                    </div>
                  `).join('')}
                </div>
              </div>`;
          });
          html += `</div><hr>`;
        });
        cont.innerHTML = html;
      })
      .catch(_ => cont.innerHTML = `<div class="col-12 text-danger">Error al cargar permisos</div>`);
  });
</script>

</body>
</html>