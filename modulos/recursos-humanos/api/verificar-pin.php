<?php
/**
 * API: Verificar PIN y Obtener Firma (DESENCRIPTADA)
 * Verifica el PIN del empleado y retorna la firma desencriptada si es correcto
 * Usado para estampar firma en documentos
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
requireAuth();

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

/**
 * Desencripta la firma usando AES-256-GCM
 */
function desencriptarFirma(string $firmaEncriptada, int $empleadoId): string {
    // Clave secreta del sistema (debe ser la misma que en guardar-firma.php)
    $secretKey = 'PAO_FIRMA_SECRET_KEY_2026_SECOPE_DGO';
    
    // Derivar clave única para este empleado
    $key = hash('sha256', $secretKey . '_EMP_' . $empleadoId, true);
    
    // Decodificar datos
    $data = base64_decode($firmaEncriptada);
    
    // Extraer IV (12 bytes), Tag (16 bytes) y datos encriptados
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $encrypted = substr($data, 28);
    
    // Desencriptar
    $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    if ($decrypted === false) {
        throw new Exception('Error al desencriptar la firma');
    }
    
    return $decrypted;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $empleadoId = isset($input['empleado_id']) ? (int)$input['empleado_id'] : 0;
    $pin = $input['pin'] ?? '';
    $documentoReferencia = $input['documento_referencia'] ?? null;
    
    // Obtener el empleado_id del usuario actual si no se especifica
    if (!$empleadoId) {
        $user = getCurrentUser();
        $empleadoId = $user['empleado_id'] ?? 0;
    }
    
    if (!$empleadoId) {
        throw new Exception('No se pudo identificar al empleado');
    }
    
    // Verificar que el usuario solo pueda verificar su propio PIN (excepto admin)
    $user = getCurrentUser();
    if (!isAdmin() && $user['empleado_id'] != $empleadoId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    if (!preg_match('/^\d{4}$/', $pin)) {
        throw new Exception('PIN no válido');
    }
    
    $pdo = getConnection();
    
    // Obtener firma del empleado
    $stmt = $pdo->prepare("
        SELECT * FROM empleado_firmas 
        WHERE empleado_id = ? AND estado = 1
    ");
    $stmt->execute([$empleadoId]);
    $firma = $stmt->fetch();
    
    if (!$firma) {
        throw new Exception('No tiene firma registrada. Contacte al administrador.');
    }
    
    // Verificar bloqueo
    if ($firma['bloqueado_hasta'] && strtotime($firma['bloqueado_hasta']) > time()) {
        $tiempoRestante = ceil((strtotime($firma['bloqueado_hasta']) - time()) / 60);
        throw new Exception("PIN bloqueado temporalmente. Intente en {$tiempoRestante} minutos.");
    }
    
    // Verificar PIN
    if (!password_verify($pin, $firma['pin_hash'])) {
        // Incrementar intentos fallidos
        $intentos = $firma['intentos_fallidos'] + 1;
        
        if ($intentos >= 5) {
            // Bloquear por 15 minutos
            $bloqueoHasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmtUpdate = $pdo->prepare("
                UPDATE empleado_firmas 
                SET intentos_fallidos = ?, bloqueado_hasta = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$intentos, $bloqueoHasta, $firma['id']]);
            
            // Log
            logFirmaAccion($pdo, $empleadoId, 'INTENTO_FALLIDO', null, [
                'intentos' => $intentos,
                'bloqueado' => true
            ]);
            
            throw new Exception('PIN bloqueado por múltiples intentos fallidos. Intente en 15 minutos.');
        } else {
            $stmtUpdate = $pdo->prepare("
                UPDATE empleado_firmas 
                SET intentos_fallidos = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$intentos, $firma['id']]);
            
            // Log
            logFirmaAccion($pdo, $empleadoId, 'INTENTO_FALLIDO', null, [
                'intentos' => $intentos
            ]);
            
            $restantes = 5 - $intentos;
            throw new Exception("PIN incorrecto. Le quedan {$restantes} intentos.");
        }
    }
    
    // PIN correcto - resetear intentos
    $stmtReset = $pdo->prepare("
        UPDATE empleado_firmas 
        SET intentos_fallidos = 0, bloqueado_hasta = NULL
        WHERE id = ?
    ");
    $stmtReset->execute([$firma['id']]);
    
    // Log de verificación exitosa
    logFirmaAccion($pdo, $empleadoId, 'PIN_VERIFICADO', $documentoReferencia);
    
    // Si hay documento de referencia, registrar el estampado
    if ($documentoReferencia) {
        logFirmaAccion($pdo, $empleadoId, 'FIRMA_ESTAMPADA', $documentoReferencia);
    }
    
    // DESENCRIPTAR la firma antes de enviarla
    $firmaDesencriptada = desencriptarFirma($firma['firma_imagen'], $empleadoId);
    
    echo json_encode([
        'success' => true,
        'message' => 'PIN verificado correctamente',
        'firma_imagen' => $firmaDesencriptada
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Registrar acción en log de firma
 */
function logFirmaAccion($pdo, $empleadoId, $accion, $documentoReferencia = null, $detalles = []) {
    $stmt = $pdo->prepare("
        INSERT INTO firma_log (empleado_id, accion, documento_referencia, ip_address, user_agent, detalles)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $empleadoId,
        $accion,
        $documentoReferencia,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        json_encode($detalles)
    ]);
}
