<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("DESCRIBE vehiculos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $output = "Columns in 'vehiculos':\n";
    foreach ($columns as $col) {
        $output .= "- $col\n";
    }
    file_put_contents('vehiculos_columns.txt', $output);
    echo "Done.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
