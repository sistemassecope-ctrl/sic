<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    // Add observacion_1 and observacion_2 if they don't exist
    $pdo->exec("ALTER TABLE vehiculos ADD COLUMN observacion_1 TEXT AFTER resguardo_nombre");
    $pdo->exec("ALTER TABLE vehiculos ADD COLUMN observacion_2 TEXT AFTER observacion_1");
    // We can keep 'observaciones' as a fallback or drop it later, but for now let's leave it.
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage() . "\n";
}
