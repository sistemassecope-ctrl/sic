<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

// --- 1. Catálogo Prioridades ---
$sqlPrioridades = "CREATE TABLE IF NOT EXISTS cat_prioridades (
    id_prioridad INT AUTO_INCREMENT PRIMARY KEY,
    nombre_prioridad VARCHAR(100) NOT NULL,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$db->exec($sqlPrioridades);

// Add 'activo' if missing
try {
    $db->exec("ALTER TABLE cat_prioridades ADD COLUMN activo TINYINT(1) DEFAULT 1");
} catch (Exception $e) { /* Column likely exists */
}

// Insert defaults if empty
$stmt = $db->query("SELECT COUNT(*) FROM cat_prioridades");
if ($stmt->fetchColumn() == 0) {
    $db->exec("INSERT INTO cat_prioridades (nombre_prioridad) VALUES ('ALTA'), ('MEDIA'), ('BAJA')");
}

// --- 2. Catálogo Objetivos (Simplificado para este requerimiento) ---
// Nota: Si ya existía con id_eje, lo respetaremos pero lo haremos nullable si es necesario para simplificar,
// o crearemos una estructura simple si no existe.
$sqlObjetivos = "CREATE TABLE IF NOT EXISTS cat_objetivos (
    id_objetivo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_objetivo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$db->exec($sqlObjetivos);

try {
    $db->exec("ALTER TABLE cat_objetivos ADD COLUMN activo TINYINT(1) DEFAULT 1");
} catch (Exception $e) { /* Column exists */
}

// Insert defaults if empty
$stmt = $db->query("SELECT COUNT(*) FROM cat_objetivos");
if ($stmt->fetchColumn() == 0) {
    $db->exec("INSERT INTO cat_objetivos (nombre_objetivo) VALUES ('MEJORAR LA INFRAESTRUCTURA'), ('ATENCIÓN SOCIAL'), ('DESARROLLO URBANO')");
}

// --- 3. Actualizar Proyectos Obra ---
$cols = $db->query("SHOW COLUMNS FROM proyectos_obra")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('id_prioridad', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_prioridad INT DEFAULT NULL");
    // Add FK later or allow loose coupling to avoid strict errors during setup
}

if (!in_array('id_objetivo', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_objetivo INT DEFAULT NULL");
}

echo "Tablas de catálogos y columnas en proyectos actualizadas correctamente.";
?>