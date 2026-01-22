<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 ORDER BY id");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
