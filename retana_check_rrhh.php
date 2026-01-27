<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT ump.id_modulo, m.nombre_modulo, p.clave
    FROM usuario_modulo_permisos ump
    INNER JOIN modulos m ON ump.id_modulo = m.id
    INNER JOIN permisos p ON ump.id_permiso = p.id
    WHERE ump.id_usuario = 10
");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
