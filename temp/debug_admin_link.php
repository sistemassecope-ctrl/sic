<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    // Check admin user linkage
    echo "--- Admin User Info ---\n";
    $stmt = $pdo->query("SELECT u.id, u.usuario, u.id_empleado FROM usuarios_sistema u WHERE u.usuario = 'admin'");
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($adminUser);
    
    if ($adminUser && $adminUser['id_empleado']) {
        // Check linked employee
        echo "\n--- Linked Employee Info ---\n";
        $stmt = $pdo->prepare("SELECT id, nombres, apellido_paterno, apellido_materno FROM empleados WHERE id = ?");
        $stmt->execute([$adminUser['id_empleado']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        print_r($employee);
    }
    
    // Check if a 'System Admin' employee exists
    echo "\n--- Search for 'Admin' Employee ---\n";
    $stmt = $pdo->query("SELECT id, nombres FROM empleados WHERE nombres LIKE '%Admin%' OR apellido_paterno LIKE '%Admin%'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($admins);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
