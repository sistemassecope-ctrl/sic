<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    echo "--- Columnas de Empleados ---\n";
    $stmt = $pdo->query("DESCRIBE empleados");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($cols);
    
    echo "\n--- Columnas de Puestos Trabajo ---\n";
    $stmt = $pdo->query("DESCRIBE puestos_trabajo");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($cols);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
