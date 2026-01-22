<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
echo "Valid Areas:\n";
$stmt = $pdo->query("SELECT id, nombre_area FROM areas LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nValid Puestos:\n";
$stmt = $pdo->query("SELECT id, nombre FROM puestos_trabajo LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
