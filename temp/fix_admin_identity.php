<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    // 1. Find a suitable Area (e.g., Dirección General, Sistemas, or similar)
    $stmt = $pdo->query("SELECT id, nombre_area FROM areas WHERE nombre_area LIKE '%Sistemas%' OR nombre_area LIKE '%Tecnolog%' OR nombre_area LIKE '%Administra%' LIMIT 1");
    $area = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$area) {
        $stmt = $pdo->query("SELECT id, nombre_area FROM areas LIMIT 1");
        $area = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 2. Find a suitable Position
    $stmt = $pdo->query("SELECT id, nombre FROM puestos_trabajo WHERE nombre LIKE '%Director%' OR nombre LIKE '%Jefe%' LIMIT 1");
    $puesto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$puesto) {
        $stmt = $pdo->query("SELECT id, nombre FROM puestos_trabajo LIMIT 1");
        $puesto = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo "Selected Area: " . $area['nombre_area'] . " (ID: " . $area['id'] . ")\n";
    echo "Selected Puesto: " . $puesto['nombre'] . " (ID: " . $puesto['id'] . ")\n";
    
    // 3. Create 'System Admin' Employee
    $sql = "INSERT INTO empleados (
        nombres, apellido_paterno, apellido_materno, 
        area_id, puesto_trabajo_id, 
        numero_empleado, estatus, rol_sistema, activo, fecha_creacion
    ) VALUES (
        'Administrador', 'del Sistema', 'PAO',
        ?, ?,
        'ADMIN001', 'ACTIVO', 'admin_global', 1, NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$area['id'], $puesto['id']]);
    $newEmpId = $pdo->lastInsertId();
    
    echo "Created new Admin Employee with ID: $newEmpId\n";
    
    // 4. Link 'admin' user to this new employee
    $stmt = $pdo->prepare("UPDATE usuarios_sistema SET id_empleado = ? WHERE usuario = 'admin'");
    $stmt->execute([$newEmpId]);
    
    echo "Successfully linked user 'admin' to Employee ID $newEmpId\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
