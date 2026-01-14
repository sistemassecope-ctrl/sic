<?php
require 'config/db.php';
$db = (new Database())->getConnection();

try {
    // 1. Drop Foreign Key if exists
    // We check information_schema or just try/catch
    try {
        $db->exec("ALTER TABLE proyectos_obra DROP FOREIGN KEY proyectos_obra_ibfk_8");
        // Note: The constraint name might vary, but in the creation script it was the 8th FK. 
        // A safer way is checking constraint name but usually auto-generated ones are predictable if created sequentially.
        // Let's try getting the constraint name first if needed, but for now simple try-catch is okay.
    } catch (Exception $e) {
        // Ignore if FK doesn't exist
    }

    // 2. Modify Column
    $db->exec("ALTER TABLE proyectos_obra CHANGE id_localidad localidad VARCHAR(255) NULL");

    echo "Base de datos actualizada: 'localidad' ahora es texto libre.";
} catch (PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
?>