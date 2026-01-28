<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM solicitudes_baja LIKE 'visto'");
    if ($stmt->fetch()) {
        echo "Column 'visto' already exists.\n";
    } else {
        $pdo->exec("ALTER TABLE solicitudes_baja ADD COLUMN visto TINYINT DEFAULT 0");
        echo "Column 'visto' added successfully.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
