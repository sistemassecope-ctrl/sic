<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Buscar la baja con No. Económico 12345 (sin ECO-)
echo "=== BAJAS CON NUMERO ECONOMICO 12345 ===\n";
$stmt = $pdo->query("SELECT * FROM vehiculos_bajas WHERE numero_economico LIKE '%12345%'");
$bajas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($bajas as $b) {
    echo "Baja ID: {$b['id']}\n";
    echo "  vehiculo_origen_id: {$b['vehiculo_origen_id']}\n";
    echo "  numero_economico: {$b['numero_economico']}\n";
    echo "  numero_placas: {$b['numero_placas']}\n\n";
}

// Verificar notas para cada vehiculo_origen_id encontrado
foreach ($bajas as $b) {
    $vid = $b['vehiculo_origen_id'];
    echo "=== NOTAS PARA vehiculo_id=$vid (baja ID {$b['id']}) ===\n";
    $stmtN = $pdo->prepare("SELECT id, tipo_origen, LEFT(nota, 50) as nota FROM vehiculos_notas WHERE vehiculo_id = ?");
    $stmtN->execute([$vid]);
    $notas = $stmtN->fetchAll(PDO::FETCH_ASSOC);
    echo "  Total: " . count($notas) . "\n";
    foreach ($notas as $n) {
        echo "  - ID:{$n['id']} Tipo:{$n['tipo_origen']}\n";
    }
    echo "\n";
}

// También verificar si hay un vehículo con número económico 12345
echo "=== VEHICULOS CON NUMERO ECONOMICO 12345 ===\n";
$stmtV = $pdo->query("SELECT id, numero_economico, activo FROM vehiculos WHERE numero_economico LIKE '%12345%'");
while ($v = $stmtV->fetch(PDO::FETCH_ASSOC)) {
    echo "Veh ID: {$v['id']}, Eco: {$v['numero_economico']}, Activo: {$v['activo']}\n";
}
