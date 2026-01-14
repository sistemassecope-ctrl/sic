<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Instalar tabla area desde area.sql
    if (file_exists('area.sql')) {
        echo "Importing area.sql...<br>";
        $sql = file_get_contents('area.sql');
        $db->exec($sql);
        echo "Table 'area' imported successfully.<br>";
    } else {
        echo "Error: area.sql not found.<br>";
    }

    // 2. Crear tabla area_pao
    echo "Creating table area_pao...<br>";
    $sql_pao = "CREATE TABLE IF NOT EXISTS `area_pao` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `area_id` int(11) NOT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `deleted_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `fk_area_pao` (`area_id`),
      CONSTRAINT `fk_area_pao` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_pao);
    echo "Table 'area_pao' created successfully.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>