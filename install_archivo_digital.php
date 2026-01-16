<?php
require_once 'config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a la base de datos.<br>";

    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/sql/structure_archivo_digital.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("El archivo SQL no existe: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Ejecutar múltiples sentencias
    // Nota: PDO::exec a veces tiene problemas con múltiples sentencias si no está configurado, 
    // pero intentaremos partirlo o ejecutarlo directo si el driver lo permite.
    // Para mayor seguridad en migraciones, partimos por punto y coma si es estructura simple.

    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $db->exec($stmt);
        }
    }

    echo "Tablas de Archivo Digital creadas correctamente.<br>";
    echo "<ul>
            <li>archivo_documentos</li>
            <li>archivo_firmas</li>
          </ul>";

} catch (PDOException $e) {
    echo "Error de Base de Datos: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>