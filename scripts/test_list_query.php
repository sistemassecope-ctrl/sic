<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

$vehiculo_id = 99;

// Simular exactamente lo que hace list.php
echo "=== Query: vehiculo_id=99, tipo_origen LIKE '%BAJA%' ===\n";
$stmtNotas = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen LIKE '%BAJA%' ORDER BY created_at DESC");
$stmtNotas->execute([$vehiculo_id]);
$notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

echo "Resultados: " . count($notas) . "\n\n";
foreach ($notas as $n) {
    echo "ID: {$n['id']}\n";
    echo "Tipo: {$n['tipo_origen']}\n";
    echo "Nota: {$n['nota']}\n";
    echo "---\n";
}

// También verificar vehiculo_origen_id de la baja 19
echo "\n=== VERIFICAR vehiculo_origen_id de baja 19 ===\n";
$stmtB = $pdo->query("SELECT id, vehiculo_origen_id FROM vehiculos_bajas WHERE id = 19");
$b = $stmtB->fetch(PDO::FETCH_ASSOC);
echo "Baja ID: {$b['id']}, vehiculo_origen_id: {$b['vehiculo_origen_id']}\n";
