<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

// Buscar TODAS las notas para vehiculo_id = 99
echo "=== TODAS LAS NOTAS PARA VEHICULO 99 ===\n";
$stmtN = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = 99");
$stmtN->execute();
$notas = $stmtN->fetchAll(PDO::FETCH_ASSOC);
echo "Total notas: " . count($notas) . "\n\n";
foreach ($notas as $n) {
    echo "---\n";
    echo "ID: {$n['id']}\n";
    echo "Tipo: {$n['tipo_origen']}\n";
    echo "Nota: {$n['nota']}\n";
    echo "Fecha: {$n['created_at']}\n";
}

// Si no hay notas, verificar que las inserciones no fallaron silenciosamente
if (count($notas) == 0) {
    echo "\n*** NO HAY NOTAS - Verificando si el proceso de aprobación ejecutó correctamente ***\n";
    
    // Ver la solicitud
    $stmtS = $pdo->prepare("SELECT * FROM solicitudes_baja WHERE vehiculo_id = 99");
    $stmtS->execute();
    $sol = $stmtS->fetch(PDO::FETCH_ASSOC);
    echo "\nSolicitud:\n";
    print_r($sol);
}
