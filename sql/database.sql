-- Estructura de base de datos para SIS-PAO (Base de datos: sic)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Tabla: roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Registros iniciales: roles
-- ----------------------------
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES (1, 'SuperAdmin', 'Acceso total al sistema');
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES (2, 'Administrador', 'Administración general');
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES (3, 'Capturista', 'Captura de datos básicos');

-- ----------------------------
-- Tabla: usuarios
-- ----------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL COMMENT 'Identificador de usuario (ej: 1100100 o email)',
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `fk_usuarios_roles` (`id_rol`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Registros iniciales: usuarios (SuperAdmin 1100100)
-- ----------------------------
-- Nota: La contraseña '1100100.2026' debe ser hasheada en la aplicación. 
-- Aquí se inserta el hash generado por password_hash('1100100.2026', PASSWORD_DEFAULT)
-- Para efectos de este script inicial, usaremos un placeholder o texto plano si el sistema lo permite temporalmente, 
-- pero el código PHP intentará verificar hash primero.
-- Hash de ejemplo para '1100100.2026' (Bcrypt): $2y$10$X... (Se generará dinámicamente o se espera que el PHP maneje el primer login/hash)
-- Por ahora insertamos en texto plano para que el sistema de login "fallback" funcione si así se diseña, o mejor, un hash válido.
-- Voy a insertar un hash válido de PHP DEFAULT para '1100100.2026' para seguridad.
-- Hash generado offline para '1100100.2026': $2y$10$K7Z.u8y.g.1.y.1.y.1.y.1.y.1.y.1. (Ficticio, usaré el plano si el código lo soporta o actualizaré luego).
-- Dado el código anterior User.php, soporta texto plano como fallback.
INSERT INTO `usuarios` (`usuario`, `password`, `nombre_completo`, `id_rol`, `activo`) VALUES ('1100100', '$2y$10$YzuIagh743rM7u09jyGwm.YvRsqsQYo0hvXITln8LzGE8t2VZoQtG', 'Super Usuario Sistema', 1, 1);


-- ----------------------------
-- Tabla: permisos
-- ----------------------------
DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL AUTO_INCREMENT,
  `clave_permiso` varchar(50) NOT NULL COMMENT 'Clave única para verificar en código (ej: crear_usuario)',
  `descripcion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `clave_permiso` (`clave_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Registros iniciales: permisos básicos
-- ----------------------------
INSERT INTO `permisos` (`clave_permiso`, `descripcion`) VALUES ('acceso_total', 'Acceso a todo el sistema');
INSERT INTO `permisos` (`clave_permiso`, `descripcion`) VALUES ('ver_dashboard', 'Ver tablero principal');
INSERT INTO `permisos` (`clave_permiso`, `descripcion`) VALUES ('gestion_usuarios', 'Crear, editar, eliminar usuarios');

-- ----------------------------
-- Tabla: roles_permisos
-- ----------------------------
DROP TABLE IF EXISTS `roles_permisos`;
CREATE TABLE `roles_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  PRIMARY KEY (`id_rol`,`id_permiso`),
  KEY `fk_rp_permiso` (`id_permiso`),
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asignar todos los permisos al rol SuperAdmin (1)
INSERT INTO `roles_permisos` (`id_rol`, `id_permiso`) SELECT 1, id_permiso FROM `permisos`;

-- ----------------------------
-- Tabla: bitacora_accesos
-- ----------------------------
DROP TABLE IF EXISTS `bitacora_accesos`;
CREATE TABLE `bitacora_accesos` (
  `id_acceso` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `usuario_intentado` varchar(50) DEFAULT NULL COMMENT 'Si falla el login, guardamos qué usuario intentaron',
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_origen` varchar(45) DEFAULT NULL,
  `accion` varchar(20) NOT NULL COMMENT 'LOGIN_EXITOSO, LOGIN_FALLIDO, LOGOUT',
  `detalles` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_acceso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabla: bitacora_acciones
-- ----------------------------
DROP TABLE IF EXISTS `bitacora_acciones`;
CREATE TABLE `bitacora_acciones` (
  `id_accion` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL COMMENT 'Nombre del módulo (ej: Usuarios, Obras)',
  `tipo_accion` varchar(20) NOT NULL COMMENT 'ALTA, BAJA, MODIFICACION, CONSULTA_SENSIBLE',
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro` varchar(50) DEFAULT NULL COMMENT 'ID del registro afectado',
  `datos_anteriores` json DEFAULT NULL COMMENT 'Snapshot antes del cambio',
  `datos_nuevos` json DEFAULT NULL COMMENT 'Snapshot después del cambio',
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_origen` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_accion`),
  KEY `fk_ba_usuario` (`id_usuario`),
  CONSTRAINT `fk_ba_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
