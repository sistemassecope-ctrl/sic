<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM empleados LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
foreach($row as $k => $v) {
    echo "$k: " . (is_null($v) ? 'NULL' : $v) . "\n";
}
