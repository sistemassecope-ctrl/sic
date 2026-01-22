<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- Checking Employee 402 ---\n";
$stmt = $pdo->query("SELECT area_id, puesto_trabajo_id FROM empleados WHERE id = 402");
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($emp);

if ($emp) {
    echo "\n--- Checking Area {$emp['area_id']} ---\n";
    $stmt = $pdo->prepare("SELECT id, nombre_area FROM areas WHERE id = ?");
    $stmt->execute([$emp['area_id']]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

    echo "\n--- Checking Puesto {$emp['puesto_trabajo_id']} ---\n";
    $stmt = $pdo->prepare("SELECT id, nombre FROM puestos_trabajo WHERE id = ?");
    $stmt->execute([$emp['puesto_trabajo_id']]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
}
