<?php
require_once 'config/db.php';

$users_to_add = [
    ['user' => '24212', 'pass' => '123456', 'name' => 'Usuario Prueba 24212'],
    ['user' => '8784', 'pass' => '123456', 'name' => 'Usuario Prueba 8784']
];

$role_id = 1; // SuperAdmin

try {
    $db = (new Database())->getConnection();

    echo "Agregando usuarios...\n";

    foreach ($users_to_add as $userData) {
        // Check if exists
        $stmt_check = $db->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
        $stmt_check->execute([$userData['user']]);

        if ($stmt_check->rowCount() > 0) {
            echo "El usuario {$userData['user']} ya existe. Saltando.\n";
        } else {
            // Hash password
            $hashedPass = password_hash($userData['pass'], PASSWORD_BCRYPT);

            $stmt_insert = $db->prepare("INSERT INTO usuarios (usuario, password, nombre_completo, id_rol, activo) VALUES (?, ?, ?, ?, 1)");

            if ($stmt_insert->execute([$userData['user'], $hashedPass, $userData['name'], $role_id])) {
                echo "Usuario {$userData['user']} creado exitosamente con Rol ID: $role_id.\n";
            } else {
                echo "Error al crear usuario {$userData['user']}.\n";
            }
        }
    }

} catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage();
}
?>