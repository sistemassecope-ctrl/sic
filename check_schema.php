<?php
// Simple schema checker
require_once __DIR__ . '/config/database.php';
// define('DB_HOST', 'localhost'); ... should be in db.php

try {
    $pdo = getConnection();
    
    echo "COLUMNS IN empleados:\n";
    $stmt = $pdo->query("DESCRIBE empleados");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($cols);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
