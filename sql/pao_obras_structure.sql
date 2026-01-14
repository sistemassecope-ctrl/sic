-- Estructura de Base de Datos para el Programa Anual de Obra Pública (PAO)

-- =============================================
-- 1. CATÁLOGOS (Tablas de Consulta)
-- Normalizan la información para evitar errores de captura
-- =============================================

-- Catálogo de Ejes Estratégicos
CREATE TABLE IF NOT EXISTS cat_ejes (
    id_eje INT AUTO_INCREMENT PRIMARY KEY,
    nombre_eje VARCHAR(255) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Objetivos (Vinculados a un Eje)
CREATE TABLE IF NOT EXISTS cat_objetivos (
    id_objetivo INT AUTO_INCREMENT PRIMARY KEY,
    id_eje INT NOT NULL,
    nombre_objetivo TEXT NOT NULL,
    FOREIGN KEY (id_eje) REFERENCES cat_ejes(id_eje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Unidades Responsables (Dependencias ejecutoras)
CREATE TABLE IF NOT EXISTS cat_unidades_responsables (
    id_unidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre_unidad VARCHAR(255) NOT NULL,
    clave_unidad VARCHAR(50) -- Ejemplo: 'OP-2024'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Prioridades
CREATE TABLE IF NOT EXISTS cat_prioridades (
    id_prioridad INT AUTO_INCREMENT PRIMARY KEY,
    nombre_prioridad VARCHAR(50) NOT NULL -- Alta, Media, Baja
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Tipos de Proyecto
CREATE TABLE IF NOT EXISTS cat_tipos_proyectos (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(100) NOT NULL -- Obra Nueva, Mantenimiento, Rehabilitación, etc.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Ramos Presupuestales
CREATE TABLE IF NOT EXISTS cat_ramos (
    id_ramo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_ramo VARCHAR(100) NOT NULL,
    clave_ramo VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Municipios (Si se limita al estado)
CREATE TABLE IF NOT EXISTS cat_municipios (
    id_municipio INT AUTO_INCREMENT PRIMARY KEY,
    nombre_municipio VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de Localidades
CREATE TABLE IF NOT EXISTS cat_localidades (
    id_localidad INT AUTO_INCREMENT PRIMARY KEY,
    id_municipio INT NOT NULL,
    nombre_localidad VARCHAR(150) NOT NULL,
    FOREIGN KEY (id_municipio) REFERENCES cat_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 2. TABLA PRINCIPAL: PROYECTOS DE OBRA
-- Contiene toda la información del Programa Anual
-- =============================================

CREATE TABLE IF NOT EXISTS proyectos_obra (
    id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Información General
    ejercicio INT NOT NULL, -- Año o ejercicio
    nombre_proyecto TEXT NOT NULL,
    breve_descripcion TEXT,
    clave_cartera_shcp VARCHAR(100), -- Clave SHCP
    
    -- Clasificación y Vinculación
    id_unidad_responsable INT,
    id_eje INT,
    id_objetivo INT,
    id_prioridad INT,
    id_tipo_proyecto INT,
    id_ramo INT,
    
    -- Ubicación Geográfica
    id_municipio INT,
    id_localidad INT,
    
    -- Indicadores de Impacto
    impacto_proyecto TEXT, -- Descripción cualitativa
    num_beneficiarios INT DEFAULT 0,
    
    -- Estructura Financiera (Montos)
    monto_federal DECIMAL(20, 2) DEFAULT 0.00,
    monto_estatal DECIMAL(20, 2) DEFAULT 0.00,
    monto_municipal DECIMAL(20, 2) DEFAULT 0.00,
    monto_otros DECIMAL(20, 2) DEFAULT 0.00,
    
    -- Columna Calculada (Total) - Se actualiza automáticamente (MySQL 5.7+)
    monto_total DECIMAL(20, 2) GENERATED ALWAYS AS (monto_federal + monto_estatal + monto_municipal + monto_otros) STORED,

    -- Control de Gestión
    es_multianual TINYINT(1) DEFAULT 0, -- 1 = Sí, 0 = No
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_usuario_registro INT, -- Quién capturó
    
    -- Restricciones de Llave Foránea (Integridad Referencial)
    FOREIGN KEY (id_unidad_responsable) REFERENCES cat_unidades_responsables(id_unidad),
    FOREIGN KEY (id_eje) REFERENCES cat_ejes(id_eje),
    FOREIGN KEY (id_objetivo) REFERENCES cat_objetivos(id_objetivo),
    FOREIGN KEY (id_prioridad) REFERENCES cat_prioridades(id_prioridad),
    FOREIGN KEY (id_tipo_proyecto) REFERENCES cat_tipos_proyectos(id_tipo),
    FOREIGN KEY (id_ramo) REFERENCES cat_ramos(id_ramo),
    FOREIGN KEY (id_municipio) REFERENCES cat_municipios(id_municipio),
    FOREIGN KEY (id_localidad) REFERENCES cat_localidades(id_localidad)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. DATOS DE EJEMPLO (Opcional - Seeds)
-- =============================================

INSERT INTO cat_prioridades (nombre_prioridad) VALUES ('Alta'), ('Media'), ('Baja');
INSERT INTO cat_tipos_proyectos (nombre_tipo) VALUES ('Obra Nueva'), ('Rehabilitación'), ('Ampliación'), ('Mantenimiento');
