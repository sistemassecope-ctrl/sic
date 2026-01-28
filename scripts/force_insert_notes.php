<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

echo "Insertando notas BAJA para vehiculo 99...\n";

try {
    // Nota de solicitud
    $sql1 = "INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (99, 'SOLICITUD_BAJA', 'SOLICITUD DE BAJA: Fin de Vida Util - ya no sirve, solicito dar de baja', NOW())";
    $pdo->exec($sql1);
    $id1 = $pdo->lastInsertId();
    echo "Insertado SOLICITUD_BAJA, ID: $id1\n";
    
    // Nota de autorizacion
    $sql2 = "INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (99, 'AUTORIZACION_BAJA', 'BAJA AUTORIZADA: Aprobado por administrador', NOW())";
    $pdo->exec($sql2);
    $id2 = $pdo->lastInsertId();
    echo "Insertado AUTORIZACION_BAJA, ID: $id2\n";
    
    // Verificar
    echo "\nVerificando...\n";
    $stmt = $pdo->query("SELECT id, vehiculo_id, tipo_origen FROM vehiculos_notas WHERE vehiculo_id = 99 ORDER BY id DESC LIMIT 5");
    while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID:{$n['id']} VehID:{$n['vehiculo_id']} Tipo:{$n['tipo_origen']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
