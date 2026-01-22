<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    echo "--- CORRIGIENDO ESQUEMA DE VEHICULOS_BAJAS ---\n";
    
    // Obtener columnas actuales
    $cols = $pdo->query("DESCRIBE vehiculos_bajas")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columnas actuales: " . implode(", ", $cols) . "\n\n";
    
    $changes = [];
    
    // 1. Renombrar 'serie' a 'numero_serie' si existe
    if (in_array('serie', $cols) && !in_array('numero_serie', $cols)) {
        $changes[] = "CHANGE COLUMN serie numero_serie VARCHAR(100)";
        echo "✓ Renombrando 'serie' -> 'numero_serie'\n";
    }
    
    // 2. Agregar observacion_1 y observacion_2 si no existen
    if (!in_array('observacion_1', $cols)) {
        $changes[] = "ADD COLUMN observacion_1 TEXT AFTER resguardo_nombre";
        echo "✓ Agregando 'observacion_1'\n";
    }
    if (!in_array('observacion_2', $cols)) {
        $changes[] = "ADD COLUMN observacion_2 TEXT AFTER observacion_1";
        echo "✓ Agregando 'observacion_2'\n";
    }
    
    // 3. Agregar campos faltantes
    if (!in_array('poliza', $cols)) {
        $changes[] = "ADD COLUMN poliza VARCHAR(100) AFTER numero_placas";
        echo "✓ Agregando 'poliza'\n";
    }
    if (!in_array('kilometraje', $cols)) {
        $changes[] = "ADD COLUMN kilometraje VARCHAR(50)";
        echo "✓ Agregando 'kilometraje'\n";
    }
    if (!in_array('telefono', $cols)) {
        $changes[] = "ADD COLUMN telefono VARCHAR(50)";
        echo "✓ Agregando 'telefono'\n";
    }
    if (!in_array('con_logotipos', $cols)) {
        $changes[] = "ADD COLUMN con_logotipos ENUM('SI','NO') DEFAULT 'NO'";
        echo "✓ Agregando 'con_logotipos'\n";
    }
    if (!in_array('vehiculo_origen_id', $cols)) {
        $changes[] = "ADD COLUMN vehiculo_origen_id INT AFTER id";
        echo "✓ Agregando 'vehiculo_origen_id'\n";
    }
    if (!in_array('usuario_baja_id', $cols)) {
        $changes[] = "ADD COLUMN usuario_baja_id INT";
        echo "✓ Agregando 'usuario_baja_id'\n";
    }
    
    // 4. Migrar datos de 'observaciones' a 'observacion_1' si existe la columna antigua
    if (in_array('observaciones', $cols) && !empty($changes)) {
        // Primero aplicamos los cambios de estructura
        if (!empty($changes)) {
            $sql = "ALTER TABLE vehiculos_bajas " . implode(", ", $changes);
            $pdo->exec($sql);
            echo "\n✓ Cambios de estructura aplicados\n";
        }
        
        // Luego migramos los datos
        echo "\n--- MIGRANDO DATOS ---\n";
        $pdo->exec("UPDATE vehiculos_bajas SET observacion_1 = observaciones WHERE observacion_1 IS NULL AND observaciones IS NOT NULL");
        echo "✓ Datos migrados de 'observaciones' -> 'observacion_1'\n";
        
        // Opcional: eliminar columna antigua
        // $pdo->exec("ALTER TABLE vehiculos_bajas DROP COLUMN observaciones");
        
    } else if (!empty($changes)) {
        $sql = "ALTER TABLE vehiculos_bajas " . implode(", ", $changes);
        $pdo->exec($sql);
        echo "\n✓ Cambios aplicados\n";
    } else {
        echo "\n✓ No se requieren cambios\n";
    }
    
    echo "\n--- ESQUEMA CORREGIDO ---\n";
    $colsFinal = $pdo->query("DESCRIBE vehiculos_bajas")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columnas finales: " . implode(", ", $colsFinal) . "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
