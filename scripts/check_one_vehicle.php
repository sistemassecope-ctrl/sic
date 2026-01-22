<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
// Check for specific eco number from screenshot
$eco = "ECO-V1500-225";
$stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE numero_economico = ?");
$stmt->execute([$eco]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if ($v) {
    echo "Found: ID " . $v['id'] . "\n";
} else {
    echo "Not Found in vehiculos.\n";
    // Check if it exists in vehiculos_bajas with full details to verify we can restore
    $stmtB = $pdo->prepare("SELECT * FROM vehiculos_bajas WHERE numero_economico = ?");
    $stmtB->execute([$eco]);
    $vb = $stmtB->fetch(PDO::FETCH_ASSOC);
    if ($vb) {
        echo "Found in bajas: " . print_r($vb, true);
    }
}
