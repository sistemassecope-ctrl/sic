<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM permisos");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
