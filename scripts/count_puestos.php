<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    $c1 = $pdo->query("SELECT COUNT(*) FROM puestos")->fetchColumn();
    echo "Puestos: $c1\n";
} catch (Exception $e) { echo "Puestos Error: " . $e->getMessage() . "\n"; }

try {
    $c2 = $pdo->query("SELECT COUNT(*) FROM puestos_trabajo")->fetchColumn();
    echo "Puestos_Trabajo: $c2\n";
} catch (Exception $e) { echo "Puestos_Trabajo Error: " . $e->getMessage() . "\n"; }
