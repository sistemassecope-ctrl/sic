-- ============================================
-- MIGRACIÓN: Sistema de Módulos Jerárquicos
-- ============================================
USE pao_v2;

-- Agregar columna clave si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'pao_v2' AND TABLE_NAME = 'modulos' AND COLUMN_NAME = 'clave') = 0,
    'ALTER TABLE modulos ADD COLUMN clave VARCHAR(50) NULL',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- LIMPIAR Y RECREAR MÓDULOS CON JERARQUÍA
-- ============================================
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM usuario_modulo_permisos;
DELETE FROM modulos;

ALTER TABLE modulos AUTO_INCREMENT = 1;

-- MÓDULOS RAÍZ
INSERT INTO modulos (id, nombre_modulo, clave, icono, orden, id_padre, ruta, descripcion) VALUES
(1, 'Dashboard', 'dashboard', 'fa-home', 1, NULL, '/index.php', 'Panel principal'),
(2, 'Recursos Humanos', 'rh', 'fa-users', 2, NULL, NULL, 'Gestion de personal'),
(3, 'Administracion', 'admin', 'fa-cog', 3, NULL, NULL, 'Configuracion del sistema'),
(4, 'Correspondencia', 'correspondencia', 'fa-envelope', 4, NULL, NULL, 'Documentos oficiales'),
(5, 'Reportes', 'reportes', 'fa-chart-bar', 5, NULL, '/reportes/index.php', 'Estadisticas');

-- SUB-MÓDULOS DE RECURSOS HUMANOS
INSERT INTO modulos (id, nombre_modulo, clave, icono, orden, id_padre, ruta, descripcion) VALUES
(20, 'Empleados', 'rh_empleados', 'fa-id-card', 1, 2, '/recursos-humanos/empleados.php', 'Gestion de empleados'),
(21, 'Vacaciones', 'rh_vacaciones', 'fa-umbrella-beach', 2, 2, '/recursos-humanos/vacaciones.php', 'Control de vacaciones'),
(22, 'Incidencias', 'rh_incidencias', 'fa-calendar-times', 3, 2, '/recursos-humanos/incidencias.php', 'Faltas y retardos'),
(23, 'Pases de Salida', 'rh_pases', 'fa-door-open', 4, 2, '/recursos-humanos/pases-salida.php', 'Pases de salida'),
(24, 'Nomina', 'rh_nomina', 'fa-money-check-alt', 5, 2, '/recursos-humanos/nomina.php', 'Calculo de nomina'),
(25, 'Salarios', 'rh_salarios', 'fa-coins', 6, 2, '/recursos-humanos/salarios.php', 'Tabulador de sueldos');

-- SUB-MÓDULOS DE ADMINISTRACIÓN
INSERT INTO modulos (id, nombre_modulo, clave, icono, orden, id_padre, ruta, descripcion) VALUES
(30, 'Usuarios', 'admin_usuarios', 'fa-user-cog', 1, 3, '/admin/usuarios.php', 'Usuarios del sistema'),
(31, 'Permisos', 'admin_permisos', 'fa-key', 2, 3, '/admin/permisos.php', 'Asignacion de permisos'),
(32, 'Areas', 'admin_areas', 'fa-sitemap', 3, 3, '/admin/areas.php', 'Estructura organizacional'),
(33, 'Puestos', 'admin_puestos', 'fa-briefcase', 4, 3, '/admin/puestos.php', 'Catalogo de puestos'),
(34, 'Configuracion', 'admin_config', 'fa-sliders-h', 5, 3, '/admin/configuracion.php', 'Parametros del sistema'),
(35, 'Auditoria', 'admin_auditoria', 'fa-history', 6, 3, '/admin/auditoria.php', 'Bitacora de actividades');

-- SUB-MÓDULOS DE CORRESPONDENCIA
INSERT INTO modulos (id, nombre_modulo, clave, icono, orden, id_padre, ruta, descripcion) VALUES
(40, 'Oficios Recibidos', 'corr_entrada', 'fa-inbox', 1, 4, '/correspondencia/entrada.php', 'Oficios de entrada'),
(41, 'Oficios Enviados', 'corr_salida', 'fa-paper-plane', 2, 4, '/correspondencia/salida.php', 'Oficios de salida'),
(42, 'Memorandums', 'corr_memos', 'fa-file-alt', 3, 4, '/correspondencia/memorandums.php', 'Memorandums internos'),
(43, 'Circulares', 'corr_circulares', 'fa-bullhorn', 4, 4, '/correspondencia/circulares.php', 'Circulares generales');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- ASIGNAR PERMISOS A USUARIOS
-- ============================================

-- Admin (id 1) - todo
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 1, m.id, p.id FROM modulos m CROSS JOIN permisos p WHERE m.estado = 1;

-- jmartinez (id 2) - RH completo
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 2, m.id, p.id FROM modulos m CROSS JOIN permisos p 
WHERE m.id IN (1, 5, 20, 21, 22, 23, 24, 25);

-- pmorales (id 8) - RH sin Salarios/Nomina
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 8, m.id, p.id FROM modulos m CROSS JOIN permisos p 
WHERE m.id IN (1, 5, 20, 21, 22, 23) AND p.id IN (1, 2, 3, 9);

-- mcastillo (id 7) - Dashboard + Reportes + Pases
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 7, m.id, p.id FROM modulos m CROSS JOIN permisos p 
WHERE m.id IN (1, 5, 23) AND p.id IN (1, 2, 9);

-- cvega (id 9) - Dashboard + Pases + Correspondencia
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 9, m.id, p.id FROM modulos m CROSS JOIN permisos p 
WHERE m.id IN (1, 23, 40, 41) AND p.id IN (1, 2, 9);

SELECT 'Migracion completada exitosamente' as resultado;
