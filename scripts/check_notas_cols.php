<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "=== VEHICULOS_NOTAS COLUMNS ===\n";
$r = $pdo->query("DESCRIBE vehiculos_notas");
foreach($r as $col) {
    echo $col['Field'] . "\n";
}
