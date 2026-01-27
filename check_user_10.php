<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();

echo "USER 10 DATA:\n";
$stmt = $pdo->prepare("SELECT * FROM usuarios_sistema WHERE id = ?");
$stmt->execute([10]);
print_r($stmt->fetch());

echo "\nPERMISSIONS FOR USER 10 / MODULE 31:\n";
$stmt = $pdo->prepare("
    SELECT ump.*, p.clave 
    FROM usuario_modulo_permisos ump
    JOIN permisos p ON ump.id_permiso = p.id
    WHERE ump.id_usuario = ? AND ump.id_modulo = ?
");
$stmt->execute([10, 31]);
print_r($stmt->fetchAll());

echo "\nALL PERMISSIONS IN DB:\n";
$stmt = $pdo->query("SELECT * FROM permisos");
print_r($stmt->fetchAll());

echo "\nALL MODULES IN DB:\n";
$stmt = $pdo->query("SELECT id, nombre_modulo FROM modulos WHERE id = 31");
print_r($stmt->fetchAll());
