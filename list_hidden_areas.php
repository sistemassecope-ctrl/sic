<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 ORDER BY id");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($areas);
    
    echo "Areas Ocultas (" . ($total - 25) . "):\n";
    echo "--------------------------------\n";
    
    foreach ($areas as $i => $area) {
        // La logica anterior mostraba < 20 y >= total-5.
        // Ahora mostramos los del medio: >= 20 y < total-5
        if ($i >= 20 && $i < $total - 5) {
            echo "[" . $area['id'] . "] " . $area['nombre_area'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
