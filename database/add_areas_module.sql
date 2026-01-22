-- Agregar sub-módulo de Áreas dentro de Recursos Humanos
USE pao_v2;

-- Agregar módulo de Áreas bajo RH
INSERT INTO modulos (id, nombre_modulo, clave, icono, orden, id_padre, ruta, descripcion) VALUES
(26, 'Áreas', 'rh_areas', 'fa-sitemap', 7, 2, '/modulos/recursos-humanos/areas.php', 'Estructura organizacional')
ON DUPLICATE KEY UPDATE 
    nombre_modulo = VALUES(nombre_modulo),
    ruta = VALUES(ruta),
    id_padre = VALUES(id_padre);

-- Actualizar rutas de los módulos existentes a la nueva estructura
UPDATE modulos SET ruta = '/modulos/recursos-humanos/empleados.php' WHERE id = 20;
UPDATE modulos SET ruta = '/modulos/recursos-humanos/vacaciones.php' WHERE id = 21;
UPDATE modulos SET ruta = '/modulos/recursos-humanos/incidencias.php' WHERE id = 22;
UPDATE modulos SET ruta = '/modulos/recursos-humanos/pases-salida.php' WHERE id = 23;
UPDATE modulos SET ruta = '/modulos/recursos-humanos/nomina.php' WHERE id = 24;
UPDATE modulos SET ruta = '/modulos/recursos-humanos/salarios.php' WHERE id = 25;

-- Módulos de administración
UPDATE modulos SET ruta = '/modulos/administracion/usuarios.php' WHERE id = 30;
UPDATE modulos SET ruta = '/modulos/administracion/permisos.php' WHERE id = 31;
UPDATE modulos SET ruta = '/modulos/administracion/areas.php' WHERE id = 32;
UPDATE modulos SET ruta = '/modulos/administracion/puestos.php' WHERE id = 33;
UPDATE modulos SET ruta = '/modulos/administracion/configuracion.php' WHERE id = 34;
UPDATE modulos SET ruta = '/modulos/administracion/auditoria.php' WHERE id = 35;

-- Módulos de correspondencia
UPDATE modulos SET ruta = '/modulos/correspondencia/entrada.php' WHERE id = 40;
UPDATE modulos SET ruta = '/modulos/correspondencia/salida.php' WHERE id = 41;
UPDATE modulos SET ruta = '/modulos/correspondencia/memorandums.php' WHERE id = 42;
UPDATE modulos SET ruta = '/modulos/correspondencia/circulares.php' WHERE id = 43;

-- Reportes
UPDATE modulos SET ruta = '/modulos/reportes/index.php' WHERE id = 5;

-- Dar permisos del módulo de áreas a usuarios de RH
-- Patricia (id 8) - agregar permiso de ver y editar áreas
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 8, 26, p.id FROM permisos p WHERE p.id IN (1, 2, 3);

-- jmartinez (id 2) - acceso completo a áreas
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 2, 26, p.id FROM permisos p;

-- Admin ya tiene acceso a todo
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 1, 26, p.id FROM permisos p;

SELECT 'Modulo de areas configurado correctamente' as resultado;
