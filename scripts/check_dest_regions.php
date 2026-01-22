<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stats = $pdo->query("SELECT region, COUNT(*) as c FROM vehiculos GROUP BY region")->fetchAll(PDO::FETCH_KEY_PAIR);
    print_r($stats);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
