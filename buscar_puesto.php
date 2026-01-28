<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT nombre FROM puestos_trabajo WHERE nombre LIKE '%bibliotecario%'");
while ($row = $stmt->fetch()) {
    echo $row['nombre'] . "\n";
}
