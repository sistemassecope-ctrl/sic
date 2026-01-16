<?php
require_once 'config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a la base de datos.<br>";

    $sqlFile = __DIR__ . '/sql/structure_firmas_config.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("El archivo SQL no existe: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    $db->exec($sql);

    echo "Tabla 'usuarios_config_firma' creada correctamente.<br>";

} catch (PDOException $e) {
    echo "Error BD: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>