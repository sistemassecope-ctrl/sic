<?php
require_once 'config/db.php';

try {
    $db = (new Database())->getConnection();

    // 1. Crear tabla usuarios_permisos
    echo "Creando tabla usuarios_permisos...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `usuarios_permisos` (
      `id_usuario` int(11) NOT NULL,
      `id_permiso` int(11) NOT NULL,
      PRIMARY KEY (`id_usuario`, `id_permiso`),
      KEY `fk_up_permiso` (`id_permiso`),
      CONSTRAINT `fk_up_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
      CONSTRAINT `fk_up_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'usuarios_permisos' creada correctamente.\n";

    // 2. Insertar el permiso específico mencionado por el usuario
    echo "Insertando permiso 'capturar_programas_operativos'...\n";
    $permSql = "INSERT IGNORE INTO permisos (clave_permiso, descripcion) VALUES ('capturar_programas_operativos', 'Permite acceder a la captura de Programas Operativos')";
    $db->exec($permSql);

    echo "Permiso creado.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>