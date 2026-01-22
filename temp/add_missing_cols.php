<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    $colsToAdd = [
        'salario_bruto' => 'DECIMAL(10,2) DEFAULT 0.00',
        'salario_neto' => 'DECIMAL(10,2) DEFAULT 0.00',
        'compensacion' => 'DECIMAL(10,2) DEFAULT 0.00',
        'vulnerabilidad' => 'TEXT DEFAULT NULL'
    ];
    
    foreach ($colsToAdd as $col => $def) {
        try {
            // Check if exists
            $pdo->query("SELECT $col FROM empleados LIMIT 1");
            echo "Column '$col' already exists.\n";
        } catch (PDOException $e) {
            // Modify table
            echo "Adding column '$col'...\n";
            $pdo->exec("ALTER TABLE empleados ADD COLUMN $col $def");
        }
    }
    
    echo "Schema update complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
