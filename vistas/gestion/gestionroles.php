<?php
session_start();
require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../modelos/Roles.php";
require_once __DIR__ . "/../../modelos/Menus.php";
require_once __DIR__ . "/../../modelos/Submenus.php";
require_once __DIR__ . "/../../modelos/Permisos.php";

if (!isset($_SESSION['id_rol'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$id_rol = $_SESSION['id_rol'];
$rolesModel = new Roles();
$menusModel = new Menus();
$submenusModel = new Submenus();
$permisosModel = new Permisos();

// Obtener el id_submenu directamente sin encriptación
$id_submenu = isset($_GET['submenu_id']) ? (int)$_GET['submenu_id'] : null;

if (!$id_submenu) {
    // Si no hay submenu_id, tratar de obtenerlo de la URL actual
    $current_url = $_SERVER['REQUEST_URI'];
    $path_parts = explode('/', $current_url);
    $script_name = end($path_parts);
    
    // Intentar encontrar el submenu correspondiente a esta URL
    $submenus = $submenusModel->obtenerTodos();
    foreach ($submenus as $submenu) {
        if (strpos($submenu['url_submenu'], $script_name) !== false) {
            $id_submenu = $submenu['id_submenu'];
            break;
        }
    }
    
    if (!$id_submenu) {
        // Si aún no se encuentra, intentar buscar por el nombre del archivo
        $script_file = basename($script_name, '.php');
        foreach ($submenus as $submenu) {
            if (strpos(basename($submenu['url_submenu'], '.php'), $script_file) !== false) {
                $id_submenu = $submenu['id_submenu'];
                break;
            }
        }
        
        if (!$id_submenu) {
            // Por último, si es gestionroles.php, podemos establecer un valor predeterminado
            if ($script_name === 'gestionroles.php') {
                // ID de "Gestión Roles" en tu sistema
                $id_submenu = 16; // Ajusta este valor según tu base de datos
            } else {
                die("Error: No se pudo determinar el ID del submenú para esta página.");
            }
        }
    }
}

// Verificar permisos para el rol y submenú actual
$permisos = $permisosModel->obtenerPermisos($id_rol, $id_submenu);

// Si no tiene permiso para acceder, redirigir a una página de error o al dashboard
if (!$permisos || empty($permisos)) {
    header('Location: ' . BASE_URL . '/vistas/error_permisos.php');
    exit();
}

// Incluir el header después de verificar permisos
require_once __DIR__ . "/../../navbars/header.php";
include __DIR__ . "/../../navbars/sidebar.php";


$menus = $menusModel->obtenerTodos();
$submenusPorMenu = [];
foreach ($menus as $menu) {
    $submenusPorMenu[$menu['id_menu']] = $submenusModel->obtenerPorMenu($menu['id_menu']);
}

$mensajeError = "";
$rolCreado = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar que el usuario tiene permiso para crear
    if (!$permisos['puede_crear']) {
        $mensajeError = "No tienes permiso para crear roles.";
    } else {
        $nombre_rol = trim($_POST['nombre_rol']);
        if ($rolesModel->existeRolPorNombre($nombre_rol)) {
            $mensajeError = "Ya existe un rol con ese nombre.";
        } else {
            $id_rol_nuevo = $rolesModel->crearRol($nombre_rol);
            if ($id_rol_nuevo && isset($_POST['permisos'])) {
                foreach ($_POST['permisos'] as $id_submenu => $acciones) {
                    $rolesModel->asociarSubmenu($id_rol_nuevo, $id_submenu);
                    $id_roles_submenus = $rolesModel->obtenerIdRolesSubmenus($id_rol_nuevo, $id_submenu);
                    $rolesModel->crearPermisos($id_roles_submenus, [
                        'puede_crear' => in_array('crear', $acciones),
                        'puede_editar' => in_array('editar', $acciones),
                        'puede_eliminar' => in_array('eliminar', $acciones),
                    ]);
                }
            }
            $rolCreado = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Roles</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3.6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Iconos Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../estilos/header.css">
<style>
  .submenu-checkboxes { margin-left: 30px; margin-bottom: 15px; }
  .acciones-permisos { margin-left: 60px; margin-bottom: 10px; display: flex; gap: 10px; }

  .submenu-checkboxes .form-check-label {
    color:rgb(105, 153, 224);
    font-weight: 500;
  }

  .acciones-permisos .form-check-label {
    color:rgb(255, 7, 27);
    font-weight: 500;
  }
</style>

</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-shield-lock-fill text-success"></i> Crear Nuevo Rol</h2>
        <!-- <a href="listarroles.php" class="btn btn-outline-primary">
            <i class="bi bi-gear-fill me-1"></i> Gestión Roles
        </a> -->
    </div>

    <form method="POST" id="formCrearRol">
        <div class="mb-3">
            <label for="nombre_rol" class="form-label">Nombre del Rol</label>
            <input type="text" class="form-control border border-primary" id="nombre_rol" name="nombre_rol" required>
        </div>

        <h4><i class="bi bi-list-check text-secondary"></i> Asignar Menús y Submenús</h4>
        <?php foreach ($menus as $menu): ?>
            <div class="form-check mt-3">
                <input class="form-check-input menu-toggle" type="checkbox" data-menu-id="<?= $menu['id_menu'] ?>">
                <label class="form-check-label fw-bold text-success">
                    <i class="bi bi-folder-fill me-1"></i> <?= $menu['nombre_menu'] ?>
                </label>
            </div>

            <div class="submenu-checkboxes d-none" id="submenus-menu-<?= $menu['id_menu'] ?>">
                <?php foreach ($submenusPorMenu[$menu['id_menu']] as $submenu): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input submenu-toggle" name="submenus[]" data-submenu-id="<?= $submenu['id_submenu'] ?>" value="<?= $submenu['id_submenu'] ?>">
                        <label class="form-check-label">
                            <i class="bi bi-diagram-3 me-1"></i> <?= $submenu['nombre_submenu'] ?>
                        </label>
                    </div>

                    <div class="acciones-permisos d-none" id="acciones-submenu-<?= $submenu['id_submenu'] ?>">
                        <?php foreach (["crear", "editar", "eliminar"] as $accion): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[<?= $submenu['id_submenu'] ?>][]" value="<?= $accion ?>">
                                <label class="form-check-label">
                                    <i class="bi bi-pencil me-1"></i> <?= ucfirst($accion) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success mt-4"><i class="bi bi-save2-fill me-1"></i> Guardar Rol</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($mensajeError)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?= $mensajeError ?>'
        });
    <?php elseif ($rolCreado): ?>
        Swal.fire({
            icon: 'success',
            title: 'Rol creado',
            text: 'El rol fue creado exitosamente',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = '<?= BASE_URL ?>/vistas/gestion/gestionroles.php';
        });
    <?php endif; ?>

    document.querySelectorAll('.menu-toggle').forEach(menuCheckbox => {
        menuCheckbox.addEventListener('change', function () {
            const id = this.getAttribute('data-menu-id');
            document.getElementById('submenus-menu-' + id).classList.toggle('d-none', !this.checked);
        });
    });

    document.querySelectorAll('.submenu-toggle').forEach(submenuCheckbox => {
        submenuCheckbox.addEventListener('change', function () {
            const id = this.getAttribute('data-submenu-id');
            document.getElementById('acciones-submenu-' + id).classList.toggle('d-none', !this.checked);
        });
    });
});
</script>

</body>
</html>
