<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- MÓDULOS ACTUALES ---\n";
echo str_pad("ID", 5) . str_pad("NOMBRE", 30) . str_pad("ORDEN", 10) . "\n";
echo str_repeat("-", 50) . "\n";

$stmt = $pdo->query("SELECT id, nombre_modulo, orden FROM modulos WHERE id_padre IS NULL ORDER BY orden ASC");
$modules = $stmt->fetchAll();

foreach ($modules as $m) {
    echo str_pad($m['id'], 5) . str_pad($m['nombre_modulo'], 30) . str_pad($m['orden'], 10) . "\n";
}
