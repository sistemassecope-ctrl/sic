<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    // Add to vehiculos
    echo "Adding factura_nombre to vehiculos...\n";
    try {
        $pdo->exec("ALTER TABLE vehiculos ADD COLUMN factura_nombre VARCHAR(255) DEFAULT NULL AFTER resguardo_nombre");
        echo "SUCCESS: Added to vehiculos.\n";
    } catch (PDOException $e) {
        echo "INFO: " . $e->getMessage() . "\n";
    }

    // Add to vehiculos_bajas
    echo "Adding factura_nombre to vehiculos_bajas...\n";
    try {
        $pdo->exec("ALTER TABLE vehiculos_bajas ADD COLUMN factura_nombre VARCHAR(255) DEFAULT NULL AFTER resguardo_nombre");
        echo "SUCCESS: Added to vehiculos_bajas.\n";
    } catch (PDOException $e) {
        echo "INFO: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage();
}
