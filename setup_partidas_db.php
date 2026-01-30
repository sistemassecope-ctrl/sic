<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

echo "Creando tablas para Partidas Presupuestales...\n";
echo "------------------------------------------------\n";

try {
    $pdo->beginTransaction();

    // 1. Tabla Catálogo de Partidas
    $sqlCat = "CREATE TABLE IF NOT EXISTS `cat_partidas_presupuestales` (
      `id_partida` INT NOT NULL AUTO_INCREMENT,
      `clave` VARCHAR(20) NOT NULL,
      `nombre` VARCHAR(255) NOT NULL,
      `descripcion` TEXT NULL,
      `activo` TINYINT(1) DEFAULT 1,
      PRIMARY KEY (`id_partida`),
      UNIQUE KEY `clave` (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sqlCat);
    echo " > Tabla 'cat_partidas_presupuestales' verificada/creada.\n";

    // 2. Tabla Relación Programa - Partidas
    $sqlRel = "CREATE TABLE IF NOT EXISTS `programa_partidas` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `id_programa` INT NOT NULL,
      `id_partida` INT NOT NULL,
      `monto_asignado` DECIMAL(15,2) DEFAULT 0.00,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `id_programa` (`id_programa`),
      KEY `id_partida` (`id_partida`),
      FOREIGN KEY (`id_programa`) REFERENCES `programas_anuales` (`id_programa`) ON DELETE CASCADE,
      FOREIGN KEY (`id_partida`) REFERENCES `cat_partidas_presupuestales` (`id_partida`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sqlRel);
    echo " > Tabla 'programa_partidas' verificada/creada.\n";

    // 3. Insertar algunas partidas de ejemplo si está vacía
    $stmtCheck = $pdo->query("SELECT COUNT(*) FROM cat_partidas_presupuestales");
    if ($stmtCheck->fetchColumn() == 0) {
        echo " > Insertando partidas de ejemplo...\n";
        $sqlInsert = "INSERT INTO cat_partidas_presupuestales (clave, nombre, descripcion) VALUES 
        ('1000', 'SERVICIOS PERSONALES', 'Remuneraciones al personal'),
        ('2000', 'MATERIALES Y SUMINISTROS', 'Materiales de administración, alimentos, etc.'),
        ('3000', 'SERVICIOS GENERALES', 'Servicios básicos, arrendamientos, etc.'),
        ('4000', 'TRANSFERENCIAS Y SUBSIDIOS', 'Ayudas sociales, pensiones, etc.'),
        ('5000', 'BIENES MUEBLES', 'Mobiliario y equipo'),
        ('6000', 'INVERSIÓN PÚBLICA', 'Obra pública en bienes de dominio público')";
        $pdo->exec($sqlInsert);
    }

    $pdo->commit();
    echo "------------------------------------------------\n";
    echo "ESTRUCTURA DE DATOS ACTUALIZADA CORRECTAMENTE.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "!!! ERROR: " . $e->getMessage() . "\n";
}
?>