<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

$userList = [
'V1210-006', 'V1500-325', 'V1100-025', 'V1100-024', 'V1100-030', 
'V1210-024', 'V1500-250', 'V1500-230', 'V1500-236', 'V1500-244', 
'V1500-254', 'V2600-025', 'V1100-027', 'V1500-353', 'V1500-319', 
'V1500-311', 'V1100-031', 'V1500-243', 'V1210-003', 'V1500-290', 
'V1100-022', 'V1500-297', 'V1100-020', 'V2600-024', 'V1500-233', 
'V1500-238', 'V1500-242', 'V1500-294', 'V1500-315', 'V1500-271', 
'V1500-261', 'V1500-256', 'V1500-318', 'V1500-307', 'V1500-268', 
'V1500-259', 'V1500-260', 'V1500-269', 'V1500-308', 'V1500-321', 
'V1500-304', 'V1500-235', 'V1500-258', 'V1500-262', 'V1500-310', 
'V1500-314', 'V1500-317', 'V1500-323', 'V1500-257', 'V1500-300', 
'V1500-255', 'V1500-263', 'V1500-267', 'V1500-226', 'V1500-286', 
'V1500-248', 'V1500-316', 'V1100-023', 'V1500-292', 'V1500-288', 
'V1500-312', 'V1500-232', 'V1500-320', 'V1500-240', 'V1500-241', 
'V1500-265', 'V1500-264', 'V1500-270', 'V1500-291', 'V1500-293', 
'V1500-295', 'V1500-298', 'V1500-299', 'V1500-301', 'V1500-305', 
'V1500-309', 'V1500-322', 'V1500-239', 'V1500-266', 'V1500-249', 
'V1500-280', 'V1500-302', 'V1500-281', 'V1500-283', 'V1500-247', 
'V1500-246', 'V1100-029', 'V1500-282', 'V1100-026', 'V1500-289', 
'V1500-355'
];

$userList = array_unique(array_map('trim', $userList));
$output = "";
$output .= "Procesando " . count($userList) . " registros únicos.\n\n";

$bajas = [];
$missing = [];
$activos = 0;

foreach ($userList as $eco) {
    if (empty($eco)) continue;

    // Check Activo
    $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE numero_economico = ? AND activo = 1");
    $stmt->execute([$eco]);
    if ($stmt->fetch()) {
        $activos++;
        continue;
    }

    // Check Baja
    $stmtB = $pdo->prepare("SELECT id, fecha_baja FROM vehiculos_bajas WHERE numero_economico = ?");
    $stmtB->execute([$eco]);
    $baja = $stmtB->fetch();
    if ($baja) {
        $bajas[] = "$eco (Baja: {$baja['fecha_baja']})";
        continue;
    }

    $missing[] = $eco;
}

$output .= "RESULTADO:\n";
$output .= "Activos: $activos\n";
$output .= "En Bajas: " . count($bajas) . "\n";
$output .= "Faltantes: " . count($missing) . "\n\n";

if (count($bajas) > 0) {
    $output .= "Registros que están en BAJA (Inactivos):\n";
    foreach ($bajas as $b) $output .= "- $b\n";
}

if (count($missing) > 0) {
    $output .= "\nRegistros NO ENCONTRADOS en absoluto:\n";
    foreach ($missing as $m) $output .= "- $m\n";
}

file_put_contents('check_status_result.txt', $output);
echo "Output written to check_status_result.txt";
if (count($bajas) > 0) {
    echo "Registros que están en BAJA (Inactivos):\n";
    foreach ($bajas as $b) echo "- $b\n";
}

if (count($missing) > 0) {
    echo "\nRegistros NO ENCONTRADOS en absoluto:\n";
    foreach ($missing as $m) echo "- $m\n";
}
