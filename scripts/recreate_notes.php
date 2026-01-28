<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// 1. Eliminar las notas vacías (172-179)
echo "Eliminando notas con tipo vacío para vehiculo 99...\n";
$deleted = $pdo->exec("DELETE FROM vehiculos_notas WHERE vehiculo_id = 99 AND (tipo_origen = '' OR tipo_origen IS NULL)");
echo "Eliminadas: $deleted\n";

// 2. Insertar notas correctas con binding explícito
echo "\nInsertando notas con prepare/execute...\n";

$stmt1 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (:vid, :tipo, :nota, NOW())");
$stmt1->execute([
    ':vid' => 99,
    ':tipo' => 'SOLICITUD_BAJA',
    ':nota' => 'SOLICITUD DE BAJA: Fin de Vida Util - solicito dar de baja'
]);
$id1 = $pdo->lastInsertId();
echo "Insertada nota 1, ID: $id1\n";

$stmt2 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (:vid, :tipo, :nota, NOW())");
$stmt2->execute([
    ':vid' => 99,
    ':tipo' => 'AUTORIZACION_BAJA',
    ':nota' => 'BAJA AUTORIZADA: Aprobado por administrador'
]);
$id2 = $pdo->lastInsertId();
echo "Insertada nota 2, ID: $id2\n";

// 3. Verificar
echo "\nVerificando notas vehiculo 99:\n";
$stmt = $pdo->query("SELECT id, tipo_origen FROM vehiculos_notas WHERE vehiculo_id = 99");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID:{$n['id']} Tipo:'{$n['tipo_origen']}'\n";
}
