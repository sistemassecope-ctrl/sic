<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Actualizar las notas más recientes con tipos correctos
// 178 y 179 son las notas de baja
$pdo->exec("UPDATE vehiculos_notas SET tipo_origen = 'SOLICITUD_BAJA' WHERE id = 178");
echo "Actualizado ID 178 a SOLICITUD_BAJA\n";

$pdo->exec("UPDATE vehiculos_notas SET tipo_origen = 'AUTORIZACION_BAJA' WHERE id = 179");
echo "Actualizado ID 179 a AUTORIZACION_BAJA\n";

// Verificar
echo "\nVerificando...\n";
$stmt = $pdo->query("SELECT id, tipo_origen FROM vehiculos_notas WHERE id IN (178, 179)");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID:{$n['id']} Tipo:'{$n['tipo_origen']}'\n";
}

// Verificar que ahora LIKE funciona
echo "\nNotas BAJA para vehiculo 99:\n";
$stmt2 = $pdo->query("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = 99 AND tipo_origen LIKE '%BAJA%'");
echo "Total: " . $stmt2->fetchColumn() . "\n";
