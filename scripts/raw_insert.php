<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Primero eliminar las notas con tipo vacío
$pdo->exec("DELETE FROM vehiculos_notas WHERE vehiculo_id = 99 AND tipo_origen = ''");
echo "Notas vacías eliminadas\n";

// Insertar con SQL directo sin binding
$sql = "INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) 
        VALUES (99, 'SOLICITUD_BAJA', 'SOLICITUD: Fin de vida util', NOW())";
$result = $pdo->exec($sql);
echo "Insert 1 result: $result\n";
echo "Last ID: " . $pdo->lastInsertId() . "\n";

$sql2 = "INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) 
         VALUES (99, 'AUTORIZACION_BAJA', 'AUTORIZADA: Aprobado', NOW())";
$result2 = $pdo->exec($sql2);
echo "Insert 2 result: $result2\n";
echo "Last ID: " . $pdo->lastInsertId() . "\n";

// Verificar
echo "\nNotas insertadas:\n";
$stmt = $pdo->query("SELECT id, tipo_origen FROM vehiculos_notas WHERE vehiculo_id = 99");
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID:{$n['id']} tipo:'{$n['tipo_origen']}'\n";
}
