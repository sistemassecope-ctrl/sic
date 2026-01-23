<?php
/**
 * Script de Migración: Observaciones -> Notas
 * Migra observacion_1 y observacion_2 a la tabla vehiculos_notas
 */
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    echo "--- INICIANDO MIGRACIÓN DE OBSERVACIONES A NOTAS ---\n";
    
    // Obtener vehículos que tienen observaciones
    // (ignorando vacíos o nulos)
    $stmt = $pdo->query("
        SELECT id, numero_economico, observacion_1, observacion_2 
        FROM vehiculos 
        WHERE (observacion_1 IS NOT NULL AND TRIM(observacion_1) <> '')
           OR (observacion_2 IS NOT NULL AND TRIM(observacion_2) <> '')
    ");
    
    $vehiculos = $stmt->fetchAll();
    $total = count($vehiculos);
    echo "Encontrados {$total} vehículos con observaciones.\n";
    
    $migrados = 0;
    $notas_creadas = 0;
    
    $stmtInsert = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'ACTIVO', ?, NOW())");
    
    foreach ($vehiculos as $v) {
        $created = false;
        
        // Verificamos si ya existen notas para no duplicar en ejecuciones repetidas
        // (Aunque el usuario podría querer duplicar, mejor prevenimos duplicados exactos el mismo día si se corre 2 veces)
        // Pero para simplificar y cumplir requerimiento de "fecha de hoy", insertamos siempre
        // (Asumimos que es una migración única)
        
        // 1. Observación 1 -> Nota 1
        if (!empty(trim($v['observacion_1']))) {
            $stmtInsert->execute([$v['id'], trim($v['observacion_1'])]);
            $notas_creadas++;
            $created = true;
            // Pequeña pausa para asegurar orden si created_at tiene precisión de segundos
            usleep(100000); // 100ms
        }
        
        // 2. Observación 2 -> Nota 2
        if (!empty(trim($v['observacion_2']))) {
            $stmtInsert->execute([$v['id'], trim($v['observacion_2'])]);
            $notas_creadas++;
            $created = true;
        }
        
        if ($created) $migrados++;
        
        if ($migrados % 50 == 0) echo ".";
    }
    
    echo "\n\n✅ Proceso completado.\n";
    echo "   - Vehículos procesados: $migrados\n";
    echo "   - Notas creadas: $notas_creadas\n";
    echo "\nATENCIÓN: Los campos 'observacion_1' y 'observacion_2' NO han sido borrados de la tabla 'vehiculos'.\n";

} catch (Exception $e) {
    die("❌ ERROR: " . $e->getMessage() . "\n");
}
