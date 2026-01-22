-- ============================================
-- DATOS DE EJEMPLO - SEED DATA
-- ============================================
USE pao_v2;

-- ============================================
-- INSERTAR ÁREAS (5 áreas de ejemplo)
-- ============================================
INSERT INTO areas (nombre_area, descripcion) VALUES
('Dirección General', 'Oficina de dirección y toma de decisiones estratégicas'),
('Recursos Humanos', 'Gestión del capital humano y nóminas'),
('Tecnologías de Información', 'Desarrollo, soporte técnico e infraestructura'),
('Administración y Finanzas', 'Control financiero, presupuesto y compras'),
('Operaciones', 'Área operativa y de servicios');

-- ============================================
-- INSERTAR PUESTOS (8 puestos de ejemplo)
-- ============================================
INSERT INTO puestos (nombre_puesto, descripcion, nivel_jerarquico) VALUES
('Director General', 'Máxima autoridad de la organización', 10),
('Subdirector', 'Segundo nivel de mando', 9),
('Jefe de Departamento', 'Responsable de área', 7),
('Coordinador', 'Coordinación de proyectos y equipo', 6),
('Analista Senior', 'Analista con experiencia', 5),
('Analista', 'Análisis y desarrollo de actividades', 4),
('Auxiliar Administrativo', 'Apoyo administrativo', 3),
('Asistente', 'Asistencia general', 2);

-- ============================================
-- INSERTAR 10 EMPLEADOS
-- ============================================
INSERT INTO empleados (nombre, apellido_paterno, apellido_materno, email, telefono, id_area, id_puesto) VALUES
-- Empleado 1: Director General - Dirección General
('Carlos Alberto', 'García', 'Mendoza', 'carlos.garcia@empresa.com', '555-0001', 1, 1),

-- Empleado 2: Subdirector - Dirección General
('María Elena', 'López', 'Hernández', 'maria.lopez@empresa.com', '555-0002', 1, 2),

-- Empleado 3: Jefe de RH - Recursos Humanos
('Juan Pablo', 'Martínez', 'Sánchez', 'juan.martinez@empresa.com', '555-0003', 2, 3),

-- Empleado 4: Analista RH - Recursos Humanos
('Ana Patricia', 'Rodríguez', 'Torres', 'ana.rodriguez@empresa.com', '555-0004', 2, 6),

-- Empleado 5: Jefe de TI - Tecnologías de Información
('Roberto', 'Hernández', 'Gómez', 'roberto.hernandez@empresa.com', '555-0005', 3, 3),

-- Empleado 6: Desarrollador Senior - Tecnologías de Información
('Laura Ivonne', 'Sánchez', 'Pérez', 'laura.sanchez@empresa.com', '555-0006', 3, 5),

-- Empleado 7: Jefe de Finanzas - Administración y Finanzas
('Fernando', 'Torres', 'Ramírez', 'fernando.torres@empresa.com', '555-0007', 4, 3),

-- Empleado 8: Auxiliar Contable - Administración y Finanzas
('Gabriela', 'Pérez', 'López', 'gabriela.perez@empresa.com', '555-0008', 4, 7),

-- Empleado 9: Coordinador Operaciones - Operaciones
('Miguel Ángel', 'Ramírez', 'García', 'miguel.ramirez@empresa.com', '555-0009', 5, 4),

-- Empleado 10: Asistente Operaciones - Operaciones
('Sandra Luz', 'Gómez', 'Martínez', 'sandra.gomez@empresa.com', '555-0010', 5, 8);

-- ============================================
-- INSERTAR USUARIOS DEL SISTEMA
-- (Contraseña por defecto: "password123" hasheada con password_hash)
-- ============================================
INSERT INTO usuarios_sistema (id_empleado, usuario, contrasena, tipo, estado) VALUES
-- Administrador del sistema (Director General)
(1, 'admin', '$2y$10$8KzO1xG9h2Q3vY4XrC5z6eB7W8mN9pL0aK1jD2fS3gH4iJ5kM6nO7', 1, 1),

-- Usuario con acceso total (Subdirector)
(2, 'mlopez', '$2y$10$8KzO1xG9h2Q3vY4XrC5z6eB7W8mN9pL0aK1jD2fS3gH4iJ5kM6nO7', 1, 1),

-- Jefe de RH
(3, 'jmartinez', '$2y$10$8KzO1xG9h2Q3vY4XrC5z6eB7W8mN9pL0aK1jD2fS3gH4iJ5kM6nO7', 2, 1),

-- Jefe de TI
(5, 'rhernandez', '$2y$10$8KzO1xG9h2Q3vY4XrC5z6eB7W8mN9pL0aK1jD2fS3gH4iJ5kM6nO7', 2, 1),

-- Jefe de Finanzas
(7, 'ftorres', '$2y$10$8KzO1xG9h2Q3vY4XrC5z6eB7W8mN9pL0aK1jD2fS3gH4iJ5kM6nO7', 2, 1),

-- Coordinador Operaciones
(9, 'mramirez', '$2y$10$8KzO1xG9h2Q3vY4XrC5z6eB7W8mN9pL0aK1jD2fS3gH4iJ5kM6nO7', 2, 1);

-- ============================================
-- INSERTAR MÓDULOS DEL SISTEMA
-- ============================================
INSERT INTO modulos (nombre_modulo, descripcion, icono, url_base, orden) VALUES
('Dashboard', 'Panel principal del sistema', 'fa-dashboard', '/dashboard', 1),
('Recursos Humanos', 'Gestión de empleados y nómina', 'fa-users', '/recursos-humanos', 2),
('Administración', 'Control administrativo y financiero', 'fa-building', '/administracion', 3),
('Correspondencia', 'Gestión de documentos y oficios', 'fa-envelope', '/correspondencia', 4),
('Reportes', 'Generación de reportes del sistema', 'fa-chart-bar', '/reportes', 5),
('Configuración', 'Configuración del sistema', 'fa-cog', '/configuracion', 6);

-- ============================================
-- INSERTAR PERMISOS ATÓMICOS
-- ============================================
INSERT INTO permisos (nombre_permiso, clave, descripcion) VALUES
('Ver', 'ver', 'Permiso para visualizar información'),
('Crear', 'crear', 'Permiso para crear nuevos registros'),
('Editar', 'editar', 'Permiso para modificar registros existentes'),
('Eliminar', 'eliminar', 'Permiso para eliminar registros'),
('Exportar', 'exportar', 'Permiso para exportar datos/reportes'),
('Validar', 'validar', 'Permiso para validar documentos'),
('Firmar', 'firmar', 'Permiso para firmar documentos oficiales'),
('Aprobar', 'aprobar', 'Permiso para aprobar solicitudes/trámites');

-- ============================================
-- ASIGNAR PERMISOS A USUARIOS (Ejemplos)
-- ============================================
-- Admin tiene todos los permisos en todos los módulos
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 1, m.id, p.id FROM modulos m CROSS JOIN permisos p;

-- Subdirector también tiene todos los permisos
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 2, m.id, p.id FROM modulos m CROSS JOIN permisos p;

-- Jefe de RH: permisos completos en módulo RH, solo ver en otros
INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
SELECT 3, 2, p.id FROM permisos p; -- Todos los permisos en RH

INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso)
VALUES (3, 1, 1), (3, 5, 1), (3, 5, 5); -- Ver dashboard, ver y exportar reportes

-- ============================================
-- ASIGNAR ÁREAS ACCESIBLES A USUARIOS
-- ============================================
-- Admin y Subdirector pueden ver todas las áreas
INSERT INTO usuario_areas (id_usuario, id_area)
SELECT 1, a.id FROM areas a;

INSERT INTO usuario_areas (id_usuario, id_area)
SELECT 2, a.id FROM areas a;

-- Jefe de RH solo puede ver su área
INSERT INTO usuario_areas (id_usuario, id_area) VALUES (3, 2);

-- Jefe de TI solo puede ver su área
INSERT INTO usuario_areas (id_usuario, id_area) VALUES (4, 3);

-- Jefe de Finanzas solo puede ver su área
INSERT INTO usuario_areas (id_usuario, id_area) VALUES (5, 4);

-- Coordinador Operaciones solo puede ver su área
INSERT INTO usuario_areas (id_usuario, id_area) VALUES (6, 5);
