<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

$vehiculo_id = 99;

// 1. Verificar cuántas notas tipo BAJA existen ya
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen LIKE '%BAJA%'");
$stmt->execute([$vehiculo_id]);
$count = $stmt->fetchColumn();
echo "Notas BAJA existentes para vehiculo 99: $count\n";

// 2. Si no hay, insertar las notas
if ($count == 0) {
    echo "\nInsertando notas...\n";
    
    try {
        $stmt1 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'SOLICITUD_BAJA', ?, NOW())");
        $stmt1->execute([99, "SOLICITUD DE BAJA: Fin de Vida Útil: ya no sirve solicito dar de baja"]);
        echo "Nota 1 insertada, ID: " . $pdo->lastInsertId() . "\n";
        
        $stmt2 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'AUTORIZACION_BAJA', ?, NOW())");
        $stmt2->execute([99, "BAJA AUTORIZADA: Aprobado"]);
        echo "Nota 2 insertada, ID: " . $pdo->lastInsertId() . "\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

// 3. Verificar nuevamente
echo "\n=== Verificar notas después de inserción ===\n";
$stmt = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = 99 ORDER BY id DESC");
$stmt->execute();
$notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total notas vehiculo 99: " . count($notas) . "\n";
foreach ($notas as $n) {
    echo "- ID:{$n['id']} Tipo:{$n['tipo_origen']} \n";
}
