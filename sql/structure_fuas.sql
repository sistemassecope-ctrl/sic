-- Tabla para los FUAs (Formatos Únicos de Atención o similar)
-- Estructura solicitada:

-- 1. Catálogo para "Tipo De Obra/O Accion"
CREATE TABLE IF NOT EXISTS cat_tipos_fua_accion (
    id_tipo_accion INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo_accion VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar valores iniciales si está vacío (Dummy data o esperar input real)
INSERT IGNORE INTO cat_tipos_fua_accion (nombre_tipo_accion) VALUES 
('OBRA NUEVA'), 
('MANTENIMIENTO'), 
('REHABILITACIÓN');

-- 2. Tabla Principal FUAS
CREATE TABLE IF NOT EXISTS fuas (
    id_fua INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relación con Proyectos
    id_proyecto INT DEFAULT NULL, -- Campo "Proyecto (Catalogo)"
    
    -- Campos solicitados
    estatus ENUM('CANCELADO', 'ACTIVO', 'CONTROL') DEFAULT 'ACTIVO',
    nombre_proyecto_accion TEXT, -- "Nombre Del Proyecto O Acción"
    tipo_fua ENUM('NUEVA', 'SALDO POR EJERCER', 'CONTROL') DEFAULT 'NUEVA',
    
    no_oficio_entrada VARCHAR(100) DEFAULT NULL,
    fecha_ingreso_admvo DATE DEFAULT NULL,
    fecha_ingreso_cotrl_ptal DATE DEFAULT NULL,
    fecha_acuse_antes_fa DATE DEFAULT NULL,
    oficio_desf_ya VARCHAR(100) DEFAULT NULL,
    
    importe DECIMAL(20, 2) DEFAULT 0.00,
    
    direccion_solicitante ENUM('CAMINOS', 'EDIFICACION', 'PROYECTOS DE CAMINOS', 'PROYECTOS DE EDIFICACIÓN', 'ADMINISTRACIÓN') DEFAULT NULL,
    fuente_recursos ENUM('INGRESOS PROPIOS', 'PEFM', 'FAFEF') DEFAULT NULL,
    
    id_tipo_obra_accion INT DEFAULT NULL, -- "Tipo De Obra/O Accion" (Catalogo)
    
    folio_fua VARCHAR(100) DEFAULT NULL,
    clave_presupuestal VARCHAR(100) DEFAULT NULL,
    
    tarea VARCHAR(255) DEFAULT NULL,
    observaciones TEXT,
    
    documentos_adjuntos TEXT, -- Ruta o JSON de archivos
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_proyecto) REFERENCES proyectos_obra(id_proyecto) ON DELETE SET NULL,
    FOREIGN KEY (id_tipo_obra_accion) REFERENCES cat_tipos_fua_accion(id_tipo_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
