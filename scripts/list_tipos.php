<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Lista simple: solo ID y tipo
$stmt = $pdo->query("SELECT id, tipo_origen FROM vehiculos_notas WHERE vehiculo_id = 99 ORDER BY id");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$n['id']}:{$n['tipo_origen']}\n";
}
