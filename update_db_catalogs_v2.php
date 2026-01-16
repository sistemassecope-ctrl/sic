<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

// --- 1. Catálogo Prioridades ---
// ... (mismo código)
$sqlPrioridades = "CREATE TABLE IF NOT EXISTS cat_prioridades (
    id_prioridad INT AUTO_INCREMENT PRIMARY KEY,
    nombre_prioridad VARCHAR(100) NOT NULL,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$db->exec($sqlPrioridades);

try {
    $db->exec("ALTER TABLE cat_prioridades ADD COLUMN activo TINYINT(1) DEFAULT 1");
} catch (Exception $e) {
}

$stmt = $db->query("SELECT COUNT(*) FROM cat_prioridades");
if ($stmt->fetchColumn() == 0) {
    $db->exec("INSERT INTO cat_prioridades (nombre_prioridad) VALUES ('ALTA'), ('MEDIA'), ('BAJA')");
}

// --- 2. Manejo de Ejes para Objetivos ---
// Asegurar que existe cat_ejes y tiene al menos uno
$db->exec("CREATE TABLE IF NOT EXISTS cat_ejes (
    id_eje INT AUTO_INCREMENT PRIMARY KEY,
    nombre_eje VARCHAR(255) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$stmtEje = $db->query("SELECT id_eje FROM cat_ejes ORDER BY id_eje LIMIT 1");
$id_eje_default = $stmtEje->fetchColumn();

if (!$id_eje_default) {
    $db->exec("INSERT INTO cat_ejes (nombre_eje, descripcion) VALUES ('EJE GENERAL', 'Eje por defecto del sistema')");
    $id_eje_default = $db->lastInsertId();
}

// --- 3. Catálogo Objetivos ---
$sqlObjetivos = "CREATE TABLE IF NOT EXISTS cat_objetivos (
    id_objetivo INT AUTO_INCREMENT PRIMARY KEY,
    id_eje INT NOT NULL,
    nombre_objetivo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_eje) REFERENCES cat_ejes(id_eje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
// Si falla create porque existe, sigue.
try {
    $db->exec($sqlObjetivos);
} catch (Exception $e) {
}

try {
    $db->exec("ALTER TABLE cat_objetivos ADD COLUMN activo TINYINT(1) DEFAULT 1");
} catch (Exception $e) {
}

// Insert defaults using the valid id_eje
$stmt = $db->query("SELECT COUNT(*) FROM cat_objetivos");
if ($stmt->fetchColumn() == 0) {
    $stmtIns = $db->prepare("INSERT INTO cat_objetivos (nombre_objetivo, id_eje) VALUES (?, ?)");
    $stmtIns->execute(['INFRAESTRUCTURA', $id_eje_default]);
    $stmtIns->execute(['SOCIAL', $id_eje_default]);
    $stmtIns->execute(['URBANO', $id_eje_default]);
}

// --- 4. Actualizar Proyectos Obra ---
$cols = $db->query("SHOW COLUMNS FROM proyectos_obra")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('id_prioridad', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_prioridad INT DEFAULT NULL");
}
if (!in_array('id_objetivo', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_objetivo INT DEFAULT NULL");
}

echo "Tablas actualizadas con soporte de Ejes.";
?>