<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

// 1. Create Catalog Table if not exists
$sqlCat = "CREATE TABLE IF NOT EXISTS cat_unidades_responsables (
    id_unidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre_unidad VARCHAR(255) NOT NULL,
    clave_unidad VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $db->exec($sqlCat);
    echo "Tabla cat_unidades_responsables verificada.<br>";
} catch (PDOException $e) {
    die("Error creando tabla: " . $e->getMessage());
}

// 2. Insert Default Data if empty
$stmt = $db->query("SELECT COUNT(*) FROM cat_unidades_responsables");
if ($stmt->fetchColumn() == 0) {
    $sqlInsert = "INSERT INTO cat_unidades_responsables (nombre_unidad, clave_unidad) VALUES 
    ('DIRECCIÓN DE CAMINOS', 'DC'),
    ('DIRECCIÓN DE EDIFICACIÓN', 'DE'),
    ('ADMINISTRACIÓN', 'ADM'),
    ('PLANEACIÓN', 'PLA')";
    $db->exec($sqlInsert);
    echo "Datos por defecto insertados en cat_unidades_responsables.<br>";
}

// 3. Add Column to proyectos_obra if not exists
try {
    $res = $db->query("SHOW COLUMNS FROM proyectos_obra LIKE 'id_unidad_responsable'");
    if ($res->rowCount() == 0) {
        $db->exec("ALTER TABLE proyectos_obra ADD COLUMN id_unidad_responsable INT DEFAULT NULL AFTER clave_cartera_shcp");
        $db->exec("ALTER TABLE proyectos_obra ADD CONSTRAINT fk_unidad_responsable FOREIGN KEY (id_unidad_responsable) REFERENCES cat_unidades_responsables(id_unidad)");
        echo "Columna id_unidad_responsable agregada a proyectos_obra.<br>";
    } else {
        echo "La columna id_unidad_responsable ya existe.<br>";
    }
} catch (PDOException $e) {
    echo "Error alterando tabla proyectos_obra: " . $e->getMessage();
}

echo "Migración completada.";
?>