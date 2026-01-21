<?php
// Script para instalar la tabla de vehículos
require_once __DIR__ . '/../../config/db.php';

try {
    $db = (new Database())->getConnection();
    
    // Eliminar tabla existente para reiniciar estructura
    $db->exec("DROP TABLE IF EXISTS vehiculos");
    
    // SQL para crear la tabla
    $sql = "CREATE TABLE IF NOT EXISTS vehiculos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero INT COMMENT 'N° consecutivo',
        numero_economico VARCHAR(50) NOT NULL COMMENT 'N° ECONOMICO',
        numero_patrimonio VARCHAR(50) COMMENT 'NO. PATRIMONIO',
        numero_placas VARCHAR(20) NOT NULL COMMENT 'NO. PLACAS',
        poliza VARCHAR(50) COMMENT 'POLIZA',
        marca VARCHAR(100) NOT NULL COMMENT 'MARCA',
        tipo VARCHAR(100) NOT NULL COMMENT 'TIPO',
        modelo VARCHAR(50) COMMENT 'MOD.',
        color VARCHAR(50) COMMENT 'COLOR',
        numero_serie VARCHAR(100) COMMENT 'NO.SERIE',
        secretaria_subsecretaria VARCHAR(255) COMMENT 'SECRETARIA/SUBSECRETARIA/DIRECCION',
        direccion_departamento VARCHAR(255) COMMENT 'DIRECCION/DEPARTAMENTO/CORRDINACION',
        resguardo_nombre VARCHAR(255) COMMENT 'RESGUARDO A NOMBRE DE:',
        factura_nombre VARCHAR(255) COMMENT 'FACTURA A NOMBRE DE',
        observacion_1 TEXT COMMENT 'OBSERVACION 1',
        observacion_2 TEXT COMMENT 'OBSERVACIONES 2',
        telefono VARCHAR(50) COMMENT 'TELEFONO',
        kilometraje VARCHAR(50) COMMENT 'KILOMETRAJE',
        region VARCHAR(50) DEFAULT 'SECOPE' COMMENT 'REGION (SECOPE/LAGUNA)',
        con_logotipos ENUM('SI', 'NO') DEFAULT 'SI' COMMENT 'CON LOGOTIPOS',
        en_proceso_baja ENUM('SI', 'NO') DEFAULT 'NO' COMMENT 'EN PROCESO DE BAJA',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_economico (numero_economico),
        INDEX idx_placas (numero_placas),
        INDEX idx_resguardo (resguardo_nombre),
        INDEX idx_region (region),
        INDEX idx_baja (en_proceso_baja)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'vehiculos' creada o verificada correctamente.\n";

} catch (PDOException $e) {
    echo "Error al crear la tabla: " . $e->getMessage() . "\n";
}
?>
