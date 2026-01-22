<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, usuario, correo, tipo, estado FROM usuarios_sistema WHERE tipo = 1 OR usuario = 'admin'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No admin users found.\n";
    } else {
        foreach ($users as $u) {
            echo "User: " . $u['usuario'] . " | Type: " . $u['tipo'] . " | Status: " . $u['estado'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
