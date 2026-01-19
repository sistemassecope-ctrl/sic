-- SQL Schema para módulo RH Standalone
-- Exportado desde PAO/SIC
-- Fecha: 2026-01-16

-- --------------------------------------------------------
-- Estructura de tabla `area` (antes dependencias)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `area`;
CREATE TABLE `area` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `tipo` enum('AREA','JEFATURA','DEPARTAMENTO','OFICINA','COORDINACION') DEFAULT 'AREA',
  `area_padre_id` int(11) DEFAULT NULL,
  `nivel` int(11) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_area_padre` (`area_padre_id`),
  CONSTRAINT `fk_area_padre` FOREIGN KEY (`area_padre_id`) REFERENCES `area` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------
-- Datos de ejemplo (opcional)
-- --------------------------------------------------------

INSERT INTO `area` (`id`, `nombre`, `tipo`, `area_padre_id`, `nivel`, `activo`, `descripcion`) VALUES
(1, 'Secretaría', 'AREA', NULL, 1, 1, 'Secretaría principal'),
(2, 'Dirección General', 'AREA', 1, 2, 1, 'Dirección General'),
(3, 'Recursos Humanos', 'DEPARTAMENTO', 2, 3, 1, 'Departamento de Recursos Humanos'),
(4, 'Finanzas', 'DEPARTAMENTO', 2, 3, 1, 'Departamento de Finanzas'),
(5, 'Operaciones', 'AREA', 1, 2, 1, 'Área de Operaciones');

-- --------------------------------------------------------
-- Tabla de empleados (opcional, si se requiere para el módulo completo)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `empleados`;
CREATE TABLE `empleados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_empleado` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `puesto_id` int(11) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_empleado` (`numero_empleado`),
  KEY `fk_empleado_area` (`area_id`),
  CONSTRAINT `fk_empleado_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
