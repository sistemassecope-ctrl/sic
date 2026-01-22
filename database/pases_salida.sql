-- ============================================
-- MÓDULO: PASES DE SALIDA
-- Tabla y datos para el sistema de permisos de salida
-- ============================================
USE pao_v2;

-- Tabla de pases de salida
CREATE TABLE IF NOT EXISTS pases_salida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_salida DATE NOT NULL,
    hora_salida TIME NOT NULL,
    hora_regreso TIME NULL COMMENT 'NULL si no regresa',
    motivo TEXT NOT NULL,
    tipo ENUM('personal', 'oficial') NOT NULL DEFAULT 'personal',
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    
    -- Campos de aprobación
    id_aprobador INT NULL,
    fecha_aprobacion DATETIME NULL,
    comentario_aprobador TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_empleado) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (id_aprobador) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para optimización
CREATE INDEX idx_pases_empleado ON pases_salida(id_empleado);
CREATE INDEX idx_pases_estado ON pases_salida(estado);
CREATE INDEX idx_pases_fecha ON pases_salida(fecha_salida);

-- ============================================
-- NUEVOS PERMISOS ESPECÍFICOS PARA PASES
-- ============================================
INSERT INTO permisos (nombre_permiso, clave, descripcion) VALUES
('Ver Propios', 'ver_propios', 'Permiso para ver solo sus propios registros'),
('Aprobar', 'aprobar', 'Permiso para aprobar solicitudes de otros')
ON DUPLICATE KEY UPDATE nombre_permiso = VALUES(nombre_permiso);

-- ============================================
-- ASIGNAR PERMISOS A USUARIOS EXISTENTES
-- ============================================

-- Obtener IDs de los permisos
SET @perm_ver_propios = (SELECT id FROM permisos WHERE clave = 'ver_propios');
SET @perm_ver = (SELECT id FROM permisos WHERE clave = 'ver');
SET @perm_crear = (SELECT id FROM permisos WHERE clave = 'crear');
SET @perm_aprobar = (SELECT id FROM permisos WHERE clave = 'aprobar');

-- Módulo RH (ID 2) - Agregar permisos de pases a los asistentes
-- Patricia (pmorales) - puede ver propios, crear y aprobar en su área
SET @user_patricia = (SELECT id FROM usuarios_sistema WHERE usuario = 'pmorales');
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user_patricia, 2, @perm_ver_propios),
(@user_patricia, 2, @perm_aprobar);

-- María (mcastillo) - puede ver propios y crear
SET @user_maria = (SELECT id FROM usuarios_sistema WHERE usuario = 'mcastillo');
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user_maria, 2, @perm_ver_propios);

-- Claudia (cvega) - solo puede ver propios y crear (no tiene acceso a RH normalmente)
SET @user_claudia = (SELECT id FROM usuarios_sistema WHERE usuario = 'cvega');
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user_claudia, 2, @perm_ver_propios),
(@user_claudia, 2, @perm_crear);

-- Jefe de RH (jmartinez) - todos los permisos incluyendo aprobar
SET @user_jefe_rh = (SELECT id FROM usuarios_sistema WHERE usuario = 'jmartinez');
INSERT IGNORE INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES
(@user_jefe_rh, 2, @perm_ver_propios),
(@user_jefe_rh, 2, @perm_aprobar);

-- ============================================
-- DATOS DE EJEMPLO: Algunos pases de salida
-- ============================================
INSERT INTO pases_salida (id_empleado, fecha_salida, hora_salida, hora_regreso, motivo, tipo, estado) VALUES
-- Pases de Patricia (empleado 4 - RH)
(4, CURDATE(), '10:00:00', '12:00:00', 'Cita médica personal', 'personal', 'aprobado'),
(4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', NULL, 'Trámite bancario', 'personal', 'pendiente'),

-- Pases de María (empleado 11 - Dirección)
(11, CURDATE(), '09:00:00', '11:00:00', 'Reunión externa', 'oficial', 'pendiente'),

-- Pases de Claudia (empleado 13 - Operaciones)
(13, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', '17:00:00', 'Asunto personal', 'personal', 'pendiente'),

-- Pases de otros empleados
(1, CURDATE(), '11:00:00', '13:00:00', 'Reunión con proveedor', 'oficial', 'aprobado'),
(5, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', NULL, 'Capacitación externa', 'oficial', 'pendiente');

SELECT '✅ Tabla pases_salida creada y permisos configurados' as resultado;
