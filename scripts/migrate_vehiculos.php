<?php
/**
 * Script de Migración de Vehículos (Legacy -> PAO v2)
 * Se conecta a la BD 'sic' y transfiere datos a 'pao_v2'
 * Asigna AREA_ID = 33 (Departamento de Parque Vehicular) por defecto.
 */

require_once __DIR__ . '/../config/database.php';

// Configuración de Origen (SIC) - Hardcoded based on pao_old/config/db.php
define('SIC_HOST', '192.168.100.14');
define('SIC_DB', 'sic');
define('SIC_USER', 'sic_test');
define('SIC_PASS', 'sic_test.2025');

// Configuración de Destino (Área Default)
define('DEFAULT_AREA_ID', 33);

try {
    echo "--- INICIANDO MIGRACIÓN DE VEHÍCULOS ---\n";
    
    // 1. Conexión a PAO v2 (Destino)
    $pdoDest = getConnection();
    echo "[OK] Conectado a Destino (pao_v2)\n";
    
    // 2. Conexión a SIC (Origen)
    $dsnSic = "mysql:host=" . SIC_HOST . ";dbname=" . SIC_DB . ";charset=utf8mb4";
    $pdoSrc = new PDO($dsnSic, SIC_USER, SIC_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "[OK] Conectado a Origen (" . SIC_DB . ")\n";
    
    // 3. Obtener vehículos de origen
    // Ajustar columnas según schema original (ver install_db.php legacy)
    $sqlSrc = "SELECT * FROM vehiculos"; 
    $stmtSrc = $pdoSrc->query($sqlSrc);
    $vehiculos = $stmtSrc->fetchAll();
    
    $total = count($vehiculos);
    echo "-> Encontrados $total vehículos en origen.\n";
    
    // 4. Insertar en destino
    // Limpiamos tabla primero? Mejor no, para evitar borrar datos nuevos si se corre dos veces.
    // Usaremos INSERT IGNORE o comprobaremos existencia por numero_economico
    
    $inserted = 0;
    $skipped = 0;
    
    $stmtCheck = $pdoDest->prepare("SELECT id FROM vehiculos WHERE numero_economico = ?");
    $stmtInsert = $pdoDest->prepare("
        INSERT INTO vehiculos (
            numero, numero_economico, numero_patrimonio, numero_placas, poliza,
            marca, tipo, modelo, color, numero_serie,
            resguardo_nombre, observacion_1, observacion_2,
            area_id, activo, created_at,
            region, con_logotipos, en_proceso_baja, kilometraje, telefono
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, 1, NOW(),
            ?, ?, ?, ?, ?
        )
    ");
    
    foreach ($vehiculos as $v) {
        if ($v['numero_economico'] == 'V1500-244' || stripos($v['region'] ?? '', 'laguna') !== false) {
             echo "DEBUG: Eco=" . $v['numero_economico'] . " Region=[" . ($v['region'] ?? 'NULL') . "]\n";
        }

        // Verificar si ya existe
        $stmtCheck->execute([$v['numero_economico']]);
        if ($stmtCheck->fetch()) {
            $skipped++;
            continue;
        }
        
        // Ejecutar inserción
        $stmtInsert->execute([
            $v['numero'] ?? 0,
            $v['numero_economico'],
            $v['numero_patrimonio'] ?? '',
            $v['numero_placas'],
            $v['poliza'] ?? '',
            $v['marca'],
            $v['tipo'],
            $v['modelo'] ?? '',
            $v['color'] ?? '',
            $v['numero_serie'] ?? '',
            $v['resguardo_nombre'] ?? '',
            // Mapping directly to obs 1 and 2
            $v['observacion_1'] ?? '', 
            $v['observacion_2'] ?? '',
            DEFAULT_AREA_ID,
            $v['region'] ?? 'SECOPE',
            $v['con_logotipos'] ?? 'SI',
            $v['en_proceso_baja'] ?? 'NO',
            $v['kilometraje'] ?? '',
            $v['telefono'] ?? ''
        ]);
        $inserted++;
        
        if ($inserted % 50 == 0) echo ".";
    }
    
    echo "\n\n--- RESUMEN ---\n";
    echo "Total Procesados: $total\n";
    echo "Insertados: $inserted\n";
    echo "Omitidos (Ya existían): $skipped\n";
    echo "Area Asignada: ID " . DEFAULT_AREA_ID . "\n";

} catch (PDOException $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    // Check if table 'vehiculos' exists in source
    if (strpos($e->getMessage(), "dosen't exist") !== false) { // Typo common in mysql but usually doesn't exist
       echo "Verifica que la tabla 'vehiculos' exista en la base de datos 'sic'.\n";
    }
}

function extraeObs($v) {
    $obs = [];
    if (!empty($v['observacion_1'])) $obs[] = $v['observacion_1'];
    if (!empty($v['observacion_2'])) $obs[] = $v['observacion_2'];
    return implode(" | ", $obs);
}
