<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT numero_economico, activo FROM vehiculos WHERE numero_economico LIKE ?");
    $stmt->execute(['%026%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = "";
    $output .= "Found " . count($rows) . " matches for %026%:\n";
    foreach ($rows as $row) {
        $output .= "- " . $row['numero_economico'] . " (Activo: " . $row['activo'] . ")\n";
    }
    
    // Also check for V1100-026 specifically again just to be sure
    $stmt2 = $pdo->prepare("SELECT numero_economico FROM vehiculos WHERE numero_economico = ?");
    $stmt2->execute(['V1100-026']);
    $exact = $stmt2->fetch(PDO::FETCH_ASSOC);
    $output .= "\nExact match check for V1100-026: " . ($exact ? "FOUND" : "NOT FOUND") . "\n";

    file_put_contents('search_result_safe.txt', $output);
    echo "Output written to search_result_safe.txt";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
