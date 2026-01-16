<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

try {
    // 1. Eliminar la Foreign Key antigua específica que causa el error
    echo "Intentando eliminar FK 'proyectos_obra_ibfk_1' que apunta a cat_unidades_responsables...<br>";
    try {
        $db->exec("ALTER TABLE proyectos_obra DROP FOREIGN KEY proyectos_obra_ibfk_1");
        echo "Éxito: FK 'proyectos_obra_ibfk_1' eliminada.<br>";
    } catch (PDOException $e) {
        echo "Info: No se pudo borrar 'proyectos_obra_ibfk_1'. Puede que ya no exista. Error: " . $e->getMessage() . "<br>";
    }

    // 2. Eliminar la constraint que puede haberse creado con mi script anterior si duplicó (solo limpieza)
    try {
        // $db->exec("ALTER TABLE proyectos_obra DROP FOREIGN KEY fk_unidad_responsable");
    } catch (Exception $e) {
    }

    // 3. Asegurar que tenemos la FK correcta apuntando a AREAS
    // Primero verificamos si ya existe para no duplicar o dar error
    // Una forma bruta pero efectiva es intentar borrar la nueva y recrearla, o simplemente intentarlo.
    // Vamos a intentar crear 'fk_proyecto_area_real'

    echo "Configurando nueva relación con tabla 'areas'...<br>";
    try {
        $db->exec("ALTER TABLE proyectos_obra ADD CONSTRAINT fk_proyecto_area_real FOREIGN KEY (id_unidad_responsable) REFERENCES areas(id)");
        echo "Éxito: Nueva FK creada correctamente apuntando a 'areas'.<br>";
    } catch (PDOException $e) {
        // Error 1005 o 1061 (Duplicate key name) es aceptable si ya la creamos antes
        echo "Nota: La FK hacia 'areas' probablemente ya existe o hubo un conflicto: " . $e->getMessage() . "<br>";
    }

} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>