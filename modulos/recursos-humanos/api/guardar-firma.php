<?php
/**
 * API: Guardar Firma Digital
 * Guarda la firma autógrafa y el PIN del empleado
 * SOLO SUPERADMIN
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
requireAuth();

// Verificar que sea SUPERADMIN
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $empleadoId = isset($input['empleado_id']) ? (int)$input['empleado_id'] : 0;
    $firmaImagen = $input['firma_imagen'] ?? '';
    $pin = $input['pin'] ?? '';
    
    // Validaciones
    if (!$empleadoId) {
        throw new Exception('ID de empleado no válido');
    }
    
    if (empty($firmaImagen) || strpos($firmaImagen, 'data:image/png;base64,') !== 0) {
        throw new Exception('Firma no válida');
    }
    
    if (!preg_match('/^\d{4}$/', $pin)) {
        throw new Exception('El PIN debe ser de exactamente 4 dígitos numéricos');
    }
    
    $pdo = getConnection();
    
    // Verificar que el empleado existe
    $stmtEmp = $pdo->prepare("SELECT id FROM empleados WHERE id = ?");
    $stmtEmp->execute([$empleadoId]);
    if (!$stmtEmp->fetch()) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Hash del PIN
    $pinHash = password_hash($pin, PASSWORD_DEFAULT);
    $userId = getCurrentUserId();
    $now = date('Y-m-d H:i:s');
    
    // Verificar si ya tiene firma
    $stmtCheck = $pdo->prepare("SELECT id FROM empleado_firmas WHERE empleado_id = ?");
    $stmtCheck->execute([$empleadoId]);
    $existingFirma = $stmtCheck->fetch();
    
    $pdo->beginTransaction();
    
    if ($existingFirma) {
        // Actualizar firma existente
        $stmt = $pdo->prepare("
            UPDATE empleado_firmas 
            SET firma_imagen = ?,
                pin_hash = ?,
                fecha_captura = ?,
                capturado_por = ?,
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                estado = 1,
                updated_at = NOW()
            WHERE empleado_id = ?
        ");
        $stmt->execute([$firmaImagen, $pinHash, $now, $userId, $empleadoId]);
    } else {
        // Insertar nueva firma
        $stmt = $pdo->prepare("
            INSERT INTO empleado_firmas 
            (empleado_id, firma_imagen, pin_hash, fecha_captura, capturado_por, estado)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$empleadoId, $firmaImagen, $pinHash, $now, $userId]);
    }
    
    // Registrar en log
    $stmtLog = $pdo->prepare("
        INSERT INTO firma_log (empleado_id, accion, ip_address, user_agent, detalles)
        VALUES (?, 'FIRMA_CAPTURADA', ?, ?, ?)
    ");
    $stmtLog->execute([
        $empleadoId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        json_encode([
            'capturado_por_usuario_id' => $userId,
            'es_actualizacion' => $existingFirma ? true : false
        ])
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $existingFirma ? 'Firma actualizada exitosamente' : 'Firma guardada exitosamente'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
