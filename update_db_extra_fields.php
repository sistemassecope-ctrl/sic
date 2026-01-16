<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

// --- 1. Ramo ---
$db->exec("CREATE TABLE IF NOT EXISTS cat_ramos (
    id_ramo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_ramo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Datos default Ramo (ejemplos comunes)
$stmt = $db->query("SELECT COUNT(*) FROM cat_ramos");
if ($stmt->fetchColumn() == 0) {
    $db->exec("INSERT INTO cat_ramos (nombre_ramo) VALUES ('RAMO 33'), ('RAMO 28'), ('INGRESOS PROPIOS')");
}

// --- 2. Tipo Proyecto ---
$db->exec("CREATE TABLE IF NOT EXISTS cat_tipos_proyecto (
    id_tipo_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($db->query("SELECT COUNT(*) FROM cat_tipos_proyecto")->fetchColumn() == 0) {
    $db->exec("INSERT INTO cat_tipos_proyecto (nombre_tipo) VALUES ('OBRA NUEVA'), ('REHABILITACIÓN'), ('MANTENIMIENTO')");
}

// --- 3. Agregar columnas a proyectos_obra ---
$cols = $db->query("SHOW COLUMNS FROM proyectos_obra")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('id_ramo', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_ramo INT DEFAULT NULL");
    echo "Columna id_ramo agregada.<br>";
}

if (!in_array('id_tipo_proyecto', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_tipo_proyecto INT DEFAULT NULL");
    echo "Columna id_tipo_proyecto agregada.<br>";
}

echo "Base de datos actualizada con Ramos y Tipos de Proyecto.";
?>