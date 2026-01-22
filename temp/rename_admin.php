<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$stmt = $pdo->prepare("UPDATE empleados SET nombres = 'Administrador', apellido_paterno = 'del Sistema', apellido_materno = 'PAO' WHERE id = 402");
$stmt->execute();
echo "Empleado 402 renombrado a Administrador del Sistema.\n";
