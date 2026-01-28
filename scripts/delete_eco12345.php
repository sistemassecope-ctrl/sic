<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();

$vehiculo_id = 99;

echo "Eliminando registros de prueba para ECO-12345 (vehiculo_id=99)...\n";

// 1. Notas
$del1 = $pdo->exec("DELETE FROM vehiculos_notas WHERE vehiculo_id = $vehiculo_id");
echo "Notas eliminadas: $del1\n";

// 2. Solicitudes de baja
$del2 = $pdo->exec("DELETE FROM solicitudes_baja WHERE vehiculo_id = $vehiculo_id");
echo "Solicitudes eliminadas: $del2\n";

// 3. Histórico de bajas
$del3 = $pdo->exec("DELETE FROM vehiculos_bajas WHERE vehiculo_origen_id = $vehiculo_id");
echo "Bajas históricas eliminadas: $del3\n";

// 4. Vehículo
$del4 = $pdo->exec("DELETE FROM vehiculos WHERE id = $vehiculo_id");
echo "Vehículo eliminado: $del4\n";

echo "\n¡Limpieza completada!\n";
