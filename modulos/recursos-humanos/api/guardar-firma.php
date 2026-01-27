<?php
/**
 * API: Guardar Firma Digital (ENCRIPTADA)
 * Guarda la firma autógrafa encriptada y el PIN del empleado
 * SOLO ADMIN
 * 
 * La firma se encripta antes de guardarse para protegerla en caso de hackeo
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
requireAuth();

// Verificar que sea Admin
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

/**
 * Encripta la firma usando AES-256-GCM
 * La clave se deriva del empleado_id + una clave secreta del sistema
 */
function encriptarFirma(string $firmaData, int $empleadoId): string {
    // Clave secreta del sistema (en producción debería estar en variable de entorno)
    $secretKey = 'PAO_FIRMA_SECRET_KEY_2026_SECOPE_DGO';
    
    // Derivar clave única para este empleado
    $key = hash('sha256', $secretKey . '_EMP_' . $empleadoId, true);
    
    // Generar IV aleatorio
    $iv = random_bytes(12);
    
    // Encriptar con AES-256-GCM
    $tag = '';
    $encrypted = openssl_encrypt($firmaData, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    // Combinar IV + Tag + Datos encriptados y codificar en base64
    return base64_encode($iv . $tag . $encrypted);
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
    
    // ENCRIPTAR la firma antes de guardarla
    $firmaEncriptada = encriptarFirma($firmaImagen, $empleadoId);
    
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
        $stmt->execute([$firmaEncriptada, $pinHash, $now, $userId, $empleadoId]);
    } else {
        // Insertar nueva firma
        $stmt = $pdo->prepare("
            INSERT INTO empleado_firmas 
            (empleado_id, firma_imagen, pin_hash, fecha_captura, capturado_por, estado)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$empleadoId, $firmaEncriptada, $pinHash, $now, $userId]);
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
            'es_actualizacion' => $existingFirma ? true : false,
            'firma_encriptada' => true
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
