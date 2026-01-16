<?php
require 'config/db.php';

try {
    $db = (new Database())->getConnection();

    $columnsToAdd = [
        "updated_by int NULL AFTER updated_at",
        "ip_address varchar(45) NULL AFTER updated_by",
        "user_agent text NULL AFTER ip_address",
        "os_info varchar(100) NULL AFTER user_agent"
    ];

    foreach ($columnsToAdd as $col) {
        // Simple check to avoid errors if run multiple times
        // We look for the column name (first word)
        $colName = explode(' ', $col)[0];
        $stmt = $db->query("SHOW COLUMNS FROM usuarios_config_firma LIKE '$colName'");

        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE usuarios_config_firma ADD COLUMN $col";
            $db->exec($sql);
            echo "Columna agregada: $colName\n";
        } else {
            echo "Columna ya existe: $colName\n";
        }
    }

    echo "Actualización de esquema completada.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>