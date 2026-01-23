<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE numero_economico = 'V100-026'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $output = "Data for V100-026:\n";
        foreach ($row as $k => $v) {
            $output .= "$k: $v\n";
        }
        file_put_contents('v100_details.txt', $output);
        echo "Details written to v100_details.txt";
    } else {
        echo "V100-026 NOT FOUND";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
