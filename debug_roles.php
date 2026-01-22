<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();

echo "--- empleados ---\n";
$stmt = $pdo->query("DESCRIBE empleados");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- usuarios_sistema ---\n";
$stmt = $pdo->query("DESCRIBE usuarios_sistema");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Current Session ---\n";
session_start();
print_r($_SESSION);

echo "\n--- Sample Admins ---\n";
$stmt = $pdo->query("SELECT u.usuario, u.tipo, e.rol_sistema, e.permisos_extra FROM usuarios_sistema u LEFT JOIN empleados e ON u.id_empleado = e.id WHERE u.tipo = 1 OR e.rol_sistema = 'admin_global'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
