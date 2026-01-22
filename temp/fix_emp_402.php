<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    // Buscar un área y puesto válidos
    $areaId = $pdo->query("SELECT id FROM areas LIMIT 1")->fetchColumn();
    $puestoId = $pdo->query("SELECT id FROM puestos_trabajo LIMIT 1")->fetchColumn();
    
    echo "Updating Emp 402 with Area: $areaId and Puesto: $puestoId\n";
    
    $stmt = $pdo->prepare("UPDATE empleados SET area_id = ?, puesto_trabajo_id = ? WHERE id = 402");
    $stmt->execute([$areaId, $puestoId]);
    
    echo "Update complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
