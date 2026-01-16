<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a BD...<br>";

    // 1. Crear tabla catalogo fuentes
    $sqlCreate = "CREATE TABLE IF NOT EXISTS cat_fuentes_financiamiento (
        id_fuente INT AUTO_INCREMENT PRIMARY KEY,
        anio INT NOT NULL,
        abreviatura VARCHAR(50) NOT NULL,
        nombre_fuente VARCHAR(255) NOT NULL,
        activo TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sqlCreate);
    echo "Tabla 'cat_fuentes_financiamiento' creada.<br>";

    // 2. Insertar valores iniciales (Los que tenías hardcoded)
    $sqlInsert = "INSERT IGNORE INTO cat_fuentes_financiamiento (anio, abreviatura, nombre_fuente, activo) VALUES 
        (2025, 'INGRESOS PROPIOS', 'INGRESOS PROPIOS DEL GOBIERNO', 1),
        (2025, 'PEFM', 'PROGRAMA ESTATAL DE FONDO METROPOLITANO', 1),
        (2025, 'FAFEF', 'FONDO DE APORTACIONES PARA EL FORTALECIMIENTO DE LAS ENTIDADES FEDERATIVAS', 1)";
    $db->exec($sqlInsert);
    echo "Datos iniciales insertados.<br>";

    // 3. Modificar tabla FUAS: Cambiar ENUM a VARCHAR para aceptar los nuevos valores dinamicos
    // Primero, hacemos un cambio temporal a TEXT para no perder datos por conflictos de ENUM
    $db->exec("ALTER TABLE fuas MODIFY COLUMN fuente_recursos VARCHAR(100) DEFAULT NULL");

    echo "Columna 'fuente_recursos' en tabla 'fuas' convertida a VARCHAR.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>