-- Estructura para el Sistema de Archivo Digital

-- 1. Tabla Maestra de Documentos
CREATE TABLE IF NOT EXISTS archivo_documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    
    -- Metadatos de origen
    modulo_origen VARCHAR(50) NOT NULL COMMENT 'Ej: PAO, RECURSOS_FINANCIEROS, JURIDICO',
    referencia_id INT NOT NULL COMMENT 'ID del registro origen (id_proyecto, id_fua, etc)',
    tipo_documento VARCHAR(50) NOT NULL COMMENT 'Ej: OFICIO_SOLICITUD, INFORME_TECNICO',
    
    -- Metadatos del archivo
    nombre_archivo_original VARCHAR(255) NOT NULL,
    ruta_almacenamiento VARCHAR(255) NOT NULL COMMENT 'Ruta relativa desde el root de uploads',
    hash_contenido CHAR(64) NOT NULL COMMENT 'SHA-256 del contenido binario del archivo',
    tamano_bytes BIGINT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT 'application/pdf',
    
    -- Estado
    estado ENUM('BORRADOR', 'EN_FIRMA', 'FIRMADO', 'CANCELADO') DEFAULT 'BORRADOR',
    
    -- Auditoría
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario_creador INT NOT NULL,
    
    FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Firmas
CREATE TABLE IF NOT EXISTS archivo_firmas (
    id_firma INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT NOT NULL,
    id_usuario INT NOT NULL,
    
    -- Rol en el momento de la firma
    rol_firmante VARCHAR(50) NOT NULL COMMENT 'Ej: DIRECTOR, REVISOR, ELABORO',
    
    -- Datos de la firma
    fecha_firma DATETIME DEFAULT CURRENT_TIMESTAMP,
    hash_firma VARCHAR(255) NOT NULL COMMENT 'Hash criptográfico de la firma',
    metadata_firma JSON NULL COMMENT 'IP, User Agent, ID Transacción, etc',
    
    -- Estado de la firma
    estado ENUM('VALIDA', 'REVOCADA') DEFAULT 'VALIDA',
    
    FOREIGN KEY (id_documento) REFERENCES archivo_documentos(id_documento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para búsqueda rápida
CREATE INDEX idx_archivo_uuid ON archivo_documentos(uuid);
CREATE INDEX idx_archivo_origen ON archivo_documentos(modulo_origen, referencia_id);
