<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$sql = "SELECT nombre, COUNT(*) as total, GROUP_CONCAT(id) as ids 
        FROM puestos_trabajo 
        GROUP BY nombre 
        HAVING total > 1 
        ORDER BY total DESC";
$stmt = $pdo->query($sql);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicates)) {
    echo "No se encontraron nombres duplicados en la base de datos.\n";
} else {
    echo "Puestos duplicados encontrados:\n";
    foreach ($duplicates as $d) {
        echo "- {$d['nombre']}: Se repite {$d['total']} veces (IDs: {$d['ids']})\n";
    }
}
