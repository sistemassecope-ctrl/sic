<?php
/**
 * Script de migración para el sistema de Firma Digital
 * Ejecutar una sola vez para crear las tablas necesarias
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Migración de Firma Digital ===\n\n";

try {
    $pdo = getConnection();
    
    // Tabla principal de firmas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS empleado_firmas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            firma_imagen LONGTEXT NOT NULL COMMENT 'Imagen de firma en formato Base64 (PNG)',
            pin_hash VARCHAR(255) NOT NULL COMMENT 'Hash del PIN de 4 dígitos',
            fecha_captura DATETIME NOT NULL COMMENT 'Fecha y hora de captura de la firma',
            capturado_por INT NOT NULL COMMENT 'ID del usuario (Superadmin) que capturó la firma',
            ultima_modificacion_pin DATETIME NULL COMMENT 'Última fecha de cambio de PIN',
            intentos_fallidos INT DEFAULT 0 COMMENT 'Intentos fallidos de PIN',
            bloqueado_hasta DATETIME NULL COMMENT 'Bloqueo temporal por múltiples intentos fallidos',
            estado TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_empleado_firma (empleado_id),
            FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (capturado_por) REFERENCES usuarios_sistema(id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabla 'empleado_firmas' creada o ya existe\n";
    
    // Tabla de log de uso de firmas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS firma_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            accion ENUM('FIRMA_ESTAMPADA', 'PIN_VERIFICADO', 'PIN_CAMBIADO', 'INTENTO_FALLIDO', 'FIRMA_CAPTURADA') NOT NULL,
            documento_referencia VARCHAR(255) NULL COMMENT 'Referencia al documento firmado',
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            detalles JSON NULL COMMENT 'Detalles adicionales de la acción',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabla 'firma_log' creada o ya existe\n";
    
    // Índices
    try {
        $pdo->exec("CREATE INDEX idx_firma_empleado ON empleado_firmas(empleado_id)");
        echo "[OK] Índice 'idx_firma_empleado' creado\n";
    } catch (Exception $e) {
        echo "[INFO] Índice 'idx_firma_empleado' ya existe\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_firma_estado ON empleado_firmas(estado)");
        echo "[OK] Índice 'idx_firma_estado' creado\n";
    } catch (Exception $e) {
        echo "[INFO] Índice 'idx_firma_estado' ya existe\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_firma_log_empleado ON firma_log(empleado_id)");
        echo "[OK] Índice 'idx_firma_log_empleado' creado\n";
    } catch (Exception $e) {
        echo "[INFO] Índice 'idx_firma_log_empleado' ya existe\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_firma_log_accion ON firma_log(accion)");
        echo "[OK] Índice 'idx_firma_log_accion' creado\n";
    } catch (Exception $e) {
        echo "[INFO] Índice 'idx_firma_log_accion' ya existe\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_firma_log_fecha ON firma_log(created_at)");
        echo "[OK] Índice 'idx_firma_log_fecha' creado\n";
    } catch (Exception $e) {
        echo "[INFO] Índice 'idx_firma_log_fecha' ya existe\n";
    }
    
    echo "\n=== Migración completada exitosamente ===\n";
    
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
