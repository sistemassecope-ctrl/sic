<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- ACTIVOS (" . $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE activo=1")->fetchColumn() . ") ---\n";
$stmt = $pdo->query("SELECT numero_economico FROM vehiculos WHERE activo = 1 ORDER BY numero_economico ASC");
$activos = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($activos as $eco) {
    echo $eco . "\n";
}

echo "\n--- BAJAS (" . $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas")->fetchColumn() . ") ---\n";
$stmtB = $pdo->query("SELECT numero_economico FROM vehiculos_bajas ORDER BY numero_economico ASC");
$bajas = $stmtB->fetchAll(PDO::FETCH_COLUMN);
foreach ($bajas as $eco) {
    echo $eco . "\n";
}
