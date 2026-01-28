<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();

// Verificar tabla de solicitudes
echo "--- Estructura de solicitudes_suficiencia ---\n";
$stmt = $pdo->query("DESCRIBE solicitudes_suficiencia");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

// Verificar si existe tabla de estatus
echo "\n--- Tablas existentes ---\n";
$stmt = $pdo->query("SHOW TABLES LIKE '%estatus%'");
while ($row = $stmt->fetch()) {
    echo $row[0] . "\n";
}
