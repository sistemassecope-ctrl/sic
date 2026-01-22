<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SHOW COLUMNS FROM empleados");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing columns in 'empleados':\n";
    print_r($columns);
    
    // Check specifically for the requested fields
    $requested = ['salario_bruto', 'salario_neto', 'compensacion', 'vulnerabilidad'];
    $found = array_intersect($requested, $columns);
    $missing = array_diff($requested, $columns);
    
    echo "\nFound: " . implode(', ', $found) . "\n";
    echo "Missing: " . implode(', ', $missing) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
