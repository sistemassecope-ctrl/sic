<?php
require 'config/db.php';
$db = (new Database())->getConnection();

try {
    // Verificar si la columna ya existe
    $stmt_col = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'programas_anuales' AND COLUMN_NAME = 'monto_autorizado'");
    $stmt_col->execute();
    $col_exists = $stmt_col->fetchColumn();

    if (!$col_exists) {
        $sql = "ALTER TABLE programas_anuales ADD COLUMN monto_autorizado DECIMAL(15,2) DEFAULT 0.00 AFTER descripcion";
        $db->exec($sql);
        echo "Columna 'monto_autorizado' agregada a 'programas_anuales'.";
    } else {
        echo "La columna 'monto_autorizado' ya existe.";
    }

} catch (PDOException $e) {
    echo "Error al actualizar esquema: " . $e->getMessage();
}
?>