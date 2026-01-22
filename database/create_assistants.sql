-- ============================================
-- Script para crear 3 asistentes con diferentes niveles de acceso
-- ============================================
USE pao_v2;

-- Primero verificamos los empleados y usuarios actuales
SELECT 'EMPLEADOS ACTUALES:' as info;
SELECT e.id, CONCAT(e.nombre, ' ', e.apellido_paterno) as nombre, 
       p.nombre_puesto, a.nombre_area 
FROM empleados e 
JOIN puestos p ON e.id_puesto = p.id 
JOIN areas a ON e.id_area = a.id;

SELECT 'USUARIOS ACTUALES:' as info;
SELECT u.id, u.usuario, e.nombre, p.nombre_puesto
FROM usuarios_sistema u
JOIN empleados e ON u.id_empleado = e.id
JOIN puestos p ON e.id_puesto = p.id;

-- ============================================
-- Crear 3 empleados asistentes nuevos
-- ============================================
-- Asistente 1: María - Acceso a TODAS las áreas (asignada a Dirección)
INSERT INTO empleados (nombre, apellido_paterno, apellido_materno, email, telefono, id_area, id_puesto) 
VALUES ('María Fernanda', 'Castillo', 'Ruiz', 'maria.castillo@empresa.com', '555-0011', 1, 8);

-- Asistente 2: Patricia - Solo acceso a Recursos Humanos
INSERT INTO empleados (nombre, apellido_paterno, apellido_materno, email, telefono, id_area, id_puesto) 
VALUES ('Patricia', 'Morales', 'Díaz', 'patricia.morales@empresa.com', '555-0012', 2, 8);

-- Asistente 3: Claudia - Solo acceso a Operaciones
INSERT INTO empleados (nombre, apellido_paterno, apellido_materno, email, telefono, id_area, id_puesto) 
VALUES ('Claudia Elena', 'Vega', 'Hernández', 'claudia.vega@empresa.com', '555-0013', 5, 8);

-- Obtener los IDs de los nuevos empleados
SET @asistente1_id = (SELECT id FROM empleados WHERE email = 'maria.castillo@empresa.com');
SET @asistente2_id = (SELECT id FROM empleados WHERE email = 'patricia.morales@empresa.com');
SET @asistente3_id = (SELECT id FROM empleados WHERE email = 'claudia.vega@empresa.com');

-- ============================================
-- Crear usuarios del sistema para los 3 asistentes
-- Contraseña: password123
-- ============================================
SET @pass_hash = '$2y$10$za6R7fRBSiC29h4f9.ZyylOWAYiaEc/8KidjsIb7EWnTvuMOxj4Ay.';

INSERT INTO usuarios_sistema (id_empleado, usuario, contrasena, tipo, estado) VALUES
(@asistente1_id, 'mcastillo', @pass_hash, 2, 1),
(@asistente2_id, 'pmorales', @pass_hash, 2, 1),
(@asistente3_id, 'cvega', @pass_hash, 2, 1);

-- Obtener los IDs de los usuarios creados
SET @user1_id = (SELECT id FROM usuarios_sistema WHERE usuario = 'mcastillo');
SET @user2_id = (SELECT id FROM usuarios_sistema WHERE usuario = 'pmorales');
SET @user3_id = (SELECT id FROM usuarios_sistema WHERE usuario = 'cvega');

-- ============================================
-- ASISTENTE 1 (María): Acceso a TODAS las áreas
-- Permisos: Ver y Exportar en Dashboard y Reportes para todas las áreas
-- ============================================
-- Permisos para Dashboard (módulo 1): Ver
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user1_id, 1, 1);  -- Ver en Dashboard

-- Permisos para RH (módulo 2): Ver, Crear, Exportar
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user1_id, 2, 1),  -- Ver
(@user1_id, 2, 2),  -- Crear
(@user1_id, 2, 5);  -- Exportar

-- Permisos para Administración (módulo 3): Ver, Exportar
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user1_id, 3, 1),  -- Ver
(@user1_id, 3, 5);  -- Exportar

-- Permisos para Correspondencia (módulo 4): Ver, Crear
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user1_id, 4, 1),  -- Ver
(@user1_id, 4, 2);  -- Crear

-- Permisos para Reportes (módulo 5): Ver, Exportar
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user1_id, 5, 1),  -- Ver
(@user1_id, 5, 5);  -- Exportar

-- Áreas: TODAS (1, 2, 3, 4, 5)
INSERT INTO usuario_areas (id_usuario, id_area) VALUES
(@user1_id, 1),
(@user1_id, 2),
(@user1_id, 3),
(@user1_id, 4),
(@user1_id, 5);

-- ============================================
-- ASISTENTE 2 (Patricia): Solo Recursos Humanos
-- Permisos: Ver, Crear, Exportar solo en RH y Reportes
-- ============================================
-- Permisos para Dashboard (módulo 1): Ver
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user2_id, 1, 1);  -- Ver en Dashboard

-- Permisos para RH (módulo 2): Ver, Crear, Editar, Exportar
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user2_id, 2, 1),  -- Ver
(@user2_id, 2, 2),  -- Crear
(@user2_id, 2, 3),  -- Editar
(@user2_id, 2, 5);  -- Exportar

-- Permisos para Reportes (módulo 5): Ver, Exportar
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user2_id, 5, 1),  -- Ver
(@user2_id, 5, 5);  -- Exportar

-- Áreas: Solo Recursos Humanos (área 2)
INSERT INTO usuario_areas (id_usuario, id_area) VALUES
(@user2_id, 2);

-- ============================================
-- ASISTENTE 3 (Claudia): Solo Operaciones
-- Permisos: Ver, Crear, Exportar solo en lo que compete a Operaciones
-- ============================================
-- Permisos para Dashboard (módulo 1): Ver
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user3_id, 1, 1);  -- Ver en Dashboard

-- Permisos para Correspondencia (módulo 4): Ver, Crear
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user3_id, 4, 1),  -- Ver
(@user3_id, 4, 2);  -- Crear

-- Permisos para Reportes (módulo 5): Ver, Exportar
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user3_id, 5, 1),  -- Ver
(@user3_id, 5, 5);  -- Exportar

-- Áreas: Solo Operaciones (área 5)
INSERT INTO usuario_areas (id_usuario, id_area) VALUES
(@user3_id, 5);

-- ============================================
-- Verificar la configuración
-- ============================================
SELECT '=== NUEVOS ASISTENTES CREADOS ===' as resultado;

SELECT 
    u.usuario,
    CONCAT(e.nombre, ' ', e.apellido_paterno) as nombre,
    'Asistente' as puesto,
    GROUP_CONCAT(DISTINCT a.nombre_area ORDER BY a.nombre_area SEPARATOR ', ') as areas_accesibles
FROM usuarios_sistema u
JOIN empleados e ON u.id_empleado = e.id
LEFT JOIN usuario_areas ua ON u.id = ua.id_usuario
LEFT JOIN areas a ON ua.id_area = a.id
WHERE u.usuario IN ('mcastillo', 'pmorales', 'cvega')
GROUP BY u.id, u.usuario, e.nombre, e.apellido_paterno;

SELECT '=== PERMISOS POR MÓDULO ===' as resultado;

SELECT 
    u.usuario,
    m.nombre_modulo,
    GROUP_CONCAT(p.nombre_permiso ORDER BY p.id SEPARATOR ', ') as permisos
FROM usuarios_sistema u
JOIN usuario_modulo_permisos ump ON u.id = ump.id_usuario
JOIN modulos m ON ump.id_modulo = m.id
JOIN permisos p ON ump.id_permiso = p.id
WHERE u.usuario IN ('mcastillo', 'pmorales', 'cvega')
GROUP BY u.id, u.usuario, m.id, m.nombre_modulo
ORDER BY u.usuario, m.orden;
