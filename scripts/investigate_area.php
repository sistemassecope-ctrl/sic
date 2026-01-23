<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $resguardo = "TOMAS VICENTE HERRERA SOLANO";
    
    $output = "";
    $output .= "1. Searching for other vehicles assigned to '$resguardo':\n";
    $stmt = $pdo->prepare("SELECT numero_economico, area_id, resguardo_nombre, region FROM vehiculos WHERE resguardo_nombre LIKE ?");
    $stmt->execute(["%$resguardo%"]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $areaName = $pdo->query("SELECT nombre_area FROM areas WHERE id = " . ($r['area_id'] ?? 0))->fetchColumn();
        $output .= "- {$r['numero_economico']}: Area ID {$r['area_id']} ($areaName), Region: {$r['region']}\n";
    }

    $output .= "\n2. Analyzing Area distribution for 'REGION LAGUNA':\n";
    $stmt2 = $pdo->query("SELECT area_id, count(*) as c FROM vehiculos WHERE region = 'REGION LAGUNA' GROUP BY area_id ORDER BY c DESC LIMIT 5");
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $areaName = $pdo->query("SELECT nombre_area FROM areas WHERE id = " . ($r['area_id'] ?? 0))->fetchColumn();
        $output .= "- Area ID {$r['area_id']} ($areaName): {$r['c']} vehicles\n";
    }

    $output .= "\n3. Analyzing Area distribution for 'V1100-%' series:\n";
    $stmt3 = $pdo->query("SELECT area_id, count(*) as c FROM vehiculos WHERE numero_economico LIKE 'V1100-%' GROUP BY area_id ORDER BY c DESC LIMIT 5");
    foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $r) {
         $areaName = $pdo->query("SELECT nombre_area FROM areas WHERE id = " . ($r['area_id'] ?? 0))->fetchColumn();
        $output .= "- Area ID {$r['area_id']} ($areaName): {$r['c']} vehicles\n";
    }
    
    file_put_contents('investigation_safe.txt', $output);
    echo "Done.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
