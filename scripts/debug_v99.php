<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "=== NOTAS PARA VEHICULO ID 99 (ECO-12345) ===\n";
$stmtN = $pdo->query("SELECT * FROM vehiculos_notas WHERE vehiculo_id = 99 ORDER BY id DESC");
$notas = $stmtN->fetchAll(PDO::FETCH_ASSOC);
echo "Total notas: " . count($notas) . "\n\n";
foreach ($notas as $n) {
    echo "ID: {$n['id']}\n";
    echo "Tipo: {$n['tipo_origen']}\n";
    echo "Nota: {$n['nota']}\n";
    echo "Created: {$n['created_at']}\n";
    echo "---\n";
}

echo "\n=== VEHICULOS_BAJAS para ECO-12345 ===\n";
$stmtB = $pdo->query("SELECT id, vehiculo_origen_id, numero_economico FROM vehiculos_bajas WHERE numero_economico LIKE '%12345%'");
while ($b = $stmtB->fetch(PDO::FETCH_ASSOC)) {
    echo "Baja ID: {$b['id']} | VehOrigenID: {$b['vehiculo_origen_id']} | Eco: {$b['numero_economico']}\n";
}
