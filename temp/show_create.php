<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
echo "--- CREATE TABLE empleados ---\n";
echo $pdo->query("SHOW CREATE TABLE empleados")->fetchColumn();
echo "\n\n--- CREATE TABLE usuarios_sistema ---\n";
echo $pdo->query("SHOW CREATE TABLE usuarios_sistema")->fetchColumn();
