<?php
require_once __DIR__ . '/config/database.php';
$pdo = conectarDB();
$stmt = $pdo->query("SHOW COLUMNS FROM empleados");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $cols);
