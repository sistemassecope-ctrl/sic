<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    echo "--- VEHICULOS: LOGOS Distribution ---\n";
    $logos = $pdo->query("SELECT con_logotipos, COUNT(*) as c FROM vehiculos GROUP BY con_logotipos")->fetchAll(PDO::FETCH_KEY_PAIR);
    print_r($logos);
    
    echo "\n--- BAJAS: Count ---\n";
    $bajas_count = $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas")->fetchColumn();
    echo "Total Bajas: $bajas_count\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
