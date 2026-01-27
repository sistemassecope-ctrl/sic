<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    echo "--- TABLE: puestos ---\n";
    $stmt = $pdo->query("DESCRIBE puestos");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "❌ Error describing puestos: " . $e->getMessage() . "\n";
}

try {
    echo "\n--- TABLE: puestos_trabajo ---\n";
    $stmt = $pdo->query("DESCRIBE puestos_trabajo");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "❌ Error describing puestos_trabajo: " . $e->getMessage() . "\n";
}
