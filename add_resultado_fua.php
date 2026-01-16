<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // Add column resultado_tramite
    // It will be an ENUM. Default is 'PENDIENTE'.
    $sql = "ALTER TABLE fuas ADD COLUMN resultado_tramite ENUM('PENDIENTE', 'AUTORIZADO', 'NO AUTORIZADO') DEFAULT 'PENDIENTE' AFTER fecha_respuesta_sfa";
    $db->exec($sql);

    echo "Columna 'resultado_tramite' agregada correctamente.<br>";

} catch (PDOException $e) {
    echo "Error (posiblemente ya existe): " . $e->getMessage();
}
?>