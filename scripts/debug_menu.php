<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- BUSCANDO PADRES ---\n";
// Find Admin and RH
$padres = $pdo->query("SELECT id, nombre_modulo FROM modulos WHERE nombre_modulo LIKE '%Administra%' OR nombre_modulo LIKE '%Humanos%'")->fetchAll();

foreach ($padres as $p) {
    echo "PADRE: [{$p['id']}] {$p['nombre_modulo']}\n";
    
    // Find 'Area' children for this parent
    $stmt = $pdo->prepare("SELECT id, nombre_modulo, estado, ruta FROM modulos WHERE id_padre = ?");
    $stmt->execute([$p['id']]);
    $children = $stmt->fetchAll();
    
    foreach ($children as $c) {
        $marker = (stripos($c['nombre_modulo'], 'rea') !== false) ? "  <-- POSIBLE OBJECTIVO" : "";
        echo "   Hijo: [{$c['id']}] {$c['nombre_modulo']} (Estado: {$c['estado']}) $marker\n";
    }
    echo "\n";
}
