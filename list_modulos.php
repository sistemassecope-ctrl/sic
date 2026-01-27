<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT id, nombre_modulo, ruta FROM modulos WHERE estado = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
