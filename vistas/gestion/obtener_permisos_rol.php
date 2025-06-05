<?php
require_once __DIR__ . "/../../modelos/Roles.php";

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['id_rol'])) exit;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) exit;

$roles = new Roles();
$estructura = $roles->obtenerPermisosPorRol($id);

header('Content-Type: application/json');
echo json_encode($estructura);

