<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 ORDER BY id");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = "";
    foreach ($areas as $i => $area) {
        $output .= "[" . $area['id'] . "] " . $area['nombre_area'] . "\r\n";
    }
    
    // Force UTF-8 BOM or just clean utf8
    file_put_contents('areas_full_clean.txt', "\xEF\xBB\xBF" . $output); 
    echo "Done.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
