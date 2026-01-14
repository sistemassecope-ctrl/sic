<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión expirada']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 3) {
    echo json_encode([]);
    exit;
}

$pdo = conectarDB();
$like = '%' . $q . '%';
$stmt = $pdo->prepare("SELECT id, numero_empleado, CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) AS nombre, email
                       FROM empleados
                       WHERE activo = 1 AND (
                           numero_empleado LIKE ? OR
                           nombres LIKE ? OR
                           apellido_paterno LIKE ? OR
                           apellido_materno LIKE ?
                       )
                       ORDER BY nombres ASC LIMIT 10");
$stmt->execute([$like, $like, $like, $like]);

$resultados = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $resultados[] = [
        'id' => (int) $row['id'],
        'numero_empleado' => $row['numero_empleado'],
        'nombre' => trim($row['nombre']),
        'email' => $row['email']
    ];
}

echo json_encode($resultados);
