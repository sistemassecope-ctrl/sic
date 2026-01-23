<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

$list = [
    'V1500-302',
    'V1500-281',
    'V1500-283',
    'V1500-247',
    'V1500-246',
    'V1100-029',
    'V1500-282',
    'V100-026'
];

echo "--- Verificando lista de " . count($list) . " registros ---\n";

$foundCount = 0;
foreach ($list as $eco) {
    $eco = trim($eco);
    // Check Activos
    $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE numero_economico = ?");
    $stmt->execute([$eco]);
    $act = $stmt->fetch();

    if ($act) {
        echo "[OK] $eco - Encontrado en ACTIVOS (ID: {$act['id']})\n";
        $foundCount++;
        continue;
    }

    // Check Bajas
    $stmtB = $pdo->prepare("SELECT id, fecha_baja FROM vehiculos_bajas WHERE numero_economico = ?");
    $stmtB->execute([$eco]);
    $baja = $stmtB->fetch();

    if ($baja) {
        echo "[OK] $eco - Encontrado en BAJAS (ID: {$baja['id']}, Fecha: {$baja['fecha_baja']})\n";
        $foundCount++;
        continue;
    }

    echo "[X]  $eco - NO ENCONTRADO en ninguna tabla\n";
}

if ($foundCount === count($list)) {
    echo "\nRESUMEN: Todos los registros existen en la base de datos.\n";
} else {
    echo "\nRESUMEN: Faltan " . (count($list) - $foundCount) . " registros.\n";
}
