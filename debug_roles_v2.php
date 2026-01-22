<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();

function printTable($title, $data) {
    echo "=== $title ===\n";
    foreach ($data as $row) {
        foreach ($row as $k => $v) {
            echo "$k: $v | ";
        }
        echo "\n";
    }
    echo "\n";
}

$stmt = $pdo->query("DESCRIBE empleados");
printTable("empleados schema", $stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("DESCRIBE usuarios_sistema");
printTable("usuarios_sistema schema", $stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT u.usuario, u.tipo, e.rol_sistema, e.permisos_extra FROM usuarios_sistema u LEFT JOIN empleados e ON u.id_empleado = e.id");
printTable("Users and Roles", $stmt->fetchAll(PDO::FETCH_ASSOC));
