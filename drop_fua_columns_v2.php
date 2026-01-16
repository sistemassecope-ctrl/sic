<?php
require_once 'config/db.php';

try {
    $db = (new Database())->getConnection();

    // 1. Eliminar Foreign Key if exists
    echo "Intentando eliminar Foreign Key 'fuas_ibfk_2'...\n";
    try {
        $db->exec("ALTER TABLE fuas DROP FOREIGN KEY fuas_ibfk_2");
        echo "FK Eliminada.\n";
    } catch (PDOException $e) {
        echo "FK Info (puede que no exista): " . $e->getMessage() . "\n";
        // Intentar con el nombre genérico si el nombre específico falla, aunque el error dio el nombre exacto.
    }

    // 2. Eliminar Columna id_tipo_obra_accion
    echo "Intentando eliminar columna 'id_tipo_obra_accion'...\n";
    try {
        $db->exec("ALTER TABLE fuas DROP COLUMN id_tipo_obra_accion");
        echo "Columna Eliminada.\n";
    } catch (PDOException $e) {
        echo "Error Columna: " . $e->getMessage() . "\n";
    }

    echo "Proceso finalizado.";

} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>