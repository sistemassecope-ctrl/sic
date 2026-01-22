<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    $sql = "ALTER TABLE vehiculos_bajas 
            ADD COLUMN IF NOT EXISTS numero_patrimonio VARCHAR(100),
            ADD COLUMN IF NOT EXISTS poliza VARCHAR(100),
            ADD COLUMN IF NOT EXISTS tipo VARCHAR(100),
            ADD COLUMN IF NOT EXISTS color VARCHAR(50),
            ADD COLUMN IF NOT EXISTS numero_serie VARCHAR(100),
            ADD COLUMN IF NOT EXISTS resguardo_nombre VARCHAR(255),
            ADD COLUMN IF NOT EXISTS observacion_1 TEXT,
            ADD COLUMN IF NOT EXISTS observacion_2 TEXT,
            ADD COLUMN IF NOT EXISTS kilometraje VARCHAR(50),
            ADD COLUMN IF NOT EXISTS telefono VARCHAR(50),
            ADD COLUMN IF NOT EXISTS con_logotipos ENUM('SI','NO') DEFAULT 'NO'";
            
    $pdo->exec($sql);
    echo "Columnas agregadas a vehiculos_bajas correctamente.\n";
    
    // Verificación
    $cols = $pdo->query("DESCRIBE vehiculos_bajas")->fetchAll(PDO::FETCH_COLUMN);
    print_r($cols);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
