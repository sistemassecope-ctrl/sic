<?php
require_once 'config/db.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Roles Disponibles:\n";
    foreach ($roles as $rol) {
        echo "- ID: " . $rol['id_rol'] . " | Nombre: " . $rol['nombre_rol'] . " | Descripción: " . $rol['descripcion'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>