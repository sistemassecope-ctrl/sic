-- Normalización de Tipos de Área
USE pao_v2;

-- 1. Crear tabla tipos_area
CREATE TABLE IF NOT EXISTS tipos_area (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(100) NOT NULL UNIQUE,
    estado TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insertar tipos detectados (con corrección de acentos para visualización)
-- Usamos IGNORE para evitar duplicados si se corre varias veces
INSERT IGNORE INTO tipos_area (nombre_tipo) VALUES 
('Secretaría'),
('Subsecretaría'),
('Dirección'),
('Jefatura'),
('Área');

-- 3. Agregar columna de relación a tabla areas (si no existe)
-- Procedimiento almacenado temporal para agregar columna de forma segura
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
DELIMITER //
CREATE PROCEDURE AddColumnIfNotExists()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'areas' 
        AND COLUMN_NAME = 'id_tipo_area'
    ) THEN
        ALTER TABLE areas ADD COLUMN id_tipo_area INT NULL AFTER descripcion;
    END IF;
END //
DELIMITER ;
CALL AddColumnIfNotExists();
DROP PROCEDURE AddColumnIfNotExists;

-- 4. Actualizar la relación basada en la descripción actual
-- Mapeo manual para asegurar que 'Direccion' (sin tilde) coincida con 'Dirección' (con tilde)
UPDATE areas SET id_tipo_area = (SELECT id FROM tipos_area WHERE nombre_tipo = 'Secretaría') WHERE descripcion LIKE 'Secretaria%';
UPDATE areas SET id_tipo_area = (SELECT id FROM tipos_area WHERE nombre_tipo = 'Subsecretaría') WHERE descripcion LIKE 'Subsecretaria%';
UPDATE areas SET id_tipo_area = (SELECT id FROM tipos_area WHERE nombre_tipo = 'Dirección') WHERE descripcion LIKE 'Direccion%';
UPDATE areas SET id_tipo_area = (SELECT id FROM tipos_area WHERE nombre_tipo = 'Jefatura') WHERE descripcion LIKE 'Jefatura%';
UPDATE areas SET id_tipo_area = (SELECT id FROM tipos_area WHERE nombre_tipo = 'Área') WHERE descripcion LIKE 'Área%' OR descripcion LIKE 'Area%';

-- 5. Agregar Foreign Key
-- Primero aseguramos que no haya IDs huérfanos (no debería haber, pero por seguridad)
-- Luego agregamos la FK
DROP PROCEDURE IF EXISTS AddForeignKeyIfNotExists;
DELIMITER //
CREATE PROCEDURE AddForeignKeyIfNotExists()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'areas' 
        AND CONSTRAINT_NAME = 'fk_areas_tipo'
    ) THEN
        ALTER TABLE areas ADD CONSTRAINT fk_areas_tipo FOREIGN KEY (id_tipo_area) REFERENCES tipos_area(id) ON DELETE SET NULL;
    END IF;
END //
DELIMITER ;
CALL AddForeignKeyIfNotExists();
DROP PROCEDURE AddForeignKeyIfNotExists;

SELECT 'Normalización completada exitosamente' as resultado;
SELECT * FROM tipos_area;
SELECT id, nombre_area, id_tipo_area FROM areas LIMIT 5;
