<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- Conteos ---\n";
$countV = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE activo = 1")->fetchColumn();
$countBajas = $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas")->fetchColumn();
echo "Vehiculos Activos: $countV\n";
echo "Bajas Históricas: $countBajas\n";

echo "\n--- Últimos 5 Vehículos Modificados/Creados ---\n";
// Assuming there might be an updated_at or created_at
$stmt = $pdo->query("SELECT id, numero_economico, marca, modelo, created_at FROM vehiculos ORDER BY id DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Eco: {$row['numero_economico']} | {$row['marca']} | Created: {$row['created_at']}\n";
}

echo "\n--- Últimas 5 Bajas ---\n";
$stmtB = $pdo->query("SELECT id, vehiculo_origen_id, numero_economico, fecha_baja FROM vehiculos_bajas ORDER BY id DESC LIMIT 5");
while ($row = $stmtB->fetch(PDO::FETCH_ASSOC)) {
    echo "ID Baja: {$row['id']} | ID Orig: {$row['vehiculo_origen_id']} | Eco: {$row['numero_economico']} | Fecha: {$row['fecha_baja']}\n";
}

echo "\n--- Últimas 5 Notas ---\n";
$stmtN = $pdo->query("SELECT id, vehiculo_id, tipo_origen, nota, created_at FROM vehiculos_notas ORDER BY id DESC LIMIT 5");
while ($row = $stmtN->fetch(PDO::FETCH_ASSOC)) {
    echo "ID Nota: {$row['id']} | VehID: {$row['vehiculo_id']} ({$row['tipo_origen']}) | Nota: " . substr($row['nota'], 0, 30) . "... | {$row['created_at']}\n";
}
