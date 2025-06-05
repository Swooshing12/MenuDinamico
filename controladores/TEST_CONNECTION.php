<?php
require_once "config/database.php";

try {
    $conn = Database::getConnection();
    echo "✅ Conexión exitosa a la base de datos.";
} catch (Exception $e) {
    echo "❌ Error en la conexión: " . $e->getMessage();
}
?>
