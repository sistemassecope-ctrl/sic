<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // Update ENUM for estatus to remove CONTROL
    $sql = "ALTER TABLE fuas MODIFY COLUMN estatus ENUM('ACTIVO', 'CANCELADO') DEFAULT 'ACTIVO'";
    $db->exec($sql);

    echo "Columna 'estatus' actualizada (se elimino CONTROL del ENUM) con exito.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>