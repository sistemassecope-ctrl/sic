<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // Add column fecha_titular after fecha_ingreso_cotrl_ptal
    $sql = "ALTER TABLE fuas ADD COLUMN fecha_titular DATE DEFAULT NULL AFTER fecha_ingreso_cotrl_ptal";
    $db->exec($sql);

    echo "Columna 'fecha_titular' agregada correctamente.<br>";

} catch (PDOException $e) {
    echo "Error (posiblemente ya existe): " . $e->getMessage();
}
?>