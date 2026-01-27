<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- BUSCANDO FOREIGN KEYS QUE APUNTAN A 'vehiculos' ---\n\n";

$sql = "
SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = 'pao_v2' AND
    REFERENCED_TABLE_NAME = 'vehiculos';
";

$fks = $pdo->query($sql)->fetchAll();

if (empty($fks)) {
    echo "No se encontraron Foreign Keys apuntando a 'vehiculos'.\n";
} else {
    foreach ($fks as $fk) {
        echo "Tabla: {$fk['TABLE_NAME']} | Columna: {$fk['COLUMN_NAME']} | Constraint: {$fk['CONSTRAINT_NAME']}\n";
    }
}
