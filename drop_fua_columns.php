<?php
require_once 'config/db.php';

try {
    $db = (new Database())->getConnection();

    // Intentar borrar las columnas
    // Nota: Si una de las dos no existe, fallará toda la sentencia en MySQL.
    // Lo ideal es hacerlo una por una o verificar.
    // Pero lo haré directo asumiendo que existen.

    echo "Intentando eliminar columna 'id_tipo_obra_accion'...\n";
    try {
        $db->exec("ALTER TABLE fuas DROP COLUMN id_tipo_obra_accion");
        echo "Exito.\n";
    } catch (PDOException $e) {
        echo "Info: " . $e->getMessage() . "\n";
    }

    echo "Intentando eliminar columna 'direccion_solicitante'...\n";
    try {
        $db->exec("ALTER TABLE fuas DROP COLUMN direccion_solicitante");
        echo "Exito.\n";
    } catch (PDOException $e) {
        echo "Info: " . $e->getMessage() . "\n";
    }

    echo "Proceso finalizado.";

} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>