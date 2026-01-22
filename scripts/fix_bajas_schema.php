<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    // Add vehiculo_origen_id if it doesn't exist
    $pdo->exec("ALTER TABLE vehiculos_bajas ADD COLUMN vehiculo_origen_id INT AFTER id");
    echo "Column vehiculo_origen_id added.\n";
    
    // Attempt to backfill data matching by economoico
    $sql = "UPDATE vehiculos_bajas vb
            JOIN vehiculos v ON v.numero_economico = vb.numero_economico
            SET vb.vehiculo_origen_id = v.id
            WHERE vb.vehiculo_origen_id IS NULL OR vb.vehiculo_origen_id = 0";
    $count = $pdo->exec($sql);
    echo "Backfilled $count rows.\n";
    
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage() . "\n";
}
