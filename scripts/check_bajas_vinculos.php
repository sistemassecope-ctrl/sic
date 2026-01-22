<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- VERIFICANDO DATOS DE BAJAS ---\n\n";

$bajas = $pdo->query("SELECT id, numero_economico, vehiculo_origen_id FROM vehiculos_bajas LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

echo "Total registros: " . count($bajas) . "\n\n";

foreach ($bajas as $b) {
    echo "ID: {$b['id']} | Económico: {$b['numero_economico']} | Vínculo: " . ($b['vehiculo_origen_id'] ?? 'NULL') . "\n";
}

echo "\n--- CONTEO ---\n";
$conVinculo = $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas WHERE vehiculo_origen_id IS NOT NULL AND vehiculo_origen_id > 0")->fetchColumn();
$sinVinculo = $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas WHERE vehiculo_origen_id IS NULL OR vehiculo_origen_id = 0")->fetchColumn();

echo "Con vínculo: $conVinculo\n";
echo "Sin vínculo: $sinVinculo\n";
