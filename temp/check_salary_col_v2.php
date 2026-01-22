<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SHOW COLUMNS FROM empleados");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    if (preg_match('/(sueldo|salario|ingreso|nivel|puesto_fin)/i', $col['Field'])) {
        echo "Found candidate column in 'empleados': " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
$stmt = $pdo->query("SHOW COLUMNS FROM puestos_trabajo");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    if (preg_match('/(sueldo|salario|ingreso|nivel)/i', $col['Field'])) {
        echo "Found candidate column in 'puestos_trabajo': " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
