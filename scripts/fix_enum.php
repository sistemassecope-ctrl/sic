<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

$output = "";

// 1. Modificar el ENUM para aceptar más valores
$output .= "=== MODIFICANDO ENUM ===\n";
try {
    $sql = "ALTER TABLE vehiculos_notas MODIFY COLUMN tipo_origen ENUM('ACTIVO','BAJA','SOLICITUD_BAJA','AUTORIZACION_BAJA') DEFAULT 'ACTIVO'";
    $pdo->exec($sql);
    $output .= "ALTER TABLE ejecutado correctamente\n";
} catch (Exception $e) {
    $output .= "ERROR: " . $e->getMessage() . "\n";
}

// 2. Actualizar las notas existentes con los tipos correctos
$output .= "\n=== ACTUALIZANDO NOTAS ===\n";
$affected1 = $pdo->exec("UPDATE vehiculos_notas SET tipo_origen = 'SOLICITUD_BAJA' WHERE id = 182");
$output .= "Nota 182 actualizada: $affected1 rows\n";

$affected2 = $pdo->exec("UPDATE vehiculos_notas SET tipo_origen = 'AUTORIZACION_BAJA' WHERE id = 183");
$output .= "Nota 183 actualizada: $affected2 rows\n";

// 3. Verificar
$output .= "\n=== VERIFICAR ===\n";
$stmt = $pdo->query("SELECT id, tipo_origen FROM vehiculos_notas WHERE vehiculo_id = 99");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= "ID:{$n['id']} tipo:'{$n['tipo_origen']}'\n";
}

$output .= "\n=== NOTAS TIPO BAJA PARA VEHICULO 99 ===\n";
$stmt2 = $pdo->query("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = 99 AND tipo_origen LIKE '%BAJA%'");
$output .= "Total: " . $stmt2->fetchColumn() . "\n";

file_put_contents(__DIR__ . '/debug_output.txt', $output);
echo "Output en debug_output.txt\n";
