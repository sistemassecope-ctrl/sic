-- Configuración de Firma Digital por Usuario
CREATE TABLE IF NOT EXISTS usuarios_config_firma (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    
    -- PIN de seguridad (Hashed)
    pin_firma VARCHAR(255) DEFAULT NULL COMMENT 'Hash del PIN de 6 dígitos',
    
    -- Imagen de la rúbrica
    ruta_firma_imagen VARCHAR(255) DEFAULT NULL COMMENT 'Ruta a la imagen de la firma (PNG transparente)',
    
    -- Metadatos
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
