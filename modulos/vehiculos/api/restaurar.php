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
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;
requirePermission('editar', $MODULO_ID);

$data = json_decode(file_get_contents('php://input'), true);
$bajaId = isset($data['id']) ? (int) $data['id'] : 0;

if (!$bajaId) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener datos del histórico
    $stmtB = $pdo->prepare("SELECT * FROM vehiculos_bajas WHERE id = ?");
    $stmtB->execute([$bajaId]);
    $baja = $stmtB->fetch(PDO::FETCH_ASSOC);

    if (!$baja) {
        throw new Exception("Registro histórico no encontrado.");
    }

    // 2. Insertar en vehículos (MOVER de vuelta al padrón)
    $stmtIns = $pdo->prepare("
        INSERT INTO vehiculos (
            numero, numero_economico, numero_patrimonio, numero_placas, poliza,
            marca, tipo, modelo, color, numero_serie,
            resguardo_nombre, factura_nombre, observacion_1, observacion_2,
            area_id, region, con_logotipos, kilometraje, telefono,
            activo, en_proceso_baja, created_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            1, 'NO', NOW()
        )
    ");

    $stmtIns->execute([
        $baja['numero'] ?? 0,
        $baja['numero_economico'],
        $baja['numero_patrimonio'] ?? '',
        $baja['numero_placas'],
        $baja['poliza'] ?? '',
        $baja['marca'],
        $baja['tipo'] ?? '',
        $baja['modelo'],
        $baja['color'] ?? '',
        $baja['numero_serie'] ?? '',
        $baja['resguardo_nombre'] ?? '',
        $baja['factura_nombre'] ?? '',
        $baja['observacion_1'] ?? '',
        $baja['observacion_2'] ?? '',
        $baja['area_id'],
        $baja['region'] ?? 'SECOPE',
        $baja['con_logotipos'] ?? 'NO',
        $baja['kilometraje'] ?? '',
        $baja['telefono'] ?? ''
    ]);
    // Transferir Notas de vuelta a ACTIVO
    $newVehiculoId = $pdo->lastInsertId();
    $stmtNotasRestore = $pdo->prepare("UPDATE vehiculos_notas SET vehiculo_id = ?, tipo_origen = 'ACTIVO' WHERE vehiculo_id = ? AND tipo_origen = 'BAJA'");
    $stmtNotasRestore->execute([$newVehiculoId, $bajaId]);

    // 3. Eliminar del histórico
    $stmtDelete = $pdo->prepare("DELETE FROM vehiculos_bajas WHERE id = ?");
    $stmtDelete->execute([$bajaId]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    try {
        $pdo->rollBack();
    } catch (Exception $ex) {
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
