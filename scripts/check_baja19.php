<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Baja 19 específicamente
$stmt = $pdo->query("SELECT id, vehiculo_origen_id, numero_economico FROM vehiculos_bajas WHERE id = 19");
$b = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Baja 19:\n";
echo "  vehiculo_origen_id = " . var_export($b['vehiculo_origen_id'], true) . "\n";
echo "  numero_economico = " . $b['numero_economico'] . "\n";

// Contar notas BAJA para ese vehiculo_origen_id
$vid = $b['vehiculo_origen_id'];
if ($vid) {
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen LIKE '%BAJA%'");
    $stmt2->execute([$vid]);
    echo "\nNotas tipo BAJA para vehiculo_id $vid: " . $stmt2->fetchColumn() . "\n";
} else {
    echo "\nERROR: vehiculo_origen_id es NULL o 0!\n";
}
