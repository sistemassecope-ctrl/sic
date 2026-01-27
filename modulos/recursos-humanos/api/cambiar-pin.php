<?php
/**
 * API: Cambiar PIN de Firma
 * Permite al empleado cambiar su propio PIN
 * Requiere doble introducción para confirmar
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
requireAuth();

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $pinActual = $input['pin_actual'] ?? '';
    $pinNuevo = $input['pin_nuevo'] ?? '';
    $pinConfirmar = $input['pin_confirmar'] ?? '';
    
    // Obtener empleado_id del usuario actual
    $user = getCurrentUser();
    $empleadoId = $user['empleado_id'] ?? 0;
    
    if (!$empleadoId) {
        throw new Exception('No se pudo identificar al empleado');
    }
    
    // Validaciones
    if (!preg_match('/^\d{4}$/', $pinActual)) {
        throw new Exception('El PIN actual debe ser de 4 dígitos');
    }
    
    if (!preg_match('/^\d{4}$/', $pinNuevo)) {
        throw new Exception('El nuevo PIN debe ser de 4 dígitos');
    }
    
    if ($pinNuevo !== $pinConfirmar) {
        throw new Exception('Los nuevos PINs no coinciden');
    }
    
    if ($pinActual === $pinNuevo) {
        throw new Exception('El nuevo PIN debe ser diferente al actual');
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
        throw new Exception('No tiene firma registrada. Contacte al administrador para registrar su firma.');
    }
    
    // Verificar bloqueo
    if ($firma['bloqueado_hasta'] && strtotime($firma['bloqueado_hasta']) > time()) {
        $tiempoRestante = ceil((strtotime($firma['bloqueado_hasta']) - time()) / 60);
        throw new Exception("Acceso bloqueado temporalmente. Intente en {$tiempoRestante} minutos.");
    }
    
    // Verificar PIN actual
    if (!password_verify($pinActual, $firma['pin_hash'])) {
        // Incrementar intentos fallidos
        $intentos = $firma['intentos_fallidos'] + 1;
        
        if ($intentos >= 5) {
            $bloqueoHasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmtUpdate = $pdo->prepare("
                UPDATE empleado_firmas 
                SET intentos_fallidos = ?, bloqueado_hasta = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$intentos, $bloqueoHasta, $firma['id']]);
            throw new Exception('Acceso bloqueado por múltiples intentos fallidos. Intente en 15 minutos.');
        } else {
            $stmtUpdate = $pdo->prepare("
                UPDATE empleado_firmas 
                SET intentos_fallidos = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$intentos, $firma['id']]);
            
            $restantes = 5 - $intentos;
            throw new Exception("PIN actual incorrecto. Le quedan {$restantes} intentos.");
        }
    }
    
    // PIN actual correcto - actualizar al nuevo
    $pinHashNuevo = password_hash($pinNuevo, PASSWORD_DEFAULT);
    
    $stmtUpdate = $pdo->prepare("
        UPDATE empleado_firmas 
        SET pin_hash = ?,
            ultima_modificacion_pin = NOW(),
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id = ?
    ");
    $stmtUpdate->execute([$pinHashNuevo, $firma['id']]);
    
    // Registrar en log
    $stmtLog = $pdo->prepare("
        INSERT INTO firma_log (empleado_id, accion, ip_address, user_agent, detalles)
        VALUES (?, 'PIN_CAMBIADO', ?, ?, ?)
    ");
    $stmtLog->execute([
        $empleadoId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        json_encode(['cambiado_por_usuario' => true])
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'PIN actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
