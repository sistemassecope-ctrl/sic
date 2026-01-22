<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $sql = "SELECT id, nombre_modulo, id_padre, ruta, orden FROM modulos WHERE nombre_modulo LIKE '%Veh%' OR id_padre IN (SELECT id FROM modulos WHERE nombre_modulo = 'Vehículos') ORDER BY id_padre, orden";
    $modules = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    print_r($modules);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
