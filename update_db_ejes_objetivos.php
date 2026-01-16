<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

// 1. Crear/Verificar tabla Ejes
$db->exec("CREATE TABLE IF NOT EXISTS cat_ejes (
    id_eje INT AUTO_INCREMENT PRIMARY KEY,
    nombre_eje VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Insertar Ejes Default si está vacía
$stmt = $db->query("SELECT COUNT(*) FROM cat_ejes");
if ($stmt->fetchColumn() == 0) {
    $db->exec("INSERT INTO cat_ejes (nombre_eje, descripcion) VALUES 
        ('EJE 1: SEGURIDAD Y JUSTICIA', 'Descripción del Eje 1'),
        ('EJE 2: BIENESTAR SOCIAL', 'Descripción del Eje 2'),
        ('EJE 3: DESARROLLO ECONÓMICO', 'Descripción del Eje 3')");
}

// 2. Crear/Verificar tabla Objetivos (con relación a Eje)
$db->exec("CREATE TABLE IF NOT EXISTS cat_objetivos (
    id_objetivo INT AUTO_INCREMENT PRIMARY KEY,
    id_eje INT NOT NULL,
    nombre_objetivo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    KEY fk_objetivo_eje (id_eje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
// Nota: No fuerzo FK estricta aquí para evitar errores si hay datos huérfanos previos, pero idealmente debería tenerla.

// 3. Agregar columnas a proyectos_obra
$cols = $db->query("SHOW COLUMNS FROM proyectos_obra")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('id_eje', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_eje INT DEFAULT NULL AFTER id_unidad_responsable");
    echo "Columna id_eje agregada.<br>";
}

if (!in_array('id_objetivo', $cols)) {
    $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_objetivo INT DEFAULT NULL AFTER id_eje");
    echo "Columna id_objetivo agregada.<br>";
}

echo "Base de datos actualizada correctamente para Ejes y Objetivos.";
?>