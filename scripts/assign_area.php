<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    // Area ID 33 = DEPARTAMENTO DE PARQUE VEHICULAR (Based on previous investigation)
    $stmt = $pdo->prepare("UPDATE vehiculos SET area_id = 33 WHERE numero_economico = 'V100-026'");
    $stmt->execute();
    echo "Updated V100-026 to Area ID 33";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
