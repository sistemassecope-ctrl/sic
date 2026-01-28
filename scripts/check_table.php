<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "=== ESTRUCTURA vehiculos_notas ===\n";
$stmt = $pdo->query("SHOW CREATE TABLE vehiculos_notas");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['Create Table'] . "\n";

echo "\n=== TRIGGERS EN vehiculos_notas ===\n";
$stmt2 = $pdo->query("SHOW TRIGGERS LIKE 'vehiculos_notas'");
$triggers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
if (count($triggers) == 0) {
    echo "No hay triggers\n";
} else {
    print_r($triggers);
}
