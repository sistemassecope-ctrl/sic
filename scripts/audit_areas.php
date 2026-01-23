<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    // Find vehicles NOT in Area 33 or with NULL area
    // Join with areas table to show names
    $sql = "SELECT v.id, v.numero_economico, v.marca, v.modelo, v.area_id, a.nombre_area 
            FROM vehiculos v 
            LEFT JOIN areas a ON v.area_id = a.id
            WHERE v.area_id IS NULL OR v.area_id != 33";
            
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = "--- Vehicles NOT in 'Departamento de Parque Vehicular' (ID 33) ---\n";
    $output .= "Count: " . count($results) . "\n\n";
    
    if (count($results) > 0) {
        foreach ($results as $row) {
            $areaName = $row['nombre_area'] ? $row['nombre_area'] : "NULL (Sin Área)";
            $output .= "ID: {$row['id']} | Econ: {$row['numero_economico']} | {$row['marca']} {$row['modelo']} | Area: [$areaName] (ID: {$row['area_id']})\n";
        }
    } else {
        $output .= "No misplaced vehicles found. All vehicles are in Area 33.\n";
    }
    
    file_put_contents('audit_areas_result.txt', $output);
    echo "Audit Complete. Results saved to audit_areas_result.txt";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
