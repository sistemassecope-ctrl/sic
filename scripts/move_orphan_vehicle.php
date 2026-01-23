<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE vehiculos SET area_id = 33 WHERE id = 93");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Exito: Vehículo ID 93 (Econ 12345) movido al Área 33.";
    } else {
        echo "Info: No se realizaron cambios (tal vez ya estaba en Área 33 o no existe).";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
