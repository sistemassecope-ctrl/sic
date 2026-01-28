<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "=== VEHICULOS_NOTAS - ULTIMAS 10 ===\n";
$stmtN = $pdo->query("SELECT * FROM vehiculos_notas ORDER BY id DESC LIMIT 10");
foreach ($stmtN->fetchAll(PDO::FETCH_ASSOC) as $n) {
    echo "ID:{$n['id']} | VehID:{$n['vehiculo_id']} | Tipo:{$n['tipo_origen']} | Nota:" . substr($n['nota'],0,60) . "... | {$n['created_at']}\n";
}

echo "\n=== VERIFICAR BAJA 19 ===\n";
$stmtB = $pdo->query("SELECT * FROM vehiculos_bajas WHERE id = 19");
$b = $stmtB->fetch(PDO::FETCH_ASSOC);
if ($b) {
    echo "ID:{$b['id']}\n";
    echo "VehOrigenID:{$b['vehiculo_origen_id']}\n";
    echo "Eco:{$b['numero_economico']}\n";
    echo "Motivo:{$b['motivo_baja']}\n";
}

echo "\n=== SOLICITUD 5 ===\n";
$stmtS = $pdo->query("SELECT * FROM solicitudes_baja WHERE id = 5");
$s = $stmtS->fetch(PDO::FETCH_ASSOC);
if ($s) {
    echo "ID:{$s['id']}\n";
    echo "VehID:{$s['vehiculo_id']}\n";
    echo "Estado:{$s['estado']}\n";
    echo "Motivo:{$s['motivo']}\n";
}
