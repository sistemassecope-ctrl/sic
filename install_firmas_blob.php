<?php
require 'config/db.php';
try {
    $db = (new Database())->getConnection();
    // Add BLOB column if it doesn't exist
    // Check if column exists first to avoid error
    $stmt = $db->query("SHOW COLUMNS FROM usuarios_config_firma LIKE 'firma_blob'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE usuarios_config_firma ADD COLUMN firma_blob LONGBLOB NULL AFTER ruta_firma_imagen");
        echo "Columna firma_blob agregada.";
    } else {
        echo "La columna firma_blob ya existe.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>