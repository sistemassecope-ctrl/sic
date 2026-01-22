<?php
/**
 * Instalador del Módulo de Vehículos (PAO v2)
 * - Crea tabla 'vehiculos' con soporte para permisos atómicos (area_id)
 * - Registra el módulo en la tabla 'modulos'
 * - Registra permisos específicos si son necesarios
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // 1. Crear Tabla VEHICULOS
    // Se agregan campos de control de PAO v2: area_id, activo, created_at, updated_at
    echo "1. Verificando tabla 'vehiculos'...\n";
    $sql = "CREATE TABLE IF NOT EXISTS vehiculos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero INT COMMENT 'N° consecutivo',
        numero_economico VARCHAR(50) NOT NULL COMMENT 'N° ECONOMICO',
        numero_patrimonio VARCHAR(50) COMMENT 'NO. PATRIMONIO',
        numero_placas VARCHAR(20) NOT NULL COMMENT 'NO. PLACAS',
        poliza VARCHAR(50) COMMENT 'POLIZA',
        marca VARCHAR(100) NOT NULL COMMENT 'MARCA',
        tipo VARCHAR(100) NOT NULL COMMENT 'TIPO',
        modelo VARCHAR(50) COMMENT 'MOD.',
        color VARCHAR(50) COMMENT 'COLOR',
        numero_serie VARCHAR(100) COMMENT 'NO.SERIE',
        
        -- Datos Legacy conservados como texto por referencia
        resguardo_nombre VARCHAR(255) COMMENT 'RESGUARDO A NOMBRE DE (Legacy)',
        observaciones TEXT COMMENT 'Observaciones generales',
        
        -- CAMPOS CRÍTICOS PARA PERMISOS ATÓMICOS
        area_id INT NULL COMMENT 'ID del Área propietaria (Row-Level Security)',
        activo TINYINT(1) DEFAULT 1 COMMENT 'Borrado lógico',
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_economico (numero_economico),
        INDEX idx_placas (numero_placas),
        INDEX idx_area (area_id),
        FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "   -> Tabla 'vehiculos' lista.\n";

    // 2. Registrar Módulo
    echo "\n2. Registrando módulo 'Vehículos'...\n";
    $stmt = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
    $stmt->execute(['Vehículos']);
    $modulo = $stmt->fetch();

    if ($modulo) {
        echo "   -> El módulo ya existe (ID: " . $modulo['id'] . ").\n";
    } else {
        // Obtenemos el último orden para ponerlo al final
        $stmtOrder = $pdo->query("SELECT MAX(orden) FROM modulos");
        $nextOrder = ($stmtOrder->fetchColumn() ?: 0) + 10;
        
        $sqlInsert = "INSERT INTO modulos (nombre_modulo, descripcion, icono, orden, estado) 
                      VALUES (?, ?, ?, ?, 1)";
        $pdo->prepare($sqlInsert)->execute([
            'Vehículos', 
            'Gestión del padrón vehicular y resguardos',
            'fa-car', // Icono FontAwesome
            $nextOrder
        ]);
        echo "   -> Módulo registrado correctamente.\n";
    }

    // 3. Permisos adicionales
    // (Por ahora usamos los permisos base: ver, crear, editar, eliminar que son globales)
    echo "\nConfiguración finalizada correctamente.\n";

} catch (PDOException $e) {
    die("ERROR FATAL: " . $e->getMessage() . "\n");
}
