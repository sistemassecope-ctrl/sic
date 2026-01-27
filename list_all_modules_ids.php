<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT id, nombre_modulo, ruta FROM modulos ORDER BY id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: {$row['id']} | Modulo: {$row['nombre_modulo']} | Ruta: {$row['ruta']}\n";
}
