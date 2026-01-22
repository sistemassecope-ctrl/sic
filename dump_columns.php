<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "COLUMNS IN empleados:\n";
    $stmt = $pdo->query("DESCRIBE empleados");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }

    echo "\nCOLUMNS IN puestos:\n";
    $stmt = $pdo->query("DESCRIBE puestos");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
