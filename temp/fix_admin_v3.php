<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    // 1. Mostrar usuarios
    echo "--- Usuarios del Sistema ---\n";
    $stmt = $pdo->query("SELECT u.id, u.usuario, e.nombres, e.apellido_paterno, u.id_empleado 
                         FROM usuarios_sistema u 
                         LEFT JOIN empleados e ON u.id_empleado = e.id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "User: {$row['usuario']} (ID: {$row['id']}) -> Emp: {$row['nombres']} {$row['apellido_paterno']} (ID: {$row['id_empleado']})\n";
    }

    // 2. Intentar crear el empleado Administrativo de forma más segura
    echo "\n--- Intentando crear/vincular Administrador ---\n";
    $areaId = $pdo->query("SELECT id FROM areas LIMIT 1")->fetchColumn();
    $puestoId = $pdo->query("SELECT id FROM puestos_trabajo LIMIT 1")->fetchColumn();
    
    // Verificamos si el numero_empleado ya existe
    $chk = $pdo->query("SELECT id FROM empleados WHERE numero_empleado = 'SISTEMAS' OR nombres = 'Administrador'")->fetchColumn();
    
    if ($chk) {
        $adminEmpId = $chk;
        echo "Usando empleado existente ID: $adminEmpId\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO empleados (nombres, apellido_paterno, area_id, puesto_trabajo_id, numero_empleado, activo) 
                               VALUES ('Administrador', 'del Sistema', ?, ?, 'SISTEMAS', 1)");
        $stmt->execute([$areaId, $puestoId]);
        $adminEmpId = $pdo->lastInsertId();
        echo "Nuevo empleado creado ID: $adminEmpId\n";
    }

    // 3. Vincular
    $stmt = $pdo->prepare("UPDATE usuarios_sistema SET id_empleado = ? WHERE usuario = 'admin'");
    $stmt->execute([$adminEmpId]);
    echo "Usuario 'admin' vinculado a ID $adminEmpId exitosamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
