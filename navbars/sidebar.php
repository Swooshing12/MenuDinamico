<?php 
require_once __DIR__ . "/../helpers/permisos.php";
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../modelos/Roles.php";

if (!isset($_SESSION['id_rol'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$id_rol = $_SESSION['id_rol'];

// Cargar menús según el rol
$rolModel = new Roles();
$menus = $rolModel->obtenerMenusPorRol($id_rol);

// Organizar menús y submenús
$menuItems = [];
foreach ($menus as $menu) {
    $menuId = $menu['id_menu'];
    if (!isset($menuItems[$menuId])) {
        $menuItems[$menuId] = [
            'id_menu' => $menuId,
            'nombre_menu' => $menu['nombre_menu'],
            'icono' => $menu['icono'] ?? 'bi-grid',
            'submenus' => []
        ];
    }
    if (!empty($menu['id_submenu'])) {
        $menuItems[$menuId]['submenus'][] = [
            'id_submenu' => $menu['id_submenu'],
            'nombre_submenu' => $menu['nombre_submenu'],
            'url_submenu' => $menu['url_submenu'],
            'icono' => $menu['icono_submenu'] ?? 'bi-circle'
        ];
    }
}

// Identificar la página actual para el menú activo
$current_page = basename($_SERVER['PHP_SELF']);
$current_submenu_id = $_GET['submenu_id'] ?? null;

// Función para determinar si algún submenú está activo
function submenuEstaActivo($submenus, $current_submenu_id) {
    if (!$current_submenu_id) return false;
    
    foreach ($submenus as $submenu) {
        if ($submenu['id_submenu'] == $current_submenu_id) {
            return true;
        }
    }
    return false;
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/estilos/sidebar.css">

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="logo-container">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="logo-text">MediSys</div>
        </div>
        <button type="button" id="sidebarToggle" class="toggle-btn">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-content">
        <!-- Menú Principal -->
        <div class="sidebar-menu">
            <div class="menu-label">Principal</div>
            <ul class="menu-items">
                <li class="menu-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/vistas/dashboard.php" class="menu-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Menús Dinámicos -->
        <?php if (!empty($menuItems)): ?>
        <div class="sidebar-menu">
            <div class="menu-label">Menú del Sistema</div>
            <ul class="menu-items">
                <?php foreach ($menuItems as $menu): ?>
                    <?php if (!empty($menu['submenus'])): ?>
                        <li class="menu-item has-submenu <?= submenuEstaActivo($menu['submenus'], $current_submenu_id) ? 'open' : '' ?>">
                            <a href="#submenu-<?= $menu['id_menu'] ?>" class="menu-link submenu-toggle" data-bs-toggle="collapse">
                                <i class="bi <?= $menu['icono'] ?>"></i>
                                <span><?= htmlspecialchars($menu['nombre_menu']) ?></span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </a>
                            <ul class="submenu collapse <?= submenuEstaActivo($menu['submenus'], $current_submenu_id) ? 'show' : '' ?>" id="submenu-<?= $menu['id_menu'] ?>">
                                <?php foreach ($menu['submenus'] as $submenu): ?>
                                    <li class="menu-item <?= ($current_submenu_id == $submenu['id_submenu']) ? 'active' : '' ?>">
                                        <a href="<?= htmlspecialchars($submenu['url_submenu']) ?>?submenu_id=<?= $submenu['id_submenu'] ?>" class="menu-link">
                                            <i class="bi <?= $submenu['icono'] ?>"></i>
                                            <span><?= htmlspecialchars($submenu['nombre_submenu']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-footer">
        <div class="version-info">
            <i class="bi bi-info-circle"></i>
            <span>v<?= date('Y.m') ?></span>
        </div>
    </div>
</div>

<!-- Overlay para móviles -->
<div class="sidebar-overlay"></div>

<!-- Script del sidebar -->
<script>
$(document).ready(function() {
    // Añadir atributos data-title a todos los enlaces del menú para tooltips
    $('#sidebar .menu-link').each(function() {
        $(this).attr('data-title', $(this).find('span').text());
    });
    
    // Toggle para colapsar/expandir sidebar
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').toggleClass('collapsed');
        $('body').toggleClass('sidebar-collapsed');
        
        // Cambiar el ícono del botón
        $(this).find('i').toggleClass('bi-chevron-left bi-chevron-right');
        
        // Guardar preferencia en localStorage
        localStorage.setItem('sidebarState', $('#sidebar').hasClass('collapsed') ? 'collapsed' : 'expanded');
    });
    
    // Recuperar estado guardado
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed') {
        $('#sidebar').addClass('collapsed');
        $('body').addClass('sidebar-collapsed');
        $('#sidebarToggle i').removeClass('bi-chevron-left').addClass('bi-chevron-right');
    }
    
    // Comportamiento responsive
    function checkWidth() {
        if ($(window).width() < 992) {
            if (!$('body').hasClass('sidebar-mobile-active')) {
                $('#sidebar').addClass('collapsed');
                $('body').addClass('sidebar-collapsed');
            }
        } else if (sidebarState !== 'collapsed') {
            $('#sidebar').removeClass('collapsed');
            $('body').removeClass('sidebar-collapsed');
        }
    }
    
    // Comprobar al cargar y al cambiar tamaño
    checkWidth();
    $(window).resize(checkWidth);
    
    // Click en overlay para cerrar el sidebar en móvil
    $('.sidebar-overlay').on('click', function() {
        $('body').removeClass('sidebar-mobile-active');
        $('#sidebar').removeClass('mobile-active');
        $(this).removeClass('active');
    });
    
    // Toggle para submenús en móvil
    $('.submenu-toggle').on('click', function() {
        // Alternar clase para ícono
        $(this).find('.toggle-icon').toggleClass('rotate');
        
        // Marcar elemento padre como abierto/cerrado
        $(this).closest('.menu-item').toggleClass('open');
    });
    
    // Manejar botón de menú móvil
    $('#sidebarToggle').on('click', function() {
        if ($(window).width() < 992) {
            $('body').toggleClass('sidebar-mobile-active');
            $('#sidebar').toggleClass('mobile-active');
            $('.sidebar-overlay').toggleClass('active');
        }
    });
});
</script>