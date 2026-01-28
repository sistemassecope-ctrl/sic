<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Buscar el vehiculo ECO-12345
$stmtV = $pdo->query("SELECT id, numero_economico FROM vehiculos WHERE numero_economico LIKE '%12345%'");
$veh = $stmtV->fetch();
echo "=== VEHICULO ===\n";
print_r($veh);

if ($veh) {
    $vid = $veh['id'];
    echo "\n=== NOTAS PARA VEHICULO ID $vid ===\n";
    $stmtN = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = ?");
    $stmtN->execute([$vid]);
    $notas = $stmtN->fetchAll(PDO::FETCH_ASSOC);
    if (count($notas) == 0) {
        echo "NO HAY NOTAS\n";
    } else {
        foreach ($notas as $n) {
            echo "ID: {$n['id']} | Tipo: {$n['tipo_origen']} | Nota: " . substr($n['nota'], 0, 80) . "...\n";
        }
    }
}

// Buscar también en bajas
echo "\n=== BAJAS RECIENTES ===\n";
$stmtB = $pdo->query("SELECT id, vehiculo_origen_id, numero_economico, motivo_baja FROM vehiculos_bajas ORDER BY id DESC LIMIT 3");
foreach ($stmtB->fetchAll(PDO::FETCH_ASSOC) as $b) {
    echo "ID: {$b['id']} | VehOrigenID: {$b['vehiculo_origen_id']} | Eco: {$b['numero_economico']} | Motivo: {$b['motivo_baja']}\n";
}

// Solicitudes recientes
echo "\n=== SOLICITUDES RECIENTES ===\n";
$stmtS = $pdo->query("SELECT id, vehiculo_id, estado, motivo FROM solicitudes_baja ORDER BY id DESC LIMIT 3");
foreach ($stmtS->fetchAll(PDO::FETCH_ASSOC) as $s) {
    echo "ID: {$s['id']} | VehID: {$s['vehiculo_id']} | Estado: {$s['estado']} | Motivo: " . substr($s['motivo'], 0, 50) . "\n";
}
