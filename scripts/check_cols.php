<?php
require 'config/database.php';
$pdo = getConnection();

function desc($pdo, $tbl) {
    echo "TABLE: $tbl\n";
    $stmt = $pdo->query("DESCRIBE $tbl");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . "\n";
    }
    echo "----------------\n";
}

desc($pdo, 'vehiculos_notas');
