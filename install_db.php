<?php
// Script de instalación automática de la base de datos
// Ejecuta el archivo sql/database.sql

require_once 'config/db.php';

echo "<h1>Instalación de Base de Datos SIS-PAO</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        echo "<p>Conexión exitosa a la base de datos 'sic'.</p>";

        $sqlFile = 'sql/database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);

            // PDO no soporta múltiples consultas por defecto en execute() de forma robusta con parámetros,
            // pero exec() sí puede ejecutar bloques SQL completos si el driver lo permite.
            // Una forma más segura es dividir por ';' pero simple es exec aqui.

            try {
                $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0); // Importante para multi-query a veces
                $db->exec($sql);
                echo "<div style='color: green; background: #e8f5e9; padding: 10px; border: 1px solid green;'>";
                echo "<h3>¡Tablas creadas correctamente!</h3>";
                echo "<p>Se han creado las tablas: usuarios, roles, permisos, bitacoras.</p>";
                echo "<p>Usuario Administrador creado: <strong>1100100</strong></p>";
                echo "</div>";
                echo "<p><a href='index.php'>Ir al Inicio de Sesión</a></p>";
            } catch (PDOException $e) {
                echo "<div style='color: red; background: #ffebee; padding: 10px; border: 1px solid red;'>";
                echo "<h3>Error al ejecutar SQL:</h3>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "</div>";
            }

        } else {
            echo "<p style='color: red;'>No se encontró el archivo sql/database.sql</p>";
        }

    } else {
        echo "<p style='color: red;'>Falló la conexión a la base de datos.</p>";
    }

} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}
?>