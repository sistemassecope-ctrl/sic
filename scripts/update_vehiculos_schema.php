<?php
/**
 * Script de Actualización de Esquema: Vehículos v2 (Refinamiento)
 * - Agrega submenú
 * - Agrega columnas faltantes (logotipos, region, etc.)
 * - Crea tabla para histórico de bajas
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    echo "--- ACTUALIZANDO ESQUEMA DE VEHÍCULOS ---\n";

    // 1. Alterar tabla vehiculos (Añadir campos legacy faltantes)
    echo "1. Agregando columnas faltantes a 'vehiculos'...\n";
    $cols = $pdo->query("DESCRIBE vehiculos")->fetchAll(PDO::FETCH_COLUMN);
    
    $alter = [];
    if (!in_array('region', $cols)) $alter[] = "ADD COLUMN region VARCHAR(50) DEFAULT 'SECOPE' AFTER area_id";
    if (!in_array('con_logotipos', $cols)) $alter[] = "ADD COLUMN con_logotipos ENUM('SI', 'NO') DEFAULT 'SI' AFTER region";
    if (!in_array('en_proceso_baja', $cols)) $alter[] = "ADD COLUMN en_proceso_baja ENUM('SI', 'NO') DEFAULT 'NO' AFTER con_logotipos";
    if (!in_array('kilometraje', $cols)) $alter[] = "ADD COLUMN kilometraje VARCHAR(50) AFTER numero_serie";
    if (!in_array('telefono', $cols)) $alter[] = "ADD COLUMN telefono VARCHAR(50) AFTER poliza";
    
    if (!empty($alter)) {
        $pdo->exec("ALTER TABLE vehiculos " . implode(', ', $alter));
        echo "   -> Columnas agregadas.\n";
    } else {
        echo "   -> Columnas ya existían.\n";
    }

    // 2. Crear tabla vehiculos_bajas
    echo "2. Creando tabla 'vehiculos_bajas'...\n";
    $sqlBajas = "CREATE TABLE IF NOT EXISTS vehiculos_bajas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero INT,
        numero_economico VARCHAR(50),
        numero_patrimonio VARCHAR(50),
        numero_placas VARCHAR(50),
        marca VARCHAR(100),
        modelo VARCHAR(100),
        tipo VARCHAR(100),
        color VARCHAR(50),
        serie VARCHAR(100),
        
        -- Datos de Baja
        fecha_baja DATE NOT NULL,
        anio_baja YEAR,
        motivo_baja VARCHAR(255),
        
        -- Datos Legacy de Referencia
        resguardo_nombre VARCHAR(150),
        observaciones TEXT,
        region VARCHAR(50),
        
        -- Atomic Permissions
        area_id INT NULL,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_economico (numero_economico),
        FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sqlBajas);
    echo "   -> Tabla creada/verificada.\n";

    // 3. Reestructurar Menú (Submenús)
    echo "3. Reestructurando menú lateral...\n";
    
    // Obtener ID del módulo padre actual
    $stmt = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
    $stmt->execute(['Vehículos']);
    $modVehiculos = $stmt->fetch();
    
    if ($modVehiculos) {
        $parentId = $modVehiculos['id'];
        
        // Convertir módulo actual en "Contenedor" (sin ruta)
        $pdo->prepare("UPDATE modulos SET ruta = NULL, icono = 'fa-car' WHERE id = ?")->execute([$parentId]);
        
        // Crear sub-módulo: Padrón
        $stmtCheck = $pdo->prepare("SELECT id FROM modulos WHERE ruta = ?");
        $stmtCheck->execute(['/modulos/vehiculos/index.php']);
        if (!$stmtCheck->fetch()) {
            $pdo->prepare("INSERT INTO modulos (nombre_modulo, descripcion, icono, ruta, orden, estado, id_padre) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute(['Padrón Vehicular', 'Listado activo', 'fa-list', '/modulos/vehiculos/index.php', 10, 1, $parentId]);
            echo "   -> Submódulo 'Padrón Vehicular' creado.\n";
        }
        
        // Crear sub-módulo: Histórico de Bajas
        $stmtCheck->execute(['/modulos/vehiculos/bajas.php']);
        if (!$stmtCheck->fetch()) {
            $pdo->prepare("INSERT INTO modulos (nombre_modulo, descripcion, icono, ruta, orden, estado, id_padre) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute(['Histórico de Bajas', 'Archivo muerto', 'fa-history', '/modulos/vehiculos/bajas.php', 20, 1, $parentId]);
            echo "   -> Submódulo 'Histórico de Bajas' creado.\n";
        }
    }
    
    echo "--- ACTUALIZACIÓN COMPLETADA ---\n";

} catch (Exception $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}
