<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Check vehiculos_bajas for vehiculo_origen_id
echo "=== VEHICULOS_BAJAS ===\n";
$bajas = $pdo->query("SELECT id, vehiculo_origen_id, numero_economico FROM vehiculos_bajas LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($bajas);

// Check vehiculos_notas
echo "\n=== VEHICULOS_NOTAS (BAJA related) ===\n";
$notas = $pdo->query("SELECT * FROM vehiculos_notas WHERE tipo_origen LIKE '%BAJA%' ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($notas);

// Check a specific vehicle's notes
if (!empty($bajas)) {
    $testId = $bajas[0]['vehiculo_origen_id'];
    echo "\n=== NOTES FOR vehiculo_id = $testId ===\n";
    $stmt = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = ?");
    $stmt->execute([$testId]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
