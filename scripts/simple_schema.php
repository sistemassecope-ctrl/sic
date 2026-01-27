<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

function showCols($pdo, $table) {
    echo "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "{$c['Field']} ({$c['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

showCols($pdo, 'puestos');
showCols($pdo, 'puestos_trabajo');
