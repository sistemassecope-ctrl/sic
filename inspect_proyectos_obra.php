<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
echo "--- Estructura de proyectos_obra ---\n";
$stmt = $pdo->query("DESCRIBE proyectos_obra");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
