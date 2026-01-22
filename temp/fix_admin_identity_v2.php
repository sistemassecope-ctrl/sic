<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    // 1. Verificar quién es el empleado #1
    $stmt = $pdo->query("SELECT id, nombres, apellido_paterno FROM empleados WHERE id = 1");
    $emp1 = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Empleado ID 1: " . ($emp1 ? $emp1['nombres'] . " " . $emp1['apellido_paterno'] : "No existe") . "\n";

    // 2. Buscar si ya creamos un 'Administrador del Sistema'
    $stmt = $pdo->query("SELECT id, nombres FROM empleados WHERE nombres = 'Administrador' AND apellido_paterno = 'del Sistema'");
    $adminEmp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminEmp) {
        echo "Creando nuevo registro de empleado para el Administrador...\n";
        // Necesitamos un area_id y puesto_id válidos
        $area = $pdo->query("SELECT id FROM areas LIMIT 1")->fetchColumn();
        $puesto = $pdo->query("SELECT id FROM puestos_trabajo LIMIT 1")->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO empleados (nombres, apellido_paterno, apellido_materno, area_id, puesto_trabajo_id, numero_empleado, estatus, rol_sistema, activo, fecha_creacion) VALUES ('Administrador', 'del Sistema', 'PAO', ?, ?, 'ADMIN-PAO', 'ACTIVO', 'admin_global', 1, NOW())");
        $stmt->execute([$area, $puesto]);
        $adminEmpId = $pdo->lastInsertId();
    } else {
        $adminEmpId = $adminEmp['id'];
        echo "Ya existe el empleado administrador con ID: $adminEmpId\n";
    }

    // 3. Actualizar la tabla usuarios_sistema para que el usuario 'admin' apunte al nuevo empleado
    $stmt = $pdo->prepare("UPDATE usuarios_sistema SET id_empleado = ? WHERE usuario = 'admin'");
    $stmt->execute([$adminEmpId]);
    
    echo "Usuario 'admin' vinculado al empleado ID: $adminEmpId\n";
    
    // 4. Verificación final
    $stmt = $pdo->query("SELECT u.usuario, e.nombres, e.apellido_paterno 
                         FROM usuarios_sistema u 
                         JOIN empleados e ON u.id_empleado = e.id 
                         WHERE u.usuario = 'admin'");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Resultado final: Usuario '{$res['usuario']}' es '{$res['nombres']} {$res['apellido_paterno']}'\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
