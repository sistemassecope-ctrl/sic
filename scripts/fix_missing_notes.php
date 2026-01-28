<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Verificar solicitud 5
$stmtS = $pdo->query("SELECT * FROM solicitudes_baja WHERE id = 5");
$sol = $stmtS->fetch(PDO::FETCH_ASSOC);

echo "=== SOLICITUD 5 ===\n";
echo "Estado: {$sol['estado']}\n";
echo "VehID: {$sol['vehiculo_id']}\n";
echo "Motivo: {$sol['motivo']}\n";
echo "Solicitante: {$sol['solicitante_id']}\n";
echo "Created: {$sol['created_at']}\n";
echo "Autorizador: {$sol['autorizador_id']}\n";
echo "Comentarios: {$sol['comentarios_respuesta']}\n";

// Si la solicitud está finalizada pero no hay notas, insertarlas
if ($sol['estado'] == 'finalizado') {
    // Verificar si existen notas para este vehiculo
    $stmtN = $pdo->prepare("SELECT COUNT(*) FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen LIKE '%BAJA%'");
    $stmtN->execute([$sol['vehiculo_id']]);
    $count = $stmtN->fetchColumn();
    
    echo "\nNotas BAJA existentes para vehiculo {$sol['vehiculo_id']}: $count\n";
    
    if ($count == 0) {
        echo "\n*** INSERTANDO NOTAS FALTANTES ***\n";
        
        // Nota solicitud
        $nota1 = "SOLICITUD DE BAJA (Usuario ID: {$sol['solicitante_id']}): {$sol['motivo']}";
        $stmt1 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'SOLICITUD_BAJA', ?, ?)");
        $stmt1->execute([$sol['vehiculo_id'], $nota1, $sol['created_at']]);
        echo "Insertada nota de solicitud\n";
        
        // Nota autorizacion
        $nota2 = "BAJA AUTORIZADA (Usuario ID: {$sol['autorizador_id']}): {$sol['comentarios_respuesta']}";
        $stmt2 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'AUTORIZACION_BAJA', ?, NOW())");
        $stmt2->execute([$sol['vehiculo_id'], $nota2]);
        echo "Insertada nota de autorización\n";
        
        echo "\n*** NOTAS INSERTADAS CORRECTAMENTE ***\n";
    }
}
