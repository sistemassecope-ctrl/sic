<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Delete from vehiculos_bajas
$stmt = $pdo->prepare("DELETE FROM vehiculos_bajas WHERE numero_economico = ?");
$stmt->execute(['TEST-001']);
echo "Deleted from vehiculos_bajas: " . $stmt->rowCount() . " rows\n";

// Get vehicle ID for cleanup
$stmtId = $pdo->prepare("SELECT id FROM vehiculos WHERE numero_economico = ?");
$stmtId->execute(['TEST-001']);
$veh = $stmtId->fetch();

if ($veh) {
    $vid = $veh['id'];
    
    // Delete solicitudes_baja
    $pdo->prepare("DELETE FROM solicitudes_baja WHERE vehiculo_id = ?")->execute([$vid]);
    echo "Deleted solicitudes_baja for vehicle ID $vid\n";
    
    // Delete notas
    $pdo->prepare("DELETE FROM vehiculos_notas WHERE vehiculo_id = ?")->execute([$vid]);
    echo "Deleted notas for vehicle ID $vid\n";
    
    // Delete vehicle
    $pdo->prepare("DELETE FROM vehiculos WHERE id = ?")->execute([$vid]);
    echo "Deleted vehicle TEST-001\n";
} else {
    echo "Vehicle TEST-001 not found in vehiculos table (may already be deleted)\n";
}

echo "Cleanup complete!";
