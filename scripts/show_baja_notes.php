<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Ver TODAS las notas tipo BAJA
echo "=== NOTAS TIPO BAJA (todas) ===\n";
$stmtN = $pdo->query("SELECT * FROM vehiculos_notas WHERE tipo_origen LIKE '%BAJA%' ORDER BY id DESC");
$notas = $stmtN->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($notas) . "\n";
foreach ($notas as $n) {
    echo "ID:{$n['id']} | VehID:{$n['vehiculo_id']} | Tipo:{$n['tipo_origen']} | Created:{$n['created_at']}\n";
    echo "  Nota: {$n['nota']}\n\n";
}
