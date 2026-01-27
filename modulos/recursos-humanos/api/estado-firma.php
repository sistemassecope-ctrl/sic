<?php
/**
 * API: Obtener Estado de Firma
 * Retorna si el empleado tiene firma registrada
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
requireAuth();

try {
    $empleadoId = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : 0;
    
    // Si no se especifica, usar el del usuario actual
    if (!$empleadoId) {
        $user = getCurrentUser();
        $empleadoId = $user['empleado_id'] ?? 0;
    }
    
    // Verificar autorización (solo admin puede ver de otros)
    $user = getCurrentUser();
    if (!isAdmin() && $user['empleado_id'] != $empleadoId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    if (!$empleadoId) {
        throw new Exception('No se pudo identificar al empleado');
    }
    
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            ef.id,
            ef.fecha_captura,
            ef.ultima_modificacion_pin,
            ef.estado,
            us.usuario as capturado_por_usuario
        FROM empleado_firmas ef
        LEFT JOIN usuarios_sistema us ON ef.capturado_por = us.id
        WHERE ef.empleado_id = ? AND ef.estado = 1
    ");
    $stmt->execute([$empleadoId]);
    $firma = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'tiene_firma' => $firma ? true : false,
        'datos' => $firma ? [
            'fecha_captura' => $firma['fecha_captura'],
            'ultima_modificacion_pin' => $firma['ultima_modificacion_pin'],
            'capturado_por' => $firma['capturado_por_usuario']
        ] : null
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
