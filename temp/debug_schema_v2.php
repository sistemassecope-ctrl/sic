<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

function printTable($pdo, $sql) {
    try {
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach ($row as $k => $v) {
                if ($k == 'Create Table') echo $v . "\n\n";
            }
        }
    } catch (Exception $e) {
        echo "Error in $sql: " . $e->getMessage() . "\n";
    }
}

echo "--- empleados ---\n";
printTable($pdo, "SHOW CREATE TABLE empleados");

echo "--- usuarios_sistema ---\n";
printTable($pdo, "SHOW CREATE TABLE usuarios_sistema");

echo "--- areas ---\n";
$stmt = $pdo->query("SELECT id, nombre_area FROM areas LIMIT 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- puestos ---\n";
$stmt = $pdo->query("SELECT id, nombre FROM puestos_trabajo LIMIT 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
