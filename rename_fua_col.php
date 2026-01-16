<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // Rename column and update ENUM values
    $sql = "ALTER TABLE fuas CHANGE tipo_fua tipo_suficiencia ENUM('NUEVA', 'REFRENDO', 'SALDO POR EJERCER', 'CONTROL') DEFAULT 'NUEVA'";
    $db->exec($sql);

    echo "Columna 'tipo_fua' renombrada a 'tipo_suficiencia' con exito.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>