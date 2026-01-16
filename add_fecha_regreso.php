<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // Add column fecha_firma_regreso after fecha_titular
    $sql = "ALTER TABLE fuas ADD COLUMN fecha_firma_regreso DATE DEFAULT NULL AFTER fecha_titular";
    $db->exec($sql);

    echo "Columna 'fecha_firma_regreso' agregada correctamente.<br>";

} catch (PDOException $e) {
    echo "Error (posiblemente ya existe): " . $e->getMessage();
}
?>