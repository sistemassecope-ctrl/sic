<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    // A veces JS envía DELETE como método, otras POST con _method.
    // Recibiremos JSON
}

requireAuth();

// ID del módulo de Padrón Vehicular
define('MODULO_ID', 45);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
if (!in_array('eliminar', $permisos_user)) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar registros.']);
    exit;
}

$pdo = getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Primero borrar notas asociadas (ya que no hay CASCADE en BD para evitar problemas con ID)
    $stmtNotasDel = $pdo->prepare("DELETE FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen = 'ACTIVO'");
    $stmtNotasDel->execute([$id]);

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
