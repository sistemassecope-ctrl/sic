<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

try {
    // 1. Drop existing FK if exists
    // We need to know the name. Assuming auto-generated or the one I created 'fk_unidad_responsable'
    try {
        $db->exec("ALTER TABLE proyectos_obra DROP FOREIGN KEY fk_unidad_responsable");
        echo "FK fk_unidad_responsable eliminada.<br>";
    } catch (PDOException $e) {
        // Maybe it has a different name or doesn't exist
        echo "Info: No se pudo eliminar FK fk_unidad_responsable (quizás no existe o tiene otro nombre).<br>";
    }

    // 2. Add new FK referencing Areas
    // Check if table areas exists first to be safe
    $res = $db->query("SHOW TABLES LIKE 'areas'");
    if ($res->rowCount() > 0) {
        // Ensure column types match. areas.id is likely INT. proyectos_obra.id_unidad_responsable is INT.
        $db->exec("ALTER TABLE proyectos_obra ADD CONSTRAINT fk_unidad_responsable_area FOREIGN KEY (id_unidad_responsable) REFERENCES areas(id)");
        echo "Nueva FK fk_unidad_responsable_area creada apuntando a tabla 'areas'.<br>";
    } else {
        echo "Error: La tabla 'areas' no existe. No se creó la FK.<br>";
    }

} catch (PDOException $e) {
    echo "Error general: " . $e->getMessage();
}
?>