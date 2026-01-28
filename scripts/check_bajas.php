<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "=== SOLICITUDES_BAJA (últimas 5) ===\n";
$r = $pdo->query("SELECT * FROM solicitudes_baja ORDER BY id DESC LIMIT 5");
foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: {$row['id']} | Veh: {$row['vehiculo_id']} | Estado: {$row['estado']} | Visto: {$row['visto']} | Solicitante: {$row['solicitante_id']} | Created: {$row['created_at']}\n";
}
