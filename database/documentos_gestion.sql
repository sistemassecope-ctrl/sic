-- ============================================
-- MIGRACIÓN: Sistema de Gestión Documental
-- Archivo: database/documentos_gestion.sql
-- Descripción: Tablas para el ciclo de vida de documentos, 
--              bitácora inmutable y flujo de firmas.
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Catálogo de Tipos de Documento
CREATE TABLE IF NOT EXISTS cat_tipos_documento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código único, ej: SUFPRE, OFICIO',
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    
    -- Configuración del flujo
    requiere_aprobacion TINYINT(1) DEFAULT 1,
    requiere_firma TINYINT(1) DEFAULT 1,
    tipo_firma_default ENUM('pin', 'fiel', 'ambas') DEFAULT 'pin',
    plantilla_flujo_id INT NULL COMMENT 'Plantilla predeterminada',
    
    -- Generación de folio
    prefijo_folio VARCHAR(10) NULL COMMENT 'Prefijo para folios',
    ultimo_folio INT DEFAULT 0,
    
    -- Plantilla PDF
    plantilla_pdf VARCHAR(255) NULL COMMENT 'Ruta a plantilla TCPDF',
    
    -- Control
    activo TINYINT(1) DEFAULT 1,
    orden_menu TINYINT DEFAULT 99,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla Maestra de Documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento_id INT NOT NULL,
    folio_sistema VARCHAR(50) NOT NULL UNIQUE COMMENT 'Folio automático del sistema',
    folio_oficial VARCHAR(50) NULL COMMENT 'Folio oficial (si aplica)',
    titulo VARCHAR(255) NOT NULL,
    contenido_json JSON NULL COMMENT 'Datos dinámicos del documento',
    archivo_pdf VARCHAR(255) NULL COMMENT 'Ruta al PDF generado',
    hash_integridad VARCHAR(64) NULL COMMENT 'SHA-256 del contenido',
    
    -- Ciclo de vida
    fase_actual ENUM('generacion', 'aprobacion', 'firmas', 'gestion', 'resuelto', 'cancelado') 
        DEFAULT 'generacion',
    estatus ENUM('borrador', 'pendiente', 'en_proceso', 'aprobado', 'rechazado', 'firmado', 'resuelto', 'cancelado') 
        DEFAULT 'borrador',
    prioridad ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    
    -- Referencias
    usuario_generador_id INT NOT NULL,
    usuario_aprobador_id INT NULL,
    documento_padre_id INT NULL COMMENT 'Para vincular respuestas/anexos',
    
    -- Fechas clave
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME NULL,
    fecha_primera_firma DATETIME NULL,
    fecha_ultima_firma DATETIME NULL,
    fecha_resolucion DATETIME NULL,
    fecha_limite DATETIME NULL COMMENT 'Fecha máxima para resolución',
    
    -- Resolución
    resultado_final ENUM('positivo', 'negativo', 'parcial', 'cancelado') NULL,
    observaciones_finales TEXT NULL,
    
    -- Metadatos
    metadata_json JSON NULL COMMENT 'Datos adicionales flexibles',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tipo_documento_id) REFERENCES cat_tipos_documento(id),
    FOREIGN KEY (usuario_generador_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (usuario_aprobador_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (documento_padre_id) REFERENCES documentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bitácora de Vida (Immutable Log)
CREATE TABLE IF NOT EXISTS documento_bitacora (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    
    -- Transición de estado
    fase_anterior ENUM('generacion', 'aprobacion', 'firmas', 'gestion', 'resuelto', 'cancelado') NULL,
    fase_nueva ENUM('generacion', 'aprobacion', 'firmas', 'gestion', 'resuelto', 'cancelado') NULL,
    estatus_anterior VARCHAR(50) NULL,
    estatus_nuevo VARCHAR(50) NULL,
    
    -- Acción
    accion VARCHAR(100) NOT NULL COMMENT 'Ej: CREAR, APROBAR, FIRMAR, RECHAZAR, DELEGAR',
    descripcion TEXT NOT NULL,
    observaciones TEXT NULL,
    
    -- Auditoría
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Firma electrónica (si aplica)
    firma_tipo ENUM('ninguna', 'pin', 'fiel') DEFAULT 'ninguna',
    firma_hash VARCHAR(255) NULL,
    certificado_serial VARCHAR(100) NULL,
    
    -- Timestamp inmutable
    timestamp_evento DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6),
    
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Flujo de Firmas Dinámico
CREATE TABLE IF NOT EXISTS documento_flujo_firmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    orden TINYINT NOT NULL COMMENT 'Orden en la cadena de firmas',
    firmante_id INT NOT NULL COMMENT 'ID del usuario firmante',
    rol_firmante VARCHAR(100) NULL,
    
    estatus ENUM('pendiente', 'firmado', 'rechazado', 'delegado', 'vencido') DEFAULT 'pendiente',
    tipo_firma ENUM('pin', 'fiel') DEFAULT 'pin',
    
    fecha_asignacion DATETIME NULL,
    fecha_firma DATETIME NULL,
    fecha_limite DATETIME NULL,
    
    tipo_respuesta ENUM('aprobado', 'aprobado_con_observaciones', 'rechazado', 'solicita_cambios') NULL,
    observaciones TEXT NULL,
    
    firma_pin_hash VARCHAR(64) NULL,
    firma_fiel_hash VARCHAR(255) NULL,
    certificado_serial VARCHAR(100) NULL,
    sello_tiempo DATETIME NULL,
    
    delegado_a INT NULL,
    fecha_delegacion DATETIME NULL,
    motivo_delegacion TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (firmante_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (delegado_a) REFERENCES usuarios_sistema(id),
    UNIQUE KEY uk_doc_orden (documento_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bandeja Universal de Documentos
CREATE TABLE IF NOT EXISTS usuario_bandeja_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    documento_id INT NOT NULL,
    tipo_accion_requerida ENUM('aprobar', 'firmar', 'revisar', 'responder', 'gestionar', 'informativo', 'vencido') NOT NULL,
    prioridad TINYINT DEFAULT 2,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_limite DATETIME NULL,
    leido TINYINT(1) DEFAULT 0,
    fecha_lectura DATETIME NULL,
    procesado TINYINT(1) DEFAULT 0,
    fecha_proceso DATETIME NULL,
    origen VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    UNIQUE KEY uk_usuario_doc (usuario_id, documento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Plantillas de Flujo
CREATE TABLE IF NOT EXISTS flujo_plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    tipo_documento_id INT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flujo_plantilla_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    orden TINYINT NOT NULL,
    actor_usuario_id INT NULL,
    actor_rol VARCHAR(50) NULL,
    tipo_firma ENUM('pin', 'fiel') DEFAULT 'pin',
    tiempo_maximo_horas INT DEFAULT 48,
    puede_delegar TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (plantilla_id) REFERENCES flujo_plantillas(id),
    FOREIGN KEY (actor_usuario_id) REFERENCES usuarios_sistema(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Índices de optimización
CREATE INDEX idx_doc_tipo ON documentos(tipo_documento_id);
CREATE INDEX idx_doc_fase ON documentos(fase_actual);
CREATE INDEX idx_bitacora_doc ON documento_bitacora(documento_id);
CREATE INDEX idx_flujo_doc ON documento_flujo_firmas(documento_id);
CREATE INDEX idx_bandeja_usuario ON usuario_bandeja_documentos(usuario_id, procesado);

-- Datos iniciales del catálogo
INSERT INTO cat_tipos_documento (codigo, nombre, descripcion, tipo_firma_default, prefijo_folio) VALUES
('SUFPRE', 'Suficiencia Presupuestal', 'Solicitud de suficiencia presupuestal para proyectos de obra', 'fiel', 'SP'),
('OFICIO', 'Oficio', 'Oficio oficial interno o externo', 'fiel', 'OF'),
('MEMO', 'Memorándum', 'Comunicación interna', 'pin', 'MEM'),
('MINUTA', 'Minuta de Reunión', 'Acta de reunión de trabajo', 'pin', 'MIN'),
('CONTRATO', 'Contrato', 'Contrato o convenio', 'fiel', 'CONT'),
('VIATICO', 'Solicitud de Viáticos', 'Solicitud de gastos de viaje', 'pin', 'VIA');
