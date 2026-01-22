<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("SHOW TABLES");
echo implode(", ", $stmt->fetchAll(PDO::FETCH_COLUMN));
