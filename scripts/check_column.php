<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

$output = "";

// Estructura de la columna
$output .= "=== DESCRIBE vehiculos_notas ===\n";
$stmt = $pdo->query("DESCRIBE vehiculos_notas");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= $row['Field'] . " | " . $row['Type'] . " | Default: " . $row['Default'] . "\n";
}

// Intentar UPDATE directo
$output .= "\n=== UPDATE tipo_origen directamente ===\n";
$affected = $pdo->exec("UPDATE vehiculos_notas SET tipo_origen = 'SOLICITUD_BAJA' WHERE id = 182");
$output .= "Rows affected: $affected\n";

// Verificar el resultado
$stmt2 = $pdo->prepare("SELECT id, tipo_origen FROM vehiculos_notas WHERE id = 182");
$stmt2->execute();
$row = $stmt2->fetch(PDO::FETCH_ASSOC);
$output .= "ID 182 tipo_origen: '{$row['tipo_origen']}'\n";

file_put_contents(__DIR__ . '/debug_output.txt', $output);
echo "Output en debug_output.txt\n";
