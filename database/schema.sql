-- ============================================
-- SISTEMA DE CONTROL DE PERMISOS ATÓMICOS
-- Schema de Base de Datos
-- ============================================

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS pao_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pao_v2;

-- ============================================
-- PASO 0: TABLA DE ÁREAS
-- ============================================
CREATE TABLE IF NOT EXISTS areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_area VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    estado TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASO 1: TABLA DE PUESTOS
-- ============================================
CREATE TABLE IF NOT EXISTS puestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_puesto VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    nivel_jerarquico INT DEFAULT 1 COMMENT 'Nivel jerárquico del puesto (1=más bajo)',
    estado TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASO 2: TABLA DE EMPLEADOS
-- ============================================
CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    telefono VARCHAR(20) NULL,
    id_area INT NOT NULL,
    id_puesto INT NOT NULL,
    estado TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_area) REFERENCES areas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_puesto) REFERENCES puestos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASO 3: TABLA DE USUARIOS DEL SISTEMA
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL COMMENT 'Hash de la contraseña',
    tipo TINYINT(1) DEFAULT 2 COMMENT '1=Administrador, 2=Usuario',
    estado TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    ultimo_acceso DATETIME NULL,
    intentos_fallidos INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLAS ADICIONALES PARA PERMISOS GRANULARES
-- (Preparación para siguiente fase)
-- ============================================

-- Tabla de módulos del sistema
CREATE TABLE IF NOT EXISTS modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_modulo VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    icono VARCHAR(50) NULL COMMENT 'Clase de icono (ej: fa-users)',
    url_base VARCHAR(200) NULL,
    orden INT DEFAULT 0,
    estado TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos atómicos (acciones disponibles)
CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_permiso VARCHAR(100) NOT NULL,
    clave VARCHAR(50) NOT NULL UNIQUE COMMENT 'Clave única del permiso (ej: crear, editar, eliminar)',
    descripcion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación: Permisos de usuario por módulo
CREATE TABLE IF NOT EXISTS usuario_modulo_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_modulo INT NOT NULL,
    id_permiso INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios_sistema(id) ON DELETE CASCADE,
    FOREIGN KEY (id_modulo) REFERENCES modulos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module_permission (id_usuario, id_modulo, id_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación: Áreas accesibles por usuario (filtrado de información)
CREATE TABLE IF NOT EXISTS usuario_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_area INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios_sistema(id) ON DELETE CASCADE,
    FOREIGN KEY (id_area) REFERENCES areas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_area (id_usuario, id_area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- ============================================
CREATE INDEX idx_empleados_area ON empleados(id_area);
CREATE INDEX idx_empleados_puesto ON empleados(id_puesto);
CREATE INDEX idx_usuarios_estado ON usuarios_sistema(estado);
CREATE INDEX idx_usuarios_tipo ON usuarios_sistema(tipo);
