<?php
require_once __DIR__ . '/config/database.php';
$pdo = conectarDB();
$stmt = $pdo->query("DESCRIBE empleados");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
