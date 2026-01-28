<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Ver TODAS las notas de vehiculo 99 sin filtro
echo "=== TODAS LAS NOTAS PARA VEHICULO 99 (sin filtro) ===\n";
$stmt = $pdo->query("SELECT id, vehiculo_id, tipo_origen, HEX(tipo_origen) as hex_tipo FROM vehiculos_notas WHERE vehiculo_id = 99");
$notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($notas) . "\n";
foreach ($notas as $n) {
    echo "ID:{$n['id']} | Tipo:'{$n['tipo_origen']}' | HEX:{$n['hex_tipo']}\n";
}

// Ver si el LIKE funciona
echo "\n=== TEST LIKE ===\n";
$stmt2 = $pdo->query("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = 99 AND tipo_origen LIKE '%BAJA%'");
echo "Con LIKE '%BAJA%': " . $stmt2->fetchColumn() . "\n";

$stmt3 = $pdo->query("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = 99 AND tipo_origen LIKE '%baja%'");
echo "Con LIKE '%baja%' (minúsculas): " . $stmt3->fetchColumn() . "\n";
