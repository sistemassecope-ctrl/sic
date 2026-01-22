<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$cols = $pdo->query("DESCRIBE vehiculos")->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);
