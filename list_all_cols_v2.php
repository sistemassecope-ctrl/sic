<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("DESC empleados");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach($cols as $c) echo $c . "\n";
