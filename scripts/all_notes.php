<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "=== ULTIMAS 15 NOTAS EN vehiculos_notas ===\n";
$stmt = $pdo->query("SELECT id, vehiculo_id, tipo_origen FROM vehiculos_notas ORDER BY id DESC LIMIT 15");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID:{$n['id']} | VehID:{$n['vehiculo_id']} | Tipo:{$n['tipo_origen']}\n";
}

echo "\n=== NOTAS DONDE tipo_origen CONTIENE 'BAJA' ===\n";
$stmt2 = $pdo->query("SELECT id, vehiculo_id, tipo_origen FROM vehiculos_notas WHERE tipo_origen LIKE '%BAJA%' ORDER BY id DESC LIMIT 10");
$count = 0;
while ($n = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "ID:{$n['id']} | VehID:{$n['vehiculo_id']} | Tipo:{$n['tipo_origen']}\n";
    $count++;
}
echo "Total encontradas: $count\n";
