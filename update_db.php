<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE fuas ADD COLUMN monto_obra DECIMAL(20,2) DEFAULT 0.00 AFTER importe");
    $db->exec("ALTER TABLE fuas ADD COLUMN monto_supervision DECIMAL(20,2) DEFAULT 0.00 AFTER monto_obra");
    echo "Columns added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>