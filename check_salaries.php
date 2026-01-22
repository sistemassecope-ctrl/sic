<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT nombres, salario_bruto, salario_neto, compensacion FROM empleados LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
