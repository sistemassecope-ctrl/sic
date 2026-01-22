<?php
require_once __DIR__ . '/../config/database.php'; // Correct path

try {
    $pdo = getConnection();
    
    // 1. List current admins
    echo "--- Current Admins ---\n";
    $stmt = $pdo->query("SELECT id, usuario, tipo, estado FROM usuarios_sistema WHERE usuario = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Found user 'admin' (ID: {$admin['id']}). Status: {$admin['estado']}\n";
        
        // 2. Reset password to 'admin123'
        $newPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET contrasena = ?, estado = 1, intentos_fallidos = 0 WHERE id = ?");
        $stmt->execute([$newPass, $admin['id']]);
        echo "Password for 'admin' has been reset to: admin123\n";
    } else {
        echo "User 'admin' not found. Creating it...\n";
        
        // Create admin user if not exists
        // Need an employee link? Since we enforce id_empleado, we might need to link it to a dummy or existing employee.
        // Let's check for an employee first.
        $emp = $pdo->query("SELECT id FROM empleados LIMIT 1")->fetch();
        if (!$emp) {
            die("Error: No employees found to link the admin user to.\n");
        }
        
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios_sistema (usuario, contrasena, tipo, estado, id_empleado, created_at) VALUES ('admin', ?, 1, 1, ?, NOW())");
        $stmt->execute([$pass, $emp['id']]);
        echo "Created user 'admin' linked to employee ID {$emp['id']} with password: admin123\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
