<?php
// Script to install the FUAS structure
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();

    $sqlFile = __DIR__ . '/sql/structure_fuas.sql';
    if (!file_exists($sqlFile)) {
        die("Error: SQL file not found at $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Execute SQL
    $db->exec($sql);

    echo "FUA structure installed successfully.<br>";
    echo "Tables 'cat_tipos_fua_accion' and 'fuas' created/checked.";

} catch (PDOException $e) {
    echo "Error installing DB: " . $e->getMessage();
}
?>