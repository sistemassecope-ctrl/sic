<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- RESTAURANDO ID 32 ---\n";
// 1. Re-enable ID 32 just in case it was the wrong one, to stop damage.
$pdo->prepare("UPDATE modulos SET estado = 1 WHERE id = 32")->execute();
echo "✅ ID 32 Reactivado.\n\n";

echo "--- ANÁLISIS DE ESTRUCTURA ---\n";

// 2. Find Parents
$parents = $pdo->query("SELECT id, nombre_modulo FROM modulos WHERE id_padre IS NULL")->fetchAll();

foreach ($parents as $p) {
    echo "PADRE: [{$p['id']}] {$p['nombre_modulo']}\n";
    
    // Get children
    $stmt = $pdo->prepare("SELECT id, nombre_modulo, ruta, estado, orden FROM modulos WHERE id_padre = ?");
    $stmt->execute([$p['id']]);
    $children = $stmt->fetchAll();
    
    foreach ($children as $c) {
        echo "   └── HIJO: [{$c['id']}] {$c['nombre_modulo']} (Estado: {$c['estado']}) - Ruta: {$c['ruta']}\n";
    }
    echo "\n";
}
