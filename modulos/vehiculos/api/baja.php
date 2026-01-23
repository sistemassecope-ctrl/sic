<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar Auth
requireAuth();
$pdo = getConnection();
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;
// Use 'editar' permission for Baja
requirePermission('editar', $MODULO_ID);

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? (int) $data['id'] : 0;
$motivo = isset($data['motivo']) ? trim($data['motivo']) : 'Baja Definitiva';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID de vehículo inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener vehículo actual
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        throw new Exception("Vehículo no encontrado");
    }

    // 2. Insertar en Histórico (vehiculos_bajas)
    // Asegurarse que vehiculos_bajas tenga las columnas necesarias.
    // Si no existe tabla, crearla al vuelo (práctica de resistencia, aunque idealmente ya existe por migración)
    // Asumiremos que migrate la creó. Si faltan columnas, este insert fallará y lo atrapamos.

    $cols = ['numero_economico', 'numero_placas', 'marca', 'modelo', 'area_id', 'region'];
    // Insertamos datos básicos + fecha baja + motivo

    // Insertamos datos COMPLETOS para snapshot histórico fiel
    $stmtBaja = $pdo->prepare("
        INSERT INTO vehiculos_bajas (
            vehiculo_origen_id, numero_economico, numero_placas, 
            marca, modelo, area_id, region, 
            numero_patrimonio, poliza, tipo, color, numero_serie, resguardo_nombre, factura_nombre,
            observacion_1, observacion_2, kilometraje, telefono, con_logotipos,
            fecha_baja, motivo_baja, usuario_baja_id
        ) VALUES (
             ?, ?, ?, 
             ?, ?, ?, ?, 
             ?, ?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?,
             NOW(), ?, ?
        )
    ");

    $stmtBaja->execute([
        $vehiculo['id'],
        $vehiculo['numero_economico'],
        $vehiculo['numero_placas'],
        $vehiculo['marca'],
        $vehiculo['modelo'],
        $vehiculo['area_id'],
        $vehiculo['region'],
        $vehiculo['numero_patrimonio'],
        $vehiculo['poliza'],
        $vehiculo['tipo'],
        $vehiculo['color'],
        $vehiculo['numero_serie'],
        $vehiculo['resguardo_nombre'],
        $vehiculo['factura_nombre'],
        $vehiculo['observacion_1'],
        $vehiculo['observacion_2'],
        $vehiculo['kilometraje'],
        $vehiculo['telefono'],
        $vehiculo['con_logotipos'],
        $motivo,
        getCurrentUserId()
    ]);

    // 3. TRANSFERIR NOTAS al nuevo ID de baja
    $nuevoIdBaja = $pdo->lastInsertId();
    $stmtNotasUpdate = $pdo->prepare("UPDATE vehiculos_notas SET vehiculo_id = ?, tipo_origen = 'BAJA' WHERE vehiculo_id = ? AND tipo_origen = 'ACTIVO'");
    $stmtNotasUpdate->execute([$nuevoIdBaja, $id]);

    // 4. ELIMINAR vehículo del padrón activo
    $stmtDelete = $pdo->prepare("DELETE FROM vehiculos WHERE id = ?");
    $stmtDelete->execute([$id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    try {
        $pdo->rollBack();
    } catch (Exception $ex) {
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
