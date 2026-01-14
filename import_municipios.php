<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$file_path = __DIR__ . '/comun/municipios.csv';

if (!file_exists($file_path)) {
    die("Error: No se encuentra el archivo en $file_path");
}

if (($handle = fopen($file_path, "r")) !== FALSE) {
    // Skip header
    fgetcsv($handle, 1000, ",");

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO cat_municipios (id_municipio, nombre_municipio) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE nombre_municipio = VALUES(nombre_municipio)");

        $count = 0;
        $processed_ids = [];

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // cve_municipio,municipio,localidad,cve_localidad
            if (count($data) >= 2) {
                $id = trim($data[0]);
                $nombre = trim($data[1]);

                // Ensure unique processing per execution (although DB handles duplicate key)
                if (!in_array($id, $processed_ids) && is_numeric($id) && !empty($nombre)) {
                    $stmt->execute([$id, $nombre]);
                    $processed_ids[] = $id;
                    $count++;
                }
            }
        }

        fclose($handle);
        $db->commit();
        echo "Se han importado/actualizado $count municipios únicos correctamente desde el archivo.";

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error al importar: " . $e->getMessage();
    }
} else {
    die("No se pudo abrir el archivo CSV.");
}


?>