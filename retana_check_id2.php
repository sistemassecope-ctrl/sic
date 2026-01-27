<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM usuario_modulo_permisos WHERE id_usuario = 10 AND id_modulo = 2");
$stmt->execute();
print_r($stmt->fetchAll());
