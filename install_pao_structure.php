<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$sql_file = __DIR__ . '/sql/pao_obras_structure.sql';

if (!file_exists($sql_file)) {
    die("Error: No se encuentra el archivo SQL en $sql_file");
}

$sql = file_get_contents($sql_file);

try {
    // Enable exceptions
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Execute the SQL
    $db->exec($sql);
    echo "Estructura de PAO Obra Pública instalada correctamente.\n";
    echo "Tablas creadas: cat_ejes, cat_objetivos, cat_unidades_responsables, cat_prioridades, cat_tipos_proyectos, cat_ramos, cat_municipios, cat_localidades, proyectos_obra.";
} catch (PDOException $e) {
    echo "Error al instalar estructura: " . $e->getMessage();
}
?>