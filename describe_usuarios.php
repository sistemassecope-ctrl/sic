<?php
require_once __DIR__ . '/config/database.php';
$pdo = conectarDB();
$stmt = $pdo->query("DESCRIBE usuarios_sistema");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
