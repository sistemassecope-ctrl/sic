<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE vehiculos SET activo = 1 WHERE numero_economico = 'V100-026'");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " records. V100-026 is now ACTIVE.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
