<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT DISTINCT e.puesto_trabajo_id, p.nombre 
                    FROM empleados e 
                    LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id 
                    WHERE e.puesto_trabajo_id IS NOT NULL");
while ($row = $stmt->fetch()) {
    echo "ID Used: " . $row['puesto_trabajo_id'] . " - Name: " . $row['nombre'] . "\n";
}
