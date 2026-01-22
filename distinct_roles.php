<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT DISTINCT rol_sistema FROM empleados");
echo implode(", ", $stmt->fetchAll(PDO::FETCH_COLUMN));
