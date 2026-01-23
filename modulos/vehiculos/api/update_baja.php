<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

requireAuth();
$pdo = getConnection();

// Check Permissions
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;
requirePermission('editar', $MODULO_ID);

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID faltante']);
    exit;
}

try {
    // Checkbox handling ('SI' if present, 'NO' if not, standard logic for checkboxes often requires checking presence)
    // But since we send JSON, we might send 'SI' or 'NO' explicitly or boolean.
    // The form JS should send 'SI' or 'NO'. If unchecked, maybe it sends nothing or 'NO'.

    $conLogotipos = isset($data['con_logotipos']) ? $data['con_logotipos'] : 'NO';
    // Ensure it's strictly SI/NO
    $conLogotipos = ($conLogotipos === 'SI' || $conLogotipos === true || $conLogotipos === 'on') ? 'SI' : 'NO';

    $sql = "UPDATE vehiculos_bajas SET 
            fecha_baja = ?, 
            motivo_baja = ?, 
            
            numero_economico = ?, 
            numero_placas = ?, 
            marca = ?, 
            modelo = ?, 
            region = ?,
            
            numero_patrimonio = ?,
            poliza = ?,
            tipo = ?,
            color = ?,
            numero_serie = ?,
            resguardo_nombre = ?,
            factura_nombre = ?,
            observacion_1 = ?,
            observacion_2 = ?,
            kilometraje = ?,
            telefono = ?,
            con_logotipos = ?
            
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['fecha_baja'],
        $data['motivo_baja'],

        $data['numero_economico'],
        $data['numero_placas'],
        $data['marca'],
        $data['modelo'],
        $data['region'],

        $data['numero_patrimonio'] ?? null,
        $data['poliza'] ?? null,
        $data['tipo'] ?? null,
        $data['color'] ?? null,
        $data['numero_serie'] ?? null,
        $data['resguardo_nombre'] ?? null,
        $data['factura_nombre'] ?? null,
        $data['observacion_1'] ?? null,
        $data['observacion_2'] ?? null,
        $data['kilometraje'] ?? null,
        $data['telefono'] ?? null,
        $conLogotipos,

        $data['id']
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
}
