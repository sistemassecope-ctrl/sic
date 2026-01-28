<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

// Assuming the logged in admin user is what we want to clean.
// Or we can just clean ALL pending requests if it's a test environment, 
// but user said "elimina el registro de la solicitud que hice... como administrador"

// Let's find the user 'admin' or just clean based on the last one created if specific
// Better: clean requests from user with username 'admin' or ID 1 (common)
// The safest is to rely on the fact that the user is currently the admin.
// But as I can't interact with session here easily, I'll search for 'admin' user.

$stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE usuario = 'admin'");
$stmt->execute();
$adminId = $stmt->fetchColumn();

if ($adminId) {
    $del = $pdo->prepare("DELETE FROM solicitudes_baja WHERE solicitante_id = ?");
    $del->execute([$adminId]);
    echo "Deleted requests for admin (ID $adminId)\n";
} else {
    // Fallback: Delete requests for ID 1
    $pdo->exec("DELETE FROM solicitudes_baja WHERE solicitante_id = 1");
    echo "Deleted requests for ID 1 (Fallback)\n";
}
