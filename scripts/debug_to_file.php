<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

$output = "";

$output .= "=== NOTAS VEHICULO 99 ===\n";
$stmt = $pdo->query("SELECT id, tipo_origen, LEFT(nota,40) as nota FROM vehiculos_notas WHERE vehiculo_id = 99 ORDER BY id");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= "ID: {$n['id']}\n";
    $output .= "  tipo_origen: '" . $n['tipo_origen'] . "'\n";
    $output .= "  nota: " . $n['nota'] . "\n";
}

$output .= "\n=== NOTAS CON BAJA EN tipo_origen ===\n";
$stmt2 = $pdo->query("SELECT id, tipo_origen FROM vehiculos_notas WHERE tipo_origen LIKE '%BAJA%'");
$bajaNotas = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$output .= "Total: " . count($bajaNotas) . "\n";
foreach ($bajaNotas as $n) {
    $output .= "ID:{$n['id']} tipo:'{$n['tipo_origen']}'\n";
}

file_put_contents(__DIR__ . '/debug_output.txt', $output);
echo "Output escrito a debug_output.txt\n";
