<?php
require 'config/db.php';
$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    // 1. Crear tabla `programas_anuales`
    $sql_create = "CREATE TABLE IF NOT EXISTS programas_anuales (
        id_programa INT AUTO_INCREMENT PRIMARY KEY,
        ejercicio INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        estatus ENUM('Abierto', 'Cerrado', 'En Revisión') DEFAULT 'Abierto',
        UNIQUE KEY unique_ejercicio (ejercicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_create);
    echo "Tabla 'programas_anuales' creada o verificada.<br>";

    // Insertar un programa base para el año actual si no existe
    $anio_actual = date('Y');
    $stmt_check = $db->prepare("SELECT id_programa FROM programas_anuales WHERE ejercicio = ?");
    $stmt_check->execute([$anio_actual]);
    $programa_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$programa_actual) {
        $stmt_insert = $db->prepare("INSERT INTO programas_anuales (ejercicio, nombre, descripcion) VALUES (?, ?, ?)");
        $stmt_insert->execute([$anio_actual, "Programa Operativo Anual $anio_actual", "Programa generado automáticamente."]);
        $id_programa_actual = $db->lastInsertId();
        echo "Programa base para $anio_actual creado con ID: $id_programa_actual.<br>";
    } else {
        $id_programa_actual = $programa_actual['id_programa'];
        echo "Programa base para $anio_actual ya existe (ID: $id_programa_actual).<br>";
    }

    // 2. Modificar `proyectos_obra`
    // Verificar si la columna ya existe
    $stmt_col = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proyectos_obra' AND COLUMN_NAME = 'id_programa'");
    $stmt_col->execute();
    $col_exists = $stmt_col->fetchColumn();

    if (!$col_exists) {
        // Agregar columna
        $sql_alter = "ALTER TABLE proyectos_obra 
                      ADD COLUMN id_programa INT NULL AFTER id_proyecto,
                      ADD CONSTRAINT fk_proyecto_programa FOREIGN KEY (id_programa) REFERENCES programas_anuales(id_programa) ON DELETE CASCADE ON UPDATE CASCADE;";
        $db->exec($sql_alter);
        echo "Columna 'id_programa' agregada a 'proyectos_obra' y vinculada.<br>";

        // Actualizar registros existentes para que pertenezcan al programa del año actual (o basado en su columna ejercicio si existe)
        // Asumimos que proyectos_obra ya tiene columna 'ejercicio'. Vamos a vincular basado en eso.
        $sql_update_existing = "UPDATE proyectos_obra p
                                JOIN programas_anuales pa ON p.ejercicio = pa.ejercicio
                                SET p.id_programa = pa.id_programa
                                WHERE p.id_programa IS NULL";
        $count_updates = $db->exec($sql_update_existing);
        echo "Se actualizaron $count_updates proyectos existentes vinculándolos a su programa anual correspondiente.<br>";

    } else {
        echo "La columna 'id_programa' ya existe en 'proyectos_obra'.<br>";
    }

    $db->commit();
    echo "Actualización de esquema completada con éxito.";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
?>