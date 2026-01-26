<?php
require_once __DIR__ . '/config/database.php';

function generateTempPassword($length = 10) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*';
    
    $password = '';
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];
    
    $all = $uppercase . $lowercase . $numbers . $symbols;
    for ($i = 0; $i < $length - 3; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    return str_shuffle($password);
}


try {
    $targetDomain = '@durango.gob.mx'; // Cambiar esto si el dominio es diferente (ej: @durango.gob, @secope.gob.mx)
    
    $pdo = getConnection();
    $pdo->beginTransaction();

    // 1. Delete all users except 'admin'
    echo "Deleting existing users (except admin)...\n";
    $stmt = $pdo->prepare("DELETE FROM usuarios_sistema WHERE usuario != 'admin'");
    $stmt->execute();
    echo "Deleted " . $stmt->rowCount() . " users.\n";

    // 2. Fetch eligible employees
    echo "Fetching employees with $targetDomain in email_institucional...\n";
    $stmt = $pdo->prepare("SELECT id, nombres, apellido_paterno, apellido_materno, email_institucional FROM empleados WHERE email_institucional LIKE ?");
    $stmt->execute(['%' . $targetDomain . '%']);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($employees) . " employees.\n";

    $createdUsers = [];
    $insertStmt = $pdo->prepare("INSERT INTO usuarios_sistema (id_empleado, usuario, contrasena, tipo, estado, cambiar_password) VALUES (?, ?, ?, 2, 1, 1)");

    foreach ($employees as $emp) {
        $tempPassword = generateTempPassword(10);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        $username = $emp['email_institucional']; // Use institutional email as username
        
        // Execute insert
        $insertStmt->execute([
            $emp['id'],
            $username,
            $hashedPassword
        ]);

        $createdUsers[] = [
            'id_empleado' => $emp['id'],
            'nombre_completo' => $emp['nombres'] . ' ' . $emp['apellido_paterno'] . ' ' . $emp['apellido_materno'],
            'usuario' => $username,
            'password_temporal' => $tempPassword
        ];
    }

    $pdo->commit();
    echo "Users created successfully.\n\n";

    // Output table
    echo str_pad("ID Emp", 8) . " | " . str_pad("Usuario", 30) . " | " . str_pad("Password", 12) . " | " . "Nombre" . "\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($createdUsers as $u) {
        echo str_pad($u['id_empleado'], 8) . " | " . 
             str_pad($u['usuario'], 30) . " | " . 
             str_pad($u['password_temporal'], 12) . " | " . 
             $u['nombre_completo'] . "\n";
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
