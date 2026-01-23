<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE vehiculos SET activo = 1 WHERE activo = 0");
    $stmt->execute();
    echo "Corrected " . $stmt->rowCount() . " hidden records (activo=0 -> activo=1). All records in vehiculos table are now ACTIVE.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
