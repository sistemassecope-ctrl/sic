<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

// Lista proporcionada por el usuario (Texto)
$userList = [
'V1210-006',
'V1500-325',
'V1100-025',
'V1100-024',
'V1100-030',
'V1210-024',
'V1500-250',
'V1500-230',
'V1500-236',
'V1500-244',
'V1500-254',
'V2600-025',
'V1100-027',
'V1500-353',
'V1500-319',
'V1500-311',
'V1100-031',
'V1500-243',
'V1210-003',
'V1500-290',
'V1100-022',
'V1500-297',
'V1100-020',
'V2600-024',
'V1500-233',
'V1500-238',
'V1500-242',
'V1500-294',
'V1500-315',
'V1500-271',
'V1500-261',
'V1500-256',
'V1500-318',
'V1500-307',
'V1500-268',
'V1500-259',
'V1500-260',
'V1500-269',
'V1500-308',
'V1500-321',
'V1500-304',
'V1500-235',
'V1500-258',
'V1500-262',
'V1500-310',
'V1500-314',
'V1500-317',
'V1500-323',
'V1500-257',
'V1500-300',
'V1500-255',
'V1500-263',
'V1500-267',
'V1500-226',
'V1500-286',
'V1500-248',
'V1500-316',
'V1100-023',
'V1500-292',
'V1500-288',
'V1500-312',
'V1500-232',
'V1500-320',
'V1500-240',
'V1500-241',
'V1500-265',
'V1500-264',
'V1500-270',
'V1500-291',
'V1500-293',
'V1500-295',
'V1500-298',
'V1500-299',
'V1500-301',
'V1500-305',
'V1500-309',
'V1500-322',
'V1500-239',
'V1500-266',
'V1500-249',
'V1500-280'
];

// Limpieza básica
$userList = array_map('trim', $userList);
$userListUnique = array_unique($userList);

echo "Total en Lista Usuario: " . count($userList) . "\n";
echo "Total Únicos Usuario: " . count($userListUnique) . "\n";

// Obtener DB
$dbAll = $pdo->query("SELECT numero_economico FROM vehiculos UNION SELECT numero_economico FROM vehiculos_bajas")->fetchAll(PDO::FETCH_COLUMN);

// Comparar
$missing = [];
foreach ($userListUnique as $u) {
    if (!empty($u) && !in_array($u, $dbAll)) { // Ignorar vacíos
        $missing[] = $u;
    }
}

if (count($missing) > 0) {
    echo "FALTAN EN BD:\n";
    foreach ($missing as $m) echo "$m\n";
} else {
    echo "TODOS_EXISTEN";
}
