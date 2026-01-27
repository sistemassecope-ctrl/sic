<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT id, nombre_modulo, id_padre FROM modulos WHERE id IN (2, 20, 26, 50)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
