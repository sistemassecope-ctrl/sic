<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

function addActiveColumn($db, $table)
{
    try {
        $cols = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('activo', $cols)) {
            $db->exec("ALTER TABLE $table ADD COLUMN activo TINYINT(1) DEFAULT 1");
            echo "Columna 'activo' agregada a tabla '$table'.<br>";
        } else {
            echo "Columna 'activo' ya existe en tabla '$table'.<br>";
        }
    } catch (PDOException $e) {
        echo "Error verificando '$table': " . $e->getMessage() . "<br>";
    }
}

addActiveColumn($db, 'cat_ejes');
addActiveColumn($db, 'cat_objetivos');
addActiveColumn($db, 'cat_prioridades');
addActiveColumn($db, 'cat_ramos');
addActiveColumn($db, 'cat_tipos_proyecto');

echo "Verificación de columnas completada.";
?>