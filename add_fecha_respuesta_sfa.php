<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // Add column fecha_respuesta_sfa after fecha_acuse_antes_fa
    $sql = "ALTER TABLE fuas ADD COLUMN fecha_respuesta_sfa DATE DEFAULT NULL AFTER fecha_acuse_antes_fa";
    $db->exec($sql);

    echo "Columna 'fecha_respuesta_sfa' agregada correctamente.<br>";

} catch (PDOException $e) {
    echo "Error (posiblemente ya existe): " . $e->getMessage();
}
?>