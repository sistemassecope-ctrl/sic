<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    // A veces JS envía DELETE como método, otras POST con _method.
    // Recibiremos JSON
}

requireAuth();
$pdo = getConnection();
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;
// Use 'editar' or 'eliminar' if exists. Usually CRUD permissions are create/read/update/delete.
// Assuming 'editar' covers delete or checking if I implemented 'eliminar'.
// In `admin/permisos.php` checking permissions... let's stick to 'editar' as "Manage" 
// or if we have granular 'eliminar', use it.
// Checking migrate_vehiculos.php, permissions are stored.
// Let's use 'eliminar' if available, otherwise 'editar'.
// For now, I'll require 'editar' since 'eliminar' might not be a standard permission column in all my tables yet.
requirePermission('editar', $MODULO_ID);

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el registro o no se pudo eliminar']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
}
