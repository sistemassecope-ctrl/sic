<?php
require 'config/db.php';
try {
    $db = (new Database())->getConnection();
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM certificados LIKE 'id_usuario_firma'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE certificados ADD COLUMN id_usuario_firma INT NULL");
        echo "Columna id_usuario_firma agregada.\n";
    } else {
        echo "Columna id_usuario_firma ya existe.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>