<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

$id = 89;
echo "--- Verificando ID $id ---\n";

// Activo
$stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
$stmt->execute([$id]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if ($v) {
    echo "ENCONTRADO en Activos\n";
    echo "ID: {$v['id']}\n";
    echo "Eco: {$v['numero_economico']}\n";
    echo "Marca: {$v['marca']}\n";
    echo "Activo: {$v['activo']}\n";
    echo "En Proceso Baja: {$v['en_proceso_baja']}\n";
} else {
    echo "NO ENCONTRADO en Activos\n";
}

// Baja
$stmtB = $pdo->prepare("SELECT * FROM vehiculos_bajas WHERE vehiculo_origen_id = ?");
$stmtB->execute([$id]);
$b = $stmtB->fetch(PDO::FETCH_ASSOC);

if ($b) {
     echo "ENCONTRADO en Bajas Históricas\n";
     echo "ID Baja: {$b['id']}\n";
     echo "Fecha Baja: {$b['fecha_baja']}\n";
} else {
     echo "NO ENCONTRADO en Bajas Históricas\n";
}
