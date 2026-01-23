<?php
/**
 * Script de instalación para Bitácora de Notas de Vehículos (V2 - Flexible)
 * - Soporta notas para vehículos activos y bajas (tipo_origen)
 * - Sin Foreign Key estricta para permitir mover registros entre tablas
 */
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    echo "--- RE-INSTALANDO SISTEMA DE NOTAS ---\n";
    
    // 1. Drop tabla anterior si existe (Para aplicar cambios limpios)
    $pdo->exec("DROP TABLE IF EXISTS vehiculos_notas");
    
    // 2. Crear tabla vehiculos_notas modificada
    $sql = "CREATE TABLE vehiculos_notas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL COMMENT 'ID del registro en la tabla origen',
        tipo_origen ENUM('ACTIVO', 'BAJA') DEFAULT 'ACTIVO' COMMENT 'Indica si el ID es de vehiculos o vehiculos_bajas',
        nota TEXT NOT NULL,
        imagen_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_lookup (vehiculo_id, tipo_origen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "✅ Tabla 'vehiculos_notas' recreada con soporte para ACTIVO/BAJA.\n";
    
    // 3. Directorios (ya deberían existir pero verificamos)
    $uploadDir = __DIR__ . '/../modulos/vehiculos/notas/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    echo "--- INSTALACIÓN COMPLETADA ---\n";

} catch (Exception $e) {
    die("❌ ERROR: " . $e->getMessage() . "\n");
}
