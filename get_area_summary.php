<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 ORDER BY id");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mostramos las primeras 20 y las ultimas 5
    $total = count($areas);
    $shown = 0;
    
    echo "Total Areas: $total\n\n";
    
    foreach ($areas as $i => $area) {
        if ($i < 20 || $i >= $total - 5) {
            echo "[" . $area['id'] . "] " . $area['nombre_area'] . "\n";
            $shown++;
        } elseif ($i == 20) {
            echo "... (" . ($total - 25) . " areas ocultas) ...\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
