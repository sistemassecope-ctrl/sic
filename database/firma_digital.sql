-- ============================================
-- SISTEMA DE FIRMA AUTÓGRAFA DIGITAL
-- Migración para tabla de firmas de empleados
-- ============================================

-- Tabla para almacenar firmas digitales de empleados
CREATE TABLE IF NOT EXISTS empleado_firmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL UNIQUE,
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
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (capturado_por) REFERENCES usuarios_sistema(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para optimización
CREATE INDEX idx_firma_empleado ON empleado_firmas(empleado_id);
CREATE INDEX idx_firma_estado ON empleado_firmas(estado);

-- Tabla de log de uso de firmas (auditoría)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_firma_log_empleado ON firma_log(empleado_id);
CREATE INDEX idx_firma_log_accion ON firma_log(accion);
CREATE INDEX idx_firma_log_fecha ON firma_log(created_at);
