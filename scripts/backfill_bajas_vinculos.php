<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    echo "--- POBLANDO VEHICULO_ORIGEN_ID EN BAJAS ---\n\n";
    
    // Intentar vincular bajas con vehículos activos/inactivos por numero_economico
    $sql = "UPDATE vehiculos_bajas vb
            INNER JOIN vehiculos v ON v.numero_economico = vb.numero_economico
            SET vb.vehiculo_origen_id = v.id
            WHERE vb.vehiculo_origen_id IS NULL OR vb.vehiculo_origen_id = 0";
    
    $count = $pdo->exec($sql);
    echo "✓ Vinculados: $count registros\n\n";
    
    // Verificar resultados
    $conVinculo = $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas WHERE vehiculo_origen_id IS NOT NULL AND vehiculo_origen_id > 0")->fetchColumn();
    $sinVinculo = $pdo->query("SELECT COUNT(*) FROM vehiculos_bajas WHERE vehiculo_origen_id IS NULL OR vehiculo_origen_id = 0")->fetchColumn();
    
    echo "--- RESULTADO ---\n";
    echo "Con vínculo: $conVinculo\n";
    echo "Sin vínculo: $sinVinculo\n";
    
    if ($sinVinculo > 0) {
        echo "\nNOTA: Los registros sin vínculo son vehículos que ya no existen en la tabla 'vehiculos'.\n";
        echo "Estos mostrarán el botón amarillo (recrear) en lugar del verde (restaurar).\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
