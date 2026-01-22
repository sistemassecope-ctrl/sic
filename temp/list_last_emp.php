<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
echo "--- Ultimos 10 empleados ---\n";
$stmt = $pdo->query("SELECT id, nombres, apellido_paterno, area_id, puesto_trabajo_id FROM empleados ORDER BY id DESC LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
