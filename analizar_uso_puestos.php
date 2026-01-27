<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();

$sql = "SELECT p.nombre, p.id, COUNT(e.id) as total_empleados 
        FROM puestos_trabajo p
        LEFT JOIN empleados e ON e.puesto_trabajo_id = p.id
        WHERE p.nombre IN (
            SELECT nombre FROM puestos_trabajo GROUP BY nombre HAVING COUNT(*) > 1
        )
        GROUP BY p.nombre, p.id
        ORDER BY p.nombre, p.id";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Análisis de empleados por ID de puesto duplicado:\n";
foreach ($results as $r) {
    if ($r['total_empleados'] > 0) {
        echo "- {$r['nombre']} (ID: {$r['id']}): {$r['total_empleados']} empleados\n";
    }
}
