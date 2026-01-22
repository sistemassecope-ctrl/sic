<?php
/**
 * Script de Migración de Bajas (Legacy -> PAO v2)
 */

require_once __DIR__ . '/../config/database.php';

// Configuración de Origen (SIC)
define('SIC_HOST', '192.168.100.14');
define('SIC_DB', 'sic');
define('SIC_USER', 'sic_test');
define('SIC_PASS', 'sic_test.2025');

define('DEFAULT_AREA_ID', 33);

try {
    echo "--- INICIANDO MIGRACIÓN DE BAJAS ---\n";
    
    $pdoDest = getConnection();
    $dsnSic = "mysql:host=" . SIC_HOST . ";dbname=" . SIC_DB . ";charset=utf8mb4";
    $pdoSrc = new PDO($dsnSic, SIC_USER, SIC_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check if table exists
    try {
        $pdoSrc->query("SELECT 1 FROM vehiculos_bajas LIMIT 1");
    } catch (Exception $e) {
        die("La tabla 'vehiculos_bajas' no existe en la base de datos origen. Saltando migración de histórico.\n");
    }

    $bajas = $pdoSrc->query("SELECT * FROM vehiculos_bajas")->fetchAll(PDO::FETCH_ASSOC);
    echo "-> Encontrados " . count($bajas) . " registros en histórico de bajas.\n";
    
    $stmtInsert = $pdoDest->prepare("
        INSERT INTO vehiculos_bajas (
            numero, numero_economico, numero_patrimonio, numero_placas,
            marca, modelo, tipo, color, serie,
            fecha_baja, anio_baja, motivo_baja,
            resguardo_nombre, observaciones, region,
            area_id, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, NOW()
        )
    ");
    
    $inserted = 0;
    foreach ($bajas as $b) {
        $obs = extraeObs($b);
        $stmtInsert->execute([
            $b['numero'] ?? 0,
            $b['numero_economico'],
            $b['numero_patrimonio'] ?? '',
            $b['numero_placas'] ?? '',
            $b['marca'],
            $b['modelo'] ?? '',
            $b['tipo'],
            $b['color'] ?? '',
            $b['numero_serie'] ?? '',
            
            $b['fecha_baja'] ?? null,
            $b['anio_baja'] ?? null,
            $b['motivo_baja'] ?? 'Desconocido',
            
            $b['resguardo_nombre'] ?? '',
            $obs,
            $b['region'] ?? 'SECOPE',
            DEFAULT_AREA_ID
        ]);
        $inserted++;
    }
    
    echo "Migración de Bajas completada. Insertados: $inserted\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

function extraeObs($v) {
    $obs = [];
    if (!empty($v['observacion_1'])) $obs[] = $v['observacion_1'];
    if (!empty($v['observacion_2'])) $obs[] = $v['observacion_2'];
    return implode(" | ", $obs);
}
